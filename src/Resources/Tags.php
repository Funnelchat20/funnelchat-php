<?php

declare(strict_types=1);

/*
 * GENERATED FILE — do not edit. Run `composer generate`.
 * Source: spec/funnelchat.json (sha256 7db2ead826697c80…) + spec/pending.json + spec/resources.json.
 */

namespace Funnelchat\Resources;

use Funnelchat\Core\AbstractResource;
use Funnelchat\Funnelchat;
use Funnelchat\Generated\Schemas;
use Funnelchat\Pagination\Paginator;

/**
 * Tags (conversations area).
 *
 * In the developers portal: «Etiquetas» (Funciones de Funnelchat).
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
     * Permission: **Contacts and conversations → View**.
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
     * Permission: **Contacts and conversations → Edit**.
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
     * Permission: **Contacts and conversations → View**.
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
     * Permission: **Contacts and conversations → Edit**.
     * The spec documents no body: returns `null`, or the decoded body if the API sends one.
     *
     * @param int $tag
     * @param array{
     *     name: string,
     *     description?: string|null,
     *     color?: string|null,
     * } $body
     * @param RequestOptions $options
     *
     * @return mixed
     */
    public function update(int|string $tag, array $body, array $options = []): mixed
    {
        return $this->client->request('PUT', '/api/v1/tags/{tag}', ['path' => ['tag' => $tag], 'body' => $body] + $options);
    }

    /**
     * `POST /api/v1/tags/delete`: deletes the tags in `tagIds`.
     *
     * Permission: **Contacts and conversations → Edit**.
     * The spec documents no body: returns `null`, or the decoded body if the API sends one.
     *
     * @param array{
     *     tagIds: list<string>,
     * } $body
     * @param RequestOptions $options
     *
     * @return mixed
     */
    public function delete(array $body, array $options = []): mixed
    {
        return $this->client->request('POST', '/api/v1/tags/delete', ['body' => $body] + $options);
    }

    /**
     * `GET /api/v1/tags/{tag}/contacts`, paginated: the contacts with a tag.
     *
     * Permission: **Contacts and conversations → View**.
     * Response: pending of the spec (`conversations-listings` in spec/pending.json); it may change.
     *
     * @param int $tag
     * @param array{
     *     perPage?: int,
     *     orderBy?: 'name'|'last_message_time'|'follow_up_date',
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
