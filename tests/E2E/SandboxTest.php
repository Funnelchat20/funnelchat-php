<?php

declare(strict_types=1);

namespace Funnelchat\Tests\E2E;

use Funnelchat\Exception\ApiException;
use Funnelchat\Exception\ConflictException;
use Funnelchat\Exception\InternalServerException;
use Funnelchat\Exception\PermissionDeniedException;
use Funnelchat\Exception\RateLimitException;
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

    public function testAQrNumberIsCreatedScannedDisconnectedAndDeletedWithItsSignedNotices(): void
    {
        $hook = $this->startReceiver();
        $secret = $this->funnelchat->webhooks->create(['url' => $hook, 'events' => ['device.connected', 'device.disconnected', 'device.deleted']])['secret'];

        $body = ['connection' => 'qr', 'alias' => 'Store 12', 'external_reference' => 'e2e-php-customer'];
        $number = $this->funnelchat->devices->create($body, ['idempotencyKey' => 'e2e-php-number'])['data'];
        self::assertSame('qr', $number['connection']);
        self::assertSame('e2e-php-customer', $number['external_reference']);
        self::assertEquals(false, $number['connected']);
        // The same key with the same body: the same number, nothing new.
        self::assertSame($number['id'], $this->funnelchat->devices->create($body, ['idempotencyKey' => 'e2e-php-number'])['data']['id']);

        $qr = $this->funnelchat->devices->qr($number['id'])['data'];
        self::assertFalse($qr['connected']);
        self::assertStringStartsWith('data:image/png;base64,', (string) $qr['qr_code']);
        self::assertGreaterThan(0, $qr['expires_in']);

        $this->sandbox('POST', "/_sandbox/devices/{$number['id']}/connect", ['number' => '5491155550000']);
        [$connected, $connectedDevice] = $this->event($this->waitForDeliveries(1)[0], $secret, 'device.connected');
        self::assertSame($number['id'], $connectedDevice['id']);
        self::assertSame('qr', $connectedDevice['connection']);
        self::assertSame('e2e-php-customer', $connectedDevice['external_reference']);
        self::assertNull($connected['connect_link_id']);
        self::assertTrue($this->funnelchat->devices->qr($number['id'])['data']['connected']);
        self::assertNull($this->funnelchat->devices->qr($number['id'])['data']['qr_code']);

        $ids = [];
        foreach ($this->funnelchat->devices->list(['external_reference' => 'e2e-php-customer']) as $device) {
            $ids[] = $device['id'];
        }
        self::assertSame([$number['id']], $ids);

        $this->sandbox('POST', "/_sandbox/devices/{$number['id']}/disconnect", ['reason' => 'qr_logged_out']);
        [$disconnected, $disconnectedDevice] = $this->event($this->waitForDeliveries(2)[1], $secret, 'device.disconnected');
        self::assertSame('qr_logged_out', $disconnected['reason']);
        self::assertSame('e2e-php-customer', $disconnectedDevice['external_reference']);

        $this->funnelchat->request('DELETE', '/api/v1/devices/{device}', ['path' => ['device' => $number['id']]]);
        [$deleted, $deletedDevice] = $this->event($this->waitForDeliveries(3)[2], $secret, 'device.deleted');
        self::assertSame($number['id'], $deletedDevice['id']);
        self::assertSame('qr', $deletedDevice['connection']);
        self::assertSame('e2e-php-customer', $deletedDevice['external_reference']);
    }

    public function testAConnectLinkCompletesOnItsPageRedirectsAndNotifiesWithItsId(): void
    {
        $hook = $this->startReceiver();
        $secret = $this->funnelchat->webhooks->create(['url' => $hook, 'events' => ['device.connected']])['secret'];

        $link = $this->funnelchat->connectLinks->create([
            'type' => 'meta',
            'allowed_connection_types' => ['coexistence', 'dedicated'],
            'external_reference' => 'e2e-php-link',
            'return_url' => 'https://app.example/connected',
            'expires_in_minutes' => 60,
        ], ['idempotencyKey' => 'e2e-php-link'])['data'];
        self::assertSame('pending', $link['status']);
        self::assertIsString($link['url'] ?? null);
        self::assertNull($link['device_id']);

        // The sandbox's test page connects at once and sends the customer back with the result.
        [$status, $location] = $this->openPage($link['url'] . '?connection=coexistence');
        self::assertSame(303, $status);
        self::assertIsString($location);
        self::assertStringStartsWith('https://app.example/connected?', $location);
        parse_str((string) parse_url($location, \PHP_URL_QUERY), $result);
        self::assertSame('completed', $result['status'] ?? null);
        self::assertSame((string) $link['id'], $result['connect_link_id'] ?? null);

        $read = $this->funnelchat->connectLinks->get($link['id'])['data'];
        self::assertSame('completed', $read['status']);
        self::assertArrayNotHasKey('url', $read);
        self::assertIsInt($read['device_id']);
        self::assertSame((string) $read['device_id'], $result['device_id'] ?? null);

        [$connected, $connectedDevice] = $this->event($this->waitForDeliveries(1)[0], $secret, 'device.connected');
        self::assertSame($link['id'], $connected['connect_link_id']);
        self::assertSame($read['device_id'], $connectedDevice['id']);
        self::assertSame('coexistence', $connectedDevice['connection']);
        self::assertSame('e2e-php-link', $connectedDevice['external_reference']);

        // QR on the number in coexistence: the same phone, no new place.
        $qr = $this->funnelchat->devices->create(['connection' => 'qr', 'same_number_as' => $read['device_id']])['data'];
        self::assertSame('qr', $qr['connection']);

        $completed = [];
        foreach ($this->funnelchat->connectLinks->list(['status' => 'completed', 'external_reference' => 'e2e-php-link']) as $item) {
            $completed[] = $item['id'];
        }
        self::assertSame([$link['id']], $completed);
    }

    public function testAHealthChangeOfAnOfficialNumberArrivesSigned(): void
    {
        $hook = $this->startReceiver();
        $secret = $this->funnelchat->webhooks->create(['url' => $hook, 'events' => ['device.health_changed']])['secret'];

        $this->sandbox('POST', '/_sandbox/devices/' . self::OFFICIAL_DEVICE . '/health', ['state' => 'limited']);
        [$changed, $device] = $this->event($this->waitForDeliveries(1)[0], $secret, 'device.health_changed');

        self::assertSame(self::OFFICIAL_DEVICE, $device['id']);
        self::assertSame('dedicated', $device['connection']);
        self::assertSame('limited', $changed['state']);
        self::assertContains($changed['previous_state'], ['available', 'limited', 'blocked', 'unknown']);
        self::assertNotSame($changed['previous_state'], $changed['state']);
        self::assertIsString($changed['changed_at']);
    }

    public function testAPendingLinkIsRevokedOnceAndAUsedPageIsClosed(): void
    {
        $link = $this->funnelchat->connectLinks->create(['type' => 'qr'])['data'];

        self::assertNull($this->funnelchat->connectLinks->revoke($link['id']));
        self::assertSame('revoked', $this->funnelchat->connectLinks->get($link['id'])['data']['status']);
        try {
            $this->funnelchat->connectLinks->revoke($link['id']);
            self::fail('should have thrown');
        } catch (ConflictException $exception) {
            self::assertSame(409, $exception->status);
        }
        self::assertSame(410, $this->openPage((string) ($link['url'] ?? ''))[0]);
    }

    public function testTheRefusalsOfCreatingNumbersAndLinks(): void
    {
        $this->funnelchat->devices->create(['connection' => 'qr'], ['idempotencyKey' => 'e2e-php-mismatch']);
        try {
            $this->funnelchat->devices->create(['connection' => 'qr', 'alias' => 'Another'], ['idempotencyKey' => 'e2e-php-mismatch']);
            self::fail('should have thrown');
        } catch (UnprocessableEntityException $exception) {
            self::assertSame('idempotency_key_mismatch', $exception->errorCode);
            self::assertTrue($exception->isDeviceError());
        }

        $this->sandbox('POST', '/_sandbox/numbers', ['limit' => 3]);
        try {
            $this->funnelchat->devices->create(['connection' => 'qr']);
            self::fail('should have thrown');
        } catch (PermissionDeniedException $exception) {
            self::assertSame('number_limit_reached', $exception->errorCode);
            self::assertTrue($exception->isDeviceError());
            $this->assertRequestId($exception->requestId);
        }

        $this->sandbox('POST', '/_sandbox/numbers', ['included' => false]);
        try {
            $this->funnelchat->connectLinks->create(['type' => 'qr']);
            self::fail('should have thrown');
        } catch (PermissionDeniedException $exception) {
            self::assertSame('numbers_not_included', $exception->errorCode);
        }

        try {
            $this->funnelchat->devices->qr(self::OFFICIAL_DEVICE);
            self::fail('should have thrown');
        } catch (UnprocessableEntityException $exception) {
            self::assertSame('provider_unsupported', $exception->errorCode);
        }
    }

    public function testTooManyOpenLinksAndTheMetaSwitchAreNotRetried(): void
    {
        $sleeps = [];
        $funnelchat = new Funnelchat(apiKey: self::SANDBOX_KEY, baseUrl: self::$url, maxRetries: 2, sleep: static function (int $milliseconds) use (&$sleeps): void {
            $sleeps[] = $milliseconds;
        });
        // 20 open links per account: the 21st is refused, and waiting does not fix it.
        for ($i = 0; $i < 20; ++$i) {
            $funnelchat->connectLinks->create(['type' => 'qr']);
        }
        try {
            $funnelchat->connectLinks->create(['type' => 'qr']);
            self::fail('should have thrown');
        } catch (RateLimitException $exception) {
            self::assertSame('too_many_open_links', $exception->errorCode);
            self::assertTrue($exception->isDeviceError());
        }

        $this->sandbox('POST', '/_sandbox/reset');
        $this->sandbox('POST', '/_sandbox/numbers', ['meta_connect' => false]);
        try {
            $funnelchat->connectLinks->create(['type' => 'meta', 'allowed_connection_types' => ['coexistence']], ['idempotencyKey' => 'e2e-php-meta-off']);
            self::fail('should have thrown');
        } catch (InternalServerException $exception) {
            self::assertSame(503, $exception->status);
            self::assertSame('meta_connect_disabled', $exception->errorCode);
        }

        self::assertSame([], $sleeps);
    }

    /**
     * A signed `device.*` delivery, verified with the SDK: its `data` and the `device` in it.
     *
     * @param array{signature: string|null, body: string} $delivery
     *
     * @return array{array<array-key, mixed>, array<array-key, mixed>}
     */
    private function event(array $delivery, string $secret, string $type): array
    {
        $event = Webhook::constructEvent($delivery['body'], $delivery['signature'], $secret);
        self::assertSame($type, $event['type']);
        self::assertIsArray($event['data']);
        self::assertIsArray($event['data']['device'] ?? null);

        return [$event['data'], $event['data']['device']];
    }

    /**
     * Opens a connect link as a browser would (no key, no redirect followed).
     *
     * @return array{int, string|null} the status and the `Location` header
     */
    private function openPage(string $url): array
    {
        $context = stream_context_create(['http' => ['method' => 'GET', 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 5]]);
        $stream = @fopen($url, 'r', false, $context);
        self::assertNotFalse($stream, "the page does not answer at {$url}");
        $headers = stream_get_meta_data($stream)['wrapper_data'] ?? [];
        fclose($stream);
        self::assertIsArray($headers);
        $status = 0;
        $location = null;
        foreach ($headers as $line) {
            self::assertIsString($line);
            if (preg_match('#^HTTP/\S+ (\d{3})#', $line, $match) === 1) {
                $status = (int) $match[1];
            } elseif (stripos($line, 'Location:') === 0) {
                $location = trim(substr($line, \strlen('Location:')));
            }
        }

        return [$status, $location];
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
