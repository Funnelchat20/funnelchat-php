<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/**
 * The stable error codes of sending with an API key: the WhatsApp rules and the other reasons a
 * send is refused. When the API answers one of these, nothing went out.
 *
 * Source: conversations v1.18.10 (`ApiSendRejection`, `ApiSendGuard`, `ApiSendRateLimiter`,
 * `ApiKeySendResponse`, `FormSendPreflight` and `lang/en/public_api.php`) and communities v2.34.52
 * (`ApiGroupActionRejected`), in production since 21-10.
 */
final class SendErrors
{
    /**
     * Error code => HTTP status.
     *
     * - `rate_limited` (429, always with `Retry-After`): the number's pace (per second, or per minute to the same contact).
     * - `mac_lockout_24h` (429, no `Retry-After`): the account is in its 24-hour lockout for running out of MACs, also for a contact inactive by MAC.
     * - `mac_limit_reached` (429): the account ran out of MACs.
     * - `window_closed` (422): free text outside the 24-hour window on a WhatsApp Cloud number. Send a template.
     * - `antiblock_duplicate_content` (422): the same content sent too many times to a contact that did not answer.
     * - `antiblock_unanswered_limit` (422): too many messages in a row to a contact that did not answer.
     * - `recipient_mismatch` (422): the `phone` / `whatsapp_id` of the body is not the contact of the route.
     * - `recipient_unsupported` (422): the contact is a group, a broadcast list or a channel.
     * - `recipient_unreachable` (422): the contact cannot receive a form.
     * - `device_unavailable` (422): the number is not connected to WhatsApp.
     * - `not_sent` (422): the message could not be accepted.
     * - `provider_unsupported` (422): the number's provider cannot do it (group actions, reactions).
     * - `template_not_found` (404), `template_unresolved_placeholder` (422, with `placeholders` and `count`).
     * - `form_not_found` (404), `form_not_published`, `form_not_on_number`, `number_not_eligible` (422).
     * - `not_forwardable` (422, with `reason`): the message cannot be forwarded.
     */
    public const CODES = [
        'rate_limited' => 429,
        'mac_lockout_24h' => 429,
        'mac_limit_reached' => 429,
        'window_closed' => 422,
        'antiblock_duplicate_content' => 422,
        'antiblock_unanswered_limit' => 422,
        'recipient_mismatch' => 422,
        'recipient_unsupported' => 422,
        'recipient_unreachable' => 422,
        'device_unavailable' => 422,
        'not_sent' => 422,
        'provider_unsupported' => 422,
        'template_not_found' => 404,
        'template_unresolved_placeholder' => 422,
        'form_not_found' => 404,
        'form_not_published' => 422,
        'form_not_on_number' => 422,
        'number_not_eligible' => 422,
        'not_forwardable' => 422,
    ];

    /**
     * Codes that a retry does not fix soon: the SDK never retries them, not even with `retrySend`.
     */
    public const NOT_RETRYABLE = ['mac_lockout_24h', 'mac_limit_reached'];

    private function __construct() {}

    /**
     * Whether an `ApiException::$errorCode` is one of the send rules or refusals: nothing went out.
     *
     * @phpstan-assert-if-true key-of<self::CODES> $code
     */
    public static function isSendRuleError(?string $code): bool
    {
        return $code !== null && \array_key_exists($code, self::CODES);
    }
}
