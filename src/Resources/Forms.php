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
 * WhatsApp Forms (conversations area): build them, version and publish them, choose their numbers,
 * and read the answers. To send one to a contact, `messages->sendForm()`.
 *
 * A form has versions: the `{id}` routes take a VERSION id, the `families/{familyId}` routes the
 * form's id (`family_id`). Reading needs **Contacts and conversations → View**, changing
 * **Contacts and conversations → Edit** and sending **Contacts and conversations → Send**. The
 * answers also arrive by `whatsapp_form.completed`.
 *
 * In the developers portal: «Atender una conversación» (Funciones de WhatsApp), «Formularios»
 * (Funciones de WhatsApp).
 *
 * @phpstan-import-type RequestOptions from Funnelchat
 * @phpstan-import-type AddFormNumberRequest from Schemas
 * @phpstan-import-type SaveWhatsappFormRequest from Schemas
 * @phpstan-import-type StoreWhatsappFormRequest from Schemas
 * @phpstan-import-type WhatsappForm from Schemas
 * @phpstan-import-type WhatsappFormResource from Schemas
 * @phpstan-import-type WhatsappFormTemplate from Schemas
 */
final class Forms extends AbstractResource
{
    /**
     * `GET /api/v1/forms`, paginated: one row per form, with its current version.
     *
     * Permission: **Contacts and conversations → View**.
     *
     * @param array{
     *     q?: string|null,
     *     status?: 'draft'|'published'|'deprecated'|null,
     *     device_id?: int|null,
     *     page?: int|null,
     *     perPage?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<WhatsappFormResource>
     */
    public function list(array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<WhatsappFormResource> $result */
        $result = $this->client->paginate('/api/v1/forms', ['query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/forms/templates`: the templates to start a form from (`blank` is an empty one).
     *
     * Permission: **Contacts and conversations → View**.
     *
     * @param RequestOptions $options
     *
     * @return array{
     *     data: list<WhatsappFormTemplate>,
     * }
     */
    public function templates(array $options = []): mixed
    {
        /**
         * @var array{
         *     data: list<WhatsappFormTemplate>,
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/forms/templates', $options);

        return $result;
    }

    /**
     * `POST /api/v1/forms`: creates a draft from a template (`template_key`) for a number.
     *
     * Permission: **Contacts and conversations → Edit**.
     *
     * @param StoreWhatsappFormRequest $body
     * @param RequestOptions $options
     *
     * @return array{
     *     data: WhatsappFormResource,
     *     lint: list<mixed>,
     *     numbers: list<mixed>,
     * }
     */
    public function create(array $body, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: WhatsappFormResource,
         *     lint: list<mixed>,
         *     numbers: list<mixed>,
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
     *
     * @param int $id
     * @param RequestOptions $options
     *
     * @return array{
     *     data: WhatsappFormResource,
     *     lint: list<mixed>,
     *     versions: list<WhatsappForm>,
     *     numbers: list<mixed>,
     * }
     */
    public function retrieve(int|string $id, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: WhatsappFormResource,
         *     lint: list<mixed>,
         *     versions: list<WhatsappForm>,
         *     numbers: list<mixed>,
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
     *
     * @param int $id
     * @param SaveWhatsappFormRequest $body
     * @param RequestOptions $options
     *
     * @return array{
     *     data: WhatsappFormResource,
     *     lint: list<mixed>,
     * }
     */
    public function update(int|string $id, array $body, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: WhatsappFormResource,
         *     lint: list<mixed>,
         * } $result
         */
        $result = $this->client->request('PUT', '/api/v1/forms/{id}', ['path' => ['id' => $id], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `DELETE /api/v1/forms/{id}`: deletes a draft (a published form is deprecated, not deleted).
     *
     * Permission: **Contacts and conversations → Edit**.
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
     *
     * @param int $id
     * @param RequestOptions $options
     *
     * @return array{
     *     data: WhatsappFormResource,
     *     created: bool,
     *     lint: list<mixed>,
     *     numbers: list<mixed>,
     * }
     */
    public function createVersion(int|string $id, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: WhatsappFormResource,
         *     created: bool,
         *     lint: list<mixed>,
         *     numbers: list<mixed>,
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
     *
     * @param int $id
     * @param RequestOptions $options
     *
     * @return array{
     *     data: WhatsappFormResource,
     *     lint: list<mixed>,
     *     numbers: list<mixed>,
     * }
     */
    public function sync(int|string $id, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: WhatsappFormResource,
         *     lint: list<mixed>,
         *     numbers: list<mixed>,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/forms/{id}/sync', ['path' => ['id' => $id]] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/forms/{id}/publish`: publishes the version (`422 LINT` with errors).
     *
     * Permission: **Contacts and conversations → Edit**.
     *
     * @param int $id
     * @param RequestOptions $options
     *
     * @return array{
     *     data: WhatsappFormResource,
     *     lint: list<mixed>,
     *     numbers: list<mixed>,
     * }
     */
    public function publish(int|string $id, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: WhatsappFormResource,
         *     lint: list<mixed>,
         *     numbers: list<mixed>,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/forms/{id}/publish', ['path' => ['id' => $id]] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/forms/{id}/numbers`: the numbers of the form and their state.
     *
     * Permission: **Contacts and conversations → View**.
     *
     * @param int $id
     * @param RequestOptions $options
     *
     * @return array{
     *     numbers: list<mixed>,
     * }
     */
    public function numbers(int|string $id, array $options = []): mixed
    {
        /**
         * @var array{
         *     numbers: list<mixed>,
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/forms/{id}/numbers', ['path' => ['id' => $id]] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/forms/{id}/numbers`: adds a number.
     *
     * Permission: **Contacts and conversations → Edit**.
     *
     * @param int $id
     * @param AddFormNumberRequest $body
     * @param RequestOptions $options
     *
     * @return array{
     *     numbers: list<mixed>,
     * }
     */
    public function addNumber(int|string $id, array $body, array $options = []): mixed
    {
        /**
         * @var array{
         *     numbers: list<mixed>,
         * } $result
         */
        $result = $this->client->request('POST', '/api/v1/forms/{id}/numbers', ['path' => ['id' => $id], 'body' => $body] + $options);

        return $result;
    }

    /**
     * `DELETE /api/v1/forms/{id}/numbers/{deviceId}`: removes a number (not the primary one).
     *
     * Permission: **Contacts and conversations → Edit**.
     *
     * @param int $id
     * @param int $deviceId
     * @param RequestOptions $options
     *
     * @return array{
     *     numbers: list<mixed>,
     * }
     */
    public function removeNumber(int|string $id, int|string $deviceId, array $options = []): mixed
    {
        /**
         * @var array{
         *     numbers: list<mixed>,
         * } $result
         */
        $result = $this->client->request('DELETE', '/api/v1/forms/{id}/numbers/{deviceId}', ['path' => ['id' => $id, 'deviceId' => $deviceId]] + $options);

        return $result;
    }

    /**
     * `POST /api/v1/forms/{id}/numbers/retry`: retries the numbers that failed.
     *
     * Permission: **Contacts and conversations → Edit**.
     *
     * @param int $id
     * @param RequestOptions $options
     *
     * @return array{
     *     numbers: list<mixed>,
     * }
     */
    public function retryNumbers(int|string $id, array $options = []): mixed
    {
        /**
         * @var array{
         *     numbers: list<mixed>,
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
     *         definition: list<mixed>|array{
     *             screens: list<string>,
     *         },
     *     },
     * }|array{
     *     data: null,
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
         *         definition: list<mixed>|array{
         *             screens: list<string>,
         *         },
         *     },
         * }|array{
         *     data: null,
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/forms/families/{familyId}/definition', ['path' => ['familyId' => $familyId]] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/forms/families/{familyId}/versions`, paginated: the versions of a form.
     *
     * Permission: **Contacts and conversations → View**.
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
     *
     * @param int $familyId
     * @param array{
     *     from?: string|null,
     *     to?: string|null,
     *     version_id?: int|null,
     *     origin?: string|null,
     *     status?: string|null,
     *     kind?: string|null,
     *     effect_status?: string|null,
     *     search?: string|null,
     *     page?: int|null,
     *     perPage?: int,
     *     cursor?: string,
     * } $query
     * @param RequestOptions $options
     *
     * @return Paginator<array<string, mixed>>
     */
    public function listResponses(int|string $familyId, array $query = [], array $options = []): Paginator
    {
        /** @var Paginator<array<string, mixed>> $result */
        $result = $this->client->paginate('/api/v1/forms/families/{familyId}/responses', ['path' => ['familyId' => $familyId], 'query' => $query] + $options);

        return $result;
    }

    /**
     * `GET /api/v1/forms/families/{familyId}/responses/{runId}`: one answer in detail.
     *
     * Permission: **Contacts and conversations → View**.
     *
     * @param int $familyId
     * @param int $runId
     * @param RequestOptions $options
     *
     * @return array{
     *     data: array{
     *         id: int,
     *         flow_token: string,
     *         contact: array{
     *             id: int,
     *             name: string|null,
     *             phone: string|null,
     *         },
     *         status: array{
     *             key: 'completed',
     *             label: 'Completado',
     *         }|array{
     *             key: 'pending',
     *             label: 'Esperando respuesta',
     *         }|array{
     *             key: 'unfinished',
     *             label: 'Sin completar',
     *         }|array{
     *             key: 'not_sent',
     *             label: 'No enviado',
     *         },
     *         origin: string,
     *         node_id: string|null,
     *         automation_run_id: int|null,
     *         conversation_id: int|null,
     *         version: int,
     *         version_id: int,
     *         output_id: string|null,
     *         output_label: string|null,
     *         reason: string|null,
     *         reason_label: string|null,
     *         completed_after_expiry: bool,
     *         partial: string,
     *         last_screen_id: string|null,
     *         sent_at: string|null,
     *         delivered_at: string|null,
     *         read_at: string|null,
     *         responded_at: string|null,
     *         expires_at: string|null,
     *         answers: list<array{
     *             name: string,
     *             label: string,
     *             value: string,
     *             unknown: bool,
     *         }>,
     *         effects: list<list<mixed>|array{
     *             id: int,
     *             run_id: int,
     *             kind: string,
     *             kind_label: string,
     *             status: string,
     *             attempts: int,
     *             max_attempts: int,
     *             next_attempt_at: string|null,
     *             payload: list<mixed>,
     *             request_excerpt: string|null,
     *             response_excerpt: string|null,
     *             created_at: string|null,
     *         }>,
     *         response_json: list<mixed>|null,
     *     },
     * }
     */
    public function retrieveResponse(int|string $familyId, int|string $runId, array $options = []): mixed
    {
        /**
         * @var array{
         *     data: array{
         *         id: int,
         *         flow_token: string,
         *         contact: array{
         *             id: int,
         *             name: string|null,
         *             phone: string|null,
         *         },
         *         status: array{
         *             key: 'completed',
         *             label: 'Completado',
         *         }|array{
         *             key: 'pending',
         *             label: 'Esperando respuesta',
         *         }|array{
         *             key: 'unfinished',
         *             label: 'Sin completar',
         *         }|array{
         *             key: 'not_sent',
         *             label: 'No enviado',
         *         },
         *         origin: string,
         *         node_id: string|null,
         *         automation_run_id: int|null,
         *         conversation_id: int|null,
         *         version: int,
         *         version_id: int,
         *         output_id: string|null,
         *         output_label: string|null,
         *         reason: string|null,
         *         reason_label: string|null,
         *         completed_after_expiry: bool,
         *         partial: string,
         *         last_screen_id: string|null,
         *         sent_at: string|null,
         *         delivered_at: string|null,
         *         read_at: string|null,
         *         responded_at: string|null,
         *         expires_at: string|null,
         *         answers: list<array{
         *             name: string,
         *             label: string,
         *             value: string,
         *             unknown: bool,
         *         }>,
         *         effects: list<list<mixed>|array{
         *             id: int,
         *             run_id: int,
         *             kind: string,
         *             kind_label: string,
         *             status: string,
         *             attempts: int,
         *             max_attempts: int,
         *             next_attempt_at: string|null,
         *             payload: list<mixed>,
         *             request_excerpt: string|null,
         *             response_excerpt: string|null,
         *             created_at: string|null,
         *         }>,
         *         response_json: list<mixed>|null,
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
     *
     * @param int $familyId
     * @param array{
     *     from?: string|null,
     *     to?: string|null,
     *     version_id?: int|null,
     *     origin?: string|null,
     *     status?: string|null,
     *     kind?: string|null,
     *     effect_status?: string|null,
     *     search?: string|null,
     *     per_page?: int|null,
     *     page?: int|null,
     * } $query
     * @param RequestOptions $options
     *
     * @return array{
     *     data: array{
     *         not_sent: int,
     *         not_sent_reasons: list<array{
     *             reason: string,
     *             label: string,
     *             count: int,
     *         }|array{
     *             reason: 'desconocido',
     *             label: 'sin motivo registrado',
     *             count: int,
     *         }>,
     *         sent: int,
     *         delivered: int,
     *         read: int,
     *         completed: int,
     *         pending: int,
     *         unfinished: int,
     *         partial: int,
     *         completion_rate: float|int|null,
     *         delivery_rate: float|int|null,
     *         read_rate: float|int|null,
     *         median_seconds_to_complete: int|null,
     *     },
     * }
     */
    public function metrics(int|string $familyId, array $query = [], array $options = []): mixed
    {
        /**
         * @var array{
         *     data: array{
         *         not_sent: int,
         *         not_sent_reasons: list<array{
         *             reason: string,
         *             label: string,
         *             count: int,
         *         }|array{
         *             reason: 'desconocido',
         *             label: 'sin motivo registrado',
         *             count: int,
         *         }>,
         *         sent: int,
         *         delivered: int,
         *         read: int,
         *         completed: int,
         *         pending: int,
         *         unfinished: int,
         *         partial: int,
         *         completion_rate: float|int|null,
         *         delivery_rate: float|int|null,
         *         read_rate: float|int|null,
         *         median_seconds_to_complete: int|null,
         *     },
         * } $result
         */
        $result = $this->client->request('GET', '/api/v1/forms/families/{familyId}/metrics', ['path' => ['familyId' => $familyId], 'query' => $query] + $options);

        return $result;
    }
}
