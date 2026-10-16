<?php

declare(strict_types=1);

namespace Funnelchat\Tests\Support;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * A timeout told only by the class name, like Guzzle 8's `ConnectTimeoutException`,
 * `NetworkTimeoutException` and `ResponseTimeoutException`.
 */
final class FakeTimeoutException extends \RuntimeException implements NetworkExceptionInterface
{
    public function __construct()
    {
        parent::__construct('Transfer aborted');
    }

    public function getRequest(): RequestInterface
    {
        return new Request('GET', 'https://api.funnelchat.com/');
    }
}
