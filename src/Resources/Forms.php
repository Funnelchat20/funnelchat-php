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
 * WhatsApp Forms (conversations area): build them, version and publish them, choose their numbers,
 * and read the answers. To send one to a contact, `messages->sendForm()`.
 *
 * A form has versions: the `{id}` routes take a VERSION id, the `families/{familyId}` routes the
 * form's id (`family_id`). Reading needs **Contacts and conversations → View**, changing
 * **Contacts and conversations → Edit** and sending **Contacts and conversations → Send**. The
 * answers also arrive by `whatsapp_form.completed`.
 *
 * @phpstan-import-type RequestOptions from Funnelchat
 * @phpstan-import-type PendingForm from Schemas
 * @phpstan-import-type PendingFormLintIssue from Schemas
 * @phpstan-import-type PendingFormNumber from Schemas
 */
final class Forms extends AbstractResource
{
    /**
     * `GET /api/v1/forms`, paginated: one row per form, with its current version.
     *
     * Permission: **Contacts and conversations → View**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     *
     * @param array{
     *     q?: string,
     *     status?: 'draft'|'published'|'deprecated',
     *     device_id?: int,
     *     perPage?: int,
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<PendingForm>
     */
    public function list(array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<PendingForm> $result */
        $result = $this->client->paginate('/api/v1/forms', ['query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/forms/templates`: the templates to start a form from (`blank` is an empty one).
     *
     * Permission: **Contacts and conversations → View**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     *
     * @param RequestOptions $options
     *
     * @return array{
     *     data: list<array{
     *         key: string,
     *         title: string,
     *         description: string|null,
     *         category: string|null,
     *     }>,
     * }
     */
    public function templates(array $options = []): mixed
    {
        /**
         * @var array{
         *     data: list<array{
         *         key: string,
         *         title: string,
         *         description: string|null,
         *         category: string|null,
         *     }>,
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/forms/templates', $options);

        return $result;
    }

    /**
     * `POST /api/v1/forms`: creates a draft from a template (`template_key`) for a number.
     *
     * Permission: **Contacts and conversations → Edit**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     *
     * @param array{
     *     device_id: int,
     *     device_ids?: list<int>,
     *     template_key: string,
     * } $body
     * @param RequestOptions $options
     *
     * @return array{
     *     data: PendingForm,
     *     lint: list<PendingFormLintIssue>,
     *     numbers: list<PendingFormNumber>,
     * }
     */
    public function create(array $body, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: PendingForm,
         *     lint: list<PendingFormLintIssue>,
         *     numbers: list<PendingFormNumber>,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/forms', ['body' => $body] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/forms/{id}`: one version (`id` is the version id), with its lint issues,
     * versions and numbers.
     *
     * Permission: **Contacts and conversations → View**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     *
     * @param int $id
     * @param RequestOptions $options
     *
     * @return array{
     *     data: PendingForm,
     *     lint: list<PendingFormLintIssue>,
     *     versions: list<array{
     *         id: int,
     *         version: int,
     *         status: string,
     *         published_at: string|null,
     *     }>,
     *     numbers: list<PendingFormNumber>,
     * }
     */
    public function retrieve(int|string $id, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: PendingForm,
         *     lint: list<PendingFormLintIssue>,
         *     versions: list<array{
         *         id: int,
         *         version: int,
         *         status: string,
         *         published_at: string|null,
         *     }>,
         *     numbers: list<PendingFormNumber>,
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/forms/{id}', ['path' => ['id' => $id]] + $options);

        return $result;
    }

    /**
     * `PUT /api/v1/forms/{id}`: saves a draft. A published version answers `409 PUBLISHED`; with
     * `expected_updated_at`, someone else's change answers `409 STALE`.
     *
     * Permission: **Contacts and conversations → Edit**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     *
     * @param int $id
     * @param array{
     *     name?: string,
     *     definition: array<string, mixed>,
     *     expected_updated_at?: string|null,
     * } $body
     * @param RequestOptions $options
     *
     * @return array{
     *     data: PendingForm,
     *     lint: list<PendingFormLintIssue>,
     * }
     */
    public function update(int|string $id, array $body, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: PendingForm,
         *     lint: list<PendingFormLintIssue>,
         * } $result
         */
        $result = $this->client->request('PUT', '/api/v1/forms/{id}', ['path' => ['id' => $id], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `DELETE /api/v1/forms/{id}`: deletes a draft (a published form is deprecated, not deleted).
     *
     * Permission: **Contacts and conversations → Edit**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     * The spec documents no body: returns `null`, or the decoded body if the API sends one.
     *
     * @param int $id
     * @param RequestOptions $options
     *
     * @return mixed
     */
    public function delete(int|string $id, array $options = []): mixed
    {
        return $this->client->request('DELETE', '/api/v1/forms/{id}', ['path' => ['id' => $id]] + $options);
    }

    /**
     * `POST /api/v1/forms/{id}/versions`: a new draft from this version, or the draft that already
     * existed (`created: false`).
     *
     * Permission: **Contacts and conversations → Edit**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     *
     * @param int $id
     * @param RequestOptions $options
     *
     * @return array{
     *     data: PendingForm,
     *     created: bool,
     *     lint: list<PendingFormLintIssue>,
     *     numbers: list<PendingFormNumber>,
     * }
     */
    public function createVersion(int|string $id, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: PendingForm,
         *     created: bool,
         *     lint: list<PendingFormLintIssue>,
         *     numbers: list<PendingFormNumber>,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/forms/{id}/versions', ['path' => ['id' => $id]] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/forms/{id}/sync`: validates the version in Meta (`502 META` when Meta rejects
     * it).
     *
     * Permission: **Contacts and conversations → Edit**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     *
     * @param int $id
     * @param RequestOptions $options
     *
     * @return array{
     *     data: PendingForm,
     *     lint: list<PendingFormLintIssue>,
     *     numbers: list<PendingFormNumber>,
     * }
     */
    public function sync(int|string $id, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: PendingForm,
         *     lint: list<PendingFormLintIssue>,
         *     numbers: list<PendingFormNumber>,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/forms/{id}/sync', ['path' => ['id' => $id]] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/forms/{id}/publish`: publishes the version (`422 LINT` with errors).
     *
     * Permission: **Contacts and conversations → Edit**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     *
     * @param int $id
     * @param RequestOptions $options
     *
     * @return array{
     *     data: PendingForm,
     *     lint: list<PendingFormLintIssue>,
     *     numbers: list<PendingFormNumber>,
     * }
     */
    public function publish(int|string $id, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: PendingForm,
         *     lint: list<PendingFormLintIssue>,
         *     numbers: list<PendingFormNumber>,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/forms/{id}/publish', ['path' => ['id' => $id]] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/forms/{id}/numbers`: the numbers of the form and their state.
     *
     * Permission: **Contacts and conversations → View**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     *
     * @param int $id
     * @param RequestOptions $options
     *
     * @return array{
     *     numbers: list<PendingFormNumber>,
     * }
     */
    public function numbers(int|string $id, array $options = []): mixed
    {
        /**
         * @var array{
         *     numbers: list<PendingFormNumber>,
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/forms/{id}/numbers', ['path' => ['id' => $id]] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/forms/{id}/numbers`: adds a number.
     *
     * Permission: **Contacts and conversations → Edit**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     *
     * @param int $id
     * @param array{
     *     device_id: int,
     * } $body
     * @param RequestOptions $options
     *
     * @return array{
     *     numbers: list<PendingFormNumber>,
     * }
     */
    public function addNumber(int|string $id, array $body, array $options = []): mixed
    {
        /**
         * @var array{
         *     numbers: list<PendingFormNumber>,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/forms/{id}/numbers', ['path' => ['id' => $id], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `DELETE /api/v1/forms/{id}/numbers/{deviceId}`: removes a number (not the primary one).
     *
     * Permission: **Contacts and conversations → Edit**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     *
     * @param int $id
     * @param int $deviceId
     * @param RequestOptions $options
     *
     * @return array{
     *     numbers: list<PendingFormNumber>,
     * }
     */
    public function removeNumber(int|string $id, int|string $deviceId, array $options = []): mixed
    {
        /**
         * @var array{
         *     numbers: list<PendingFormNumber>,
         * } $result
         */
        $result = $this->client->request('DELETE', '/api/v1/forms/{id}/numbers/{deviceId}', ['path' => ['id' => $id, 'deviceId' => $deviceId]] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/forms/{id}/numbers/retry`: retries the numbers that failed.
     *
     * Permission: **Contacts and conversations → Edit**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     *
     * @param int $id
     * @param RequestOptions $options
     *
     * @return array{
     *     numbers: list<PendingFormNumber>,
     * }
     */
    public function retryNumbers(int|string $id, array $options = []): mixed
    {
        /**
         * @var array{
         *     numbers: list<PendingFormNumber>,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/forms/{id}/numbers/retry', ['path' => ['id' => $id]] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/forms/families/{familyId}/definition`: the published definition of a form
     * (`familyId`), or `data: null`.
     *
     * Permission: **Contacts and conversations → View**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     *
     * @param int $familyId
     * @param RequestOptions $options
     *
     * @return array{
     *     data: array{
     *         family_id: int,
     *         form_id: int,
     *         name: string,
     *         status: string,
     *         version: int,
     *         definition: array<string, mixed>,
     *     }|null,
     * }
     */
    public function definition(int|string $familyId, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: array{
         *         family_id: int,
         *         form_id: int,
         *         name: string,
         *         status: string,
         *         version: int,
         *         definition: array<string, mixed>,
         *     }|null,
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/forms/families/{familyId}/definition', ['path' => ['familyId' => $familyId]] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/forms/families/{familyId}/versions`, paginated: the versions of a form.
     *
     * Permission: **Contacts and conversations → View**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     *
     * @param int $familyId
     * @param array{
     *     perPage?: int,
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<array{
     *     id: int,
     *     version: int,
     *     name: string,
     *     status: string,
     *     is_current: bool,
     *     published_at: string|null,
     *     deprecated_at: string|null,
     *     meta_flow_id: string|null,
     *     retained_reason: string|null,
     *     responses: int,
     *     templates: int,
     * }>
     */
    public function versions(int|string $familyId, array $query = [], array $options = []): Paginator
    {
        /**
         * @var Paginator<array{
         *     id: int,
         *     version: int,
         *     name: string,
         *     status: string,
         *     is_current: bool,
         *     published_at: string|null,
         *     deprecated_at: string|null,
         *     meta_flow_id: string|null,
         *     retained_reason: string|null,
         *     responses: int,
         *     templates: int,
         * }> $result
         */
        $result = $this->client->paginate('/api/v1/forms/families/{familyId}/versions', ['path' => ['familyId' => $familyId], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/forms/families/{familyId}/responses`, paginated: the answers to a form, with
     * `columns`.
     *
     * Permission: **Contacts and conversations → View**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     *
     * @param int $familyId
     * @param array{
     *     from?: string,
     *     to?: string,
     *     version_id?: int,
     *     origin?: string,
     *     status?: 'completed'|'pending'|'not_sent'|'unfinished',
     *     search?: string,
     *     per_page?: int,
     *     page?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<array{
     *     id: int,
     *     contact: array{
     *         id: int|null,
     *         name: string|null,
     *         phone: string|null,
     *     },
     *     conversation_id: int|null,
     *     status: array{
     *         key: string,
     *         label: string,
     *     },
     *     origin: string|null,
     *     version: int|null,
     *     partial: bool,
     *     at: string|null,
     *     values: array<string, string>,
     * }>
     */
    public function listResponses(int|string $familyId, array $query = [], array $options = []): Paginator
    {
        /**
         * @var Paginator<array{
         *     id: int,
         *     contact: array{
         *         id: int|null,
         *         name: string|null,
         *         phone: string|null,
         *     },
         *     conversation_id: int|null,
         *     status: array{
         *         key: string,
         *         label: string,
         *     },
         *     origin: string|null,
         *     version: int|null,
         *     partial: bool,
         *     at: string|null,
         *     values: array<string, string>,
         * }> $result
         */
        $result = $this->client->paginate('/api/v1/forms/families/{familyId}/responses', ['path' => ['familyId' => $familyId], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/forms/families/{familyId}/responses/{runId}`: one answer in detail.
     *
     * Permission: **Contacts and conversations → View**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     *
     * @param int $familyId
     * @param int $runId
     * @param RequestOptions $options
     *
     * @return array{
     *     data: array{
     *         id?: int,
     *         status?: array{
     *             key: string,
     *             label: string,
     *         },
     *         answers?: list<array{
     *             name: string,
     *             label: string|null,
     *             value: mixed,
     *             unknown: bool,
     *         }>,
     *         sent_at?: string|null,
     *         responded_at?: string|null,
     *     },
     * }
     */
    public function retrieveResponse(int|string $familyId, int|string $runId, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: array{
         *         id?: int,
         *         status?: array{
         *             key: string,
         *             label: string,
         *         },
         *         answers?: list<array{
         *             name: string,
         *             label: string|null,
         *             value: mixed,
         *             unknown: bool,
         *         }>,
         *         sent_at?: string|null,
         *         responded_at?: string|null,
         *     },
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/forms/families/{familyId}/responses/{runId}', ['path' => ['familyId' => $familyId, 'runId' => $runId]] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/forms/families/{familyId}/metrics`: the funnel: sent, delivered, read, completed
     * and the rates.
     *
     * Permission: **Contacts and conversations → View**.
     * Pending of the spec (`conversations-forms` in spec/pending.json).
     *
     * @param int $familyId
     * @param array{
     *     from?: string,
     *     to?: string,
     *     version_id?: int,
     *     origin?: string,
     *     status?: 'completed'|'pending'|'not_sent'|'unfinished',
     *     search?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return array{
     *     data: array{
     *         not_sent: int,
     *         sent: int,
     *         delivered: int,
     *         read: int,
     *         completed: int,
     *         pending: int,
     *         unfinished: int,
     *         partial: int,
     *         not_sent_reasons: list<array{
     *             reason: string,
     *             label: string,
     *             count: int,
     *         }>,
     *         completion_rate: float|int|null,
     *         delivery_rate: float|int|null,
     *         read_rate: float|int|null,
     *         median_seconds_to_complete: float|int|null,
     *     },
     * }
     */
    public function metrics(int|string $familyId, array $query = [], array $options = []): mixed
    {
        /**
         * @var array{
         *     data: array{
         *         not_sent: int,
         *         sent: int,
         *         delivered: int,
         *         read: int,
         *         completed: int,
         *         pending: int,
         *         unfinished: int,
         *         partial: int,
         *         not_sent_reasons: list<array{
         *             reason: string,
         *             label: string,
         *             count: int,
         *         }>,
         *         completion_rate: float|int|null,
         *         delivery_rate: float|int|null,
         *         read_rate: float|int|null,
         *         median_seconds_to_complete: float|int|null,
         *     },
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/forms/families/{familyId}/metrics', ['path' => ['familyId' => $familyId], 'query' => $query] + $options);

        return $result;
    }
}
