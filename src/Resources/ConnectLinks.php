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
 * Connect links (accounts area): a single-use page where your customer creates or reconnects a
 * WhatsApp number, by QR or with Meta's signup, without a Funnelchat session. The number is created
 * when the page ends. Learn the result by `device.connected` (with `connect_link_id`, the link of
 * that connection, and `external_reference`), by `return_url`, or with `retrieve()`.
 *
 * Creating and revoking need `accounts:operate`; reading, `accounts:read`. The official signup
 * (`type: 'meta'`) is off in production until Funnelchat registers its domain with Meta: it answers
 * `503 meta_connect_disabled`. PENDING OF THE SPEC: from the contract 1022-alta-de-numeros v0.2
 * (spec/pending.json, `accounts-number-onboarding`).
 *
 * @phpstan-import-type RequestOptions from Funnelchat
 * @phpstan-import-type PendingConnectLink from Schemas
 * @phpstan-import-type PendingConnectLinkCreate from Schemas
 */
final class ConnectLinks extends AbstractResource
{
    /**
     * `POST /api/v1/connect-links`: returns `['data' => <link>]` with its `url`, the only time the
     * URL comes: give it to your customer. `allowed_connection_types` is required with `type:
     * 'meta'` and forbidden with `type: 'qr'`; `device_id` reconnects that number without new
     * quota. `country` (two letters, optional; only `qr`, not with `device_id`) is the country of
     * the phone, where the WhatsApp session is opened. `type: 'meta'` is off in production (`503
     * meta_connect_disabled`). `provision_us_number` (a US pool number, always dedicated) needs
     * `dedicated` in `allowed_connection_types` and does not go with `device_id`: otherwise `422
     * invalid_request`. A `meta` link stays `pending` after a Facebook or Meta failure, so your
     * customer can retry on the page; the fifth failure makes it `failed`. Refusals: see
     * `Funnelchat\Exception\DeviceErrors`. Pass `idempotencyKey` in the options to repeat it
     * safely.
     *
     * Permission: `accounts:operate`.
     * Pending of the spec (`accounts-number-onboarding` in spec/pending.json).
     *
     * @param PendingConnectLinkCreate $body
     * @param RequestOptions $options
     *
     * @return array{
     *     data: PendingConnectLink,
     * }
     */
    public function create(array $body, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: PendingConnectLink,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/connect-links', ['body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/connect-links/{link}`: the link and its `status` (`pending`, `completed`,
     * `failed`, `expired`, `revoked`), with `device_id` and `error_code`; never its URL.
     *
     * Permission: `accounts:read`.
     * Pending of the spec (`accounts-number-onboarding` in spec/pending.json).
     *
     * @param int $link
     * @param RequestOptions $options
     *
     * @return array{
     *     data: PendingConnectLink,
     * }
     */
    public function retrieve(int|string $link, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: PendingConnectLink,
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/connect-links/{link}', ['path' => ['link' => $link]] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/connect-links`, paginated, newest first. Filter by `status` and
     * `external_reference`.
     *
     * Permission: `accounts:read`.
     * Pending of the spec (`accounts-number-onboarding` in spec/pending.json).
     *
     * @param array{
     *     status?: 'pending'|'completed'|'failed'|'expired'|'revoked',
     *     external_reference?: string,
     *     perPage?: int,
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<PendingConnectLink>
     */
    public function list(array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<PendingConnectLink> $result */
        $result = $this->client->paginate('/api/v1/connect-links', ['query' => $query] + $options);

        return $result;
    }

    /**
     * `DELETE /api/v1/connect-links/{link}`: revokes a `pending` link. A link that is no longer
     * `pending` answers `409 link_not_pending`.
     *
     * Permission: `accounts:operate`.
     * Pending of the spec (`accounts-number-onboarding` in spec/pending.json).
     * The spec documents no body: returns `null`, or the decoded body if the API sends one.
     *
     * @param int $link
     * @param RequestOptions $options
     *
     * @return mixed
     */
    public function revoke(int|string $link, array $options = []): mixed
    {
        return $this->client->request('DELETE', '/api/v1/connect-links/{link}', ['path' => ['link' => $link]] + $options);
    }
}
