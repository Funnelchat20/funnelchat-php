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
 * Messages to one contact (conversations area): send text, files and buttons, read the history,
 * forward, react.
 *
 * Sending with an API key needs the `conversations:send` permission and is subject to the WhatsApp
 * rules: `422 window_closed`, `422 antiblock_*`, `422 recipient_*` and `429 rate_limited` (with
 * `retryAfter`). A rejected send stores nothing. Send operations are never retried automatically
 * unless you pass `retrySend: true`.
 *
 * @phpstan-import-type RequestOptions from Funnelchat
 * @phpstan-import-type ConversationsMessageResource from Schemas
 * @phpstan-import-type ForwardMessageRequest from Schemas
 * @phpstan-import-type PendingClientReference from Schemas
 * @phpstan-import-type PendingForwardResult from Schemas
 * @phpstan-import-type PendingPublicMessage from Schemas
 * @phpstan-import-type PendingQueuedContactCard from Schemas
 * @phpstan-import-type StoreContactCardRequest from Schemas
 * @phpstan-import-type StoreMessageReactionRequest from Schemas
 */
final class Messages extends AbstractResource
{
    /**
     * `POST /api/v1/contacts/{contact}/message`: text, a file (`file` with a URL) or buttons and
     * lists (`options`). Returns `['message' => …]`: the registered message, `status` `queued`
     * (pending of the spec), or `null` while the API answers `204`.
     *
     * Send `phone` (or `whatsapp_id`) of the same contact: the API validates it. `contact_id` is
     * optional: it defaults to the contact of the route. `client_reference` (pending of the spec)
     * comes back in the history and in `message.status_changed`.
     *
     * Permission: `conversations:send`.
     * Pending of the spec (`conversations-c2` in spec/pending.json): `client_reference`.
     * Response: pending of the spec (`conversations-send-result` in spec/pending.json); it may
     * change.
     * Portal note (v0, `send_pending`): sending with an API key is not open yet; until it is, this
     * route answers `403 route_not_available`.
     *
     * @param int $contact
     * @param array{
     *     phone?: string,
     *     whatsapp_id?: string,
     *     device_id: int,
     *     text?: string,
     *     contact_id?: int,
     *     file?: string,
     *     temporal_uuid?: string,
     *     note?: bool,
     *     options?: list<array{
     *         id?: string,
     *         label?: string,
     *     }>,
     *     reference_message_uid?: string|null,
     *     link_preview?: bool,
     *     link_preview_meta?: array{
     *         url?: string,
     *         title?: string|null,
     *         description?: string|null,
     *         image_url?: string|null,
     *         domain?: string,
     *     },
     *     client_reference?: PendingClientReference,
     * } $body
     * @param RequestOptions $options
     *
     * @return array{
     *     message: PendingPublicMessage|null,
     * }|null
     */
    public function send(int|string $contact, array $body, array $options = []): mixed
    {
        $body['contact_id'] ??= $contact;

        /**
         * @var array{
         *     message: PendingPublicMessage|null,
         * }|null $result
         */
        $result = $this->client->request('POST', '/api/v1/contacts/{contact}/message', ['path' => ['contact' => $contact], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/contacts/{contactId}/messages`, paginated by cursor: the history of a contact.
     *
     * Permission: `conversations:read`.
     *
     * @param int $contactId
     * @param array{
     *     cursor?: string,
     *     updated_since?: string,
     *     perPage?: int,
     *     page?: int,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<ConversationsMessageResource>
     */
    public function list(int|string $contactId, array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<ConversationsMessageResource> $result */
        $result = $this->client->paginate('/api/v1/contacts/{contactId}/messages', ['path' => ['contactId' => $contactId], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/contacts/{contact}/message/contact-card`: sends up to 5 contact cards.
     *
     * Permission: `conversations:send`.
     * Pending of the spec (`conversations-c2` in spec/pending.json): `client_reference`.
     * Response: pending of the spec (`conversations-send-result` in spec/pending.json); it may
     * change.
     * Portal note (v0, `send_pending`): sending with an API key is not open yet; until it is, this
     * route answers `403 route_not_available`.
     *
     * @param int $contact
     * @param StoreContactCardRequest $body
     * @param RequestOptions $options
     *
     * @return array{
     *     messages: list<PendingPublicMessage|PendingQueuedContactCard|null>,
     * }|null
     */
    public function sendContactCard(int|string $contact, array $body, array $options = []): mixed
    {
        /**
         * @var array{
         *     messages: list<PendingPublicMessage|PendingQueuedContactCard|null>,
         * }|null $result
         */
        $result = $this->client->request('POST', '/api/v1/contacts/{contact}/message/contact-card', ['path' => ['contact' => $contact], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/contacts/{contact}/messages/{message}/forward`: forwards a message to up to 5
     * contacts.
     *
     * Permission: `conversations:send`.
     * Pending of the spec (`conversations-c2` in spec/pending.json): `client_reference`.
     * Response: pending of the spec (`conversations-send-result` in spec/pending.json); it may
     * change.
     * Portal note (v0, `send_pending`): sending with an API key is not open yet; until it is, this
     * route answers `403 route_not_available`.
     *
     * @param int $contact
     * @param int $message
     * @param ForwardMessageRequest $body
     * @param RequestOptions $options
     *
     * @return list<PendingForwardResult>
     */
    public function forward(int|string $contact, int|string $message, array $body, array $options = []): mixed
    {
        /** @var list<PendingForwardResult> $result */
        $result = $this->client->request('POST', '/api/v1/contacts/{contact}/messages/{message}/forward', ['path' => ['contact' => $contact, 'message' => $message], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `PUT /api/v1/contacts/{contact}/messages/{message}/reaction`: reacts with an emoji.
     *
     * Permission: `conversations:send`.
     * The spec documents no body: returns `null`, or the decoded body if the API sends one.
     * Portal note (v0, `send_pending`): sending with an API key is not open yet; until it is, this
     * route answers `403 route_not_available`.
     *
     * @param int $contact
     * @param int $message
     * @param StoreMessageReactionRequest $body
     * @param RequestOptions $options
     *
     * @return mixed
     */
    public function react(int|string $contact, int|string $message, array $body, array $options = []): mixed
    {
        return $this->client->request('PUT', '/api/v1/contacts/{contact}/messages/{message}/reaction', ['path' => ['contact' => $contact, 'message' => $message], 'body' => $body] + $options);
    }

    /**
     * `DELETE /api/v1/contacts/{contact}/messages/{message}/reaction`: removes your reaction.
     *
     * Permission: `conversations:send`.
     * The spec documents no body: returns `null`, or the decoded body if the API sends one.
     * Portal note (v0, `send_pending`): sending with an API key is not open yet; until it is, this
     * route answers `403 route_not_available`.
     *
     * @param int $contact
     * @param int $message
     * @param RequestOptions $options
     *
     * @return mixed
     */
    public function removeReaction(int|string $contact, int|string $message, array $options = []): mixed
    {
        return $this->client->request('DELETE', '/api/v1/contacts/{contact}/messages/{message}/reaction', ['path' => ['contact' => $contact, 'message' => $message]] + $options);
    }
}
