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
 * WhatsApp groups (communities area): read groups, and act on them by API: create, manage
 * participants and admins, name, description, photo, settings, invite link, send a message, and
 * join requests.
 *
 * Group actions are asynchronous: each one answers `202` with `action_id` and `status: queued`;
 * follow it with `action()` or the `group_action.completed` / `group_action.failed` events. They
 * are OFF by default: until communities turns them on, they answer `403 route_not_available` to any
 * key. An official (Meta) number needs the `cloud-api-groups` beta (`422 provider_unsupported`
 * without it).
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
 * @phpstan-import-type PendingGroupAction from Schemas
 * @phpstan-import-type PendingGroupActionAccepted from Schemas
 * @phpstan-import-type PendingGroupJoinRequest from Schemas
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

    /**
     * `POST /api/v1/groups`: creates a group (`communities:operate`). On a QR number `participants`
     * is required (up to 20); on an official one it is not allowed.
     *
     * Permission: `communities:operate`.
     * Pending of the spec (`communities-group-actions` in spec/pending.json).
     *
     * @param array{
     *     device_id: int,
     *     name: string,
     *     description?: string|null,
     *     join_approval_required?: bool,
     *     participants?: list<string>,
     * } $body
     * @param RequestOptions $options
     *
     * @return PendingGroupActionAccepted
     */
    public function create(array $body, array $options = []): mixed
    {
        /** @var PendingGroupActionAccepted $result */
        $result = $this->client->request('POST', '/api/v1/groups', ['body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/groups/actions/{action}`: an action you requested, with its `status` (`queued`,
     * `running`, `completed`, `failed`), `result` and `reason`.
     *
     * Permission: `communities:read`.
     * Pending of the spec (`communities-group-actions` in spec/pending.json).
     *
     * @param string $action
     * @param RequestOptions $options
     *
     * @return array{
     *     data: PendingGroupAction,
     * }
     */
    public function action(int|string $action, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: PendingGroupAction,
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/groups/actions/{action}', ['path' => ['action' => $action]] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/groups/{group}/participants`: adds participants (an official number invites
     * them).
     *
     * Permission: `communities:operate`.
     * Pending of the spec (`communities-group-actions` in spec/pending.json).
     *
     * @param string $group
     * @param array{
     *     device_id: int,
     *     phones: list<string>,
     * } $body
     * @param RequestOptions $options
     *
     * @return PendingGroupActionAccepted
     */
    public function addParticipants(int|string $group, array $body, array $options = []): mixed
    {
        /** @var PendingGroupActionAccepted $result */
        $result = $this->client->request('POST', '/api/v1/groups/{group}/participants', ['path' => ['group' => $group], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/groups/{group}/participants/remove`: removes participants.
     *
     * Permission: `communities:operate`.
     * Pending of the spec (`communities-group-actions` in spec/pending.json).
     *
     * @param string $group
     * @param array{
     *     device_id: int,
     *     phones: list<string>,
     * } $body
     * @param RequestOptions $options
     *
     * @return PendingGroupActionAccepted
     */
    public function removeParticipants(int|string $group, array $body, array $options = []): mixed
    {
        /** @var PendingGroupActionAccepted $result */
        $result = $this->client->request('POST', '/api/v1/groups/{group}/participants/remove', ['path' => ['group' => $group], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/groups/{group}/admins`: makes participants admins (QR numbers only).
     *
     * Permission: `communities:operate`.
     * Pending of the spec (`communities-group-actions` in spec/pending.json).
     *
     * @param string $group
     * @param array{
     *     device_id: int,
     *     phones: list<string>,
     * } $body
     * @param RequestOptions $options
     *
     * @return PendingGroupActionAccepted
     */
    public function promoteAdmins(int|string $group, array $body, array $options = []): mixed
    {
        /** @var PendingGroupActionAccepted $result */
        $result = $this->client->request('POST', '/api/v1/groups/{group}/admins', ['path' => ['group' => $group], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/groups/{group}/admins/remove`: removes admins (QR numbers only).
     *
     * Permission: `communities:operate`.
     * Pending of the spec (`communities-group-actions` in spec/pending.json).
     *
     * @param string $group
     * @param array{
     *     device_id: int,
     *     phones: list<string>,
     * } $body
     * @param RequestOptions $options
     *
     * @return PendingGroupActionAccepted
     */
    public function demoteAdmins(int|string $group, array $body, array $options = []): mixed
    {
        /** @var PendingGroupActionAccepted $result */
        $result = $this->client->request('POST', '/api/v1/groups/{group}/admins/remove', ['path' => ['group' => $group], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `PUT /api/v1/groups/{group}/name`: changes the name.
     *
     * Permission: `communities:operate`.
     * Pending of the spec (`communities-group-actions` in spec/pending.json).
     *
     * @param string $group
     * @param array{
     *     device_id: int,
     *     name: string,
     * } $body
     * @param RequestOptions $options
     *
     * @return PendingGroupActionAccepted
     */
    public function updateName(int|string $group, array $body, array $options = []): mixed
    {
        /** @var PendingGroupActionAccepted $result */
        $result = $this->client->request('PUT', '/api/v1/groups/{group}/name', ['path' => ['group' => $group], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `PUT /api/v1/groups/{group}/description`: changes the description (`null` or empty clears it
     * on a QR number).
     *
     * Permission: `communities:operate`.
     * Pending of the spec (`communities-group-actions` in spec/pending.json).
     *
     * @param string $group
     * @param array{
     *     device_id: int,
     *     description: string|null,
     * } $body
     * @param RequestOptions $options
     *
     * @return PendingGroupActionAccepted
     */
    public function updateDescription(int|string $group, array $body, array $options = []): mixed
    {
        /** @var PendingGroupActionAccepted $result */
        $result = $this->client->request('PUT', '/api/v1/groups/{group}/description', ['path' => ['group' => $group], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `PUT /api/v1/groups/{group}/photo`: changes the photo from a public `https` URL to a `.jpg`,
     * `.jpeg` or `.png` (QR numbers only).
     *
     * Permission: `communities:operate`.
     * Pending of the spec (`communities-group-actions` in spec/pending.json).
     *
     * @param string $group
     * @param array{
     *     device_id: int,
     *     photo_url: string,
     * } $body
     * @param RequestOptions $options
     *
     * @return PendingGroupActionAccepted
     */
    public function updatePhoto(int|string $group, array $body, array $options = []): mixed
    {
        /** @var PendingGroupActionAccepted $result */
        $result = $this->client->request('PUT', '/api/v1/groups/{group}/photo', ['path' => ['group' => $group], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `PUT /api/v1/groups/{group}/settings`: whether members can send messages (QR numbers only).
     *
     * Permission: `communities:operate`.
     * Pending of the spec (`communities-group-actions` in spec/pending.json).
     *
     * @param string $group
     * @param array{
     *     device_id: int,
     *     members_can_send_messages: bool,
     * } $body
     * @param RequestOptions $options
     *
     * @return PendingGroupActionAccepted
     */
    public function updateSettings(int|string $group, array $body, array $options = []): mixed
    {
        /** @var PendingGroupActionAccepted $result */
        $result = $this->client->request('PUT', '/api/v1/groups/{group}/settings', ['path' => ['group' => $group], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/groups/{group}/invite-link/renew`: revokes the invite link and creates a new
     * one.
     *
     * Permission: `communities:operate`.
     * Pending of the spec (`communities-group-actions` in spec/pending.json).
     *
     * @param string $group
     * @param array{
     *     device_id: int,
     * } $body
     * @param RequestOptions $options
     *
     * @return PendingGroupActionAccepted
     */
    public function renewInviteLink(int|string $group, array $body, array $options = []): mixed
    {
        /** @var PendingGroupActionAccepted $result */
        $result = $this->client->request('POST', '/api/v1/groups/{group}/invite-link/renew', ['path' => ['group' => $group], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/groups/{group}/invite-link/live`: the current invite link, read live through
     * `device_id` (cached 30 seconds).
     *
     * Permission: `communities:read`.
     * Pending of the spec (`communities-group-actions` in spec/pending.json).
     *
     * @param string $group
     * @param array{
     *     device_id: int,
     * } $query
     * @param RequestOptions $options
     *
     * @return array{
     *     data: array{
     *         invite_link: string,
     *     },
     * }
     */
    public function inviteLink(int|string $group, array $query, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: array{
         *         invite_link: string,
         *     },
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/groups/{group}/invite-link/live', ['path' => ['group' => $group], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/groups/{group}/messages`: sends text or a file (`media_url`) to a group
     * (`communities:send`). A send: never retried automatically.
     *
     * Permission: `communities:send`.
     * Pending of the spec (`communities-group-actions` in spec/pending.json).
     *
     * @param string $group
     * @param array{
     *     device_id: int,
     *     type: 'text'|'image'|'video'|'audio'|'document',
     *     text?: string,
     *     media_url?: string,
     *     caption?: string|null,
     *     file_name?: string|null,
     * } $body
     * @param RequestOptions $options
     *
     * @return PendingGroupActionAccepted
     */
    public function sendMessage(int|string $group, array $body, array $options = []): mixed
    {
        /** @var PendingGroupActionAccepted $result */
        $result = $this->client->request('POST', '/api/v1/groups/{group}/messages', ['path' => ['group' => $group], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/groups/{group}/join-requests`: the pending join requests (up to 100), read live.
     * Official numbers and groups only.
     *
     * Permission: `communities:read`.
     * Pending of the spec (`communities-group-actions` in spec/pending.json).
     *
     * @param string $group
     * @param array{
     *     device_id: int,
     * } $query
     * @param RequestOptions $options
     *
     * @return array{
     *     data: list<PendingGroupJoinRequest>,
     * }
     */
    public function listJoinRequests(int|string $group, array $query, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: list<PendingGroupJoinRequest>,
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/groups/{group}/join-requests', ['path' => ['group' => $group], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/groups/{group}/join-requests/approve`: approves join requests by phone.
     * Official numbers only, behind an option communities keeps off.
     *
     * Permission: `communities:operate`.
     * Pending of the spec (`communities-group-actions` in spec/pending.json).
     *
     * @param string $group
     * @param array{
     *     device_id: int,
     *     phones: list<string>,
     * } $body
     * @param RequestOptions $options
     *
     * @return PendingGroupActionAccepted
     */
    public function approveJoinRequests(int|string $group, array $body, array $options = []): mixed
    {
        /** @var PendingGroupActionAccepted $result */
        $result = $this->client->request('POST', '/api/v1/groups/{group}/join-requests/approve', ['path' => ['group' => $group], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/groups/{group}/join-requests/reject`: rejects join requests by phone. Official
     * numbers only, behind an option communities keeps off.
     *
     * Permission: `communities:operate`.
     * Pending of the spec (`communities-group-actions` in spec/pending.json).
     *
     * @param string $group
     * @param array{
     *     device_id: int,
     *     phones: list<string>,
     * } $body
     * @param RequestOptions $options
     *
     * @return PendingGroupActionAccepted
     */
    public function rejectJoinRequests(int|string $group, array $body, array $options = []): mixed
    {
        /** @var PendingGroupActionAccepted $result */
        $result = $this->client->request('POST', '/api/v1/groups/{group}/join-requests/reject', ['path' => ['group' => $group], 'body' => $body] + $options);

        return $result;
    }
}
