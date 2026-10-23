<?php

declare(strict_types=1);

namespace Funnelchat\Core;

use Funnelchat\Funnelchat;

/**
 * Base of every resource: a group of operations of one piece of the API. The resources themselves
 * are generated (src/Resources) from spec/resources.json and the spec.
 *
 * @phpstan-import-type RequestOptions from Funnelchat
 */
abstract class AbstractResource
{
    /** The keys of the request options (`RequestOptions`). */
    private const OPTION_KEYS = ['headers', 'timeout', 'maxRetries', 'retrySend', 'idempotencyKey', 'onResponse'];

    public function __construct(protected readonly Funnelchat $client) {}

    /**
     * For a method that took only `$options` and now takes `$query` first: a `$query` with only
     * request-option keys, and no `$options`, is the call of before, so it is taken as the options.
     *
     * @param array<array-key, mixed> $query
     * @param RequestOptions          $options
     *
     * @return array{array<array-key, mixed>, RequestOptions}
     */
    protected static function queryOrOptions(array $query, array $options): array
    {
        if ($options === [] && $query !== [] && array_diff(array_keys($query), self::OPTION_KEYS) === []) {
            /** @var RequestOptions $query */
            return [[], $query];
        }

        return [$query, $options];
    }

    /**
     * Dumping a resource does not dump the whole client again.
     *
     * @return array{}
     */
    public function __debugInfo(): array
    {
        return [];
    }
}
