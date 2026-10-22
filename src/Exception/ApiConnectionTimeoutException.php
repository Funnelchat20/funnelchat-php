<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/** The request took longer than the `timeout`. */
class ApiConnectionTimeoutException extends ApiConnectionException
{
    public function __construct(public readonly ?float $timeout, ?\Throwable $previous = null)
    {
        parent::__construct(
            $timeout === null
                ? 'The Funnelchat API did not answer in time.'
                : 'The Funnelchat API did not answer within ' . self::seconds($timeout) . ' s.',
            $previous,
        );
    }

    private static function seconds(float $timeout): string
    {
        return rtrim(rtrim(number_format($timeout, 3, '.', ''), '0'), '.');
    }
}
