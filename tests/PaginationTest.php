<?php

declare(strict_types=1);

namespace Funnelchat\Tests;

use Funnelchat\Exception\FunnelchatException;
use Funnelchat\Pagination\Page;
use Funnelchat\Tests\Support\MockHttpClient;
use Funnelchat\Tests\Support\Reply;
use Funnelchat\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

final class PaginationTest extends TestCase
{
    /**
     * A Laravel length-aware page, as the list routes of conversations answer.
     *
     * @param list<mixed> $items
     */
    private static function laravelPage(array $items, int $current, int $last): ResponseInterface
    {
        return Reply::ok([
            'data' => $items,
            'links' => ['first' => '…?page=1', 'last' => "…?page={$last}", 'prev' => null, 'next' => $current < $last ? '…?page=' . ($current + 1) : null],
            'meta' => ['current_page' => $current, 'from' => 1, 'last_page' => $last, 'links' => [], 'path' => 'https://api.funnelchat.com/api/v1/tags', 'per_page' => 2, 'to' => 2, 'total' => 5],
        ]);
    }

    /**
     * @param list<mixed> $items
     */
    private static function cursorPage(array $items, ?string $nextCursor): ResponseInterface
    {
        return Reply::ok([
            'data' => $items,
            'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
            'meta' => ['path' => '…', 'per_page' => 2, 'next_cursor' => $nextCursor, 'prev_cursor' => null],
        ]);
    }

    public function testWalksEveryItemOfEveryPageWithForeach(): void
    {
        $http = new MockHttpClient(
            self::laravelPage([['id' => 1], ['id' => 2]], 1, 3),
            self::laravelPage([['id' => 3], ['id' => 4]], 2, 3),
            self::laravelPage([['id' => 5]], 3, 3),
        );

        $ids = [];
        foreach ($this->client($http)->tags->list(['perPage' => 2, 'name' => 'vip']) as $tag) {
            $ids[] = $tag['id'];
        }

        self::assertSame([1, 2, 3, 4, 5], $ids);
        self::assertSame(
            ['perPage=2&name=vip', 'perPage=2&name=vip&page=2', 'perPage=2&name=vip&page=3'],
            array_map(static fn($request): string => $request->getUri()->getQuery(), $http->requests),
        );
    }

    public function testKeysAreSequentialAcrossPages(): void
    {
        $http = new MockHttpClient(self::laravelPage([['id' => 1], ['id' => 2]], 1, 2), self::laravelPage([['id' => 3]], 2, 2));

        self::assertSame([['id' => 1], ['id' => 2], ['id' => 3]], iterator_to_array($this->client($http)->tags->list()));
    }

    public function testFollowsNextCursorAndDropsPageForCursorRoutes(): void
    {
        $http = new MockHttpClient(self::cursorPage([['id' => 11], ['id' => 12]], 'CUR2'), self::cursorPage([['id' => 13]], null));

        $ids = [];
        foreach ($this->client($http)->messages->list(2001, ['perPage' => 2, 'page' => 1]) as $message) {
            $ids[] = $message['id'];
        }

        self::assertSame([11, 12, 13], $ids);
        self::assertSame('https://api.funnelchat.com/api/v1/contacts/2001/messages?perPage=2&page=1', (string) $http->requests[0]->getUri());
        self::assertSame('https://api.funnelchat.com/api/v1/contacts/2001/messages?perPage=2&cursor=CUR2', (string) $http->requests[1]->getUri());
    }

    public function testFirstPageWithHasNextPageAndGetNextPage(): void
    {
        $http = new MockHttpClient(self::laravelPage([['id' => 1]], 1, 2), self::laravelPage([['id' => 2]], 2, 2));

        $first = $this->client($http)->contacts->list()->firstPage();
        self::assertSame([['id' => 1]], $first->data);
        self::assertCount(1, $first);
        self::assertTrue($first->hasNextPage());
        self::assertSame(['page' => 2], $first->nextPageQuery());

        $second = $first->getNextPage();
        self::assertSame([['id' => 2]], $second->data);
        self::assertFalse($second->hasNextPage());
        self::assertCount(2, $http->requests);

        $this->expectException(FunnelchatException::class);
        $this->expectExceptionMessage('There are no more pages.');
        $second->getNextPage();
    }

    public function testIsLazyAndRequestsTheFirstPageOnce(): void
    {
        $http = new MockHttpClient(self::laravelPage([], 1, 1));

        $list = $this->client($http)->tags->list();
        self::assertCount(0, $http->requests);
        $list->firstPage();
        $list->firstPage();
        self::assertCount(1, $http->requests);
    }

    public function testTreatsAPlainArrayAsASinglePage(): void
    {
        $http = new MockHttpClient(Reply::ok([['id' => 3], ['id' => 4]]));

        self::assertSame([['id' => 3], ['id' => 4]], iterator_to_array($this->client($http)->devices->list(), false));
        self::assertCount(1, $http->requests);
    }

    public function testIteratesPagesWithIterPages(): void
    {
        $http = new MockHttpClient(self::laravelPage([['id' => 1]], 1, 2), self::laravelPage([['id' => 2]], 2, 2));

        $sizes = [];
        foreach ($this->client($http)->tags->list()->iterPages() as $page) {
            $sizes[] = \count($page->data);
        }
        self::assertSame([1, 1], $sizes);
    }

    public function testStopsOnAnEmptyPageEvenIfMetaSaysThereAreMore(): void
    {
        $http = new MockHttpClient(self::laravelPage([], 1, 9));

        self::assertSame([], iterator_to_array($this->client($http)->tags->list()));
        self::assertCount(1, $http->requests);
    }

    public function testPaginatesAnyRouteWithPaginate(): void
    {
        $http = new MockHttpClient(self::laravelPage([['id' => 1]], 1, 1), Reply::ok(['data' => [['x' => 1]], 'meta' => ['current_page' => '1', 'last_page' => '1']]));
        $client = $this->client($http);

        self::assertSame([['id' => 1]], $client->paginate('/api/v1/custom-fields', ['query' => ['perPage' => 50]])->firstPage()->data);
        $untyped = $client->paginate('/api/v1/something-new', ['query' => ['page' => 1]])->firstPage();
        self::assertSame([['x' => 1]], $untyped->data);
        self::assertFalse($untyped->hasNextPage());
        self::assertSame('https://api.funnelchat.com/api/v1/something-new?page=1', (string) $http->requests[1]->getUri());
    }

    public function testThePageCarriesTheWholeBody(): void
    {
        $http = new MockHttpClient(self::laravelPage([['id' => 1]], 1, 1));

        $body = $this->client($http)->tags->list()->firstPage()->body;
        self::assertIsArray($body);
        self::assertIsArray($body['meta']);
        self::assertSame(5, $body['meta']['total']);
    }

    /**
     * @return array<string, array{mixed, array<string, int|string>|null}>
     */
    public static function bodies(): array
    {
        return [
            'meta page numbers' => [['data' => [1], 'meta' => ['current_page' => 2, 'last_page' => 3]], ['page' => 3]],
            'last page' => [['data' => [1], 'meta' => ['current_page' => 3, 'last_page' => 3]], null],
            'numeric strings (communities)' => [['data' => [1], 'meta' => ['current_page' => '1', 'last_page' => '2']], ['page' => 2]],
            'meta cursor' => [['data' => [1], 'meta' => ['next_cursor' => 'abc']], ['cursor' => 'abc']],
            'meta cursor at the end' => [['data' => [1], 'meta' => ['next_cursor' => null]], null],
            'plain Laravel paginator' => [['data' => [1], 'current_page' => 1, 'last_page' => 2], ['page' => 2]],
            'only links.next with a page' => [['data' => [1], 'links' => ['next' => 'https://api.funnelchat.com/api/v1/x?page=4']], ['page' => 4]],
            'only links.next with a cursor' => [['data' => [1], 'links' => ['next' => '/api/v1/x?cursor=zz']], ['cursor' => 'zz']],
            'next_page_url' => [['data' => [1], 'next_page_url' => 'https://h/x?page=2'], ['page' => 2]],
            'nothing' => [['data' => [1]], null],
            'a list' => [[1, 2], null],
            'not an array' => ['texto', null],
        ];
    }

    /**
     * @param array<string, int|string>|null $expected
     */
    #[DataProvider('bodies')]
    public function testNextPageQueryOf(mixed $body, ?array $expected): void
    {
        self::assertSame($expected, Page::nextPageQueryOf($body));
    }
}
