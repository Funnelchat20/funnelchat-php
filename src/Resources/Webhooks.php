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
use Funnelchat\Webhooks\WebhookHelpers;

/**
 * Webhook subscriptions (accounts area) and the verification of deliveries.
 *
 * PENDING OF THE SPEC: the `/api/v1/webhooks` subscription routes come from accounts `origin/main`
 * @ c34cba2c (unified subscriptions, in production since 13-10), not from the snapshot; their types
 * are in spec/pending.json until a snapshot brings them. At that commit the routes are not in the
 * gate's `allowed` list, so an API key may get `403 route_not_available` until accounts opens them.
 * The event catalog (`events()`) is in the snapshot.
 *
 * @phpstan-import-type RequestOptions from Funnelchat
 * @phpstan-import-type PendingWebhookSubscription from Schemas
 * @phpstan-import-type PendingWebhookSubscriptionCreate from Schemas
 * @phpstan-import-type PendingWebhookSubscriptionDelivery from Schemas
 * @phpstan-import-type PendingWebhookSubscriptionTestResult from Schemas
 * @phpstan-import-type PendingWebhookSubscriptionUpdate from Schemas
 * @phpstan-import-type PendingWebhookSubscriptionWithSecret from Schemas
 */
final class Webhooks extends AbstractResource
{
    use WebhookHelpers;

    /**
     * `GET /api/v1/webhooks/events`: the event types you can subscribe to, each with `status`.
     *
     * Permission: `accounts:read`.
     *
     * @param RequestOptions $options
     *
     * @return array{
     *     data: list<mixed>,
     *     meta: array{
     *         total: int,
     *         active: int,
     *         envelope_version: 'v1',
     *         signature_header: 'X-Funnelchat-Signature',
     *         signature_format: string,
     *     },
     * }
     */
    public function events(array $options = []): mixed
    {
        /**
         * @var array{
         *     data: list<mixed>,
         *     meta: array{
         *         total: int,
         *         active: int,
         *         envelope_version: 'v1',
         *         signature_header: 'X-Funnelchat-Signature',
         *         signature_format: string,
         *     },
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/webhooks/events', $options);

        return $result;
    }

    /**
     * `GET /api/v1/webhooks`: every subscription of the account (at most 50), not only yours.
     *
     * Permission: `accounts:read`.
     * Pending of the spec (`accounts-webhooks` in spec/pending.json).
     *
     * @param RequestOptions $options
     *
     * @return list<PendingWebhookSubscription>
     */
    public function list(array $options = []): mixed
    {
        /** @var list<PendingWebhookSubscription> $result */
        $result = $this->client->request('GET', '/api/v1/webhooks', $options);

        return $result;
    }

    /**
     * `POST /api/v1/webhooks`: the response brings `secret` once. Store it, and store the `id`.
     *
     * Permission: `accounts:write`.
     * Pending of the spec (`accounts-webhooks` in spec/pending.json).
     *
     * @param PendingWebhookSubscriptionCreate $body
     * @param RequestOptions $options
     *
     * @return PendingWebhookSubscriptionWithSecret
     */
    public function create(array $body, array $options = []): mixed
    {
        /** @var PendingWebhookSubscriptionWithSecret $result */
        $result = $this->client->request('POST', '/api/v1/webhooks', ['body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/webhooks/{id}` (without the secret).
     *
     * Permission: `accounts:read`.
     * Pending of the spec (`accounts-webhooks` in spec/pending.json).
     *
     * @param string $id
     * @param RequestOptions $options
     *
     * @return PendingWebhookSubscription
     */
    public function retrieve(int|string $id, array $options = []): mixed
    {
        /** @var PendingWebhookSubscription $result */
        $result = $this->client->request('GET', '/api/v1/webhooks/{id}', ['path' => ['id' => $id]] + $options);

        return $result;
    }

    /**
     * `PUT /api/v1/webhooks/{id}`: replaces `url`, `events`, `description` and `filters`. The
     * secret stays.
     *
     * Permission: `accounts:write`.
     * Pending of the spec (`accounts-webhooks` in spec/pending.json).
     *
     * @param string $id
     * @param PendingWebhookSubscriptionUpdate $body
     * @param RequestOptions $options
     *
     * @return PendingWebhookSubscription
     */
    public function update(int|string $id, array $body, array $options = []): mixed
    {
        /** @var PendingWebhookSubscription $result */
        $result = $this->client->request('PUT', '/api/v1/webhooks/{id}', ['path' => ['id' => $id], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `DELETE /api/v1/webhooks/{id}`.
     *
     * Permission: `accounts:write`.
     * Pending of the spec (`accounts-webhooks` in spec/pending.json).
     * The spec documents no body: returns `null`, or the decoded body if the API sends one.
     *
     * @param string $id
     * @param RequestOptions $options
     *
     * @return mixed
     */
    public function delete(int|string $id, array $options = []): mixed
    {
        return $this->client->request('DELETE', '/api/v1/webhooks/{id}', ['path' => ['id' => $id]] + $options);
    }

    /**
     * `POST /api/v1/webhooks/{id}/rotate-secret`: the new secret comes once; the old one stops
     * working.
     *
     * Permission: `accounts:write`.
     * Pending of the spec (`accounts-webhooks` in spec/pending.json).
     *
     * @param string $id
     * @param RequestOptions $options
     *
     * @return PendingWebhookSubscriptionWithSecret
     */
    public function rotateSecret(int|string $id, array $options = []): mixed
    {
        /** @var PendingWebhookSubscriptionWithSecret $result */
        $result = $this->client->request('POST', '/api/v1/webhooks/{id}/rotate-secret', ['path' => ['id' => $id]] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/webhooks/{id}/test`: delivers one signed `webhook.test` now. Check `succeeded`.
     *
     * Permission: `accounts:write`.
     * Pending of the spec (`accounts-webhooks` in spec/pending.json).
     *
     * @param string $id
     * @param RequestOptions $options
     *
     * @return PendingWebhookSubscriptionTestResult
     */
    public function test(int|string $id, array $options = []): mixed
    {
        /** @var PendingWebhookSubscriptionTestResult $result */
        $result = $this->client->request('POST', '/api/v1/webhooks/{id}/test', ['path' => ['id' => $id]] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/webhooks/{id}/deliveries`, paginated: one row per attempt, newest first.
     *
     * Permission: `accounts:read`.
     * Pending of the spec (`accounts-webhooks` in spec/pending.json).
     *
     * @param string $id
     * @param array{
     *     page?: int,
     *     per_page?: int,
     *     event_type?: string,
     *     event_id?: string,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<PendingWebhookSubscriptionDelivery>
     */
    public function listDeliveries(int|string $id, array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<PendingWebhookSubscriptionDelivery> $result */
        $result = $this->client->paginate('/api/v1/webhooks/{id}/deliveries', ['path' => ['id' => $id], 'query' => $query] + $options);

        return $result;
    }
}
