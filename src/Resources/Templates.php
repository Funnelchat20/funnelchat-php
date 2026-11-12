<?php

declare(strict_types=1);

/*
 * GENERATED FILE — do not edit. Run `composer generate`.
 * Source: spec/funnelchat.json (sha256 a7b480743848dabc…) + spec/pending.json + spec/resources.json.
 */

namespace Funnelchat\Resources;

use Funnelchat\Core\AbstractResource;
use Funnelchat\Funnelchat;
use Funnelchat\Generated\Schemas;
use Funnelchat\Pagination\Paginator;

/**
 * WhatsApp message templates (conversations area): list, create, edit, send.
 *
 * In the developers portal: «Mensajes» (Primer uso), «Plantillas» (Funciones de WhatsApp).
 *
 * @phpstan-import-type RequestOptions from Funnelchat
 * @phpstan-import-type PublicMessageResource from Schemas
 * @phpstan-import-type StoreMessageTemplateRequest from Schemas
 * @phpstan-import-type TemplateResource from Schemas
 * @phpstan-import-type UpdateMessageTemplateRequest from Schemas
 */
final class Templates extends AbstractResource
{
    /**
     * `GET /api/v1/templates`, paginated.
     *
     * Permission: **Contacts and conversations → View**.
     * Response: pending of the spec (`conversations-listings` in spec/pending.json); it may change.
     *
     * @param array{
     *     name?: string|null,
     *     uid?: string|null,
     *     language_code?: string|null,
     *     status?: 'PENDING'|'APPROVED'|'REJECTED'|'PENDING_DELETION'|'PAUSED'|'DISABLED'|'ARCHIVED'|null,
     *     category?: string|null,
     *     deviceId?: int|null,
     *     type?: 'text'|'image'|'document'|'video'|'location'|'default'|null,
     *     created_by_webhook?: bool,
     *     quality?: list<'GREEN'|'YELLOW'|'RED'|'UNKNOWN'|'NOT_APPLICABLE'|null>|null,
     *     include?: 'quality_counts'|null,
     *     perPage?: int,
     *     orderBy?: 'name'|'created_at'|'updated_at',
     *     orderByMethod?: 'asc'|'desc',
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<TemplateResource>
     */
    public function list(array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<TemplateResource> $result */
        $result = $this->client->paginate('/api/v1/templates', ['query' => $query] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/templates`: creates a template and submits it to Meta.
     *
     * Permission: **Contacts and conversations → Edit**.
     *
     * @param StoreMessageTemplateRequest $body
     * @param RequestOptions $options
     *
     * @return TemplateResource
     */
    public function create(array $body, array $options = []): mixed
    {
        /** @var TemplateResource $result */
        $result = $this->client->request('POST', '/api/v1/templates', ['body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/templates/{template}`.
     *
     * Permission: **Contacts and conversations → View**.
     *
     * @param int $template
     * @param RequestOptions $options
     *
     * @return TemplateResource
     */
    public function retrieve(int|string $template, array $options = []): mixed
    {
        /** @var TemplateResource $result */
        $result = $this->client->request('GET', '/api/v1/templates/{template}', ['path' => ['template' => $template]] + $options);

        return $result;
    }

    /**
     * `PUT /api/v1/templates/{template}`.
     *
     * Permission: **Contacts and conversations → Edit**.
     * The spec documents no body: returns `null`, or the decoded body if the API sends one.
     *
     * @param int $template
     * @param UpdateMessageTemplateRequest $body
     * @param RequestOptions $options
     *
     * @return mixed
     */
    public function update(int|string $template, array $body, array $options = []): mixed
    {
        return $this->client->request('PUT', '/api/v1/templates/{template}', ['path' => ['template' => $template], 'body' => $body] + $options);
    }

    /**
     * `POST /api/v1/devices/{device}/templates/{template}/send-template`: sends an approved
     * template (the way to write to a contact outside the 24-hour window). Send `phone` alone (the
     * contact is created if it does not exist, as in `messages->sendDirect()`), or `contact_id`, or
     * both if they are the same contact; `template_variables.body` fills the body variables in
     * order. Returns `['message' => …]`, the registered message (`type` `template`).
     *
     * Permission: **Contacts and conversations → Send**.
     * Response: pending of the spec (`conversations-send` in spec/pending.json); it may change.
     *
     * @param int $device
     * @param string $template
     * @param array{
     *     contact_id?: int|null,
     *     phone?: string|null,
     *     whatsapp_id?: string|null,
     *     template_variables?: array{
     *         body?: list<string|null>,
     *     },
     *     client_reference?: string|null,
     * } $body
     * @param RequestOptions $options
     *
     * @return array{
     *     message: PublicMessageResource,
     * }
     */
    public function send(int|string $device, int|string $template, array $body, array $options = []): mixed
    {
        /**
         * @var array{
         *     message: PublicMessageResource,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/devices/{device}/templates/{template}/send-template', ['path' => ['device' => $device, 'template' => $template], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/devices/{device}/templates`: brings the templates an official number has in
     * Meta into Funnelchat (and drops those of a WhatsApp Business account the number no longer
     * uses), so a template you just created in Meta can be sent; then list them again with
     * `list()`. `{device}` is the official number's id; no body. Returns `['status' =>
     * 'completed']` (done) or `['status' => 'in_progress']` (the first 100 are in, the rest keep
     * coming: list again in a few seconds). Refusals: `404` (the number is not in your key's
     * workspace), `409 template_sync_in_progress` (one is already running for that number: do not
     * start another), `422 number_not_connected_to_meta` (a QR number, or no WhatsApp Business
     * account or credentials), `422 meta_rejected` (Meta refused; its code in `body['meta_code']`;
     * nothing was brought), `502 meta_unavailable` (Meta did not answer). Needs **Contacts and
     * conversations → Edit**.
     *
     * Permission: **Contacts and conversations → Edit**.
     *
     * @param int $device
     * @param RequestOptions $options
     *
     * @return array{
     *     status: 'in_progress'|'completed',
     * }
     */
    public function sync(int|string $device, array $options = []): mixed
    {
        /**
         * @var array{
         *     status: 'in_progress'|'completed',
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/devices/{device}/templates', ['path' => ['device' => $device]] + $options);

        return $result;
    }

    /**
     * `DELETE /api/v1/devices/{device}/templates/{template}`.
     *
     * Permission: **Contacts and conversations → Edit**.
     * The spec documents no body: returns `null`, or the decoded body if the API sends one.
     *
     * @param int $device
     * @param int $template
     * @param RequestOptions $options
     *
     * @return mixed
     */
    public function delete(int|string $device, int|string $template, array $options = []): mixed
    {
        return $this->client->request('DELETE', '/api/v1/devices/{device}/templates/{template}', ['path' => ['device' => $device, 'template' => $template]] + $options);
    }
}
