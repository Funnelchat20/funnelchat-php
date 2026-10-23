<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/**
 * The stable error codes of creating and connecting numbers by API: `devices->create()`,
 * `devices->qr()` and `connectLinks`. When the API answers one of these, nothing was created.
 *
 * Source: the contract `1022-alta-de-numeros` v0.2 (23-10, its "Enmienda v0.1" and "Enmienda v0.2",
 * which describe accounts `feat/alta-de-numeros-api` @ 52d49b319 and @ 8852b3fad), not the code:
 * these codes are not in production yet. The quota codes are the ones the panel already answers.
 */
final class DeviceErrors
{
    /**
     * Error code => the HTTP statuses it comes with (some depend on the route).
     *
     * Quota and plan:
     * - `numbers_not_included` (403): the account's plan includes no numbers.
     * - `number_limit_reached` (403, with `limit`, `used` and `pending_device_ids`): the plan's number quota is full. It is counted by phone number: QR and WhatsApp Cloud of the same phone count once.
     * - `connect_not_allowed` (403): the account cannot connect numbers.
     * - `payment_required` (403, `devices->qr()`): the account has a payment pending.
     * - `number_suspended_by_plan` (422 creating, 409 `devices->qr()`): the number is suspended by the plan.
     * - `trial_number_blocked`, `device_inactive`, `provisioning_quota_exceeded` (409, `devices->qr()`): the number cannot start a session now.
     *
     * The request:
     * - `pending_number_exists` (409, with `device_id`): the account already has a QR number not connected yet. Connect it (`devices->qr()`) or delete it before creating another.
     * - `operation_in_progress` (409): another creation of the account, or the first call with the same `Idempotency-Key`, is still running.
     * - `invalid_request` (422, with `errors`): a field is wrong, also `provision_us_number` without `dedicated` in `allowed_connection_types` or with `device_id`.
     * - `invalid_reference_device` (422): `same_number_as` or `device_id` is not a number of the account that can be used.
     * - `qr_requires_coexistence` (422 creating, 409 `devices->qr()`): QR can only be added to an official number in coexistence.
     * - `method_already_present` (422, with `device_id`): that phone number already has this method.
     * - `connection_type_mismatch` (422): the link's `type` is not the method of the number in `device_id`.
     * - `idempotency_key_mismatch` (422): the same `Idempotency-Key` came with a different body.
     * - `invalid_idempotency_key` (422): the `Idempotency-Key` is not 1 to 255 characters.
     * - `provider_error` (500 creating, 502 `devices->qr()`): WhatsApp's provider failed.
     *
     * Connect links:
     * - `too_many_open_links` (429): the account has 20 open links. Revoke one or let them expire.
     * - `meta_connect_disabled` (503): the official signup by link (`type: 'meta'`) is off. QR links still work.
     * - `link_not_pending` (409): the link to revoke is no longer `pending`.
     * - `credentials_not_allowed` (403): the hosted page (`/connect/...`) was opened with an API key. The page is for your customer's browser, never with a key.
     */
    public const CODES = [
        'numbers_not_included' => [403],
        'number_limit_reached' => [403],
        'connect_not_allowed' => [403],
        'payment_required' => [403],
        'number_suspended_by_plan' => [422, 409],
        'trial_number_blocked' => [409],
        'device_inactive' => [409],
        'provisioning_quota_exceeded' => [409],
        'pending_number_exists' => [409],
        'operation_in_progress' => [409],
        'invalid_request' => [422],
        'invalid_reference_device' => [422],
        'qr_requires_coexistence' => [422, 409],
        'method_already_present' => [422],
        'connection_type_mismatch' => [422],
        'idempotency_key_mismatch' => [422],
        'invalid_idempotency_key' => [422],
        'provider_error' => [500, 502],
        'too_many_open_links' => [429],
        'meta_connect_disabled' => [503],
        'link_not_pending' => [409],
        'credentials_not_allowed' => [403],
    ];

    /**
     * Codes that a retry does not fix soon: the SDK never retries them, although their status (`429`,
     * `503`) is otherwise retried.
     */
    public const NOT_RETRYABLE = ['too_many_open_links', 'meta_connect_disabled'];

    private function __construct() {}

    /**
     * Whether an `ApiException::$errorCode` is one of the refusals of creating or connecting a number.
     *
     * @phpstan-assert-if-true key-of<self::CODES> $code
     */
    public static function isDeviceError(?string $code): bool
    {
        return $code !== null && \array_key_exists($code, self::CODES);
    }
}
