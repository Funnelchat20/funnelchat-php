<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/**
 * The request never got a response: DNS, connection refused, reset, TLS…
 *
 * `getPrevious()` is the exception of your HTTP client (PSR-18). That one can carry the request,
 * with its headers: do not log it whole.
 */
class ApiConnectionException extends FunnelchatException
{
    public function __construct(string $message = 'Could not connect to the Funnelchat API.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
