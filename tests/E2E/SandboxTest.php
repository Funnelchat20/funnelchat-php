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

    /** The official (WhatsApp Cloud) number of the sandbox: 24-hour window, pace, forms. */
    private const OFFICIAL_DEVICE = 3;

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
            self::markTestSkipped('FUNNELCHAT_SANDBOX_URL is not set: start funnelchat-sandbox and set it.');
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
            'text' => 'Hello from the PHP SDK',
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

    public function testAFormIsBuiltPublishedSentAndItsAnswersArriveSigned(): void
    {
        $hook = $this->startReceiver();
        $secret = $this->funnelchat->webhooks->create(['url' => $hook, 'events' => ['whatsapp_form.completed']])['secret'];

        // Forms go only through an official number; its 24-hour window opens with an inbound message.
        $draft = $this->funnelchat->forms->create(['device_id' => self::OFFICIAL_DEVICE, 'template_key' => 'lead'])['data'];
        $published = $this->funnelchat->forms->publish($draft['id'])['data'];
        self::assertSame('published', $published['status']);
        $this->sandbox('POST', '/_sandbox/inbound/message', ['phone' => self::PHONE, 'device_id' => self::OFFICIAL_DEVICE, 'text' => 'Hola']);

        $message = $this->funnelchat->messages->sendForm(self::CONTACT, [
            'form_id' => $published['family_id'],
            'device_id' => self::OFFICIAL_DEVICE,
            'client_reference' => 'e2e-php-form',
        ])['message'];
        self::assertSame('whatsapp_form', $message['type']);
        self::assertSame('queued', $message['status']);

        $this->sandbox('POST', '/_sandbox/forms/complete', ['message_id' => $message['id']]);
        [$delivery] = $this->waitForDeliveries(1);
        $event = Webhook::constructEvent($delivery['body'], $delivery['signature'], $secret);
        self::assertSame('whatsapp_form.completed', $event['type']);
        self::assertIsArray($event['data']);
        $form = $event['data']['form'];
        self::assertIsArray($form);
        self::assertSame($published['family_id'], $form['id']);
        self::assertSame($message['id'], $event['data']['message_id']);
        self::assertNotSame([], $event['data']['answers']);
    }

    public function testAFormDoesNotGoThroughAQrNumber(): void
    {
        try {
            $this->funnelchat->forms->create(['device_id' => self::QR_DEVICE, 'template_key' => 'lead']);
            self::fail('should have thrown');
        } catch (UnprocessableEntityException $exception) {
            self::assertSame('NUMBER_NOT_ELIGIBLE', $exception->errorCode);
        }
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

    /**
     * A call to the sandbox's own routes (`/_sandbox/...`), which take no key.
     *
     * @param array<string, mixed>|null $body
     */
    private function sandbox(string $method, string $path, ?array $body = null): void
    {
        $http = ['method' => $method, 'ignore_errors' => true, 'timeout' => 5];
        if ($body !== null) {
            $http['header'] = 'Content-Type: application/json';
            $http['content'] = json_encode($body, \JSON_THROW_ON_ERROR);
        }
        $context = stream_context_create(['http' => $http]);
        $answer = @file_get_contents(self::$url . $path, false, $context);
        self::assertNotFalse($answer, 'the sandbox does not answer at ' . self::$url);
    }
}
