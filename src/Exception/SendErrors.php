<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/**
 * The stable error codes of sending with an API key: the WhatsApp rules and the other reasons a
 * send is refused. When the API answers one of these, nothing went out.
 *
 * Source: conversations v1.18.10, in production since 21-10 and the same in v1.19.7 (04-11).
 * Sending to a group (`groups->sendMessage()`): communities v2.35.5 and, for following a group,
 * v2.35.7.
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
     * - `provider_unsupported` (422): the number's provider cannot do it (reactions).
     * - `template_not_found` (404), `template_unresolved_placeholder` (422, with `placeholders` and `count`).
     * - `form_not_found` (404), `form_not_published`, `form_not_on_number`, `number_not_eligible` (422).
     * - `not_forwardable` (422, with `reason`): the message cannot be forwarded.
     *
     * Sending to a group (`groups->sendMessage()`) also has `window_closed`, `provider_unsupported` (with `requires`) and:
     *
     * - `group_not_found`, `device_not_found` (404): the group or the number is not of your key's workspace.
     * - `device_not_connected` (422): the number is not connected.
     * - `device_not_group_admin` (422): a QR number that is not an admin of the group.
     * - `device_not_group_number` (422): an official number that is not the group's own number.
     * - `unsupported_group_type` (422): a community or a channel, not a group.
     * - `unsupported_type` (422): an official number cannot send that type of message.
     * - `number_restricted` (422): the quality of the official number does not let it send.
     * - `too_many_pending_actions` (429): the number already has 50 group actions open. Never retried.
     *
     * A group of the number that is not in the account is followed first (communities v2.35.7), so
     * `groups->sendMessage()` and `groups->follow()` also have:
     *
     * - `import_permission_required` (403): the key lacks **Groups and campaigns → Manage groups**.
     * - `import_rate_limited` (429, with `Retry-After`): the number followed 5 groups in the last minute.
     * - `import_in_progress` (409): the same group is being followed by another call.
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
        'group_not_found' => 404,
        'device_not_found' => 404,
        'device_not_connected' => 422,
        'device_not_group_admin' => 422,
        'device_not_group_number' => 422,
        'unsupported_group_type' => 422,
        'unsupported_type' => 422,
        'number_restricted' => 422,
        'too_many_pending_actions' => 429,
        'import_permission_required' => 403,
        'import_rate_limited' => 429,
        'import_in_progress' => 409,
    ];

    /**
     * Codes that a retry does not fix soon: the SDK never retries them, not even with `retrySend`.
     */
    public const NOT_RETRYABLE = ['mac_lockout_24h', 'mac_limit_reached', 'too_many_pending_actions'];

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
