<?php

declare(strict_types=1);

namespace Funnelchat\Pagination;

/**
 * A lazy list: nothing is requested until it is iterated or until you ask for its first page.
 *
 * ```php
 * foreach ($funnelchat->contacts->list(['perPage' => 100]) as $contact) { … } // every item of every page
 * $page = $funnelchat->tags->list()->firstPage();                              // one page at a time
 * ```
 *
 * @template T
 *
 * @implements \IteratorAggregate<int, T>
 */
final class Paginator implements \IteratorAggregate
{
    /** @var Page<T>|null */
    private ?Page $first = null;

    /**
     * @param \Closure(array<string, mixed>): mixed $fetchPage fetches one page body given its query
     * @param array<string, mixed>                  $query     the query of the first page
     */
    public function __construct(
        private readonly \Closure $fetchPage,
        private readonly array $query = [],
    ) {}

    /**
     * The first page. Requested once: asking again reuses it.
     *
     * @return Page<T>
     */
    public function firstPage(): Page
    {
        if ($this->first === null) {
            /** @var Page<T> $page */
            $page = new Page($this->fetchPage, $this->query, ($this->fetchPage)($this->query));
            $this->first = $page;
        }

        return $this->first;
    }

    /**
     * Walks the pages, one by one.
     *
     * @return \Generator<int, Page<T>, mixed, void>
     */
    public function iterPages(): \Generator
    {
        yield from $this->firstPage()->iterPages();
    }

    /**
     * Walks every item of every page.
     *
     * @return \Generator<int, T, mixed, void>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->firstPage()->getIterator() as $item) {
            yield $item;
        }
    }
}
