<?php

declare(strict_types=1);

namespace Funnelchat\Tests\Support;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

/** What a PSR-18 client throws when the connection fails. */
final class FakeNetworkException extends \RuntimeException implements NetworkExceptionInterface
{
    public function __construct(string $message = 'Connection reset by peer')
    {
        parent::__construct($message);
    }

    public function getRequest(): RequestInterface
    {
        return new Request('GET', 'https://api.funnelchat.com/');
    }
}
