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
 * Contacts (conversations area): search, create, update, custom fields, notes and the 24-hour
 * window.
 *
 * @phpstan-import-type RequestOptions from Funnelchat
 * @phpstan-import-type ContactCustomFieldResource from Schemas
 * @phpstan-import-type ContactNoteResource from Schemas
 * @phpstan-import-type ContactResource from Schemas
 * @phpstan-import-type ContactTagResource from Schemas
 * @phpstan-import-type StoreContactNoteRequest from Schemas
 * @phpstan-import-type StoreContactRequest from Schemas
 * @phpstan-import-type UpdateContactRequest from Schemas
 */
final class Contacts extends AbstractResource
{
    /**
     * `GET /api/v1/contacts`, paginated. Filter by `phone` without the `+`.
     *
     * Permission: **Contacts and conversations → View** (`conversations:read`).
     *
     * @param array{
     *     name?: string,
     *     email?: string,
     *     phone?: string,
     *     whatsapp_id?: string,
     *     country_codes?: list<string>,
     *     status?: string,
     *     image?: string,
     *     created_at?: string,
     *     created_from?: string,
     *     created_to?: string,
     *     tag_ids?: list<int>,
     *     tag_mode?: 'all'|'any'|'exclude',
     *     custom_field_ids?: list<int>,
     *     custom_field_mode?: 'all'|'any'|'exclude',
     *     custom_field_value?: string,
     *     custom_field_date_from?: string,
     *     custom_field_date_to?: string,
     *     custom_field_numeric_operator?: 'lt'|'eq'|'gt',
     *     custom_field_numeric_value?: float|int,
     *     only_with_phone?: string,
     *     only_with_mac?: string,
     *     scopeToAdvisor?: string,
     *     lead_score_categories?: list<'insufficient_data'|'hot'|'warm'|'cold'>,
     *     lead_score_min?: int,
     *     lead_score_max?: int,
     *     lead_score_disqualified?: 'include'|'exclude'|'only'|null,
     *     tag_filters?: list<array{
     *         mode: 'all'|'any'|'exclude',
     *         tag_ids?: list<int>,
     *     }>,
     *     custom_field_filters?: list<array{
     *         type: '0'|'1'|'2'|'3'|'4',
     *         mode?: 'all'|'any'|'exclude'|null,
     *         value?: string|null,
     *         date_from?: string|null,
     *         date_to?: string|null,
     *         numeric_operator?: 'lt'|'eq'|'gt'|null,
     *         numeric_value?: float|int|null,
     *         date_operator?: 'lt'|'gt'|'eq'|'period'|null,
     *         custom_field_ids?: list<int>,
     *     }>,
     *     perPage?: int,
     *     orderBy?: 'id'|'name'|'email'|'phone'|'status'|'favorite'|'unread_messages'|'created_at'|'updated_at',
     *     orderByMethod?: 'asc'|'desc',
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<ContactResource>
     */
    public function list(array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<ContactResource> $result */
        $result = $this->client->paginate('/api/v1/contacts', ['query' => $query] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/contacts`.
     *
     * Permission: **Contacts and conversations → Edit** (`conversations:write`).
     *
     * @param StoreContactRequest $body
     * @param RequestOptions $options
     *
     * @return ContactResource
     */
    public function create(array $body, array $options = []): mixed
    {
        /** @var ContactResource $result */
        $result = $this->client->request('POST', '/api/v1/contacts', ['body' => $body] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/contacts/update-or-create`: creates the contact or updates the one with that
     * phone.
     *
     * Permission: **Contacts and conversations → Edit** (`conversations:write`).
     *
     * @param array{
     *     phone: string,
     *     name?: string|null,
     *     email?: string|null,
     * } $body
     * @param RequestOptions $options
     *
     * @return ContactResource
     */
    public function upsert(array $body, array $options = []): mixed
    {
        /** @var ContactResource $result */
        $result = $this->client->request('POST', '/api/v1/contacts/update-or-create', ['body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/contacts/{contact}`.
     *
     * Permission: **Contacts and conversations → View** (`conversations:read`).
     *
     * @param int $contact
     * @param RequestOptions $options
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     phone: string,
     *     whatsapp_id: string,
     *     whatsapp_username: string|null,
     *     has_phone: bool,
     *     country_code: string,
     *     status: int,
     *     image: string,
     *     notes: string|null,
     *     custom_fields: list<ContactCustomFieldResource>,
     *     created_at: string|null,
     *     active_conversation: int,
     *     favorite: int,
     *     unread_messages: int,
     *     manually_unread: bool,
     *     last_message?: array{
     *         text: string,
     *         file: string,
     *         date: string,
     *         type: string,
     *         own: string,
     *         deleted_at: string,
     *         deleted_scope: string,
     *     },
     *     device_id: int|null,
     *     adviser_id: int|null,
     *     paused_at: string|null,
     *     mac_date: string|null,
     *     ai_assistant_name: string|null,
     *     stage_since?: string,
     *     is_stagnant?: bool,
     *     lead_score?: array{
     *         score: int,
     *         signals_count: int,
     *         category: string,
     *         confidence: string,
     *         disqualified: bool,
     *         disqualified_at: string|null,
     *         updated_at: string|null,
     *     },
     * }
     */
    public function retrieve(int|string $contact, array $options = []): mixed
    {
        /**
         * @var array{
         *     id: int,
         *     name: string,
         *     email: string,
         *     phone: string,
         *     whatsapp_id: string,
         *     whatsapp_username: string|null,
         *     has_phone: bool,
         *     country_code: string,
         *     status: int,
         *     image: string,
         *     notes: string|null,
         *     custom_fields: list<ContactCustomFieldResource>,
         *     created_at: string|null,
         *     active_conversation: int,
         *     favorite: int,
         *     unread_messages: int,
         *     manually_unread: bool,
         *     last_message?: array{
         *         text: string,
         *         file: string,
         *         date: string,
         *         type: string,
         *         own: string,
         *         deleted_at: string,
         *         deleted_scope: string,
         *     },
         *     device_id: int|null,
         *     adviser_id: int|null,
         *     paused_at: string|null,
         *     mac_date: string|null,
         *     ai_assistant_name: string|null,
         *     stage_since?: string,
         *     is_stagnant?: bool,
         *     lead_score?: array{
         *         score: int,
         *         signals_count: int,
         *         category: string,
         *         confidence: string,
         *         disqualified: bool,
         *         disqualified_at: string|null,
         *         updated_at: string|null,
         *     },
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/contacts/{contact}', ['path' => ['contact' => $contact]] + $options);

        return $result;
    }

    /**
     * `PUT /api/v1/contacts/{contact}`.
     *
     * Permission: **Contacts and conversations → Edit** (`conversations:write`).
     *
     * @param int $contact
     * @param UpdateContactRequest $body
     * @param RequestOptions $options
     *
     * @return array<string, mixed>
     */
    public function update(int|string $contact, array $body, array $options = []): mixed
    {
        /** @var array<string, mixed> $result */
        $result = $this->client->request('PUT', '/api/v1/contacts/{contact}', ['path' => ['contact' => $contact], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/contacts/delete`: deletes the contacts of the body (`contactIds`, or a filter).
     *
     * Permission: **Contacts and conversations → Edit** (`conversations:write`).
     *
     * @param array{
     *     all?: bool,
     *     contactIds?: list<int>,
     *     created_from?: string|null,
     *     created_to?: string|null,
     *     tag_mode?: 'all'|'any'|'exclude'|null,
     *     tag_ids?: list<int>|null,
     *     tag_filters?: list<array{
     *         tag_ids: list<int>,
     *         mode: 'all'|'any'|'exclude',
     *     }>|null,
     *     custom_field_mode?: 'all'|'any'|'exclude'|null,
     *     custom_field_ids?: list<int>|null,
     *     custom_field_value?: string|null,
     *     custom_field_date_from?: string|null,
     *     custom_field_date_to?: string|null,
     *     custom_field_numeric_operator?: 'lt'|'eq'|'gt'|null,
     *     custom_field_numeric_value?: float|int|null,
     *     custom_field_filters?: list<array{
     *         custom_field_ids: list<int>,
     *         type: '0'|'1'|'2'|'3'|'4',
     *         mode?: 'all'|'any'|'exclude'|null,
     *         value?: string|null,
     *         date_from?: string|null,
     *         date_to?: string|null,
     *         numeric_operator?: 'lt'|'eq'|'gt'|null,
     *         numeric_value?: float|int|null,
     *         date_operator?: 'lt'|'gt'|'eq'|'period'|null,
     *     }>|null,
     *     country_codes?: list<string>,
     * } $body
     * @param RequestOptions $options
     *
     * @return array<string, mixed>
     */
    public function delete(array $body, array $options = []): mixed
    {
        /** @var array<string, mixed> $result */
        $result = $this->client->request('POST', '/api/v1/contacts/delete', ['body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/contacts/{contact}/tags`, paginated.
     *
     * Permission: **Contacts and conversations → View** (`conversations:read`).
     *
     * @param int $contact
     * @param array{
     *     perPage?: int,
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<ContactTagResource>
     */
    public function listTags(int|string $contact, array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<ContactTagResource> $result */
        $result = $this->client->paginate('/api/v1/contacts/{contact}/tags', ['path' => ['contact' => $contact], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/contacts/{contact}/custom-fields`, paginated.
     *
     * Permission: **Contacts and conversations → View** (`conversations:read`).
     *
     * @param int $contact
     * @param array{
     *     name?: string,
     *     perPage?: int,
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<ContactCustomFieldResource>
     */
    public function listCustomFields(int|string $contact, array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<ContactCustomFieldResource> $result */
        $result = $this->client->paginate('/api/v1/contacts/{contact}/custom-fields', ['path' => ['contact' => $contact], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/contacts/attach-custom-field`: sets a custom field on contacts.
     *
     * Permission: **Contacts and conversations → Edit** (`conversations:write`).
     *
     * @param list<array{
     *     customFieldId: int,
     *     contactId: int,
     *     value: string,
     * }> $body
     * @param RequestOptions $options
     *
     * @return list<array{
     *     message: string,
     *     status: 403,
     * }|array{
     *     message: 'Custom field value attached successfully.',
     *     status: 201,
     * }>
     */
    public function attachCustomField(array $body, array $options = []): mixed
    {
        /**
         * @var list<array{
         *     message: string,
         *     status: 403,
         * }|array{
         *     message: 'Custom field value attached successfully.',
         *     status: 201,
         * }> $result
         */
        $result = $this->client->request('POST', '/api/v1/contacts/attach-custom-field', ['body' => $body] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/contacts/detach-custom-field`.
     *
     * Permission: **Contacts and conversations → Edit** (`conversations:write`).
     *
     * @param list<array{
     *     customFieldId: int,
     *     contactId: int,
     * }> $body
     * @param RequestOptions $options
     *
     * @return array{
     *     messages: list<string>,
     * }
     */
    public function detachCustomField(array $body, array $options = []): mixed
    {
        /**
         * @var array{
         *     messages: list<string>,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/contacts/detach-custom-field', ['body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/contacts/{contact}/meta-window`: whether the 24-hour window with the contact is
     * open on a WhatsApp Cloud number (`is_open`). Outside it, only templates can be sent.
     *
     * Permission: **Contacts and conversations → View** (`conversations:read`).
     *
     * @param int $contact
     * @param array{
     *     device_id: int,
     * } $query
     * @param RequestOptions $options
     *
     * @return array{
     *     is_open: bool,
     *     meta_device_id: null,
     * }|array{
     *     is_open: bool,
     *     meta_device_id: mixed,
     * }
     */
    public function metaWindow(int|string $contact, array $query, array $options = []): mixed
    {
        /**
         * @var array{
         *     is_open: bool,
         *     meta_device_id: null,
         * }|array{
         *     is_open: bool,
         *     meta_device_id: mixed,
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/contacts/{contact}/meta-window', ['path' => ['contact' => $contact], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/devices/{device}/contacts`, paginated: the contacts of one number.
     *
     * Permission: **Contacts and conversations → View** (`conversations:read`).
     *
     * @param int $device
     * @param array{
     *     perPage?: int,
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<array{
     *     id: int,
     *     name: string,
     *     email: string,
     *     phone: string,
     *     whatsapp_id: string,
     *     whatsapp_username: string|null,
     *     has_phone: bool,
     *     country_code: string,
     *     status: int,
     *     image: string,
     *     notes: string|null,
     *     custom_fields?: list<ContactCustomFieldResource>,
     *     created_at: string|null,
     *     active_conversation: int,
     *     favorite: int,
     *     unread_messages: int,
     *     manually_unread?: bool,
     *     last_message?: array{
     *         text: string,
     *         file: string,
     *         date: string,
     *         type: string,
     *         own: string,
     *         deleted_at: string,
     *         deleted_scope: string,
     *     },
     *     device_id: int|null,
     *     adviser_id: int|null,
     *     paused_at: string|null,
     *     mac_date: string|null,
     *     ai_assistant_name: string|null,
     *     stage_since?: string,
     *     is_stagnant?: bool,
     *     lead_score?: array{
     *         score: int,
     *         signals_count: int,
     *         category: string,
     *         confidence: string,
     *         disqualified: bool,
     *         disqualified_at: string|null,
     *         updated_at: string|null,
     *     },
     * }>
     */
    public function listByDevice(int|string $device, array $query = [], array $options = []): Paginator
    {
        /**
         * @var Paginator<array{
         *     id: int,
         *     name: string,
         *     email: string,
         *     phone: string,
         *     whatsapp_id: string,
         *     whatsapp_username: string|null,
         *     has_phone: bool,
         *     country_code: string,
         *     status: int,
         *     image: string,
         *     notes: string|null,
         *     custom_fields?: list<ContactCustomFieldResource>,
         *     created_at: string|null,
         *     active_conversation: int,
         *     favorite: int,
         *     unread_messages: int,
         *     manually_unread?: bool,
         *     last_message?: array{
         *         text: string,
         *         file: string,
         *         date: string,
         *         type: string,
         *         own: string,
         *         deleted_at: string,
         *         deleted_scope: string,
         *     },
         *     device_id: int|null,
         *     adviser_id: int|null,
         *     paused_at: string|null,
         *     mac_date: string|null,
         *     ai_assistant_name: string|null,
         *     stage_since?: string,
         *     is_stagnant?: bool,
         *     lead_score?: array{
         *         score: int,
         *         signals_count: int,
         *         category: string,
         *         confidence: string,
         *         disqualified: bool,
         *         disqualified_at: string|null,
         *         updated_at: string|null,
         *     },
         * }> $result
         */
        $result = $this->client->paginate('/api/v1/devices/{device}/contacts', ['path' => ['device' => $device], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/contacts/{contact}/notes`, paginated.
     *
     * Permission: **Contacts and conversations → View** (`conversations:read`).
     *
     * @param int $contact
     * @param array{
     *     search?: string|null,
     *     perPage?: int,
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<ContactNoteResource>
     */
    public function listNotes(int|string $contact, array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<ContactNoteResource> $result */
        $result = $this->client->paginate('/api/v1/contacts/{contact}/notes', ['path' => ['contact' => $contact], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/contacts/{contact}/notes`.
     *
     * Permission: **Contacts and conversations → Edit** (`conversations:write`).
     *
     * @param int $contact
     * @param StoreContactNoteRequest $body
     * @param RequestOptions $options
     *
     * @return ContactNoteResource
     */
    public function createNote(int|string $contact, array $body, array $options = []): mixed
    {
        /** @var ContactNoteResource $result */
        $result = $this->client->request('POST', '/api/v1/contacts/{contact}/notes', ['path' => ['contact' => $contact], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `DELETE /api/v1/contacts/{contact}/notes/{note}`.
     *
     * Permission: **Contacts and conversations → Edit** (`conversations:write`).
     * The spec documents no body: returns `null`, or the decoded body if the API sends one.
     *
     * @param int $contact
     * @param int $note
     * @param RequestOptions $options
     *
     * @return mixed
     */
    public function deleteNote(int|string $contact, int|string $note, array $options = []): mixed
    {
        return $this->client->request('DELETE', '/api/v1/contacts/{contact}/notes/{note}', ['path' => ['contact' => $contact, 'note' => $note]] + $options);
    }
}
