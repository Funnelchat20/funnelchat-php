<?php

declare(strict_types=1);

/*
 * GENERATED FILE — do not edit. Run `composer generate`.
 * Source: spec/funnelchat.json (sha256 8bbbae569b4cab52…) + spec/pending.json + spec/resources.json.
 */

namespace Funnelchat\Resources;

use Funnelchat\Core\AbstractResource;
use Funnelchat\Funnelchat;
use Funnelchat\Generated\Schemas;
use Funnelchat\Pagination\Paginator;

/**
 * WhatsApp numbers of the account (accounts area). The `id` of a number is the `device_id` used to
 * send.
 *
 * @phpstan-import-type RequestOptions from Funnelchat
 * @phpstan-import-type DeviceConnectionEventResource from Schemas
 * @phpstan-import-type DeviceHealthDetailResource from Schemas
 * @phpstan-import-type DeviceResource from Schemas
 * @phpstan-import-type PendingDeviceCreate from Schemas
 * @phpstan-import-type PendingDeviceQr from Schemas
 * @phpstan-import-type PendingExternalReference from Schemas
 */
final class Devices extends AbstractResource
{
    /**
     * `GET /api/v1/devices`. Iterate it with `foreach`, or call `firstPage()`. Filter by your own
     * reference with `external_reference` (exact match). The call of 0.1, `list($options)`, still
     * works.
     *
     * Permission: `accounts:read`.
     * Pending of the spec (`accounts-number-onboarding` in spec/pending.json):
     * `external_reference`.
     *
     * @param array{
     *     external_reference?: string,
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<DeviceResource>
     */
    public function list(array $query = [], array $options = []): Paginator
    {
        // The call of 0.1, `list($options)`, keeps working.
        [$query, $options] = self::queryOrOptions($query, $options);

        /** @var Paginator<DeviceResource> $result */
        $result = $this->client->paginate('/api/v1/devices', ['query' => $query] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/devices`: creates a QR number (`connection: 'qr'`), not connected yet. Returns
     * `['data' => <number>]`; then scan its QR with `qr()`, or send your customer a link with
     * `connectLinks->create()`. The account has at most one QR number not connected yet: another
     * one answers `409 pending_number_exists` with its `device_id`. It uses the plan's quota: see
     * `Funnelchat\Exception\DeviceErrors`. Pass `idempotencyKey` in the options to repeat it
     * safely.
     *
     * Permission: `accounts:operate`.
     * Pending of the spec (`accounts-number-onboarding` in spec/pending.json).
     *
     * @param PendingDeviceCreate $body
     * @param RequestOptions $options
     *
     * @return array{
     *     data: DeviceResource,
     * }
     */
    public function create(array $body, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: DeviceResource,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/devices', ['body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/devices/{device}`.
     *
     * Permission: `accounts:read`.
     *
     * @param int $device
     * @param RequestOptions $options
     *
     * @return DeviceResource
     */
    public function retrieve(int|string $device, array $options = []): mixed
    {
        /** @var DeviceResource $result */
        $result = $this->client->request('GET', '/api/v1/devices/{device}', ['path' => ['device' => $device]] + $options);

        return $result;
    }

    /**
     * `PUT /api/v1/devices/{device}`.
     *
     * Permission: `accounts:write`.
     * Pending of the spec (`accounts-number-onboarding` in spec/pending.json):
     * `external_reference`.
     *
     * @param int $device
     * @param array{
     *     alias?: string|null,
     *     color?: string|null,
     *     image?: string|null,
     *     'number'?: string|null,
     *     first_conecction_at?: string|null,
     *     is_business?: bool,
     *     phone_number_id?: string,
     *     waba_id?: string,
     *     code?: string,
     *     whatsApp_business_app?: bool,
     *     capi_consent?: bool,
     *     external_reference?: PendingExternalReference,
     * } $body
     * @param RequestOptions $options
     *
     * @return DeviceResource
     */
    public function update(int|string $device, array $body, array $options = []): mixed
    {
        /** @var DeviceResource $result */
        $result = $this->client->request('PUT', '/api/v1/devices/{device}', ['path' => ['device' => $device], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `DELETE /api/v1/devices/{device}`: deletes the number for good and frees its quota. An
     * official number loses the app subscription but stays registered with Meta; a QR number's
     * session is closed. It emits `device.deleted`. There is no undo.
     *
     * Permission: `accounts:operate`.
     * Response: pending of the spec (`accounts-number-onboarding` in spec/pending.json); it may
     * change.
     * The spec documents no body: returns `null`, or the decoded body if the API sends one.
     *
     * @param int $device
     * @param RequestOptions $options
     *
     * @return mixed
     */
    public function delete(int|string $device, array $options = []): mixed
    {
        return $this->client->request('DELETE', '/api/v1/devices/{device}', ['path' => ['device' => $device]] + $options);
    }

    /**
     * `GET /api/v1/devices/{device}/status`: connection status.
     *
     * Permission: `accounts:read`.
     *
     * @param int $device
     * @param RequestOptions $options
     *
     * @return array<string, mixed>
     */
    public function status(int|string $device, array $options = []): mixed
    {
        /** @var array<string, mixed> $result */
        $result = $this->client->request('GET', '/api/v1/devices/{device}/status', ['path' => ['device' => $device]] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/devices/{device}/qr`: the QR to scan, as a PNG data URI in `data.qr_code`
     * (`null` once `data.connected`). Ask again after `data.expires_in` seconds (20 with a QR, 3
     * while it starts, 0 once connected), or wait for `device.connected`. An official number
     * answers `422 provider_unsupported`.
     *
     * Permission: `accounts:operate`.
     * Pending of the spec (`accounts-number-onboarding` in spec/pending.json).
     *
     * @param int $device
     * @param RequestOptions $options
     *
     * @return array{
     *     data: PendingDeviceQr,
     * }
     */
    public function qr(int|string $device, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: PendingDeviceQr,
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/devices/{device}/qr', ['path' => ['device' => $device]] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/devices/{device}/health`.
     *
     * Permission: `accounts:read`.
     *
     * @param int $device
     * @param RequestOptions $options
     *
     * @return DeviceHealthDetailResource
     */
    public function health(int|string $device, array $options = []): mixed
    {
        /** @var DeviceHealthDetailResource $result */
        $result = $this->client->request('GET', '/api/v1/devices/{device}/health', ['path' => ['device' => $device]] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/devices/{device}/me`: the WhatsApp profile of the number.
     *
     * Permission: `accounts:read`.
     *
     * @param int $device
     * @param RequestOptions $options
     *
     * @return null
     */
    public function me(int|string $device, array $options = []): mixed
    {
        /** @var null $result */
        $result = $this->client->request('GET', '/api/v1/devices/{device}/me', ['path' => ['device' => $device]] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/devices/{device}/check-phone`: whether a phone has WhatsApp.
     *
     * Permission: `accounts:read`.
     *
     * @param int $device
     * @param array{
     *     phone: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return null
     */
    public function checkPhone(int|string $device, array $query, array $options = []): mixed
    {
        /** @var null $result */
        $result = $this->client->request('GET', '/api/v1/devices/{device}/check-phone', ['path' => ['device' => $device], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/devices/{device}/connection-history`, paginated.
     *
     * Permission: `accounts:read`.
     *
     * @param int $device
     * @param array{
     *     orderBy?: string,
     *     orderByMethod?: string,
     *     perPage?: int,
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<DeviceConnectionEventResource>
     */
    public function listConnectionHistory(int|string $device, array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<DeviceConnectionEventResource> $result */
        $result = $this->client->paginate('/api/v1/devices/{device}/connection-history', ['path' => ['device' => $device], 'query' => $query] + $options);

        return $result;
    }
}
