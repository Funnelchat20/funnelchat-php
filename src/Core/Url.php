<?php

declare(strict_types=1);

namespace Funnelchat\Core;

use Funnelchat\Exception\FunnelchatException;

/**
 * URL building: path parameters, the Laravel-style query and the base URL.
 *
 * @internal
 */
final class Url
{
    private function __construct() {}

    /**
     * Serializes a query the way Laravel reads it:
     *
     * - lists as `key[]=a&key[]=b` (a key already written as `key[]` is not doubled);
     * - lists of arrays and nested arrays with brackets: `filters[0][mode]=all`;
     * - booleans as `1` / `0` (Laravel's `boolean` rule rejects the strings `true` / `false`);
     * - dates (`DateTimeInterface`) as ISO 8601 in UTC, with milliseconds;
     * - `null` is left out.
     *
     * @param array<array-key, mixed> $query
     */
    public static function serializeQuery(array $query): string
    {
        $pairs = [];
        foreach ($query as $key => $value) {
            self::append($pairs, (string) $key, $value);
        }

        return implode('&', array_map(
            static fn(array $pair): string => rawurlencode($pair[0]) . '=' . rawurlencode($pair[1]),
            $pairs,
        ));
    }

    /**
     * @param list<array{string, string}> $pairs
     */
    private static function append(array &$pairs, string $key, mixed $value): void
    {
        if ($value === null) {
            return;
        }
        if (\is_array($value) && array_is_list($value)) {
            $isBracketed = str_ends_with($key, '[]');
            $scalarKey = $isBracketed ? $key : $key . '[]';
            $base = $isBracketed ? substr($key, 0, -2) : $key;
            foreach ($value as $index => $item) {
                if (\is_array($item) || $item instanceof \JsonSerializable) {
                    self::append($pairs, "{$base}[{$index}]", $item);
                } else {
                    self::append($pairs, $scalarKey, $item);
                }
            }

            return;
        }
        if (\is_array($value)) {
            foreach ($value as $child => $childValue) {
                self::append($pairs, "{$key}[{$child}]", $childValue);
            }

            return;
        }
        if ($value instanceof \DateTimeInterface) {
            $utc = \DateTimeImmutable::createFromInterface($value)->setTimezone(new \DateTimeZone('UTC'));
            $pairs[] = [$key, $utc->format('Y-m-d\TH:i:s.v\Z')];

            return;
        }
        if ($value instanceof \BackedEnum) {
            $pairs[] = [$key, (string) $value->value];

            return;
        }
        if ($value instanceof \JsonSerializable) {
            self::append($pairs, $key, $value->jsonSerialize());

            return;
        }
        if (\is_bool($value)) {
            $pairs[] = [$key, $value ? '1' : '0'];

            return;
        }
        if (\is_int($value) || \is_float($value) || \is_string($value) || $value instanceof \Stringable) {
            $pairs[] = [$key, (string) $value];

            return;
        }

        throw new FunnelchatException(\sprintf('No se puede mandar en la query el valor de "%s" (%s).', $key, get_debug_type($value)));
    }

    /**
     * Replaces the `{name}` placeholders of a path template with URL-encoded values. Throws when a
     * placeholder has no value, so a request never goes out with `{contact}` in it.
     *
     * @param array<string, mixed> $params
     */
    public static function interpolatePath(string $template, array $params): string
    {
        return (string) preg_replace_callback('/\{([^}]+)\}/', static function (array $match) use ($template, $params): string {
            $value = $params[$match[1]] ?? null;
            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            }
            if (!\is_int($value) && !\is_string($value) && !$value instanceof \Stringable) {
                throw new FunnelchatException("Falta el parámetro de ruta \"{$match[1]}\" para {$template}.");
            }
            $text = (string) $value;
            if ($text === '') {
                throw new FunnelchatException("Falta el parámetro de ruta \"{$match[1]}\" para {$template}.");
            }

            return rawurlencode($text);
        }, $template);
    }

    /** Joins the base URL and a path (`/api/v1/...`) without doubling or losing slashes. */
    public static function join(string $baseUrl, string $path, string $query): string
    {
        $base = rtrim($baseUrl, '/');
        $suffix = str_starts_with($path, '/') ? $path : '/' . $path;
        if ($query === '') {
            return $base . $suffix;
        }

        return $base . $suffix . (str_contains($suffix, '?') ? '&' : '?') . $query;
    }
}
