<?php

declare(strict_types=1);

namespace Funnelchat\Pagination;

use Funnelchat\Exception\FunnelchatException;

/**
 * One page of a list route. Three shapes exist and all are handled:
 *
 * - page numbers (Laravel): `{data, links, meta: {current_page, last_page, …}}`, next with `page`;
 * - cursors: `{data, links, meta: {next_cursor, …}}`, next with `cursor`;
 * - plain arrays: one page with everything.
 *
 * Iterating a page with `foreach` walks its items and the items of every following page.
 *
 * @template T
 *
 * @implements \IteratorAggregate<int, T>
 */
final class Page implements \IteratorAggregate, \Countable
{
    /**
     * Items of this page.
     *
     * @var list<T>
     */
    public readonly array $data;

    /** The full response body of this page (with `meta` and `links` when the route sends them). */
    public readonly mixed $body;

    /**
     * @param \Closure(array<string, mixed>): mixed $fetchPage fetches one page body given its query
     * @param array<string, mixed>                  $query     the query of this page
     */
    public function __construct(
        private readonly \Closure $fetchPage,
        private readonly array $query,
        mixed $body,
    ) {
        $this->body = $body;
        /** @var list<T> $items */
        $items = self::extractItems($body);
        $this->data = $items;
    }

    /**
     * The query keys that ask for the next page (`['page' => 2]` or `['cursor' => '…']`), or
     * `null` on the last page. An empty page is always the last one.
     *
     * @return array<string, int|string>|null
     */
    public function nextPageQuery(): ?array
    {
        if ($this->data === []) {
            return null;
        }
        $next = self::nextPageQueryOf($this->body);
        if ($next === null) {
            return null;
        }
        foreach ($next as $key => $value) {
            $current = $this->query[$key] ?? null;
            if (!\is_scalar($current) || (string) $current !== (string) $value) {
                return $next;
            }
        }

        return null;
    }

    public function hasNextPage(): bool
    {
        return $this->nextPageQuery() !== null;
    }

    /**
     * Fetches the next page. Throws when there is none: check {@see self::hasNextPage()} first.
     *
     * @return self<T>
     */
    public function getNextPage(): self
    {
        $next = $this->nextPageQuery();
        if ($next === null) {
            throw new FunnelchatException('There are no more pages.');
        }
        $query = array_merge($this->query, $next);
        if (\array_key_exists('cursor', $next)) {
            unset($query['page']);
        }

        /** @var self<T> $page */
        $page = new self($this->fetchPage, $query, ($this->fetchPage)($query));

        return $page;
    }

    /**
     * Walks this page and the following ones, page by page.
     *
     * @return \Generator<int, self<T>, mixed, void>
     */
    public function iterPages(): \Generator
    {
        $page = $this;
        while (true) {
            yield $page;
            if (!$page->hasNextPage()) {
                return;
            }
            $page = $page->getNextPage();
        }
    }

    /**
     * Walks every item of this page and of the following ones.
     *
     * @return \Generator<int, T, mixed, void>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->iterPages() as $page) {
            foreach ($page->data as $item) {
                yield $item;
            }
        }
    }

    /** The number of items of this page (not of the whole list). */
    public function count(): int
    {
        return \count($this->data);
    }

    /**
     * The query of the next page according to a response body, or `null`.
     *
     * @return array<string, int|string>|null
     */
    public static function nextPageQueryOf(mixed $body): ?array
    {
        if (!self::isRecord($body)) {
            return null;
        }
        foreach ([$body['meta'] ?? null, $body] as $holder) {
            if (!self::isRecord($holder)) {
                continue;
            }
            if (\array_key_exists('next_cursor', $holder)) {
                $cursor = $holder['next_cursor'];

                return \is_string($cursor) && $cursor !== '' ? ['cursor' => $cursor] : null;
            }
            $current = self::toInt($holder['current_page'] ?? null);
            $last = self::toInt($holder['last_page'] ?? null);
            if ($current !== null && $last !== null) {
                return $current < $last ? ['page' => $current + 1] : null;
            }
        }
        $links = self::isRecord($body['links'] ?? null) ? $body['links'] : [];
        $nextUrl = $links['next'] ?? $body['next_page_url'] ?? null;
        if (\is_string($nextUrl) && $nextUrl !== '') {
            parse_str((string) parse_url($nextUrl, \PHP_URL_QUERY), $params);
            $cursor = $params['cursor'] ?? null;
            if (\is_string($cursor) && $cursor !== '') {
                return ['cursor' => $cursor];
            }
            $page = self::toInt($params['page'] ?? null);
            if ($page !== null) {
                return ['page' => $page];
            }
        }

        return null;
    }

    /**
     * @return list<mixed>
     */
    private static function extractItems(mixed $body): array
    {
        if (\is_array($body) && array_is_list($body)) {
            return $body;
        }
        if (\is_array($body) && isset($body['data']) && \is_array($body['data']) && array_is_list($body['data'])) {
            return $body['data'];
        }

        return [];
    }

    private static function toInt(mixed $value): ?int
    {
        if (\is_int($value)) {
            return $value;
        }
        if (\is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            return (int) $value;
        }

        return null;
    }

    /**
     * @phpstan-assert-if-true array<string, mixed> $value
     */
    private static function isRecord(mixed $value): bool
    {
        return \is_array($value) && ($value === [] || !array_is_list($value));
    }
}
