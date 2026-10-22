<?php

declare(strict_types=1);

namespace Funnelchat\Tests;

use Funnelchat\Core\Retry;
use Funnelchat\Exception\ApiConnectionException;
use Funnelchat\Exception\ApiConnectionTimeoutException;
use Funnelchat\Exception\ApiException;
use Funnelchat\Exception\InternalServerException;
use Funnelchat\Exception\RateLimitException;
use Funnelchat\Funnelchat;
use Funnelchat\Generated\Routes;
use Funnelchat\Tests\Support\FakeCurlTimeoutException;
use Funnelchat\Tests\Support\FakeNetworkException;
use Funnelchat\Tests\Support\FakeTimeoutException;
use Funnelchat\Tests\Support\MockHttpClient;
use Funnelchat\Tests\Support\Reply;
use Funnelchat\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

final class RetriesTest extends TestCase
{
    public function testWaitsExactlyRetryAfterSecondsOnA429AndThenSucceeds(): void
    {
        $http = new MockHttpClient(Reply::rateLimited('2'), Reply::ok());

        self::assertSame(['data' => []], $this->client($http, maxRetries: 2)->request('GET', '/api/v1/tags'));
        self::assertSame([2000], $this->sleeps);
        self::assertCount(2, $http->requests);
    }

    public function testReadsRetryAfterAsAnHttpDate(): void
    {
        $http = new MockHttpClient(Reply::rateLimited(gmdate('D, d M Y H:i:s \G\M\T', time() + 3)), Reply::ok());

        $this->client($http, maxRetries: 2)->request('GET', '/api/v1/tags');
        self::assertCount(1, $this->sleeps);
        self::assertGreaterThanOrEqual(2000, $this->sleeps[0]);
        self::assertLessThanOrEqual(3000, $this->sleeps[0]);
    }

    public function testDoesNotWaitForARetryAfterLongerThan60Seconds(): void
    {
        $http = new MockHttpClient(Reply::rateLimited('3600'));

        try {
            $this->client($http, maxRetries: 2)->request('GET', '/api/v1/tags');
            self::fail('should have thrown');
        } catch (RateLimitException $exception) {
            self::assertSame(3600, $exception->retryAfter);
        }
        self::assertCount(1, $http->requests);
        self::assertSame([], $this->sleeps);
    }

    public function testRetries5xxWithExponentialBackoffAndGivesUpAfterMaxRetries(): void
    {
        $http = new MockHttpClient(Reply::serverError(), Reply::serverError(), Reply::serverError());

        try {
            $this->client($http, maxRetries: 2)->request('GET', '/api/v1/tags');
            self::fail('should have thrown');
        } catch (InternalServerException $exception) {
            self::assertSame(503, $exception->status);
        }
        self::assertCount(3, $http->requests);
        self::assertCount(2, $this->sleeps);
        // 500 ms and 1000 ms, minus up to 25 % of jitter.
        self::assertGreaterThanOrEqual(375, $this->sleeps[0]);
        self::assertLessThanOrEqual(500, $this->sleeps[0]);
        self::assertGreaterThanOrEqual(750, $this->sleeps[1]);
        self::assertLessThanOrEqual(1000, $this->sleeps[1]);
    }

    public function testRetriesNetworkErrors(): void
    {
        $http = new MockHttpClient(new FakeNetworkException(), Reply::ok());

        self::assertSame(['data' => []], $this->client($http, maxRetries: 2)->request('GET', '/api/v1/tags'));
        self::assertCount(2, $http->requests);
    }

    public function testTurnsANetworkErrorIntoApiConnectionExceptionOnceRetriesRunOut(): void
    {
        $cause = new FakeNetworkException();
        $http = new MockHttpClient($cause);

        try {
            $this->client($http)->request('GET', '/api/v1/tags');
            self::fail('should have thrown');
        } catch (ApiConnectionException $exception) {
            self::assertNotInstanceOf(ApiConnectionTimeoutException::class, $exception);
            self::assertSame($cause, $exception->getPrevious());
            self::assertSame('Could not connect to the Funnelchat API.', $exception->getMessage());
        }
    }

    public function testRecognizesATimeoutOfTheHttpClient(): void
    {
        $http = new MockHttpClient(new FakeNetworkException('cURL error 28: Operation timed out after 30001 milliseconds'), Reply::ok());

        self::assertSame(['data' => []], $this->client($http, maxRetries: 1)->request('GET', '/api/v1/tags'));
        $this->expectException(ApiConnectionTimeoutException::class);
        $this->client(new MockHttpClient(new FakeNetworkException('Request timed out')))->request('GET', '/api/v1/tags');
    }

    /**
     * @return array<string, array{\Throwable, bool}>
     */
    public static function connectionFailures(): array
    {
        return [
            'a Timeout class (Guzzle 8)' => [new FakeTimeoutException(), true],
            'cURL errno 28 in the context (Guzzle 7)' => [new FakeCurlTimeoutException(), true],
            'the message says so' => [new FakeNetworkException('Idle timeout reached for "https://api.funnelchat.com/api/v1/tags".'), true],
            'a reset connection' => [new FakeNetworkException('Connection reset by peer'), false],
        ];
    }

    #[DataProvider('connectionFailures')]
    public function testTellsATimeoutFromOtherNetworkErrors(\Throwable $cause, bool $isTimeout): void
    {
        try {
            $this->client(new MockHttpClient($cause))->request('GET', '/api/v1/tags');
            self::fail('should have thrown');
        } catch (ApiConnectionException $exception) {
            self::assertSame($isTimeout, $exception instanceof ApiConnectionTimeoutException);
            self::assertSame($cause, $exception->getPrevious());
        }
    }

    public function testDoesNotRetryOther4xx(): void
    {
        foreach ([400, 401, 403, 404, 409, 422] as $status) {
            $http = new MockHttpClient(Reply::json($status, ['error' => 'x', 'message' => 'no']));
            try {
                $this->client($http, maxRetries: 2)->request('GET', '/api/v1/tags');
                self::fail('should have thrown');
            } catch (ApiException $exception) {
                self::assertSame($status, $exception->status);
            }
            self::assertCount(1, $http->requests);
        }
    }

    public function testPerRequestMaxRetriesOverridesTheClient(): void
    {
        $http = new MockHttpClient(Reply::serverError());

        $this->expectException(InternalServerException::class);
        try {
            $this->client($http, maxRetries: 5)->request('GET', '/api/v1/tags', ['maxRetries' => 0]);
        } finally {
            self::assertCount(1, $http->requests);
        }
    }

    /**
     * @return array<string, array{ResponseInterface|\Throwable}>
     */
    public static function sendFailures(): array
    {
        return [
            '429 with Retry-After' => [Reply::rateLimited('1')],
            'HTTP 503' => [Reply::serverError()],
            'network error' => [new FakeNetworkException()],
        ];
    }

    #[DataProvider('sendFailures')]
    public function testAMessageSendIsNotRetried(ResponseInterface|\Throwable $reply): void
    {
        $http = new MockHttpClient($reply, Reply::noContent());

        try {
            self::send($this->client($http, maxRetries: 2));
            self::fail('should have thrown');
        } catch (ApiException|ApiConnectionException) {
        }
        self::assertCount(1, $http->requests);
        self::assertSame([], $this->sleeps);
    }

    public function testKeepsRetryAfterOnTheExceptionSoTheCallerCanRetryByHand(): void
    {
        $http = new MockHttpClient(Reply::rateLimited('7'));

        try {
            self::send($this->client($http, maxRetries: 2));
            self::fail('should have thrown');
        } catch (RateLimitException $exception) {
            self::assertSame(429, $exception->status);
            self::assertSame('rate_limited', $exception->errorCode);
            self::assertSame(7, $exception->retryAfter);
            self::assertTrue($exception->isSendRuleError());
        }
    }

    public function testRetriesASendWhenTheCallerPassesRetrySend(): void
    {
        $http = new MockHttpClient(Reply::rateLimited('1'), Reply::noContent());

        self::send($this->client($http, maxRetries: 2), true);
        self::assertCount(2, $http->requests);
        self::assertSame([1000], $this->sleeps);
    }

    public function testAppliesTheSameRuleToTheGenericRequestOnASendPath(): void
    {
        $http = new MockHttpClient(Reply::serverError(), Reply::ok());

        try {
            $this->client($http, maxRetries: 2)->request('POST', '/api/v1/devices/{device}/templates/{template}/send-template', [
                'path' => ['device' => 3, 'template' => '45'],
                'body' => ['phone' => '5491100000000', 'contact_id' => 2001],
            ]);
            self::fail('should have thrown');
        } catch (InternalServerException) {
        }
        self::assertCount(1, $http->requests);
    }

    public function testStillRetriesIdempotentOperationsOfTheSendArea(): void
    {
        // Reactions are PUT and DELETE: repeating them gives the same result.
        $http = new MockHttpClient(Reply::serverError(), Reply::noContent());

        $this->client($http, maxRetries: 2)->messages->react(2001, 99, ['emoji' => '👍']);
        self::assertCount(2, $http->requests);
    }

    public function testRetriesANonSendPost(): void
    {
        $http = new MockHttpClient(Reply::serverError(), Reply::json(201, ['id' => 1, 'name' => 'VIP']));

        $tag = $this->client($http, maxRetries: 2)->tags->create(['name' => 'VIP']);
        self::assertSame(1, $tag['id']);
        self::assertCount(2, $http->requests);
    }

    /**
     * @return array<string, array{ResponseInterface|\Throwable}>
     */
    public static function answersAfterWhichAWriteMayHaveBeenApplied(): array
    {
        return [
            'HTTP 500' => [Reply::json(500, ['message' => 'Server Error'])],
            '502 origin_unreachable' => [Reply::json(502, ['error' => 'origin_unreachable', 'message' => 'La API no está disponible en este momento.'])],
            'HTTP 504' => [Reply::json(504, ['message' => 'Gateway Timeout'])],
            'timeout' => [new FakeTimeoutException()],
            'dropped connection' => [new FakeNetworkException()],
        ];
    }

    #[DataProvider('answersAfterWhichAWriteMayHaveBeenApplied')]
    public function testDoesNotRepeatAPostThatMayHaveBeenApplied(ResponseInterface|\Throwable $reply): void
    {
        $http = new MockHttpClient($reply, Reply::json(201, ['id' => 1, 'name' => 'VIP']));

        try {
            $this->client($http, maxRetries: 2)->tags->create(['name' => 'VIP']);
            self::fail('should have thrown');
        } catch (InternalServerException|ApiConnectionException) {
        }
        self::assertCount(1, $http->requests);
        self::assertSame([], $this->sleeps);
    }

    #[DataProvider('answersAfterWhichAWriteMayHaveBeenApplied')]
    public function testRepeatsAnIdempotentWriteOrAReadAfterThem(ResponseInterface|\Throwable $reply): void
    {
        // A PSR-7 response is immutable and can be answered twice; an exception is thrown anew.
        $again = $reply instanceof \Throwable ? new ($reply::class)() : $reply;
        $http = new MockHttpClient($reply, Reply::ok(['id' => 7]), $again, Reply::ok(['data' => []]));
        $client = $this->client($http, maxRetries: 2);

        self::assertSame(['id' => 7], $client->tags->update(7, ['name' => 'VIP']));
        self::assertSame(['data' => []], $client->request('GET', '/api/v1/tags'));
        self::assertCount(4, $http->requests);
    }

    public function testNeverRepeatsAMacLockoutNotEvenWhenAsked(): void
    {
        $http = new MockHttpClient(Reply::json(429, ['error' => 'mac_lockout_24h', 'message' => 'Tu cuenta está en bloqueo de 24h por agotamiento de MACs.']), Reply::ok());

        try {
            $this->client($http, maxRetries: 2)->messages->send(2001, ['device_id' => 3, 'phone' => '5491100000000', 'text' => 'Hola'], ['retrySend' => true]);
            self::fail('should have thrown');
        } catch (RateLimitException $exception) {
            self::assertSame('mac_lockout_24h', $exception->errorCode);
            self::assertNull($exception->retryAfter);
        }
        self::assertCount(1, $http->requests);
    }

    public function testRetrySendDoesNotRepeatASendThatMayHaveBeenApplied(): void
    {
        $http = new MockHttpClient(Reply::json(502, ['error' => 'origin_unreachable']), Reply::ok(['message' => []]));

        try {
            self::send($this->client($http, maxRetries: 2), retrySend: true);
            self::fail('should have thrown');
        } catch (InternalServerException $exception) {
            self::assertSame('origin_unreachable', $exception->errorCode);
        }
        self::assertCount(1, $http->requests);
    }

    public function testRepeatsAPostThatWasCertainlyNotApplied(): void
    {
        $http = new MockHttpClient(Reply::json(503, ['error' => 'gate_unavailable'], ['Retry-After' => '1']), Reply::json(201, ['id' => 1, 'name' => 'VIP']));

        self::assertSame(1, $this->client($http, maxRetries: 2)->tags->create(['name' => 'VIP'])['id']);
        self::assertCount(2, $http->requests);
        self::assertSame([1000], $this->sleeps);
    }

    public function testDetectsEveryPostWithASendPermissionInTheSpecAndNothingElse(): void
    {
        $sendPosts = array_filter(Routes::ALL, static fn(array $route): bool => $route['method'] === 'POST' && Retry::isSendPermission($route['permission']));
        self::assertGreaterThanOrEqual(6, \count($sendPosts));
        foreach ($sendPosts as $route) {
            self::assertTrue(Retry::isSendRequest('POST', (string) preg_replace('/\{[^}]+\}/', '123', $route['path'])), $route['path']);
        }
        self::assertTrue(Retry::isSendRequest('POST', '/api/v1/contacts/2001/message'));
        self::assertTrue(Retry::isSendRequest('post', '/api/v1/message/send-message'));
        self::assertFalse(Retry::isSendRequest('GET', '/api/v1/groups/120363000000000001@g.us/messages'));
        self::assertFalse(Retry::isSendRequest('GET', '/api/v1/contacts/2001/messages'));
        self::assertFalse(Retry::isSendRequest('POST', '/api/v1/contacts/delete'));
        self::assertFalse(Retry::isSendRequest('POST', '/api/v1/contacts'));
        self::assertFalse(Retry::isSendRequest('PUT', '/api/v1/contacts/2001/messages/9/reaction'));
    }

    public function testCapsTheExponentialBackoff(): void
    {
        $noJitter = static fn(): float => 0.0;
        $fullJitter = static fn(): float => 1.0;
        self::assertSame(500, Retry::delayMs(0, null, $noJitter));
        self::assertSame(1000, Retry::delayMs(1, null, $noJitter));
        self::assertSame(Retry::MAX_DELAY_MS, Retry::delayMs(10, null, $noJitter));
        self::assertSame(375, Retry::delayMs(0, null, $fullJitter));
        self::assertSame(2000, Retry::delayMs(3, 2));
        self::assertSame(0, Retry::delayMs(0, 0));
        self::assertNull(Retry::delayMs(0, 61));
    }

    private static function send(Funnelchat $client, bool $retrySend = false): void
    {
        $client->messages->send(2001, ['device_id' => 3, 'phone' => '5491100000000', 'text' => 'Hola'], $retrySend ? ['retrySend' => true] : []);
    }
}
