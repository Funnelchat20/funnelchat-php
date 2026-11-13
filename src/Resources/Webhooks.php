<?php

declare(strict_types=1);

/*
 * GENERATED FILE — do not edit. Run `composer generate`.
 * Source: spec/funnelchat.json (sha256 37c54a5aad4cc467…) + spec/pending.json + spec/resources.json.
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
 * The subscription routes need **Notifications (webhooks) → View** / **Notifications (webhooks)
 * → Manage**, and a key only sees and manages the subscriptions of its workspace whose events are
 * of areas it can read (the area's **View**). A subscription receives only the events of its
 * workspace, and every event says which one in `data.workspace_id`. The catalog (`events()`) needs
 * **WhatsApp numbers → View**. Rotating the secret is not open to API keys: it is done from the
 * dashboard. The answer of `update()` and the query parameters of `list()` and `listDeliveries()`
 * that production accepts come from spec/pending.json (`accounts-webhooks`).
 *
 * In the developers portal: «Webhooks» (Funciones de Funnelchat).
 *
 * @phpstan-import-type RequestOptions from Funnelchat
 * @phpstan-import-type StoreWebhookSubscriptionRequest from Schemas
 * @phpstan-import-type UpdateWebhookSubscriptionRequest from Schemas
 * @phpstan-import-type WebhookSubscriptionCreatedResource from Schemas
 * @phpstan-import-type WebhookSubscriptionDeliveryResource from Schemas
 * @phpstan-import-type WebhookSubscriptionResource from Schemas
 */
final class Webhooks extends AbstractResource
{
    use WebhookHelpers;

    /**
     * `GET /api/v1/webhooks/events`: the event types you can subscribe to, each with `status`.
     *
     * Permission: **WhatsApp numbers → View**.
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
     * `GET /api/v1/webhooks`, paginated: the subscriptions of your key's workspace (at most 50),
     * not only yours, except those with events of an area your key cannot read.
     *
     * Permission: **Notifications (webhooks) → View**.
     * Pending of the spec (`accounts-webhooks` in spec/pending.json): `perPage`.
     *
     * @param array{
     *     perPage?: int,
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<WebhookSubscriptionResource>
     */
    public function list(array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<WebhookSubscriptionResource> $result */
        $result = $this->client->paginate('/api/v1/webhooks', ['query' => $query] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/webhooks`: the response brings `secret` once. Store it, and store the `id`.
     *
     * Permission: **Notifications (webhooks) → Manage**.
     *
     * @param StoreWebhookSubscriptionRequest $body
     * @param RequestOptions $options
     *
     * @return WebhookSubscriptionCreatedResource
     */
    public function create(array $body, array $options = []): mixed
    {
        /** @var WebhookSubscriptionCreatedResource $result */
        $result = $this->client->request('POST', '/api/v1/webhooks', ['body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/webhooks/{id}` (without the secret).
     *
     * Permission: **Notifications (webhooks) → View**.
     *
     * @param string $id
     * @param RequestOptions $options
     *
     * @return WebhookSubscriptionResource
     */
    public function retrieve(int|string $id, array $options = []): mixed
    {
        /** @var WebhookSubscriptionResource $result */
        $result = $this->client->request('GET', '/api/v1/webhooks/{id}', ['path' => ['id' => $id]] + $options);

        return $result;
    }

    /**
     * `PUT /api/v1/webhooks/{id}`: replaces `url`, `events`, `description` and `filters`. The
     * secret stays.
     *
     * Permission: **Notifications (webhooks) → Manage**.
     * Response: pending of the spec (`accounts-webhooks` in spec/pending.json); it may change.
     *
     * @param string $id
     * @param UpdateWebhookSubscriptionRequest $body
     * @param RequestOptions $options
     *
     * @return WebhookSubscriptionResource
     */
    public function update(int|string $id, array $body, array $options = []): mixed
    {
        /** @var WebhookSubscriptionResource $result */
        $result = $this->client->request('PUT', '/api/v1/webhooks/{id}', ['path' => ['id' => $id], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `DELETE /api/v1/webhooks/{id}`.
     *
     * Permission: **Notifications (webhooks) → Manage**.
     *
     * @param string $id
     * @param RequestOptions $options
     *
     * @return null
     */
    public function delete(int|string $id, array $options = []): mixed
    {
        /** @var null $result */
        $result = $this->client->request('DELETE', '/api/v1/webhooks/{id}', ['path' => ['id' => $id]] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/webhooks/{id}/test`: delivers one signed `webhook.test` now. Check `succeeded`.
     *
     * Permission: **Notifications (webhooks) → Manage**.
     *
     * @param string $id
     * @param RequestOptions $options
     *
     * @return array{
     *     succeeded: bool,
     *     response_status: int|null,
     *     error: 'non_2xx'|'internal_error'|'timeout'|'connection_failed'|'tls_error'|'destination_not_allowed'|'secret_unreadable'|null,
     *     duration_ms: int|null,
     *     delivery_id: int,
     * }
     */
    public function test(int|string $id, array $options = []): mixed
    {
        /**
         * @var array{
         *     succeeded: bool,
         *     response_status: int|null,
         *     error: 'non_2xx'|'internal_error'|'timeout'|'connection_failed'|'tls_error'|'destination_not_allowed'|'secret_unreadable'|null,
         *     duration_ms: int|null,
         *     delivery_id: int,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/webhooks/{id}/test', ['path' => ['id' => $id]] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/webhooks/{id}/deliveries`, paginated: one row per attempt, newest first.
     *
     * Permission: **Notifications (webhooks) → View**.
     * Pending of the spec (`accounts-webhooks` in spec/pending.json): `per_page`, `event_type`,
     * `event_id`.
     *
     * @param string $id
     * @param array{
     *     per_page?: int,
     *     event_type?: string,
     *     event_id?: string,
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<WebhookSubscriptionDeliveryResource>
     */
    public function listDeliveries(int|string $id, array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<WebhookSubscriptionDeliveryResource> $result */
        $result = $this->client->paginate('/api/v1/webhooks/{id}/deliveries', ['path' => ['id' => $id], 'query' => $query] + $options);

        return $result;
    }
}
