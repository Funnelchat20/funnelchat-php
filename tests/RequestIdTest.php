<?php

declare(strict_types=1);

namespace Funnelchat\Tests;

use Funnelchat\Exception\ApiException;
use Funnelchat\ResponseInfo;
use Funnelchat\Tests\Support\MockHttpClient;
use Funnelchat\Tests\Support\Reply;
use Funnelchat\Tests\Support\TestCase;

/**
 * `X-Request-Id`: every answer of the API carries it, success or error, and the integrator cites
 * it when reporting a problem.
 */
final class RequestIdTest extends TestCase
{
    private const ID = '3f2b8c1e-9a4d-4e7b-8c21-5d6f0a9b1c3e';

    public function testAnErrorCarriesTheRequestId(): void
    {
        $http = new MockHttpClient(Reply::json(404, ['error' => 'route_not_found', 'message' => 'La ruta no existe.'], ['X-Request-Id' => self::ID]));

        try {
            $this->client($http)->request('GET', '/api/v1/nada');
            self::fail('should have thrown');
        } catch (ApiException $exception) {
            self::assertSame(self::ID, $exception->requestId);
        }
    }

    public function testAnErrorWithoutTheHeaderHasNone(): void
    {
        $http = new MockHttpClient(Reply::json(500, ['message' => 'Server Error']));

        try {
            $this->client($http)->request('GET', '/api/v1/tags');
            self::fail('should have thrown');
        } catch (ApiException $exception) {
            self::assertNull($exception->requestId);
        }
    }

    public function testTheLastResponseOfTheClientHasTheIdOfASuccess(): void
    {
        $http = new MockHttpClient(
            Reply::json(200, ['id' => 4001], ['x-request-id' => self::ID]),
            Reply::json(422, ['message' => 'Inválido.', 'errors' => ['name' => ['Falta.']]], ['X-Request-Id' => 'otro-id']),
        );
        $client = $this->client($http);
        self::assertNull($client->lastResponse());

        $client->tags->retrieve(4001);
        $last = $client->lastResponse();
        self::assertNotNull($last);
        self::assertSame(200, $last->status);
        self::assertSame(self::ID, $last->requestId);
        self::assertSame('GET', $last->method);
        self::assertSame('https://api.funnelchat.com/api/v1/tags/4001', $last->url);
        self::assertSame('application/json', $last->header('content-type'));

        try {
            $client->tags->create(['name' => '']);
        } catch (ApiException) {
        }
        self::assertSame('otro-id', $client->lastResponse()?->requestId);
    }

    public function testOnResponseSeesEveryPageOfAListAndAnError(): void
    {
        $http = new MockHttpClient(
            Reply::json(200, ['data' => [['id' => 1]], 'meta' => ['current_page' => 1, 'last_page' => 2]], ['X-Request-Id' => 'pagina-1']),
            Reply::json(200, ['data' => [['id' => 2]], 'meta' => ['current_page' => 2, 'last_page' => 2]], ['X-Request-Id' => 'pagina-2']),
            Reply::json(403, ['error' => 'permission_denied', 'message' => 'Sin permiso.'], ['X-Request-Id' => 'error-1']),
        );
        $client = $this->client($http);
        $seen = [];
        $onResponse = static function (ResponseInfo $response) use (&$seen): void {
            $seen[] = "{$response->status} {$response->requestId}";
        };

        $ids = [];
        foreach ($client->tags->list([], ['onResponse' => $onResponse]) as $tag) {
            $ids[] = $tag['id'];
        }
        self::assertSame([1, 2], $ids);

        try {
            $client->tags->retrieve(9, ['onResponse' => $onResponse]);
        } catch (ApiException) {
        }
        self::assertSame(['200 pagina-1', '200 pagina-2', '403 error-1'], $seen);
    }

    public function testOnResponseIsCalledOnceForARetriedCall(): void
    {
        $http = new MockHttpClient(
            Reply::json(503, ['error' => 'gate_unavailable'], ['X-Request-Id' => 'primero', 'Retry-After' => '1']),
            Reply::json(200, ['data' => []], ['X-Request-Id' => 'segundo']),
        );
        $seen = [];

        $this->client($http, maxRetries: 2)->request('GET', '/api/v1/tags', ['onResponse' => static function (ResponseInfo $response) use (&$seen): void {
            $seen[] = $response->requestId;
        }]);
        self::assertSame(['segundo'], $seen);
    }
}
