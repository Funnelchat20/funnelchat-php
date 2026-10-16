<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

use Funnelchat\Pending\SendErrors;

/**
 * The API answered with a non-2xx status.
 *
 * - `status`: the HTTP status (also `getCode()`).
 * - `errorCode`: the stable `error` code of the body (`window_closed`, `rate_limited`,
 *   `route_not_available`…), or `null` when the body has none (for example a Laravel validation
 *   `422`, which carries `errors` instead). In the Node SDK it is `code`; in PHP `getCode()` is
 *   final and returns an int, so it is `errorCode`.
 * - `getMessage()`: the `message` of the body, meant for people. Decide by `status` and `errorCode`.
 * - `retryAfter`: seconds from the `Retry-After` header (rounded up), when present.
 */
class ApiException extends FunnelchatException
{
    public readonly int $status;

    public readonly ?string $errorCode;

    public readonly ?int $retryAfter;

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
        $code = isset($record['error']) && \is_string($record['error']) && $record['error'] !== '' ? $record['error'] : null;
        $apiMessage = isset($record['message']) && \is_string($record['message']) && $record['message'] !== '' ? $record['message'] : null;
        $fallback = \is_string($body) && trim($body) !== '' && \strlen($body) <= 300 ? trim($body) : null;

        parent::__construct($apiMessage ?? $code ?? $fallback ?? "La API respondió {$status}.", $status);

        $this->status = $status;
        $this->errorCode = $code;
        $this->retryAfter = self::parseRetryAfter(self::header($headers, 'retry-after'), $now);
        $this->errors = self::fieldErrors($record['errors'] ?? null);
        $this->body = $body;
        $this->headers = $headers;
        $this->method = $method;
        $this->url = $url;
    }

    /**
     * Builds the subclass that matches the status.
     *
     * @param array<string, list<string>> $headers
     */
    public static function from(int $status, mixed $body, array $headers, string $method, string $url, ?int $now = null): self
    {
        return match (true) {
            $status === 400 => new BadRequestException($status, $body, $headers, $method, $url, $now),
            $status === 401 => new AuthenticationException($status, $body, $headers, $method, $url, $now),
            $status === 403 => new PermissionDeniedException($status, $body, $headers, $method, $url, $now),
            $status === 404 => new NotFoundException($status, $body, $headers, $method, $url, $now),
            $status === 409 => new ConflictException($status, $body, $headers, $method, $url, $now),
            $status === 422 => new UnprocessableEntityException($status, $body, $headers, $method, $url, $now),
            $status === 429 => new RateLimitException($status, $body, $headers, $method, $url, $now),
            $status >= 500 => new InternalServerException($status, $body, $headers, $method, $url, $now),
            default => new self($status, $body, $headers, $method, $url, $now),
        };
    }

    /**
     * Whether `errorCode` is one of the WhatsApp send rules (`window_closed`, `antiblock_*`,
     * `recipient_*`, `rate_limited`): nothing was sent and nothing was stored.
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
