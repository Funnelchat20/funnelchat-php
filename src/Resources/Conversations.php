<?php

declare(strict_types=1);

/*
 * GENERATED FILE — do not edit. Run `composer generate`.
 * Source: spec/funnelchat.json (sha256 8bbbae569b4cab52…) + spec/pending.json + spec/resources.json.
 */

namespace Funnelchat\Resources;

use Funnelchat\Core\AbstractResource;
use Funnelchat\Funnelchat;
use Funnelchat\Pagination\Paginator;

/**
 * Conversations (conversations area): the inbox, its counters and the state of each conversation.
 *
 * @phpstan-import-type RequestOptions from Funnelchat
 */
final class Conversations extends AbstractResource
{
    /**
     * `GET /api/v1/conversations`, paginated by cursor.
     *
     * Permission: **Contacts and conversations → View**.
     *
     * @param array{
     *     favorites?: bool,
     *     ownContacts?: bool,
     *     ownAndUnassigned?: bool,
     *     perName?: string,
     *     deviceId?: int,
     *     contactId?: int,
     *     advisorId?: int,
     *     advisorIds?: string,
     *     activeConversation?: bool,
     *     tags?: string,
     *     kanbanSettingId?: int,
     *     kanbanStageIds?: string,
     *     unreadMessages?: bool,
     *     onlyWithMac?: bool,
     *     cursor?: string,
     *     updated_since?: string,
     *     paused?: 'true'|'false'|'1'|'0',
     *     leadScoreCategories?: string|null,
     *     leadScoreMin?: int,
     *     leadScoreMax?: int,
     *     leadScoreDisqualified?: 'include'|'exclude'|'only'|null,
     *     metaWindowOpen?: bool,
     *     metaWindowDeviceIds?: string|null,
     *     perPage?: int,
     *     orderBy?: 'asc'|'desc',
     *     page?: int,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<array<string, mixed>>
     */
    public function list(array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<array<string, mixed>> $result */
        $result = $this->client->paginate('/api/v1/conversations', ['query' => $query] + $options);

        return $result;
    }

    /**
     * `PUT /api/v1/contacts/{contact}/update`: changes the conversation with a contact (open,
     * assigned…).
     *
     * Permission: **Contacts and conversations → Edit**.
     *
     * @param int $contact
     * @param array{
     *     favorite?: bool,
     *     active_conversation?: bool,
     *     unread_messages?: int,
     *     adviser_id?: int|null,
     *     device_id?: int,
     *     text?: string,
     *     notes?: string|null,
     *     custom_fields?: list<string>,
     *     manually_unread?: bool,
     * } $body
     * @param RequestOptions $options
     *
     * @return array<string, mixed>
     */
    public function update(int|string $contact, array $body, array $options = []): mixed
    {
        /** @var array<string, mixed> $result */
        $result = $this->client->request('PUT', '/api/v1/contacts/{contact}/update', ['path' => ['contact' => $contact], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/conversations/count/unread`.
     *
     * Permission: **Contacts and conversations → View**.
     *
     * @param array{
     *     advisorIds?: string,
     *     favorites?: bool,
     *     ownAndUnassigned?: bool,
     * } $query
     * @param RequestOptions $options
     *
     * @return array{
     *     count: int,
     * }
     */
    public function countUnread(array $query = [], array $options = []): mixed
    {
        /**
         * @var array{
         *     count: int,
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/conversations/count/unread', ['query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/conversations/count/open`.
     *
     * Permission: **Contacts and conversations → View**.
     *
     * @param array{
     *     advisorIds?: string,
     *     favorites?: bool,
     *     ownAndUnassigned?: bool,
     * } $query
     * @param RequestOptions $options
     *
     * @return array{
     *     count: int,
     * }
     */
    public function countOpen(array $query = [], array $options = []): mixed
    {
        /**
         * @var array{
         *     count: int,
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/conversations/count/open', ['query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/conversations/count/closed`.
     *
     * Permission: **Contacts and conversations → View**.
     *
     * @param array{
     *     advisorIds?: string,
     *     favorites?: bool,
     *     ownAndUnassigned?: bool,
     * } $query
     * @param RequestOptions $options
     *
     * @return array{
     *     count: int,
     * }
     */
    public function countClosed(array $query = [], array $options = []): mixed
    {
        /**
         * @var array{
         *     count: int,
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/conversations/count/closed', ['query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/conversations/count/all`.
     *
     * Permission: **Contacts and conversations → View**.
     *
     * @param array{
     *     advisorIds?: string,
     *     favorites?: bool,
     *     ownAndUnassigned?: bool,
     * } $query
     * @param RequestOptions $options
     *
     * @return array{
     *     count: int,
     * }
     */
    public function countAll(array $query = [], array $options = []): mixed
    {
        /**
         * @var array{
         *     count: int,
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/conversations/count/all', ['query' => $query] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/conversations/mark-all-read`.
     *
     * Permission: **Contacts and conversations → Edit**.
     *
     * @param array{
     *     advisorIds?: string,
     *     favorites?: bool,
     *     ownAndUnassigned?: bool,
     * }|null $body
     * @param RequestOptions $options
     *
     * @return array{
     *     message: 'Todas las conversaciones han sido marcadas como leídas',
     *     updated_count: int,
     * }
     */
    public function markAllRead(?array $body = null, array $options = []): mixed
    {
        /**
         * @var array{
         *     message: 'Todas las conversaciones han sido marcadas como leídas',
         *     updated_count: int,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/conversations/mark-all-read', ['body' => $body] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/conversations/mark-all-unread`.
     *
     * Permission: **Contacts and conversations → Edit**.
     *
     * @param array{
     *     advisorIds?: string,
     *     favorites?: bool,
     *     ownAndUnassigned?: bool,
     * }|null $body
     * @param RequestOptions $options
     *
     * @return array{
     *     message: string,
     *     marked_count: int,
     *     limit_reached: bool,
     *     contact_ids: array<string, mixed>,
     * }
     */
    public function markAllUnread(?array $body = null, array $options = []): mixed
    {
        /**
         * @var array{
         *     message: string,
         *     marked_count: int,
         *     limit_reached: bool,
         *     contact_ids: array<string, mixed>,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/conversations/mark-all-unread', ['body' => $body] + $options);

        return $result;
    }
}
