<?php

declare(strict_types=1);

namespace Funnelchat\Core;

use Funnelchat\Generated\Routes;

/**
 * The retry policy: which answers are retried, how long to wait, and which requests are sends
 * that are never retried automatically.
 */
final class Retry
{
    /** First backoff delay, in milliseconds; it doubles on every retry. */
    public const INITIAL_DELAY_MS = 500;

    /** Ceiling of the exponential backoff, in milliseconds. */
    public const MAX_DELAY_MS = 8_000;

    /**
     * A `Retry-After` longer than this is not waited for: the exception is thrown right away with
     * its `retryAfter`, so the caller decides (a daily cap can ask for hours).
     */
    public const MAX_RETRY_AFTER_SECONDS = 60;

    /** @var list<string>|null */
    private static ?array $sendPatterns = null;

    /** @var list<string>|null */
    private static ?array $idempotencyKeyPatterns = null;

    private function __construct() {}

    /** Statuses worth retrying: rate limits and server errors. */
    public static function isRetryableStatus(int $status): bool
    {
        return $status === 429 || $status >= 500;
    }

    /** Whether repeating the request gives the same result: `GET`, `HEAD`, `PUT`, `DELETE` and `OPTIONS`. */
    public static function isIdempotent(string $method): bool
    {
        return \in_array(strtoupper($method), ['GET', 'HEAD', 'PUT', 'DELETE', 'OPTIONS'], true);
    }

    /**
     * Whether a failed attempt may be repeated automatically (the same rule as the Node SDK).
     *
     * - `GET`, `PUT`, `DELETE`…: on `429`, `5xx` and network errors (timeouts included).
     * - `POST` and `PATCH` (writes): only when the call was certainly not applied, `429` and `503`.
     *   A `500`, `502` or `504`, a timeout or a dropped connection can come after the write was
     *   applied (a `502 origin_unreachable` does not guarantee the area did not get it), so the
     *   exception is thrown: read the state before repeating the write.
     *
     * @param int|null $status the HTTP status of the error, or `null` when no response arrived
     */
    public static function isRetryable(string $method, ?int $status): bool
    {
        if (self::isIdempotent($method)) {
            return $status === null || self::isRetryableStatus($status);
        }

        return $status === 429 || $status === 503;
    }

    /**
     * Milliseconds to wait before retry number `$attempt` (0 = first retry), or `null` when the
     * server asked for a wait longer than {@see self::MAX_RETRY_AFTER_SECONDS}.
     *
     * With `Retry-After` the wait is exactly what the server asked for; without it, exponential
     * backoff with up to 25 % of jitter.
     *
     * @param (\Closure(): float)|null $random a number in [0, 1); default: mt_rand
     */
    public static function delayMs(int $attempt, ?int $retryAfterSeconds, ?\Closure $random = null): ?int
    {
        if ($retryAfterSeconds !== null) {
            if ($retryAfterSeconds > self::MAX_RETRY_AFTER_SECONDS) {
                return null;
            }

            return max(0, $retryAfterSeconds * 1000);
        }
        $base = min(self::INITIAL_DELAY_MS * (2 ** min($attempt, 30)), self::MAX_DELAY_MS);
        $jitter = $random !== null ? $random() : mt_rand() / (mt_getrandmax() + 1);

        return (int) round($base * (1 - 0.25 * $jitter));
    }

    /**
     * Whether `$method` + `$pathname` (already interpolated, without query) takes an
     * `Idempotency-Key`: its spec declares the header (`idempotencyKey` in the generated route
     * table), so the API answers the same resource to a repeated key and body. On any other
     * operation the API ignores the header, so a repeat could create a duplicate.
     */
    public static function takesIdempotencyKey(string $method, string $pathname): bool
    {
        self::$idempotencyKeyPatterns ??= self::patterns(static fn(array $route): bool => $route['idempotencyKey']);

        return self::matchesAny(self::$idempotencyKeyPatterns, $method, $pathname);
    }

    /**
     * The operations that take an `Idempotency-Key` ("POST /api/v1/devices"), as the generated route
     * table has them.
     *
     * @return list<string>
     */
    public static function idempotencyKeyOperations(): array
    {
        $operations = [];
        foreach (Routes::ALL as $route) {
            if ($route['idempotencyKey']) {
                $operations[] = "{$route['method']} {$route['path']}";
            }
        }

        return $operations;
    }

    /**
     * Whether `$method` + `$pathname` (already interpolated, without query) is a non-idempotent
     * send: a `POST` that needs a **Send** permission (**Contacts and conversations → Send**,
     * **Groups and campaigns → Send**). It delivers a WhatsApp message, so it is never retried
     * automatically. Built from the generated route table, so a regeneration keeps the list current.
     */
    public static function isSendRequest(string $method, string $pathname): bool
    {
        self::$sendPatterns ??= self::patterns(static fn(array $route): bool => $route['method'] === 'POST' && self::isSendPermission($route['permission']));

        return self::matchesAny(self::$sendPatterns, $method, $pathname);
    }

    /** Whether the permission of a route, as `Routes::ALL` has it, is a **Send** one: the route delivers a message. */
    public static function isSendPermission(?string $permission): bool
    {
        return $permission !== null && str_ends_with($permission, ':send');
    }

    /**
     * A pattern of "METHOD /path" for each route of the generated table that `$include` takes.
     *
     * @param \Closure(value-of<Routes::ALL>): bool $include
     *
     * @return list<string>
     */
    private static function patterns(\Closure $include): array
    {
        $patterns = [];
        foreach (Routes::ALL as $route) {
            if (!$include($route)) {
                continue;
            }
            $parts = preg_split('/\{[^}]+\}/', $route['path']);
            $quoted = array_map(static fn(string $part): string => preg_quote($part, '#'), $parts === false ? [] : $parts);
            $patterns[] = '#^' . $route['method'] . ' ' . implode('[^/]+', $quoted) . '/?$#';
        }

        return $patterns;
    }

    /**
     * @param list<string> $patterns
     */
    private static function matchesAny(array $patterns, string $method, string $pathname): bool
    {
        $request = strtoupper($method) . ' ' . $pathname;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $request) === 1) {
                return true;
            }
        }

        return false;
    }
}
