<?php

declare(strict_types=1);

namespace Funnelchat\Tests;

use Funnelchat\Funnelchat;
use Funnelchat\Tests\Fixtures\PortalSignatureVectors as V;
use Funnelchat\Tests\Support\MockHttpClient;
use Funnelchat\Tests\Support\TestCase;
use Funnelchat\Webhook;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The API key and the webhook secret never show in a stack trace, also on PHP 8.1, which has no
 * `#[\SensitiveParameter]`: a trace shows each argument as it is when the exception is created.
 */
final class StackTracesTest extends TestCase
{
    /**
     * Calls that create an exception after receiving the secret, and the secret.
     *
     * @return array<string, array{\Closure(): \Throwable, string}>
     */
    public static function failures(): array
    {
        $signature = 't=' . V::FIXED_T . ',v1=' . str_repeat('0', 64);

        return [
            'the client, with a wrong timeout' => [
                static function (): \Throwable {
                    try {
                        new Funnelchat(apiKey: self::TEST_API_KEY, timeout: -1, httpClient: new MockHttpClient());
                    } catch (\Throwable $exception) {
                        return $exception;
                    }
                    self::fail('should have thrown');
                },
                self::TEST_API_KEY,
            ],
            'Webhook::constructEvent()' => [
                static function () use ($signature): \Throwable {
                    try {
                        Webhook::constructEvent(V::BODY, $signature, V::SECRET, now: V::FIXED_T);
                    } catch (\Throwable $exception) {
                        return $exception;
                    }
                    self::fail('should have thrown');
                },
                V::SECRET,
            ],
            'Webhook::check()' => [
                static fn(): \Throwable => Webhook::check(V::BODY, $signature, V::SECRET, now: V::FIXED_T) ?? self::fail('should have failed'),
                V::SECRET,
            ],
            '$funnelchat->webhooks->constructEvent()' => [
                static function () use ($signature): \Throwable {
                    $client = new Funnelchat(apiKey: self::TEST_API_KEY, httpClient: new MockHttpClient());
                    try {
                        $client->webhooks->constructEvent(V::BODY, $signature, V::SECRET, now: V::FIXED_T);
                    } catch (\Throwable $exception) {
                        return $exception;
                    }
                    self::fail('should have thrown');
                },
                V::SECRET,
            ],
        ];
    }

    /**
     * @param \Closure(): \Throwable $fail
     */
    #[DataProvider('failures')]
    public function testTheSecretIsNotAnArgumentOfAnyFrame(\Closure $fail, string $secret): void
    {
        // Production php.ini leaves the arguments out of traces; this test needs them in.
        $previous = ini_set('zend.exception_ignore_args', '0');
        try {
            $exception = $fail();
        } finally {
            ini_set('zend.exception_ignore_args', $previous === false ? '1' : $previous);
        }

        // The frames of the SDK (this test and PHPUnit hold the secret as their own arguments).
        $frames = array_filter($exception->getTrace(), static function (array $frame): bool {
            $class = $frame['class'] ?? '';

            return str_starts_with($class, 'Funnelchat\\') && !str_starts_with($class, 'Funnelchat\\Tests\\');
        });
        self::assertNotSame([], array_filter($frames, static fn(array $frame): bool => ($frame['args'] ?? []) !== []), 'the trace has the arguments');
        foreach ($frames as $frame) {
            foreach ($frame['args'] ?? [] as $argument) {
                // From PHP 8.2 on, a #[\SensitiveParameter] argument is wrapped, but still holds its value.
                $value = $argument instanceof \SensitiveParameterValue ? $argument->getValue() : $argument;
                self::assertNotSame($secret, $value, ($frame['class'] ?? '') . ($frame['type'] ?? '') . $frame['function']);
            }
        }
        // Nor inside an object the SDK passes instead (a property would show in var_export).
        self::assertStringNotContainsString($secret, var_export(array_map(static fn(array $frame): mixed => $frame['args'] ?? [], $frames), true));
    }
}
