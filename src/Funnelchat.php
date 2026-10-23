<?php

declare(strict_types=1);

namespace Funnelchat;

use Funnelchat\Core\Transport;
use Funnelchat\Exception\FunnelchatException;
use Funnelchat\Generated\ResourceProperties;
use Funnelchat\Pagination\Paginator;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Client of the Funnelchat public API.
 *
 * ```php
 * $funnelchat = new \Funnelchat\Funnelchat(apiKey: getenv('FUNNELCHAT_API_KEY'));
 * foreach ($funnelchat->devices->list() as $device) { … }
 * ```
 *
 * The resources (`contacts`, `conversations`, `messages`, `templates`, `forms`, `tags`,
 * `customFields`, `groups`, `devices`, `connectLinks`, `webhooks`) are generated from the spec: see
 * {@see ResourceProperties}.
 *
 * Request options, the last argument of every method: `headers`, `timeout`, `maxRetries`,
 * `retrySend` (see {@see Core\Retry}), `onResponse`, and `idempotencyKey`: sent as the
 * `Idempotency-Key` header (up to 255 characters), which `devices->create()` and
 * `connectLinks->create()` accept. With it, the API answers the same resource to the same key and
 * body, so the SDK may also repeat that write after a timeout or a `5xx`.
 *
 * @phpstan-type RequestOptions array{
 *     headers?: array<string, string>,
 *     timeout?: float|int,
 *     maxRetries?: int,
 *     retrySend?: bool,
 *     idempotencyKey?: string,
 *     onResponse?: callable(ResponseInfo): void,
 * }
 * @phpstan-type RawRequestOptions array{
 *     path?: array<string, mixed>,
 *     query?: array<array-key, mixed>,
 *     body?: mixed,
 *     headers?: array<string, string>,
 *     timeout?: float|int,
 *     maxRetries?: int,
 *     retrySend?: bool,
 *     idempotencyKey?: string,
 *     onResponse?: callable(ResponseInfo): void,
 * }
 */
final class Funnelchat
{
    use ResourceProperties;

    /** The SDK version, sent in the `User-Agent`. */
    public const VERSION = '0.1.0';

    /** Base URL of each environment: one URL for the whole public API. */
    public const BASE_URLS = [
        'production' => 'https://api.funnelchat.com',
        'staging' => 'https://api-staging.funnelchat.com',
    ];

    /** Seconds of each attempt, when the HTTP client is Guzzle. */
    public const DEFAULT_TIMEOUT = 30.0;

    public const DEFAULT_MAX_RETRIES = 2;

    public readonly string $baseUrl;

    /** Seconds of each attempt, or `null` when the HTTP client applies its own (not Guzzle). */
    public readonly ?float $timeout;

    public readonly int $maxRetries;

    private readonly Transport $transport;

    /**
     * @param string|null                  $apiKey         `<id>|fc_live_...`; default: the `FUNNELCHAT_API_KEY` environment variable
     * @param 'production'|'staging'       $environment
     * @param string|null                  $baseUrl        overrides the URL of `environment` (for example a local edge)
     * @param float|int|null               $timeout        seconds of each attempt; default 30 with Guzzle (see the README)
     * @param int                          $maxRetries     automatic retries on `429`, `5xx` and network errors; `0` turns them off
     * @param array<string, string>        $defaultHeaders headers added to every request
     * @param ClientInterface|null         $httpClient     any PSR-18 client; default: Guzzle if installed, else discovery
     * @param (\Closure(int): void)|null   $sleep          waits the given milliseconds between retries; default `usleep`
     */
    public function __construct(
        #[\SensitiveParameter]
        ?string $apiKey = null,
        string $environment = 'production',
        ?string $baseUrl = null,
        float|int|null $timeout = null,
        int $maxRetries = self::DEFAULT_MAX_RETRIES,
        array $defaultHeaders = [],
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?\Closure $sleep = null,
    ) {
        $apiKey = self::nonEmpty($apiKey) ?? self::readEnv('FUNNELCHAT_API_KEY');
        if ($apiKey === null) {
            throw new FunnelchatException('Missing API key: pass it in `apiKey` or in the FUNNELCHAT_API_KEY environment variable.');
        }
        if (!\array_key_exists($environment, self::BASE_URLS)) {
            throw new FunnelchatException("Unknown environment: {$environment}. Use \"production\" or \"staging\".");
        }
        if ($timeout !== null && $timeout <= 0) {
            throw new FunnelchatException('`timeout` must be greater than 0.');
        }

        $sdkBuiltClient = $httpClient === null;
        $httpClient ??= self::defaultHttpClient();
        $isGuzzle = $httpClient instanceof \GuzzleHttp\ClientInterface;
        if ($timeout !== null && !$isGuzzle) {
            throw new FunnelchatException(
                'A timeout can only be applied with Guzzle: PSR-18 has no timeout. Set it in your HTTP client and do not pass `timeout`.',
            );
        }

        $this->baseUrl = rtrim($baseUrl ?? self::BASE_URLS[$environment], '/');
        $this->timeout = $timeout !== null ? (float) $timeout : ($sdkBuiltClient && $isGuzzle ? self::DEFAULT_TIMEOUT : null);
        $this->maxRetries = max(0, $maxRetries);
        $this->transport = new Transport(
            $apiKey,
            $this->baseUrl,
            $this->timeout,
            $this->maxRetries,
            $defaultHeaders,
            $httpClient,
            $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory(),
            $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory(),
            $sleep ?? static function (int $milliseconds): void {
                if ($milliseconds > 0) {
                    usleep($milliseconds * 1000);
                }
            },
        );

        $this->initResources();
    }

    /**
     * Calls any route. Path parameters go in `path`, the query in `query` and the JSON body in
     * `body`; the request options (`headers`, `timeout`, `maxRetries`, `retrySend`,
     * `idempotencyKey`) are accepted too.
     *
     * Returns the decoded JSON (associative arrays), the raw text when the answer is not JSON, or
     * `null` for an empty answer (`204`).
     *
     * ```php
     * $contact = $funnelchat->request('GET', '/api/v1/contacts/{contact}', ['path' => ['contact' => 2001]]);
     * ```
     *
     * @param RawRequestOptions $options
     */
    public function request(string $method, string $path, array $options = []): mixed
    {
        return $this->transport->send($method, $path, $options);
    }

    /**
     * Walks any list route. Iterate it with `foreach` for every item, or call `firstPage()`.
     *
     * ```php
     * foreach ($funnelchat->paginate('/api/v1/broadcasts', ['query' => ['perPage' => 100]]) as $broadcast) { … }
     * ```
     *
     * @param RawRequestOptions $options
     *
     * @return Paginator<mixed>
     */
    public function paginate(string $path, array $options = []): Paginator
    {
        $query = $options['query'] ?? [];
        unset($options['query']);
        $transport = $this->transport;

        /** @var array<string, mixed> $firstQuery */
        $firstQuery = $query;

        return new Paginator(
            static fn(array $pageQuery): mixed => $transport->send('GET', $path, ['query' => $pageQuery] + $options),
            $firstQuery,
        );
    }

    /**
     * The last answer of the API through this client, success or error: its status, its headers and
     * its `requestId` (the `X-Request-Id` to cite when you report a problem). `null` before the
     * first answer. For one call in particular, pass `onResponse` in its options.
     *
     * @phpstan-impure
     */
    public function lastResponse(): ?ResponseInfo
    {
        return $this->transport->lastResponse();
    }

    /**
     * What `var_dump($client)` shows: the configuration, never the key.
     *
     * @return array{baseUrl: string, timeout: float|null, maxRetries: int}
     */
    public function __debugInfo(): array
    {
        return ['baseUrl' => $this->baseUrl, 'timeout' => $this->timeout, 'maxRetries' => $this->maxRetries];
    }

    private static function defaultHttpClient(): ClientInterface
    {
        // Guzzle is the one client the SDK knows how to give a timeout. Anything else comes from
        // discovery with its own configuration.
        // Guzzle 7 is the first one that implements PSR-18 (and the first with MAJOR_VERSION).
        if (class_exists(\GuzzleHttp\Client::class) && \defined(\GuzzleHttp\ClientInterface::class . '::MAJOR_VERSION')) {
            return new \GuzzleHttp\Client();
        }

        return Psr18ClientDiscovery::find();
    }

    private static function readEnv(string $name): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        return \is_string($value) ? self::nonEmpty($value) : null;
    }

    private static function nonEmpty(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
