<?php

declare(strict_types=1);

namespace Funnelchat\Tests\Support;

use Funnelchat\Funnelchat;
use Psr\Http\Message\RequestInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /** A fake API key with the real shape. Not a real credential. */
    public const TEST_API_KEY = '1|fc_live_test_0000000000000000';

    /** @var list<int> milliseconds the client waited between retries */
    protected array $sleeps = [];

    /**
     * A client over a simulated PSR-18 client, with no retries unless asked and no real waits.
     *
     * @param 'production'|'staging' $environment
     * @param array<string, string>  $defaultHeaders
     */
    protected function client(
        MockHttpClient $http,
        int $maxRetries = 0,
        string $environment = 'production',
        ?string $baseUrl = null,
        array $defaultHeaders = [],
    ): Funnelchat {
        $this->sleeps = [];
        $sleeps = &$this->sleeps;

        return new Funnelchat(
            apiKey: self::TEST_API_KEY,
            environment: $environment,
            baseUrl: $baseUrl,
            maxRetries: $maxRetries,
            defaultHeaders: $defaultHeaders,
            httpClient: $http,
            sleep: static function (int $milliseconds) use (&$sleeps): void {
                $sleeps[] = $milliseconds;
            },
        );
    }

    /**
     * @return array<array-key, mixed>|null
     */
    protected static function jsonBody(RequestInterface $request): ?array
    {
        $text = (string) $request->getBody();
        if ($text === '') {
            return null;
        }
        $decoded = json_decode($text, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }
}
