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
 * Custom fields of contacts (conversations area). To set a value on a contact, see
 * `contacts->attachCustomField()`.
 *
 * @phpstan-import-type RequestOptions from Funnelchat
 * @phpstan-import-type CustomFieldResource from Schemas
 */
final class CustomFields extends AbstractResource
{
    /**
     * `GET /api/v1/custom-fields`, paginated.
     *
     * Permission: `conversations:read`.
     *
     * @param array{
     *     name?: string,
     *     description?: string,
     *     type?: int,
     *     withContactCount?: bool,
     *     perPage?: int,
     *     orderBy?: 'name'|'created_at'|'updated_at',
     *     orderByMethod?: 'asc'|'desc',
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<CustomFieldResource>
     */
    public function list(array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<CustomFieldResource> $result */
        $result = $this->client->paginate('/api/v1/custom-fields', ['query' => $query] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/custom-fields`.
     *
     * Permission: `conversations:write`.
     *
     * @param array{
     *     name: string,
     *     description?: string|null,
     *     type: int,
     * } $body
     * @param RequestOptions $options
     *
     * @return CustomFieldResource
     */
    public function create(array $body, array $options = []): mixed
    {
        /** @var CustomFieldResource $result */
        $result = $this->client->request('POST', '/api/v1/custom-fields', ['body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/custom-fields/{customField}`.
     *
     * Permission: `conversations:read`.
     *
     * @param int $customField
     * @param RequestOptions $options
     *
     * @return CustomFieldResource
     */
    public function retrieve(int|string $customField, array $options = []): mixed
    {
        /** @var CustomFieldResource $result */
        $result = $this->client->request('GET', '/api/v1/custom-fields/{customField}', ['path' => ['customField' => $customField]] + $options);

        return $result;
    }

    /**
     * `PUT /api/v1/custom-fields/{customField}`.
     *
     * Permission: `conversations:write`.
     *
     * @param int $customField
     * @param array{
     *     name?: string,
     *     description?: string|null,
     * } $body
     * @param RequestOptions $options
     *
     * @return CustomFieldResource
     */
    public function update(int|string $customField, array $body, array $options = []): mixed
    {
        /** @var CustomFieldResource $result */
        $result = $this->client->request('PUT', '/api/v1/custom-fields/{customField}', ['path' => ['customField' => $customField], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/custom-fields/delete`.
     *
     * Permission: `conversations:write`.
     *
     * @param array{
     *     customFieldIds: list<string>,
     * } $body
     * @param RequestOptions $options
     *
     * @return array<string, mixed>
     */
    public function delete(array $body, array $options = []): mixed
    {
        /** @var array<string, mixed> $result */
        $result = $this->client->request('POST', '/api/v1/custom-fields/delete', ['body' => $body] + $options);

        return $result;
    }
}
