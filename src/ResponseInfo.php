<?php

declare(strict_types=1);

namespace Funnelchat;

/**
 * What the API answered to one request, besides the body: the status, the headers and the
 * `X-Request-Id`.
 *
 * Cite `requestId` when you report a problem, with the approximate time of the call: support finds
 * the call by it. Every answer of the API carries it, success or error. The API has no route to look
 * a call up by its id.
 *
 * ```php
 * $result = $funnelchat->messages->send(2001, $body);
 * $logger->info('sent', ['request_id' => $funnelchat->lastResponse()?->requestId]);
 *
 * // Or per call, also on the pages of a list:
 * $funnelchat->contacts->retrieve(2001, [], ['onResponse' => fn(ResponseInfo $r) => $log[] = $r->requestId]);
 * ```
 */
final class ResponseInfo
{
    /**
     * @param array<string, list<string>> $headers the response headers, as PSR-7 gives them
     * @param string                      $url     the URL of the request, without the query string
     */
    public function __construct(
        public readonly int $status,
        public readonly ?string $requestId,
        public readonly array $headers,
        public readonly string $method,
        public readonly string $url,
    ) {}

    /**
     * @param array<string, list<string>> $headers
     */
    public static function fromHeaders(int $status, array $headers, string $method, string $url): self
    {
        return new self($status, self::requestIdOf($headers), $headers, $method, $url);
    }

    /**
     * The `X-Request-Id` of some response headers, or `null` when absent.
     *
     * @param array<string, list<string>> $headers
     */
    public static function requestIdOf(array $headers): ?string
    {
        $value = self::headerOf($headers, 'x-request-id');

        return $value === null || trim($value) === '' ? null : trim($value);
    }

    /** A header of the response (case-insensitive), its values joined with `, `; `null` when absent. */
    public function header(string $name): ?string
    {
        return self::headerOf($this->headers, strtolower($name));
    }

    /**
     * @param array<string, list<string>> $headers
     */
    private static function headerOf(array $headers, string $lowerName): ?string
    {
        foreach ($headers as $key => $values) {
            if (strtolower((string) $key) === $lowerName) {
                return $values === [] ? null : implode(', ', $values);
            }
        }

        return null;
    }
}
