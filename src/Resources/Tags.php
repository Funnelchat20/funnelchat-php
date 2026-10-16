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
 * Tags (conversations area).
 *
 * @phpstan-import-type RequestOptions from Funnelchat
 * @phpstan-import-type ContactResource from Schemas
 * @phpstan-import-type TagResource from Schemas
 */
final class Tags extends AbstractResource
{
    /**
     * `GET /api/v1/tags`, paginated.
     *
     * Permission: `conversations:read`.
     *
     * @param array{
     *     name?: string,
     *     withContactCount?: bool,
     *     perPage?: int,
     *     orderBy?: 'name'|'created_at'|'updated_at',
     *     orderByMethod?: 'asc'|'desc',
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<TagResource>
     */
    public function list(array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<TagResource> $result */
        $result = $this->client->paginate('/api/v1/tags', ['query' => $query] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/tags`.
     *
     * Permission: `conversations:write`.
     *
     * @param array{
     *     name: string,
     *     description?: string|null,
     *     color?: string|null,
     * } $body
     * @param RequestOptions $options
     *
     * @return TagResource
     */
    public function create(array $body, array $options = []): mixed
    {
        /** @var TagResource $result */
        $result = $this->client->request('POST', '/api/v1/tags', ['body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/tags/{tag}`.
     *
     * Permission: `conversations:read`.
     *
     * @param int $tag
     * @param RequestOptions $options
     *
     * @return TagResource
     */
    public function retrieve(int|string $tag, array $options = []): mixed
    {
        /** @var TagResource $result */
        $result = $this->client->request('GET', '/api/v1/tags/{tag}', ['path' => ['tag' => $tag]] + $options);

        return $result;
    }

    /**
     * `PUT /api/v1/tags/{tag}`.
     *
     * Permission: `conversations:write`.
     *
     * @param int $tag
     * @param array{
     *     name: string,
     *     description?: string|null,
     *     color?: string|null,
     * } $body
     * @param RequestOptions $options
     *
     * @return array<string, mixed>
     */
    public function update(int|string $tag, array $body, array $options = []): mixed
    {
        /** @var array<string, mixed> $result */
        $result = $this->client->request('PUT', '/api/v1/tags/{tag}', ['path' => ['tag' => $tag], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/tags/delete`: deletes the tags in `tagIds`.
     *
     * Permission: `conversations:write`.
     *
     * @param array{
     *     tagIds: list<string>,
     * } $body
     * @param RequestOptions $options
     *
     * @return array<string, mixed>
     */
    public function delete(array $body, array $options = []): mixed
    {
        /** @var array<string, mixed> $result */
        $result = $this->client->request('POST', '/api/v1/tags/delete', ['body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/tags/{tag}/contacts`, paginated: the contacts with a tag.
     *
     * Permission: `conversations:read`.
     *
     * @param int $tag
     * @param array{
     *     perPage?: int,
     *     orderBy?: 'name'|'last_message_time',
     *     orderByMethod?: 'asc'|'desc',
     *     sortBy?: 'asc'|'desc',
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<ContactResource>
     */
    public function listContacts(int|string $tag, array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<ContactResource> $result */
        $result = $this->client->paginate('/api/v1/tags/{tag}/contacts', ['path' => ['tag' => $tag], 'query' => $query] + $options);

        return $result;
    }
}
