<?php

declare(strict_types=1);

namespace Funnelchat\Tests\Support;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A PSR-18 client that answers from a queue of canned replies and records every request. No network.
 */
final class MockHttpClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $requests = [];

    /** @var list<ResponseInterface|\Throwable|\Closure(RequestInterface): ResponseInterface> */
    private array $queue;

    /**
     * @param ResponseInterface|\Throwable|\Closure(RequestInterface): ResponseInterface ...$replies
     */
    public function __construct(ResponseInterface|\Throwable|\Closure ...$replies)
    {
        $this->queue = array_values($replies);
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        $reply = array_shift($this->queue);
        if ($reply === null) {
            throw new \LogicException("MockHttpClient: no reply left for {$request->getMethod()} {$request->getUri()}");
        }
        if ($reply instanceof \Throwable) {
            throw $reply;
        }
        if ($reply instanceof \Closure) {
            return $reply($request);
        }

        return $reply;
    }

    public function lastRequest(): RequestInterface
    {
        return $this->requests[\count($this->requests) - 1] ?? throw new \LogicException('MockHttpClient: no request was sent');
    }
}
