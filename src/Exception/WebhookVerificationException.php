<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/**
 * A webhook delivery did not pass verification. `reason` says which check failed:
 * `missing_secret`, `invalid_body`, `missing_header`, `malformed_header`,
 * `timestamp_out_of_tolerance`, `signature_mismatch`, `invalid_json` or `invalid_event`.
 */
class WebhookVerificationException extends FunnelchatException
{
    public const MISSING_SECRET = 'missing_secret';
    public const INVALID_BODY = 'invalid_body';
    public const MISSING_HEADER = 'missing_header';
    public const MALFORMED_HEADER = 'malformed_header';
    public const TIMESTAMP_OUT_OF_TOLERANCE = 'timestamp_out_of_tolerance';
    public const SIGNATURE_MISMATCH = 'signature_mismatch';
    public const INVALID_JSON = 'invalid_json';
    public const INVALID_EVENT = 'invalid_event';

    /**
     * @param self::MISSING_SECRET|self::INVALID_BODY|self::MISSING_HEADER|self::MALFORMED_HEADER|self::TIMESTAMP_OUT_OF_TOLERANCE|self::SIGNATURE_MISMATCH|self::INVALID_JSON|self::INVALID_EVENT $reason
     */
    public function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }
}
