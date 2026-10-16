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
                ? 'La API de Funnelchat no respondió a tiempo.'
                : 'La API de Funnelchat no respondió en ' . self::seconds($timeout) . ' s.',
            $previous,
        );
    }

    private static function seconds(float $timeout): string
    {
        return rtrim(rtrim(number_format($timeout, 3, ',', ''), '0'), ',');
    }
}
