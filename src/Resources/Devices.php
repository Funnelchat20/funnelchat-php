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
 */
final class Devices extends AbstractResource
{
    /**
     * `GET /api/v1/devices`. Iterate it with `foreach`, or call `firstPage()`.
     *
     * Permission: `accounts:read`.
     *
     * @param RequestOptions $options
     *
     * @return Paginator<DeviceResource>
     */
    public function list(array $options = []): Paginator
    {
        /** @var Paginator<DeviceResource> $result */
        $result = $this->client->paginate('/api/v1/devices', $options);

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
