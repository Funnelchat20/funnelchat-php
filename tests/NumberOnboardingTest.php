<?php

declare(strict_types=1);

namespace Funnelchat\Tests;

use Funnelchat\Exception\ApiConnectionException;
use Funnelchat\Exception\ConflictException;
use Funnelchat\Exception\DeviceErrors;
use Funnelchat\Exception\FunnelchatException;
use Funnelchat\Exception\InternalServerException;
use Funnelchat\Exception\PermissionDeniedException;
use Funnelchat\Exception\RateLimitException;
use Funnelchat\Exception\UnprocessableEntityException;
use Funnelchat\Generated\WebhookEvents;
use Funnelchat\Tests\Support\FakeNetworkException;
use Funnelchat\Tests\Support\FakeTimeoutException;
use Funnelchat\Tests\Support\MockHttpClient;
use Funnelchat\Tests\Support\Reply;
use Funnelchat\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

/**
 * Creating and connecting numbers by API: `devices->create()`, `devices->qr()`, the
 * `external_reference` filter, `connectLinks`, the `idempotencyKey` option, the refusals and the
 * `device.*` events. From the contract 1022-alta-de-numeros (`accounts-number-onboarding` in
 * spec/pending.json), not from production.
 */
final class NumberOnboardingTest extends TestCase
{
    /**
     * A number as `GET /api/v1/devices/{device}` answers it, with the fields of the contract.
     * `connected` is an integer in the 13-10 snapshot; the contract writes `connected: false`.
     *
     * @return array<string, mixed>
     */
    private static function device(int $id, int $connected = 0): array
    {
        return [
            'id' => $id,
            'alias' => 'Store 12',
            'number' => null,
            'provider' => 'zapi',
            'connected' => $connected,
            'connection' => 'qr',
            'external_reference' => 'customer-8841',
        ];
    }

    /**
     * A connect link as the contract describes it.
     *
     * @return array<string, mixed>
     */
    private static function link(int $id, string $status = 'pending', bool $withUrl = false): array
    {
        $link = [
            'id' => $id,
            'type' => 'meta',
            'status' => $status,
            'expires_at' => '2026-10-24T12:00:00Z',
            'allowed_connection_types' => ['coexistence', 'dedicated'],
            'provision_us_number' => false,
            'device_id' => null,
            'external_reference' => 'customer-8841',
            'return_url' => 'https://app.example/done',
            'error_code' => null,
            'created_at' => '2026-10-23T12:00:00Z',
            'completed_at' => null,
        ];

        return $withUrl ? $link + ['url' => 'https://api.funnelchat.com/connect/tok_abc'] : $link;
    }

    public function testDevicesCreatePostsTheBodyAndReturnsTheNumber(): void
    {
        $http = new MockHttpClient(Reply::json(201, ['data' => self::device(41)]));

        $result = $this->client($http)->devices->create([
            'connection' => 'qr',
            'alias' => 'Store 12',
            'external_reference' => 'customer-8841',
        ]);

        $request = $http->lastRequest();
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://api.funnelchat.com/api/v1/devices', (string) $request->getUri());
        self::assertSame(['connection' => 'qr', 'alias' => 'Store 12', 'external_reference' => 'customer-8841'], self::jsonBody($request));
        self::assertFalse($request->hasHeader('Idempotency-Key'));
        self::assertSame(41, $result['data']['id']);
        self::assertSame(0, $result['data']['connected']);
        self::assertSame('customer-8841', $result['data']['external_reference']);
    }

    public function testSameNumberAsGoesInTheBody(): void
    {
        $http = new MockHttpClient(Reply::json(201, ['data' => self::device(42)]));

        $this->client($http)->devices->create(['connection' => 'qr', 'same_number_as' => 3]);

        self::assertSame(['connection' => 'qr', 'same_number_as' => 3], self::jsonBody($http->lastRequest()));
    }

    public function testDevicesQrAnswersTheImageAndWhenToAskAgain(): void
    {
        $qr = ['connected' => false, 'qr_code' => 'data:image/png;base64,iVBORw0KGgo=', 'expires_in' => 20];
        $http = new MockHttpClient(Reply::ok(['data' => $qr]));

        $result = $this->client($http)->devices->qr(41);

        self::assertSame('GET', $http->lastRequest()->getMethod());
        self::assertSame('https://api.funnelchat.com/api/v1/devices/41/qr', (string) $http->lastRequest()->getUri());
        self::assertSame($qr, $result['data']);
    }

    public function testDevicesListFiltersByExternalReference(): void
    {
        $http = new MockHttpClient(Reply::ok(['data' => [self::device(41)], 'meta' => ['current_page' => 1, 'last_page' => 1]]));

        $ids = [];
        foreach ($this->client($http)->devices->list(['external_reference' => 'customer 8841']) as $device) {
            $ids[] = $device['id'];
        }

        self::assertSame([41], $ids);
        self::assertSame('external_reference=customer%208841', $http->lastRequest()->getUri()->getQuery());
    }

    public function testDevicesListStillWorksWithoutArguments(): void
    {
        $http = new MockHttpClient(Reply::ok(['data' => [self::device(41)]]));

        self::assertSame(41, $this->client($http)->devices->list()->firstPage()->data[0]['id']);
        self::assertSame('', $http->lastRequest()->getUri()->getQuery());
    }

    public function testDevicesUpdateCanChangeTheExternalReference(): void
    {
        $http = new MockHttpClient(Reply::ok(self::device(41)));

        $this->client($http)->devices->update(41, ['external_reference' => 'customer-9000']);

        self::assertSame('PUT', $http->lastRequest()->getMethod());
        self::assertSame(['external_reference' => 'customer-9000'], self::jsonBody($http->lastRequest()));
    }

    public function testConnectLinksCreateReturnsTheLinkWithItsUrl(): void
    {
        $http = new MockHttpClient(Reply::json(201, ['data' => self::link(7, withUrl: true)]));

        $result = $this->client($http)->connectLinks->create([
            'type' => 'meta',
            'allowed_connection_types' => ['coexistence', 'dedicated'],
            'provision_us_number' => false,
            'external_reference' => 'customer-8841',
            'return_url' => 'https://app.example/done',
            'expires_in_minutes' => 1440,
        ]);

        $request = $http->lastRequest();
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://api.funnelchat.com/api/v1/connect-links', (string) $request->getUri());
        self::assertSame([
            'type' => 'meta',
            'allowed_connection_types' => ['coexistence', 'dedicated'],
            'provision_us_number' => false,
            'external_reference' => 'customer-8841',
            'return_url' => 'https://app.example/done',
            'expires_in_minutes' => 1440,
        ], self::jsonBody($request));
        self::assertSame('https://api.funnelchat.com/connect/tok_abc', $result['data']['url'] ?? null);
        self::assertSame('pending', $result['data']['status']);
    }

    public function testAReconnectLinkCarriesTheNumber(): void
    {
        $http = new MockHttpClient(Reply::json(201, ['data' => ['type' => 'qr', 'device_id' => 41] + self::link(8, withUrl: true)]));

        $this->client($http)->connectLinks->create(['type' => 'qr', 'device_id' => 41]);

        self::assertSame(['type' => 'qr', 'device_id' => 41], self::jsonBody($http->lastRequest()));
    }

    public function testConnectLinksGetListAndRevoke(): void
    {
        $http = new MockHttpClient(
            Reply::ok(['data' => self::link(7, 'completed')]),
            Reply::ok(['data' => [self::link(7, 'completed'), self::link(9)], 'meta' => ['current_page' => 1, 'last_page' => 1]]),
            Reply::noContent(),
        );
        $client = $this->client($http);

        self::assertSame('completed', $client->connectLinks->get(7)['data']['status']);
        self::assertSame('https://api.funnelchat.com/api/v1/connect-links/7', (string) $http->lastRequest()->getUri());

        $ids = [];
        foreach ($client->connectLinks->list(['status' => 'pending', 'external_reference' => 'customer-8841']) as $link) {
            $ids[] = $link['id'];
        }
        self::assertSame([7, 9], $ids);
        self::assertSame('status=pending&external_reference=customer-8841', $http->lastRequest()->getUri()->getQuery());

        self::assertNull($client->connectLinks->revoke(9));
        self::assertSame('DELETE', $http->lastRequest()->getMethod());
        self::assertSame('https://api.funnelchat.com/api/v1/connect-links/9', (string) $http->lastRequest()->getUri());
    }

    public function testRevokingALinkThatIsNoLongerPendingIsAConflict(): void
    {
        $http = new MockHttpClient(Reply::json(409, ['message' => 'The link is no longer pending.']));

        $this->expectException(ConflictException::class);
        $this->client($http)->connectLinks->revoke(7);
    }

    // ---- Idempotency-Key ------------------------------------------------------------------------

    public function testTheIdempotencyKeyOptionIsSentAsItsHeaderOnBothCreates(): void
    {
        $http = new MockHttpClient(Reply::json(201, ['data' => self::device(41)]), Reply::json(201, ['data' => self::link(7, withUrl: true)]));
        $client = $this->client($http);

        $client->devices->create(['connection' => 'qr'], ['idempotencyKey' => 'order-8841-number']);
        self::assertSame('order-8841-number', $http->lastRequest()->getHeaderLine('Idempotency-Key'));

        $client->connectLinks->create(['type' => 'qr'], ['idempotencyKey' => 'order-8841-link']);
        self::assertSame('order-8841-link', $http->lastRequest()->getHeaderLine('Idempotency-Key'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidIdempotencyKeys(): array
    {
        return ['empty' => [''], 'blank' => ['   '], 'longer than 255' => [str_repeat('k', 256)]];
    }

    #[DataProvider('invalidIdempotencyKeys')]
    public function testRejectsAnIdempotencyKeyOutOfRangeBeforeSending(string $key): void
    {
        $http = new MockHttpClient();

        try {
            $this->client($http)->devices->create(['connection' => 'qr'], ['idempotencyKey' => $key]);
            self::fail('should have thrown');
        } catch (FunnelchatException $exception) {
            self::assertStringContainsString('idempotencyKey', $exception->getMessage());
        }
        self::assertSame([], $http->requests);
    }

    public function testAcceptsAnIdempotencyKeyOf255Characters(): void
    {
        $http = new MockHttpClient(Reply::json(201, ['data' => self::device(41)]));

        $this->client($http)->devices->create(['connection' => 'qr'], ['idempotencyKey' => str_repeat('k', 255)]);

        self::assertSame(255, \strlen($http->lastRequest()->getHeaderLine('Idempotency-Key')));
    }

    /**
     * @return array<string, array{ResponseInterface|\Throwable}>
     */
    public static function answersAfterWhichAWriteMayHaveBeenApplied(): array
    {
        return [
            'HTTP 500' => [Reply::json(500, ['message' => 'Server Error'])],
            '502 origin_unreachable' => [Reply::json(502, ['error' => 'origin_unreachable'])],
            'timeout' => [new FakeTimeoutException()],
            'dropped connection' => [new FakeNetworkException()],
        ];
    }

    #[DataProvider('answersAfterWhichAWriteMayHaveBeenApplied')]
    public function testRepeatsACreateWithAnIdempotencyKeyWithTheSameKey(ResponseInterface|\Throwable $reply): void
    {
        $http = new MockHttpClient($reply, Reply::json(201, ['data' => self::device(41)]));

        $result = $this->client($http, maxRetries: 2)->devices->create(['connection' => 'qr'], ['idempotencyKey' => 'order-8841']);

        self::assertSame(41, $result['data']['id']);
        self::assertCount(2, $http->requests);
        foreach ($http->requests as $request) {
            self::assertSame('order-8841', $request->getHeaderLine('Idempotency-Key'));
            self::assertSame(['connection' => 'qr'], self::jsonBody($request));
        }
    }

    #[DataProvider('answersAfterWhichAWriteMayHaveBeenApplied')]
    public function testDoesNotRepeatACreateWithoutAnIdempotencyKey(ResponseInterface|\Throwable $reply): void
    {
        $http = new MockHttpClient($reply, Reply::json(201, ['data' => self::device(41)]));

        try {
            $this->client($http, maxRetries: 2)->devices->create(['connection' => 'qr']);
            self::fail('should have thrown');
        } catch (InternalServerException|ApiConnectionException) {
        }
        self::assertCount(1, $http->requests);
    }

    public function testAnIdempotencyKeyDoesNotMakeASendRetryable(): void
    {
        $http = new MockHttpClient(Reply::json(500, ['message' => 'Server Error']), Reply::ok(['message' => []]));

        try {
            $this->client($http, maxRetries: 2)->messages->send(2001, ['device_id' => 3, 'phone' => '5491100000000', 'text' => 'Hola'], ['idempotencyKey' => 'k-1']);
            self::fail('should have thrown');
        } catch (InternalServerException) {
        }
        self::assertCount(1, $http->requests);
    }

    public function testAKeyStillRunningIsAConflictAndIsNotRepeated(): void
    {
        $http = new MockHttpClient(Reply::json(409, ['error' => 'operation_in_progress', 'message' => 'Another operation is running.']), Reply::ok());

        try {
            $this->client($http, maxRetries: 2)->devices->create(['connection' => 'qr'], ['idempotencyKey' => 'order-8841']);
            self::fail('should have thrown');
        } catch (ConflictException $exception) {
            self::assertSame('operation_in_progress', $exception->errorCode);
            self::assertTrue($exception->isDeviceError());
        }
        self::assertCount(1, $http->requests);
    }

    // ---- Refusals -------------------------------------------------------------------------------

    /**
     * @return array<string, array{int, string, class-string}>
     */
    public static function refusals(): array
    {
        return [
            'numbers_not_included' => [403, 'numbers_not_included', PermissionDeniedException::class],
            'number_limit_reached' => [403, 'number_limit_reached', PermissionDeniedException::class],
            'qr_requires_coexistence' => [422, 'qr_requires_coexistence', UnprocessableEntityException::class],
            'method_already_present' => [422, 'method_already_present', UnprocessableEntityException::class],
            'idempotency_key_mismatch' => [422, 'idempotency_key_mismatch', UnprocessableEntityException::class],
            'too_many_open_links' => [429, 'too_many_open_links', RateLimitException::class],
            'meta_connect_disabled' => [503, 'meta_connect_disabled', InternalServerException::class],
        ];
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('refusals')]
    public function testEachRefusalHasItsCodeAndIsNeverRetried(int $status, string $code, string $class): void
    {
        $http = new MockHttpClient(Reply::json($status, ['error' => $code, 'message' => 'Refused.', 'device_id' => 3]), Reply::ok());

        try {
            $this->client($http, maxRetries: 2)->connectLinks->create(['type' => 'meta', 'allowed_connection_types' => ['coexistence']], ['idempotencyKey' => 'k-1']);
            self::fail('should have thrown');
        } catch (\Funnelchat\Exception\ApiException $exception) {
            self::assertInstanceOf($class, $exception);
            self::assertSame($status, $exception->status);
            self::assertSame($code, $exception->errorCode);
            self::assertTrue($exception->isDeviceError());
            self::assertFalse($exception->isSendRuleError());
            self::assertSame(DeviceErrors::CODES[$code], $status);
        }
        self::assertCount(1, $http->requests);
        self::assertSame([], $this->sleeps);
    }

    public function testMethodAlreadyPresentCarriesTheNumberInTheBody(): void
    {
        $http = new MockHttpClient(Reply::json(422, ['error' => 'method_already_present', 'message' => 'That number already has QR.', 'device_id' => 3]));

        try {
            $this->client($http)->devices->create(['connection' => 'qr', 'same_number_as' => 3]);
            self::fail('should have thrown');
        } catch (UnprocessableEntityException $exception) {
            self::assertIsArray($exception->body);
            self::assertSame(3, $exception->body['device_id']);
        }
    }

    public function testAPlainRateLimitOnACreateIsStillRetried(): void
    {
        $http = new MockHttpClient(Reply::rateLimited('1'), Reply::json(201, ['data' => self::link(7, withUrl: true)]));

        self::assertSame(7, $this->client($http, maxRetries: 2)->connectLinks->create(['type' => 'qr'])['data']['id']);
        self::assertSame([1000], $this->sleeps);
    }

    /**
     * @return array<string, array{string|null, bool}>
     */
    public static function deviceCodes(): array
    {
        $cases = ['null' => [null, false], 'a send code' => ['window_closed', false], 'unknown' => ['something_else', false]];
        foreach (array_keys(DeviceErrors::CODES) as $code) {
            $cases[$code] = [$code, true];
        }

        return $cases;
    }

    #[DataProvider('deviceCodes')]
    public function testTheDeviceCodes(?string $code, bool $expected): void
    {
        self::assertSame($expected, DeviceErrors::isDeviceError($code));
    }

    // ---- Events ---------------------------------------------------------------------------------

    public function testKnowsTheFourNumberEvents(): void
    {
        $catalog = (new \ReflectionClassConstant(WebhookEvents::class, 'CATALOG'))->getValue();
        self::assertIsArray($catalog);
        foreach (['device.connected', 'device.disconnected', 'device.deleted', 'device.health_changed'] as $type) {
            self::assertIsArray($catalog[$type] ?? null, $type);
            self::assertSame(['accounts'], $catalog[$type]['domains'], $type);
            self::assertTrue($catalog[$type]['pending'], $type);
        }
        self::assertSame(['device.connection', 'device.external_reference', 'connect_link_id'], $catalog['device.connected']['pendingFields']);
        self::assertSame(['device.connection', 'device.external_reference', 'reason'], $catalog['device.disconnected']['pendingFields']);

        $source = (string) file_get_contents(__DIR__ . '/../src/Generated/WebhookEvents.php');
        self::assertMatchesRegularExpression('/@phpstan-type DeviceConnectedData array\{\s*\*\s*device: PendingWebhookDevice,.*?connect_link_id: int\|string\|null,/s', $source);
        self::assertMatchesRegularExpression("/@phpstan-type DeviceDisconnectedData array\\{.*?reason: 'qr_logged_out'\\|'meta_account_not_registered'\\|'shadow_ban_protection'\\|'provider_error'\\|'unknown',/s", $source);
        self::assertMatchesRegularExpression('/@phpstan-type DeviceDeletedData array\{\s*\*\s*device: PendingWebhookDevice,\s*\*\s*deleted_at: string,/s', $source);
        self::assertMatchesRegularExpression('/@phpstan-type DeviceHealthChangedData array\{.*?previous_state: PendingDeviceHealthState,\s*\*\s*state: PendingDeviceHealthState,/s', $source);

        $schemas = (string) file_get_contents(__DIR__ . '/../src/Generated/Schemas.php');
        self::assertMatchesRegularExpression('/@phpstan-type PendingWebhookDevice array\\{.*?connection: PendingConnectionType,\\s*\\*\\s*external_reference: PendingExternalReference,/s', $schemas);
        self::assertStringContainsString("@phpstan-type PendingConnectionType 'qr'|'coexistence'|'dedicated'", $schemas);
        self::assertStringContainsString("@phpstan-type PendingDeviceHealthState 'available'|'limited'|'blocked'|'unknown'", $schemas);
    }
}
