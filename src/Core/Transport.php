<?php

declare(strict_types=1);

namespace Funnelchat\Core;

use Funnelchat\Exception\ApiConnectionException;
use Funnelchat\Exception\ApiConnectionTimeoutException;
use Funnelchat\Exception\ApiException;
use Funnelchat\Exception\FunnelchatException;
use Funnelchat\Exception\SendErrors;
use Funnelchat\Funnelchat;
use Funnelchat\ResponseInfo;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Sends one logical request: builds it, retries it when the policy allows, and turns the answer
 * into a value or an exception.
 *
 * The API key is not a property of this object: it lives in a static map keyed by the object, so
 * `var_dump`, `print_r`, `var_export`, `dd()` or `serialize` of a client never show it.
 *
 * @internal
 *
 * @phpstan-import-type RawRequestOptions from Funnelchat
 */
final class Transport
{
    /** @var \WeakMap<self, string>|null */
    private static ?\WeakMap $keys = null;

    private ?ResponseInfo $lastResponse = null;

    /**
     * @param array<string, string>  $defaultHeaders
     * @param \Closure(int): void    $sleep          waits the given milliseconds
     * @param float|null             $timeout        seconds; `null` = the HTTP client's own setting
     */
    public function __construct(
        #[\SensitiveParameter]
        string $apiKey,
        public readonly string $baseUrl,
        public readonly ?float $timeout,
        public readonly int $maxRetries,
        private readonly array $defaultHeaders,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly \Closure $sleep,
    ) {
        self::$keys ??= new \WeakMap();
        self::$keys[$this] = $apiKey;
    }

    /**
     * @param RawRequestOptions $options
     */
    public function send(string $method, string $template, array $options): mixed
    {
        $verb = strtoupper($method);
        $pathname = Url::interpolatePath($template, $options['path'] ?? []);
        $url = Url::join($this->baseUrl, $pathname, Url::serializeQuery($options['query'] ?? []));
        $errorUrl = Url::join($this->baseUrl, explode('?', $pathname, 2)[0], '');
        $maxRetries = max(0, $options['maxRetries'] ?? $this->maxRetries);
        $retriesAllowed = ($options['retrySend'] ?? false) === true || !Retry::isSendRequest($verb, explode('?', $pathname, 2)[0]);
        $onResponse = $options['onResponse'] ?? null;
        $timeout = $this->timeoutFor($options);
        [$body, $contentType] = $this->encodeBody(\array_key_exists('body', $options) ? $options['body'] : null);

        $headers = [
            'Accept' => 'application/json',
            'User-Agent' => \sprintf('funnelchat-php/%s php/%s', Funnelchat::VERSION, \PHP_VERSION),
        ];
        if ($contentType !== null) {
            $headers['Content-Type'] = $contentType;
        }
        $headers = array_merge($headers, $this->defaultHeaders, $options['headers'] ?? []);

        for ($attempt = 0;; ++$attempt) {
            $canRetry = $retriesAllowed && $attempt < $maxRetries;
            $failure = null;
            $response = null;
            $text = '';
            try {
                [$response, $text] = $this->attempt($this->build($verb, $url, $headers, $body), $timeout);
            } catch (ClientExceptionInterface $exception) {
                $failure = $exception;
            } catch (\RuntimeException $exception) {
                // Reading the body of a response can fail half-way (connection reset).
                $failure = $exception;
            }

            if ($failure !== null || $response === null) {
                $error = self::connectionError($failure, $timeout);
                if ($canRetry && Retry::isRetryable($verb, null)) {
                    ($this->sleep)(Retry::delayMs($attempt, null) ?? 0);

                    continue;
                }

                throw $error;
            }

            $status = $response->getStatusCode();
            $parsed = self::parseBody($response, $text);
            /** @var array<string, list<string>> $responseHeaders */
            $responseHeaders = $response->getHeaders();
            $info = ResponseInfo::fromHeaders($status, $responseHeaders, $verb, $errorUrl);
            $this->lastResponse = $info;
            if ($status >= 200 && $status < 300) {
                if ($onResponse !== null) {
                    $onResponse($info);
                }

                return $parsed;
            }

            $error = ApiException::from($status, $parsed, $responseHeaders, $verb, $errorUrl);
            if ($canRetry && Retry::isRetryable($verb, $status) && !\in_array($error->errorCode, SendErrors::NOT_RETRYABLE, true)) {
                $delay = Retry::delayMs($attempt, $error->retryAfter);
                if ($delay !== null) {
                    ($this->sleep)($delay);

                    continue;
                }
            }
            if ($onResponse !== null) {
                $onResponse($info);
            }

            throw $error;
        }
    }

    /** The last answer of the API through this client (also an error), or `null` before the first one. */
    public function lastResponse(): ?ResponseInfo
    {
        return $this->lastResponse;
    }

    /** @return array{} */
    public function __debugInfo(): array
    {
        return [];
    }

    /** @return never */
    public function __serialize(): array
    {
        throw new FunnelchatException('El cliente de Funnelchat no se puede serializar.');
    }

    private function __clone() {}

    /**
     * @param RawRequestOptions $options
     */
    private function timeoutFor(array $options): ?float
    {
        $timeout = isset($options['timeout']) ? (float) $options['timeout'] : $this->timeout;
        if ($timeout !== null && !$this->httpClient instanceof GuzzleClientInterface) {
            throw new FunnelchatException(
                'El timeout solo se puede aplicar con Guzzle: PSR-18 no tiene timeout. Configúralo en tu cliente HTTP y no pases `timeout`.',
            );
        }

        return $timeout;
    }

    /**
     * @param array<string, string> $headers
     */
    private function build(string $method, string $url, array $headers, ?string $body): RequestInterface
    {
        $request = $this->requestFactory->createRequest($method, $url);
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        $request = $request->withHeader('Authorization', 'Bearer ' . (self::$keys[$this] ?? ''));
        if ($body !== null) {
            $request = $request->withBody($this->streamFactory->createStream($body));
        }

        return $request;
    }

    /**
     * @return array{ResponseInterface, string}
     *
     * @throws ClientExceptionInterface
     */
    private function attempt(RequestInterface $request, ?float $timeout): array
    {
        if ($timeout !== null && $this->httpClient instanceof GuzzleClientInterface) {
            // The same options Guzzle's own sendRequest() uses, plus the timeout.
            $response = $this->httpClient->send($request, ['timeout' => $timeout, 'http_errors' => false, 'allow_redirects' => false]);
        } else {
            $response = $this->httpClient->sendRequest($request);
        }

        return [$response, (string) $response->getBody()];
    }

    /**
     * @return array{string|null, string|null} the encoded body and its content type
     */
    private function encodeBody(mixed $body): array
    {
        if ($body === null) {
            return [null, null];
        }
        if ($body instanceof StreamInterface) {
            return [(string) $body, null];
        }
        if (\is_string($body)) {
            return [$body, 'application/json'];
        }
        if ($body === []) {
            // An empty PHP array is an empty JSON object for the API, never a list.
            return ['{}', 'application/json'];
        }
        try {
            return [json_encode($body, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRESERVE_ZERO_FRACTION), 'application/json'];
        } catch (\JsonException $exception) {
            throw new FunnelchatException('No se pudo codificar el cuerpo como JSON: ' . $exception->getMessage(), 0, $exception);
        }
    }

    private static function parseBody(ResponseInterface $response, string $text): mixed
    {
        if ($text === '') {
            return null;
        }
        $type = strtolower($response->getHeaderLine('Content-Type'));
        if (str_contains($type, 'json') || preg_match('/^\s*[\[{]/', $text) === 1) {
            try {
                return json_decode($text, true, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return $text;
            }
        }

        return $text;
    }

    private static function connectionError(?\Throwable $cause, ?float $timeout): ApiConnectionException
    {
        if ($cause !== null && self::isTimeout($cause)) {
            return new ApiConnectionTimeoutException($timeout, $cause);
        }

        return new ApiConnectionException('No se pudo conectar con la API de Funnelchat.', $cause);
    }

    /**
     * Whether the HTTP client gave up because of a timeout: a `*Timeout*` exception class (Guzzle 8:
     * `ConnectTimeoutException`, `NetworkTimeoutException`, `ResponseTimeoutException`), cURL's
     * `errno` 28 in the handler context (Guzzle 7), or a message that says so (other clients). The
     * exceptions it wraps count too.
     */
    private static function isTimeout(\Throwable $cause): bool
    {
        for ($current = $cause; $current !== null; $current = $current->getPrevious()) {
            if (str_contains((new \ReflectionClass($current))->getShortName(), 'Timeout')) {
                return true;
            }
            if (method_exists($current, 'getHandlerContext')) {
                $context = $current->getHandlerContext();
                if (\is_array($context) && ($context['errno'] ?? null) === 28) { // CURLE_OPERATION_TIMEDOUT
                    return true;
                }
            }
            if (preg_match('/\btimed?\s?out\b|\btimeout\b/i', $current->getMessage()) === 1) {
                return true;
            }
        }

        return false;
    }
}
