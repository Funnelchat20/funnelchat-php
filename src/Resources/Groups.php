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
 * WhatsApp groups (communities area), read only: the groups, their participants, joins and leaves,
 * messages, scheduled messages and actions.
 *
 * @phpstan-import-type RequestOptions from Funnelchat
 * @phpstan-import-type ActionGroupResource from Schemas
 * @phpstan-import-type ChannelMetadataResource from Schemas
 * @phpstan-import-type CommunitiesMessageResource from Schemas
 * @phpstan-import-type GroupMetadataResource from Schemas
 * @phpstan-import-type GroupMinResource from Schemas
 * @phpstan-import-type GroupResource from Schemas
 * @phpstan-import-type ParticipantEventResource from Schemas
 * @phpstan-import-type ParticipantResource from Schemas
 * @phpstan-import-type ScheduledMessageResource from Schemas
 */
final class Groups extends AbstractResource
{
    /**
     * `GET /api/v1/groups`, paginated.
     *
     * Permission: `communities:read`.
     *
     * @param array{
     *     name?: string,
     *     orderBy?: string,
     *     orderByMethod?: string,
     *     type?: '1'|'2'|'3'|'4',
     *     perPage?: int,
     *     status?: '0'|'1'|'2'|'3'|'4'|'5'|'6'|'7'|'8',
     *     deviceId?: int,
     *     noCampaign?: bool,
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<GroupResource>
     */
    public function list(array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<GroupResource> $result */
        $result = $this->client->paginate('/api/v1/groups', ['query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/groups/{group}`.
     *
     * Permission: `communities:read`.
     *
     * @param string $group
     * @param RequestOptions $options
     *
     * @return GroupResource
     */
    public function retrieve(int|string $group, array $options = []): mixed
    {
        /** @var GroupResource $result */
        $result = $this->client->request('GET', '/api/v1/groups/{group}', ['path' => ['group' => $group]] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/devices/{device}/groups`, paginated (`page`, `pageSize`): the groups of one
     * number.
     *
     * Permission: `communities:read`.
     *
     * @param int $device
     * @param array{
     *     page?: int,
     *     pageSize?: int,
     *     name?: string|null,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<GroupMinResource>
     */
    public function listByDevice(int|string $device, array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<GroupMinResource> $result */
        $result = $this->client->paginate('/api/v1/devices/{device}/groups', ['path' => ['device' => $device], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/groups/{groupUid}/metadata`: live metadata of a group by its WhatsApp uid, read
     * through one number.
     *
     * Permission: `communities:read`.
     *
     * @param string $groupUid
     * @param array{
     *     device_id: int,
     *     group_uid: string,
     *     type?: 'groups'|'ad-groups'|'channels',
     * } $query
     * @param RequestOptions $options
     *
     * @return GroupMetadataResource|ChannelMetadataResource
     */
    public function metadata(int|string $groupUid, array $query, array $options = []): mixed
    {
        /** @var GroupMetadataResource|ChannelMetadataResource $result */
        $result = $this->client->request('GET', '/api/v1/groups/{groupUid}/metadata', ['path' => ['groupUid' => $groupUid], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/groups/participants`, paginated: the participants of one group (`groupUid`).
     *
     * Permission: `communities:read`.
     *
     * @param array{
     *     groupUid: string,
     *     phone?: string,
     *     isAdmin?: 'true'|'false'|'1'|'0',
     *     eligibleForAdmin?: 'true'|'false'|'1'|'0',
     *     orderBy?: string,
     *     orderDirection?: 'asc'|'desc',
     *     perPage?: int,
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<ParticipantResource>
     */
    public function listParticipants(array $query, array $options = []): Paginator
    {
        /** @var Paginator<ParticipantResource> $result */
        $result = $this->client->paginate('/api/v1/groups/participants', ['query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/groups/{group}/participant-events`, paginated: joins and leaves.
     *
     * Permission: `communities:read`.
     *
     * @param string $group
     * @param array{
     *     search?: string|null,
     *     event_type?: 'all'|'join'|'leave'|null,
     *     page?: int|null,
     *     perPage?: int|null,
     *     start_date?: string|null,
     *     end_date?: string|null,
     *     timezone?: string|null,
     *     date_mode?: 'both'|'joins_only'|'leaves_only'|null,
     *     selection_type?: 'all'|'none'|null,
     *     groups_uids?: list<string>,
     *     participant_filter?: 'all'|'active'|null,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<ParticipantEventResource>
     */
    public function listParticipantEvents(int|string $group, array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<ParticipantEventResource> $result */
        $result = $this->client->paginate('/api/v1/groups/{group}/participant-events', ['path' => ['group' => $group], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/groups/{group}/messages`, paginated.
     *
     * Permission: `communities:read`.
     *
     * @param string $group
     * @param array{
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<CommunitiesMessageResource>
     */
    public function listMessages(int|string $group, array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<CommunitiesMessageResource> $result */
        $result = $this->client->paginate('/api/v1/groups/{group}/messages', ['path' => ['group' => $group], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/groups/{group}/scheduled-messages`, paginated.
     *
     * Permission: `communities:read`.
     *
     * @param string $group
     * @param array{
     *     name?: string,
     *     orderBy?: string,
     *     orderByMethod?: string,
     *     perPage?: int,
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<ScheduledMessageResource>
     */
    public function listScheduledMessages(int|string $group, array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<ScheduledMessageResource> $result */
        $result = $this->client->paginate('/api/v1/groups/{group}/scheduled-messages', ['path' => ['group' => $group], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/groups/{group}/actions`, paginated.
     *
     * Permission: `communities:read`.
     *
     * @param string $group
     * @param array{
     *     orderBy?: 'status'|'created_at'|'updated_at'|'completed_at',
     *     orderByMethod?: 'asc'|'desc',
     *     page?: int,
     *     perPage?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<ActionGroupResource>
     */
    public function listActions(int|string $group, array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<ActionGroupResource> $result */
        $result = $this->client->paginate('/api/v1/groups/{group}/actions', ['path' => ['group' => $group], 'query' => $query] + $options);

        return $result;
    }
}
