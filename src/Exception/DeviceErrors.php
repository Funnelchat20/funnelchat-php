<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/**
 * The stable error codes of creating and connecting numbers by API: `devices->create()` and
 * `connectLinks->create()`. When the API answers one of these, nothing was created.
 *
 * Source: the contract `1022-alta-de-numeros` v0 (22-10, section A1), not the code: accounts is
 * building it, so these codes are not in production yet. The quota codes are the ones the panel
 * already answers.
 */
final class DeviceErrors
{
    /**
     * Error code => HTTP status.
     *
     * - `numbers_not_included` (403): the account's plan includes no numbers.
     * - `number_limit_reached` (403): the plan's number quota is full. It is counted by phone number: QR and WhatsApp Cloud of the same phone count once.
     * - `qr_requires_coexistence` (422): QR can only be added (`same_number_as`) to an official number in coexistence.
     * - `method_already_present` (422, with `device_id`): that phone number already has this method.
     * - `operation_in_progress` (409): another creation of the account, or the first call with the same `Idempotency-Key`, is still running.
     * - `idempotency_key_mismatch` (422): the same `Idempotency-Key` came with a different body.
     * - `too_many_open_links` (429): the account has 20 open connect links. Revoke one or let them expire.
     * - `meta_connect_disabled` (503): the official signup by link (`type: 'meta'`) is off. QR links still work.
     */
    public const CODES = [
        'numbers_not_included' => 403,
        'number_limit_reached' => 403,
        'qr_requires_coexistence' => 422,
        'method_already_present' => 422,
        'operation_in_progress' => 409,
        'idempotency_key_mismatch' => 422,
        'too_many_open_links' => 429,
        'meta_connect_disabled' => 503,
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
