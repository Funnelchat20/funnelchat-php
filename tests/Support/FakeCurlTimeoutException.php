<?php

declare(strict_types=1);

namespace Funnelchat\Tests\Support;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

/** A timeout told only by cURL's errno in the handler context, like Guzzle 7's `ConnectException`. */
final class FakeCurlTimeoutException extends \RuntimeException implements NetworkExceptionInterface
{
    public function __construct()
    {
        parent::__construct('Transfer aborted');
    }

    public function getRequest(): RequestInterface
    {
        return new Request('GET', 'https://api.funnelchat.com/');
    }

    /**
     * @return array{errno: int}
     */
    public function getHandlerContext(): array
    {
        return ['errno' => 28];
    }
}
