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

/**
 * Messages to one contact (conversations area): send text, files and buttons, read the history or
 * one message by id, forward, react.
 *
 * Sending with an API key needs the **Contacts and conversations → Send** permission and is
 * subject to the WhatsApp rules: `422 window_closed`, `422 antiblock_*`, `422 recipient_*`, `422
 * device_unavailable`, `429 rate_limited` (with `retryAfter`) and `429 mac_lockout_24h`. A rejected
 * send goes nowhere (see `Funnelchat\Exception\SendErrors`). Send operations are never retried
 * automatically unless you pass `retrySend: true`.
 *
 * In the developers portal: «Mensajes» (Primer uso), «Conversaciones» (Funciones de
 * Funnelchat), «Atender una conversación» (Funciones de WhatsApp).
 *
 * @phpstan-import-type RequestOptions from Funnelchat
 * @phpstan-import-type ForwardMessageRequest from Schemas
 * @phpstan-import-type MessageResource from Schemas
 * @phpstan-import-type PublicMessageResource from Schemas
 * @phpstan-import-type SendDirectMessageRequest from Schemas
 * @phpstan-import-type SendWhatsappFormRequest from Schemas
 * @phpstan-import-type StoreContactCardRequest from Schemas
 * @phpstan-import-type StoreMessageReactionRequest from Schemas
 */
final class Messages extends AbstractResource
{
    /**
     * `POST /api/v1/contacts/{contact}/message`: text, a file (`file` with a URL) or buttons and
     * lists (`options`). Returns `['message' => …]`: the registered message, with its `id` and
     * `status` `queued`. Queued is not sent: the next states arrive by `message.status_changed`, or
     * read them with `get()`.
     *
     * Send `phone` (or `whatsapp_id`) of the same contact: the API validates it. `contact_id` is
     * optional: it defaults to the contact of the route. A file from a URL that does not show its
     * format (signed, or without extension) needs `file_type`; a document like that, `file_name`
     * with its extension. `client_reference` comes back in the message, the history and
     * `message.status_changed`.
     *
     * Permission: **Contacts and conversations → Send**.
     * Response: pending of the spec (`conversations-send` in spec/pending.json); it may change.
     *
     * @param int $contact
     * @param array{
     *     phone?: string,
     *     whatsapp_id?: string,
     *     device_id: int,
     *     text?: string,
     *     contact_id?: int,
     *     file?: string,
     *     file_type?: 'image'|'video'|'audio'|'document'|'sticker'|null,
     *     file_name?: string|null,
     *     temporal_uuid?: string,
     *     note?: bool,
     *     mentions?: list<int>|null,
     *     options?: list<array{
     *         id?: string,
     *         label?: string,
     *     }>,
     *     reference_message_uid?: string|null,
     *     client_reference?: string|null,
     *     link_preview?: bool,
     *     link_preview_meta?: array{
     *         url?: string,
     *         title?: string|null,
     *         description?: string|null,
     *         image_url?: string|null,
     *         domain?: string,
     *     },
     * } $body
     * @param RequestOptions $options
     *
     * @return array{
     *     message: PublicMessageResource,
     * }
     */
    public function send(int|string $contact, array $body, array $options = []): mixed
    {
        $body['contact_id'] ??= $contact;

        /**
         * @var array{
         *     message: PublicMessageResource,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/contacts/{contact}/message', ['path' => ['contact' => $contact], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/message/send-message`: a send straight to a phone: `device_id`, `phone`
     * (digits, without `+`) and the text or file, no contact needed. If the phone is not a contact
     * yet, it is created in your key's workspace (the same rules and `422`s as
     * `contacts->upsert()`), and it stays created even if a WhatsApp rule then stops the send.
     * Returns `['message' => …]`, as `send()`, with the contact in `message.contact_id`. Instead
     * of `phone`: `contact_id`, or `whatsapp_id` (a BSUID, official numbers only; not with
     * `phone`). With `phone` and `contact_id`, they must be the same contact (`422
     * recipient_mismatch`); a phone of a contact of another workspace answers `404`. Needs
     * **Contacts and conversations → Send**. It is a send: same rules as `send()`.
     *
     * Permission: **Contacts and conversations → Send**.
     * Response: pending of the spec (`conversations-send` in spec/pending.json); it may change.
     *
     * @param SendDirectMessageRequest $body
     * @param RequestOptions $options
     *
     * @return array{
     *     message: PublicMessageResource,
     * }
     */
    public function sendDirect(array $body, array $options = []): mixed
    {
        /**
         * @var array{
         *     message: PublicMessageResource,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/message/send-message', ['body' => $body] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/contacts/{contact}/message/form`: sends a published WhatsApp form (`form_id` is
     * the form's `family_id`) as a message with a button that opens it. Returns `['message' =>
     * …]` with `type` `whatsapp_form`; the answers arrive by `whatsapp_form.completed`. It is a
     * send: same rules as `send()`.
     *
     * Permission: **Contacts and conversations → Send**.
     * Response: pending of the spec (`conversations-send` in spec/pending.json); it may change.
     *
     * @param int $contact
     * @param SendWhatsappFormRequest $body
     * @param RequestOptions $options
     *
     * @return array{
     *     message: PublicMessageResource,
     * }
     */
    public function sendForm(int|string $contact, array $body, array $options = []): mixed
    {
        /**
         * @var array{
         *     message: PublicMessageResource,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/contacts/{contact}/message/form', ['path' => ['contact' => $contact], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/contacts/{contactId}/messages`, paginated by cursor: the history of a contact,
     * with the `client_reference` of each send.
     *
     * Permission: **Contacts and conversations → View**.
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
     * @return Paginator<MessageResource>
     */
    public function list(int|string $contactId, array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<MessageResource> $result */
        $result = $this->client->paginate('/api/v1/contacts/{contactId}/messages', ['path' => ['contactId' => $contactId], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/contacts/{contact}/messages/{message}`: one message by the id a send or a
     * webhook gave you (`message_id` of `message.status_changed`), with its current `status` and
     * `error`. Any refusal is a `404`.
     *
     * Permission: **Contacts and conversations → View**.
     *
     * @param int $contact
     * @param int $message
     * @param RequestOptions $options
     *
     * @return PublicMessageResource
     */
    public function get(int|string $contact, int|string $message, array $options = []): mixed
    {
        /** @var PublicMessageResource $result */
        $result = $this->client->request('GET', '/api/v1/contacts/{contact}/messages/{message}', ['path' => ['contact' => $contact, 'message' => $message]] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/contacts/{contact}/message/contact-card`: sends up to 5 contact cards. Returns
     * `['messages' => […]]`, one registered message per card, in order.
     *
     * Permission: **Contacts and conversations → Send**.
     *
     * @param int $contact
     * @param StoreContactCardRequest $body
     * @param RequestOptions $options
     *
     * @return array{
     *     messages: list<PublicMessageResource>,
     * }
     */
    public function sendContactCard(int|string $contact, array $body, array $options = []): mixed
    {
        /**
         * @var array{
         *     messages: list<PublicMessageResource>,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/contacts/{contact}/message/contact-card', ['path' => ['contact' => $contact], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/contacts/{contact}/messages/{message}/forward`: forwards a message to up to 5
     * contacts. Returns one item per target, in order: `status` `queued` or `blocked` (with
     * `reason`) and its `message`.
     *
     * Permission: **Contacts and conversations → Send**.
     *
     * @param int $contact
     * @param int $message
     * @param ForwardMessageRequest $body
     * @param RequestOptions $options
     *
     * @return list<array{
     *     contact_id: int,
     *     mode: 'internal'|'native',
     *     status: 'queued'|'blocked',
     *     reason: string|null,
     *     message: PublicMessageResource|null,
     * }>
     */
    public function forward(int|string $contact, int|string $message, array $body, array $options = []): mixed
    {
        /**
         * @var list<array{
         *     contact_id: int,
         *     mode: 'internal'|'native',
         *     status: 'queued'|'blocked',
         *     reason: string|null,
         *     message: PublicMessageResource|null,
         * }> $result
         */
        $result = $this->client->request('POST', '/api/v1/contacts/{contact}/messages/{message}/forward', ['path' => ['contact' => $contact, 'message' => $message], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `PUT /api/v1/contacts/{contact}/messages/{message}/reaction`: reacts with an emoji.
     *
     * Permission: **Contacts and conversations → Send**.
     * The spec documents no body: returns `null`, or the decoded body if the API sends one.
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
     * Permission: **Contacts and conversations → Send**.
     * The spec documents no body: returns `null`, or the decoded body if the API sends one.
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
