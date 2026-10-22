<?php

declare(strict_types=1);

namespace Funnelchat\Tests\E2E;

use Funnelchat\Exception\ApiException;
use Funnelchat\Exception\RouteNotFoundException;
use Funnelchat\Exception\UnprocessableEntityException;
use Funnelchat\Funnelchat;
use Funnelchat\Webhook;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * End to end against funnelchat-sandbox (`composer test:e2e`): the SDK over real HTTP, with a
 * webhook receiver on PHP's built-in server. Nothing leaves this machine.
 *
 *   npx funnelchat-sandbox --port 4010 --status-delays 0.2,0.2,0.2 --quiet
 *   FUNNELCHAT_SANDBOX_URL=http://127.0.0.1:4010 composer test:e2e
 *
 * Skipped when FUNNELCHAT_SANDBOX_URL is not set. Each test starts from `POST /_sandbox/reset`.
 */
#[Group('e2e')]
final class SandboxTest extends TestCase
{
    /** Any key with the shape of a real one works in the sandbox. Not a real credential. */
    private const SANDBOX_KEY = '1|fc_live_sandbox';

    private const CONTACT = 2001;

    private const PHONE = '5491100000000';

    /** The QR number of the sandbox: no 24-hour window and no pace. */
    private const QR_DEVICE = 4;

    private static string $url = '';

    private Funnelchat $funnelchat;

    /** @var resource|null */
    private $receiver;

    private string $inbox = '';

    public static function setUpBeforeClass(): void
    {
        $url = getenv('FUNNELCHAT_SANDBOX_URL');
        self::$url = \is_string($url) ? rtrim($url, '/') : '';
    }

    protected function setUp(): void
    {
        if (self::$url === '') {
            self::markTestSkipped('FUNNELCHAT_SANDBOX_URL no está definida: arranca funnelchat-sandbox y defínela.');
        }
        $this->sandbox('POST', '/_sandbox/reset');
        $this->funnelchat = new Funnelchat(apiKey: self::SANDBOX_KEY, baseUrl: self::$url, maxRetries: 0);
    }

    protected function tearDown(): void
    {
        if (\is_resource($this->receiver)) {
            proc_terminate($this->receiver);
            proc_close($this->receiver);
        }
        if ($this->inbox !== '') {
            array_map('unlink', glob($this->inbox . '/*') ?: []);
            rmdir($this->inbox);
        }
    }

    public function testASendAnswersTheQueuedMessageAndThenTheSignedStatusNotices(): void
    {
        $hook = $this->startReceiver();
        $subscription = $this->funnelchat->webhooks->create(['url' => $hook, 'events' => ['message.status_changed']]);
        $secret = $subscription['secret'];

        $result = $this->funnelchat->messages->send(self::CONTACT, [
            'device_id' => self::QR_DEVICE,
            'phone' => self::PHONE,
            'text' => 'Hola desde el SDK de PHP',
            'client_reference' => 'e2e-php-1',
        ]);
        $message = $result['message'];
        self::assertSame('queued', $message['status']);
        self::assertSame('e2e-php-1', $message['client_reference']);
        $this->assertRequestId($this->funnelchat->lastResponse()?->requestId);

        $statuses = [];
        foreach ($this->waitForDeliveries(3) as $delivery) {
            $event = Webhook::constructEvent($delivery['body'], $delivery['signature'], $secret);
            self::assertSame('message.status_changed', $event['type']);
            self::assertIsArray($event['data']);
            self::assertSame($message['id'], $event['data']['message_id']);
            self::assertSame('e2e-php-1', $event['data']['client_reference']);
            $statuses[] = $event['data']['status'];
        }
        self::assertSame(['sent', 'delivered', 'read'], $statuses);

        $read = $this->funnelchat->messages->get(self::CONTACT, $message['id']);
        self::assertSame('read', $read['status']);
    }

    public function testAFileFromAUrlWithoutExtensionGoesWithItsTypeAndName(): void
    {
        $result = $this->funnelchat->messages->send(self::CONTACT, [
            'device_id' => self::QR_DEVICE,
            'phone' => self::PHONE,
            'file' => 'https://files.example/descarga?firma=abc123',
            'file_type' => 'document',
            'file_name' => 'factura.pdf',
        ]);

        self::assertSame('queued', $result['message']['status']);
        self::assertSame('document', $result['message']['type']);
    }

    public function testARejectedSendIsASendRuleErrorWithItsRequestId(): void
    {
        try {
            $this->funnelchat->messages->send(
                self::CONTACT,
                ['device_id' => self::QR_DEVICE, 'phone' => self::PHONE, 'text' => 'Hola'],
                ['headers' => ['X-Sandbox-Reject' => 'window_closed']],
            );
            self::fail('should have thrown');
        } catch (UnprocessableEntityException $exception) {
            self::assertSame('window_closed', $exception->errorCode);
            self::assertTrue($exception->isSendRuleError());
            $this->assertRequestId($exception->requestId);
        }
    }

    public function testAMacLockoutIsNotRetried(): void
    {
        $calls = 0;
        try {
            $this->funnelchat->messages->send(
                self::CONTACT,
                ['device_id' => self::QR_DEVICE, 'phone' => self::PHONE, 'text' => 'Hola'],
                [
                    'headers' => ['X-Sandbox-Reject' => 'mac_lockout_24h'],
                    'retrySend' => true,
                    'maxRetries' => 2,
                    'onResponse' => static function () use (&$calls): void {
                        ++$calls;
                    },
                ],
            );
            self::fail('should have thrown');
        } catch (ApiException $exception) {
            self::assertSame(429, $exception->status);
            self::assertSame('mac_lockout_24h', $exception->errorCode);
        }
        self::assertSame(1, $calls);
    }

    public function testAMessageReadByIdIsTheSameObjectTheSendReturned(): void
    {
        $sent = $this->funnelchat->messages->send(self::CONTACT, ['device_id' => self::QR_DEVICE, 'phone' => self::PHONE, 'text' => 'Hola', 'client_reference' => 'e2e-php-2'])['message'];

        $read = $this->funnelchat->messages->get(self::CONTACT, $sent['id']);

        self::assertSame($sent['id'], $read['id']);
        self::assertSame('e2e-php-2', $read['client_reference']);
    }

    public function testAFormIsSentToAContact(): void
    {
        $forms = $this->funnelchat->forms->list()->firstPage();
        $published = array_values(array_filter($forms->data, static fn(array $form): bool => $form['status'] === 'published'));
        if ($published === []) {
            self::markTestIncomplete('El sandbox no trae un formulario publicado.');
        }

        $result = $this->funnelchat->messages->sendForm(self::CONTACT, ['form_id' => $published[0]['family_id'], 'device_id' => self::QR_DEVICE]);

        self::assertSame('whatsapp_form', $result['message']['type']);
        self::assertSame('queued', $result['message']['status']);
    }

    public function testARouteThatDoesNotExistIsRouteNotFound(): void
    {
        try {
            $this->funnelchat->request('GET', '/api/v1/no-existe');
            self::fail('should have thrown');
        } catch (RouteNotFoundException $exception) {
            self::assertSame('route_not_found', $exception->errorCode);
            $this->assertRequestId($exception->requestId);
        }
    }

    private function assertRequestId(?string $id): void
    {
        self::assertNotNull($id, 'every answer carries X-Request-Id');
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id);
    }

    /** Starts the receiver on a free port and returns its URL. */
    private function startReceiver(): string
    {
        $this->inbox = sys_get_temp_dir() . '/funnelchat-e2e-' . bin2hex(random_bytes(6));
        mkdir($this->inbox);
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        self::assertNotFalse($socket);
        $port = (int) substr((string) strrchr((string) stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);

        $command = [\PHP_BINARY, '-S', "127.0.0.1:{$port}", __DIR__ . '/receiver.php'];
        $process = proc_open($command, [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, null, ['FUNNELCHAT_E2E_INBOX' => $this->inbox]);
        self::assertIsResource($process);
        $this->receiver = $process;
        for ($i = 0; $i < 50; ++$i) {
            $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
            if ($connection !== false) {
                fclose($connection);

                return "http://127.0.0.1:{$port}/hooks/funnelchat";
            }
            usleep(100_000);
        }
        self::fail('the receiver did not start');
    }

    /**
     * Waits (up to 15 s) for `$count` deliveries and returns them in arrival order.
     *
     * @return list<array{signature: string|null, body: string}>
     */
    private function waitForDeliveries(int $count): array
    {
        for ($i = 0; $i < 150; ++$i) {
            $files = glob($this->inbox . '/*.json') ?: [];
            if (\count($files) >= $count) {
                sort($files, \SORT_NATURAL);
                $deliveries = [];
                foreach (\array_slice($files, 0, $count) as $file) {
                    $delivery = json_decode((string) file_get_contents($file), true, 512, \JSON_THROW_ON_ERROR);
                    self::assertIsArray($delivery);
                    self::assertIsString($delivery['body']);
                    $deliveries[] = ['signature' => \is_string($delivery['signature']) ? $delivery['signature'] : null, 'body' => $delivery['body']];
                }

                return $deliveries;
            }
            usleep(100_000);
        }
        self::fail("fewer than {$count} deliveries arrived");
    }

    private function sandbox(string $method, string $path): void
    {
        $context = stream_context_create(['http' => ['method' => $method, 'ignore_errors' => true, 'timeout' => 5]]);
        $answer = @file_get_contents(self::$url . $path, false, $context);
        self::assertNotFalse($answer, 'the sandbox does not answer at ' . self::$url);
    }
}
