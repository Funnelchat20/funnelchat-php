<?php

declare(strict_types=1);

namespace Funnelchat\Pending;

/**
 * PENDING OF THE SPEC — the error codes of sending with an API key (contract 1014, C2).
 *
 * Source: conversations `feat/armar-funnelchat-f1` @ eadef46c (PR #2284, NOT merged):
 * `ApiSendRejection`, `ApiSendGuard`, `ApiSendRateLimiter` and `lang/es/public_api.php`. Until that
 * change is deployed, every send route answers `403 route_not_available` to any key. When a
 * snapshot documents these codes, this list can be generated instead.
 */
final class SendErrors
{
    /**
     * Error code => HTTP status.
     *
     * - `rate_limited` (429, with `Retry-After`): the number's pace (per second, or per minute to the same contact).
     * - `window_closed` (422): free text outside the 24-hour window on a WhatsApp Cloud number. Send a template.
     * - `antiblock_duplicate_content` (422): the same content sent too many times to a contact that did not answer.
     * - `antiblock_unanswered_limit` (422): too many messages in a row to a contact that did not answer.
     * - `recipient_mismatch` (422): the `phone` / `whatsapp_id` of the body is not the contact of the route.
     * - `recipient_unsupported` (422): the contact is a group, a broadcast list or a channel.
     */
    public const CODES = [
        'rate_limited' => 429,
        'window_closed' => 422,
        'antiblock_duplicate_content' => 422,
        'antiblock_unanswered_limit' => 422,
        'recipient_mismatch' => 422,
        'recipient_unsupported' => 422,
    ];

    private function __construct() {}

    /**
     * Whether an `ApiException::$errorCode` is one of the send rules: nothing was sent and nothing
     * was stored.
     *
     * @phpstan-assert-if-true key-of<self::CODES> $code
     */
    public static function isSendRuleError(?string $code): bool
    {
        return $code !== null && \array_key_exists($code, self::CODES);
    }
}
