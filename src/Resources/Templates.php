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
 * WhatsApp message templates (conversations area): list, create, edit, send.
 *
 * @phpstan-import-type RequestOptions from Funnelchat
 * @phpstan-import-type PendingClientReference from Schemas
 * @phpstan-import-type PendingPublicMessage from Schemas
 * @phpstan-import-type StoreMessageTemplateRequest from Schemas
 * @phpstan-import-type TemplateResource from Schemas
 * @phpstan-import-type UpdateMessageTemplateRequest from Schemas
 */
final class Templates extends AbstractResource
{
    /**
     * `GET /api/v1/templates`, paginated.
     *
     * Permission: `conversations:read`.
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
     * Permission: `conversations:write`.
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
     * Permission: `conversations:read`.
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
     * Permission: `conversations:write`.
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
     * `GET /api/v1/templates/{template}/quick-reply-buttons`.
     *
     * Permission: `conversations:read`.
     *
     * @param int $template
     * @param RequestOptions $options
     *
     * @return array{
     *     data: list<mixed>,
     * }
     */
    public function quickReplyButtons(int|string $template, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: list<mixed>,
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/templates/{template}/quick-reply-buttons', ['path' => ['template' => $template]] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/devices/{device}/templates/{template}/send-template`: sends an approved
     * template (the way to write to a contact outside the 24-hour window). `phone` and `contact_id`
     * are required; `template_variables.body` fills the body variables in order. Returns
     * `['message' => …]`, the registered message (`type` `template`).
     *
     * Permission: `conversations:send`.
     * Pending of the spec (`conversations-send` in spec/pending.json): `client_reference`.
     * Response: pending of the spec (`conversations-send` in spec/pending.json); it may change.
     *
     * @param int $device
     * @param string $template
     * @param array{
     *     phone: string,
     *     contact_id: int,
     *     template_variables?: array{
     *         body?: list<string|null>,
     *     },
     *     client_reference?: PendingClientReference,
     * } $body
     * @param RequestOptions $options
     *
     * @return array{
     *     message: PendingPublicMessage,
     * }
     */
    public function send(int|string $device, int|string $template, array $body, array $options = []): mixed
    {
        /**
         * @var array{
         *     message: PendingPublicMessage,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/devices/{device}/templates/{template}/send-template', ['path' => ['device' => $device, 'template' => $template], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `DELETE /api/v1/devices/{device}/templates/{template}`.
     *
     * Permission: `conversations:write`.
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
