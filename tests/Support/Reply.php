<?php

declare(strict_types=1);

namespace Funnelchat\Tests\Support;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/** Canned API answers. */
final class Reply
{
    /**
     * @param array<string, string> $headers
     */
    public static function json(int $status, mixed $body, array $headers = []): ResponseInterface
    {
        return new Response($status, ['Content-Type' => 'application/json'] + $headers, json_encode($body, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE));
    }

    public static function ok(mixed $body = ['data' => []]): ResponseInterface
    {
        return self::json(200, $body);
    }

    public static function noContent(): ResponseInterface
    {
        return new Response(204);
    }

    /**
     * @param array<string, string> $headers
     */
    public static function text(int $status, string $body, array $headers = []): ResponseInterface
    {
        return new Response($status, $headers, $body);
    }

    public static function rateLimited(?string $retryAfter = null): ResponseInterface
    {
        return self::json(429, ['error' => 'rate_limited', 'message' => 'Superaste el límite.'], $retryAfter === null ? [] : ['Retry-After' => $retryAfter]);
    }

    public static function serverError(int $status = 503): ResponseInterface
    {
        return self::json($status, ['message' => 'Service Unavailable']);
    }
}
