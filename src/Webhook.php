<?php

declare(strict_types=1);

namespace Funnelchat;

use Funnelchat\Exception\WebhookVerificationException;
use Funnelchat\Generated\WebhookEvents;
use Psr\Http\Message\StreamInterface;

/**
 * Verification of the `X-Funnelchat-Signature` header of a webhook delivery.
 *
 * It replicates exactly the algorithm of the developer portal (`snippets/verificar-firma.php` of
 * developers-portal, contract C3 v4):
 *
 *   X-Funnelchat-Signature: t=<unix>,v1=<hex(hmac_sha256(secret, "<t>." + raw body))>
 *
 * - the header is split by `,` and each part by its first `=`; keys and values are trimmed;
 * - `t` must be digits only; there can be several `v1` (any match is enough);
 * - `t` farther than `$toleranceSeconds` (300 by default) from the local clock is rejected;
 * - the HMAC uses the full secret as shown (a `whsec_` prefix is part of it) over the raw bytes of
 *   the body, and is compared in constant time (`hash_equals`).
 *
 * @phpstan-import-type WebhookEvent from WebhookEvents
 */
final class Webhook
{
    /** Name of the signature header. */
    public const SIGNATURE_HEADER = 'X-Funnelchat-Signature';

    /** Default tolerance between `t` and the local clock, in seconds. */
    public const DEFAULT_TOLERANCE_SECONDS = 300;

    private function __construct() {}

    /**
     * `true` when the delivery is authentic and recent. Same contract as the portal snippet: it
     * never throws, any problem is `false`. Use {@see self::constructEvent()} to know which check failed.
     *
     * @param string|StreamInterface $rawBody         the body exactly as it arrived (`file_get_contents('php://input')`,
     *                                                `$request->getContent()` in Laravel, a PSR-7 body): never decoded and re-encoded
     * @param string|null            $signatureHeader the `X-Funnelchat-Signature` header
     * @param string|null            $secret          the full secret of the subscription, as shown when it was created or rotated
     * @param int|null               $now             the current Unix time; default `time()` (for tests)
     */
    public static function verify(
        mixed $rawBody,
        ?string $signatureHeader,
        #[\SensitiveParameter]
        ?string $secret,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
        ?int $now = null,
    ): bool {
        return self::check($rawBody, $signatureHeader, $secret, $toleranceSeconds, $now) === null;
    }

    /**
     * Verifies the signature and returns the decoded event (associative arrays). Throws
     * {@see WebhookVerificationException} (with `reason`) when the signature, the timestamp or the
     * body are not valid.
     *
     * New event types can arrive before this SDK knows them: answer `2xx` to the ones you do not
     * handle (a `match` or `switch` with a `default`). Once you checked `type`, the alias of that
     * type in {@see WebhookEvents} gives the shape of the event (`MessageReceivedEvent`, …).
     *
     * @param string|StreamInterface $rawBody the body exactly as it arrived (see {@see self::verify()})
     * @param int|null               $now     the current Unix time; default `time()` (for tests)
     *
     * @return WebhookEvent
     *
     * @throws WebhookVerificationException
     */
    public static function constructEvent(
        mixed $rawBody,
        ?string $signatureHeader,
        #[\SensitiveParameter]
        ?string $secret,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
        ?int $now = null,
    ): array {
        $failure = self::check($rawBody, $signatureHeader, $secret, $toleranceSeconds, $now);
        if ($failure !== null) {
            throw $failure;
        }
        try {
            $event = json_decode(self::bytes($rawBody) ?? '', true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new WebhookVerificationException(WebhookVerificationException::INVALID_JSON, 'The signature is valid but the body is not JSON.');
        }
        if (
            !\is_array($event)
            || array_is_list($event)
            || !\is_string($event['id'] ?? null)
            || !\is_string($event['type'] ?? null)
            || !\is_int($event['version'] ?? null)
            || !\is_string($event['occurred_at'] ?? null)
            || !\array_key_exists('data', $event)
        ) {
            throw new WebhookVerificationException(
                WebhookVerificationException::INVALID_EVENT,
                'The signature is valid but the body does not have the shape of an event (id, type, version, occurred_at, data).',
            );
        }

        /** @var WebhookEvent $event */
        return $event;
    }

    /**
     * Runs every check and returns the first that failed, or `null` when the delivery is valid.
     *
     * @param string|StreamInterface $rawBody
     */
    public static function check(
        mixed $rawBody,
        ?string $signatureHeader,
        #[\SensitiveParameter]
        ?string $secret,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
        ?int $now = null,
    ): ?WebhookVerificationException {
        if ($secret === null || $secret === '') {
            return new WebhookVerificationException(WebhookVerificationException::MISSING_SECRET, 'Missing the secret of the subscription.');
        }
        $body = self::bytes($rawBody);
        if ($body === null) {
            return new WebhookVerificationException(
                WebhookVerificationException::INVALID_BODY,
                'The body must be the raw one (a string or a StreamInterface), exactly as it arrived. In Laravel, read it with $request->getContent(), not with $request->all() or $request->json().',
            );
        }
        if ($signatureHeader === null || $signatureHeader === '') {
            return new WebhookVerificationException(WebhookVerificationException::MISSING_HEADER, 'Missing the ' . self::SIGNATURE_HEADER . ' header.');
        }

        $t = null;
        $signatures = [];
        foreach (explode(',', $signatureHeader) as $part) {
            $i = strpos($part, '=');
            if ($i === false) {
                continue;
            }
            $key = trim(substr($part, 0, $i));
            $value = trim(substr($part, $i + 1));
            if ($key === 't') {
                $t = $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }
        if ($t === null || !ctype_digit($t) || $signatures === []) {
            return new WebhookVerificationException(
                WebhookVerificationException::MALFORMED_HEADER,
                'The ' . self::SIGNATURE_HEADER . ' header does not have the shape t=<unix>,v1=<hex>.',
            );
        }

        // Reject old deliveries and deliveries from the future (replay protection).
        if (abs(($now ?? time()) - (int) $t) > $toleranceSeconds) {
            return new WebhookVerificationException(
                WebhookVerificationException::TIMESTAMP_OUT_OF_TOLERANCE,
                "The signature is more than {$toleranceSeconds} seconds away from the local clock.",
            );
        }

        $expected = hash_hmac('sha256', $t . '.' . $body, $secret);
        foreach ($signatures as $v1) {
            if (hash_equals($expected, $v1)) {
                return null;
            }
        }

        return new WebhookVerificationException(WebhookVerificationException::SIGNATURE_MISMATCH, 'The signature does not match the body and the secret.');
    }

    /** The raw bytes of a body, or `null` when it is not a raw body. */
    private static function bytes(mixed $rawBody): ?string
    {
        if (\is_string($rawBody)) {
            return $rawBody;
        }
        if ($rawBody instanceof StreamInterface) {
            return (string) $rawBody;
        }

        return null;
    }
}
