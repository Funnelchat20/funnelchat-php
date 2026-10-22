<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

use Funnelchat\ResponseInfo;

/**
 * The API answered with a non-2xx status.
 *
 * - `status`: the HTTP status (also `getCode()`).
 * - `errorCode`: the stable code of the body (`window_closed`, `rate_limited`,
 *   `route_not_available`, `NUMBER_NOT_ELIGIBLE`…), or `null` when the body has none (for example a
 *   Laravel validation `422`, which carries `errors` instead, or a `415`). It is `error` when that
 *   is a code; else `error_code`; else `reason` (a reaction's `422 provider_unsupported`). Some
 *   routes put a sentence in `error` (`"Contact not found."`): that is the message, not a code. In
 *   the Node SDK it is `code`; in PHP `getCode()` is final and returns an int, so it is `errorCode`.
 * - `getMessage()`: the `message` of the body (or that sentence), meant for people. Decide by
 *   `status` and `errorCode`.
 * - `retryAfter`: seconds from the `Retry-After` header (rounded up), when present.
 * - `requestId`: the `X-Request-Id` of the answer. Cite it when you report a problem.
 */
class ApiException extends FunnelchatException
{
    public readonly int $status;

    public readonly ?string $errorCode;

    public readonly ?int $retryAfter;

    /** The `X-Request-Id` of the answer: cite it, with the time of the call, when you report a problem. */
    public readonly ?string $requestId;

    /**
     * Per-field validation messages of a `422`, when the API sends them.
     *
     * @var array<string, list<string>>|null
     */
    public readonly ?array $errors;

    /** The parsed body (or the raw text when it is not JSON; `null` when empty). */
    public readonly mixed $body;

    /**
     * Response headers, as PSR-7 gives them.
     *
     * @var array<string, list<string>>
     */
    public readonly array $headers;

    public readonly string $method;

    /** URL of the request, without the query string. */
    public readonly string $url;

    /**
     * @param array<string, list<string>> $headers
     */
    public function __construct(int $status, mixed $body, array $headers, string $method, string $url, ?int $now = null)
    {
        $record = \is_array($body) && !array_is_list($body) ? $body : null;
        $error = self::text($record['error'] ?? null);
        $errorCode = self::text($record['error_code'] ?? null);
        $reason = self::text($record['reason'] ?? null);
        $code = match (true) {
            self::isCode($error) => $error,
            self::isCode($errorCode) => $errorCode,
            $error === null && self::isCode($reason) => $reason,
            default => null,
        };
        $apiMessage = self::text($record['message'] ?? null) ?? ($code !== $error ? $error : null);
        $fallback = \is_string($body) && trim($body) !== '' && \strlen($body) <= 300 ? trim($body) : null;

        parent::__construct($apiMessage ?? $code ?? $fallback ?? "The API answered {$status}.", $status);

        $this->status = $status;
        $this->errorCode = $code;
        $this->retryAfter = self::parseRetryAfter(self::header($headers, 'retry-after'), $now);
        $this->requestId = ResponseInfo::requestIdOf($headers);
        $this->errors = self::fieldErrors($record['errors'] ?? null);
        $this->body = $body;
        $this->headers = $headers;
        $this->method = $method;
        $this->url = $url;
    }

    /**
     * Builds the subclass that matches the status (and, for the API's own errors, the code).
     * - `404 route_not_found` → {@see RouteNotFoundException}
     * - `415` → {@see UnsupportedMediaTypeException}
     * - `500 edge_misconfigured` → {@see EdgeMisconfiguredException}
     * - `502 origin_unreachable` → {@see OriginUnreachableException}
     *
     * @param array<string, list<string>> $headers
     */
    public static function from(int $status, mixed $body, array $headers, string $method, string $url, ?int $now = null): self
    {
        return match (true) {
            $status === 400 => new BadRequestException($status, $body, $headers, $method, $url, $now),
            $status === 401 => new AuthenticationException($status, $body, $headers, $method, $url, $now),
            $status === 403 => new PermissionDeniedException($status, $body, $headers, $method, $url, $now),
            $status === 404 && self::codeOf($body) === 'route_not_found' => new RouteNotFoundException($status, $body, $headers, $method, $url, $now),
            $status === 404 => new NotFoundException($status, $body, $headers, $method, $url, $now),
            $status === 409 => new ConflictException($status, $body, $headers, $method, $url, $now),
            $status === 415 => new UnsupportedMediaTypeException($status, $body, $headers, $method, $url, $now),
            $status === 422 => new UnprocessableEntityException($status, $body, $headers, $method, $url, $now),
            $status === 429 => new RateLimitException($status, $body, $headers, $method, $url, $now),
            $status === 500 && self::codeOf($body) === 'edge_misconfigured' => new EdgeMisconfiguredException($status, $body, $headers, $method, $url, $now),
            $status === 502 && self::codeOf($body) === 'origin_unreachable' => new OriginUnreachableException($status, $body, $headers, $method, $url, $now),
            $status >= 500 => new InternalServerException($status, $body, $headers, $method, $url, $now),
            default => new self($status, $body, $headers, $method, $url, $now),
        };
    }

    /**
     * Whether `errorCode` is one of the send rules or refusals of {@see SendErrors}
     * (`window_closed`, `antiblock_*`, `recipient_*`, `rate_limited`, `mac_lockout_24h`,
     * `device_unavailable`…): nothing went out.
     */
    public function isSendRuleError(): bool
    {
        return SendErrors::isSendRuleError($this->errorCode);
    }

    /**
     * Seconds to wait from a `Retry-After` header: delta-seconds (`"30"`, rounded up when it has
     * decimals) or an HTTP date. `null` when absent or unreadable.
     */
    public static function parseRetryAfter(?string $value, ?int $now = null): ?int
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }
        if (preg_match('/^\d+(\.\d+)?$/', $trimmed) === 1) {
            return (int) ceil((float) $trimmed);
        }
        // An HTTP date: IMF-fixdate (`Fri, 16 Oct 2026 12:00:03 GMT`), RFC 850 or asctime.
        $looksLikeDate = str_contains(strtoupper($trimmed), 'GMT')
            || preg_match('/^[A-Z][a-z]{2} [A-Z][a-z]{2} [ \d]\d \d\d:\d\d:\d\d \d{4}$/', $trimmed) === 1;
        $date = $looksLikeDate ? strtotime($trimmed) : false;
        if ($date === false) {
            return null;
        }

        return max(0, $date - ($now ?? time()));
    }

    private static function codeOf(mixed $body): ?string
    {
        $error = \is_array($body) ? self::text($body['error'] ?? null) : null;

        return self::isCode($error) ? $error : null;
    }

    /**
     * A stable code: `snake_case` (`window_closed`) or `UPPER_SNAKE` (`NUMBER_NOT_ELIGIBLE`), never
     * a sentence.
     */
    private static function isCode(?string $value): bool
    {
        return $value !== null && preg_match('/^(?:[a-z][a-z0-9]*(?:_[a-z0-9]+)*|[A-Z][A-Z0-9]*(?:_[A-Z0-9]+)*)$/', $value) === 1;
    }

    private static function text(mixed $value): ?string
    {
        return \is_string($value) && trim($value) !== '' ? $value : null;
    }

    /**
     * @param array<string, list<string>> $headers
     */
    private static function header(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $values) {
            if (strtolower($key) === $name) {
                return $values === [] ? null : implode(', ', $values);
            }
        }

        return null;
    }

    /**
     * @return array<string, list<string>>|null
     */
    private static function fieldErrors(mixed $errors): ?array
    {
        if (!\is_array($errors) || $errors === [] || array_is_list($errors)) {
            return null;
        }
        $result = [];
        foreach ($errors as $field => $messages) {
            $list = [];
            foreach (\is_array($messages) ? $messages : [$messages] as $message) {
                if (\is_string($message)) {
                    $list[] = $message;
                }
            }
            $result[(string) $field] = $list;
        }

        return $result;
    }
}
