<?php

declare(strict_types=1);

namespace Funnelchat\Tests;

use Funnelchat\Core\Url;
use Funnelchat\Exception\ApiConnectionTimeoutException;
use Funnelchat\Exception\FunnelchatException;
use Funnelchat\Funnelchat;
use Funnelchat\Tests\Support\MockHttpClient;
use Funnelchat\Tests\Support\Reply;
use Funnelchat\Tests\Support\TestCase;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class RequestsTest extends TestCase
{
    // ---- Configuration ---------------------------------------------------------------------------

    public function testUsesProductionByDefaultStagingOnRequestAndBaseUrlOverBoth(): void
    {
        $http = new MockHttpClient(Reply::ok([]), Reply::ok([]), Reply::ok([]));
        $this->client($http)->request('GET', '/api/v1/devices');
        $this->client($http, environment: 'staging')->request('GET', '/api/v1/devices');
        $this->client($http, environment: 'staging', baseUrl: 'http://localhost:8011/')->request('GET', '/api/v1/devices');

        self::assertSame(
            ['https://api.funnelchat.com/api/v1/devices', 'https://api-staging.funnelchat.com/api/v1/devices', 'http://localhost:8011/api/v1/devices'],
            array_map(static fn(RequestInterface $request): string => (string) $request->getUri(), $http->requests),
        );
        self::assertSame(
            ['production' => 'https://api.funnelchat.com', 'staging' => 'https://api-staging.funnelchat.com'],
            (new \ReflectionClassConstant(Funnelchat::class, 'BASE_URLS'))->getValue(),
        );
    }

    public function testReadsTheKeyFromFunnelchatApiKey(): void
    {
        $previous = getenv('FUNNELCHAT_API_KEY');
        putenv('FUNNELCHAT_API_KEY=2|fc_live_from_env');
        try {
            $http = new MockHttpClient(Reply::ok([]));
            (new Funnelchat(httpClient: $http))->request('GET', '/api/v1/devices');
            self::assertSame('Bearer 2|fc_live_from_env', $http->lastRequest()->getHeaderLine('Authorization'));
        } finally {
            putenv($previous === false ? 'FUNNELCHAT_API_KEY' : "FUNNELCHAT_API_KEY={$previous}");
        }
    }

    public function testFailsEarlyWithoutAKey(): void
    {
        $previous = getenv('FUNNELCHAT_API_KEY');
        putenv('FUNNELCHAT_API_KEY=');
        try {
            $this->expectException(FunnelchatException::class);
            $this->expectExceptionMessage('Missing API key');
            new Funnelchat(apiKey: '  ', httpClient: new MockHttpClient());
        } finally {
            putenv($previous === false ? 'FUNNELCHAT_API_KEY' : "FUNNELCHAT_API_KEY={$previous}");
        }
    }

    public function testFailsEarlyWithAnUnknownEnvironment(): void
    {
        $this->expectException(FunnelchatException::class);
        $this->expectExceptionMessage('Unknown environment');
        // Through reflection: the PHPDoc of `environment` already rejects 'dev' statically.
        (new \ReflectionClass(Funnelchat::class))->newInstanceArgs(['apiKey' => self::TEST_API_KEY, 'environment' => 'dev', 'httpClient' => new MockHttpClient()]);
    }

    public function testNeverExposesTheKeyWhenTheClientIsDumpedOrEncoded(): void
    {
        $client = $this->client(new MockHttpClient());

        ob_start();
        var_dump($client);
        $dump = (string) ob_get_clean();
        self::assertStringNotContainsString('fc_live', $dump);
        self::assertStringNotContainsString('fc_live', print_r($client, true));
        // The transport is the object that sends the key: not even var_export (which ignores
        // __debugInfo) shows it. The client itself is circular (client -> resource -> client).
        $transport = (new \ReflectionProperty(Funnelchat::class, 'transport'))->getValue($client);
        self::assertStringNotContainsString('fc_live', var_export($transport, true));
        self::assertStringNotContainsString('fc_live', json_encode($client, \JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('fc_live', print_r((array) $client, true));
        self::assertSame(['baseUrl' => 'https://api.funnelchat.com', 'timeout' => null, 'maxRetries' => 0], $client->__debugInfo());

        $this->expectException(FunnelchatException::class);
        serialize($client);
    }

    public function testTheTimeoutNeedsGuzzle(): void
    {
        $this->expectException(FunnelchatException::class);
        $this->expectExceptionMessage('A timeout can only be applied with Guzzle');
        new Funnelchat(apiKey: self::TEST_API_KEY, timeout: 5, httpClient: new MockHttpClient());
    }

    public function testThePerRequestTimeoutNeedsGuzzleToo(): void
    {
        $http = new MockHttpClient(Reply::ok());

        $this->expectException(FunnelchatException::class);
        try {
            $this->client($http)->request('GET', '/api/v1/tags', ['timeout' => 2]);
        } finally {
            self::assertCount(0, $http->requests);
        }
    }

    // ---- Guzzle ------------------------------------------------------------------------------------

    public function testBuildsGuzzleWithA30SecondTimeoutWhenNoClientIsGiven(): void
    {
        $client = new Funnelchat(apiKey: self::TEST_API_KEY);

        self::assertSame(30.0, $client->timeout);
        self::assertSame(Funnelchat::DEFAULT_MAX_RETRIES, $client->maxRetries);
    }

    public function testPassesTheTimeoutToGuzzleAndThePerRequestOneOverIt(): void
    {
        $mock = new MockHandler([new Response(200, [], '{"data":[]}'), new Response(200, [], '{"data":[]}')]);
        $guzzle = new GuzzleClient(['handler' => HandlerStack::create($mock)]);
        $client = new Funnelchat(apiKey: self::TEST_API_KEY, timeout: 5, httpClient: $guzzle);

        $client->request('GET', '/api/v1/tags');
        $options = $mock->getLastOptions();
        self::assertSame(5.0, $options['timeout'] ?? null);
        self::assertFalse($options['http_errors'] ?? null);

        $client->request('GET', '/api/v1/tags', ['timeout' => 1.5]);
        $options = $mock->getLastOptions();
        self::assertSame(1.5, $options['timeout'] ?? null);
    }

    public function testLeavesYourGuzzleConfigurationAloneWhenYouPassNoTimeout(): void
    {
        $mock = new MockHandler([new Response(200, [], '{}')]);
        $client = new Funnelchat(apiKey: self::TEST_API_KEY, httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]));

        $client->request('GET', '/api/v1/tags');
        self::assertNull($client->timeout);
        self::assertArrayNotHasKey('timeout', $mock->getLastOptions());
    }

    public function testTurnsAGuzzleTimeoutIntoApiConnectionTimeoutException(): void
    {
        $mock = new MockHandler([
            static function (RequestInterface $request): never {
                // The two-argument constructor exists in Guzzle 7 and 8.
                throw new ConnectException('cURL error 28: Operation timed out after 2001 milliseconds', $request);
            },
        ]);
        $client = new Funnelchat(apiKey: self::TEST_API_KEY, timeout: 2, maxRetries: 0, httpClient: new GuzzleClient(['handler' => HandlerStack::create($mock)]));

        try {
            $client->request('GET', '/api/v1/tags');
            self::fail('should have thrown');
        } catch (ApiConnectionTimeoutException $exception) {
            self::assertSame(2.0, $exception->timeout);
            self::assertSame('The Funnelchat API did not answer within 2 s.', $exception->getMessage());
            self::assertInstanceOf(ConnectException::class, $exception->getPrevious());
        }
    }

    // ---- Request building ----------------------------------------------------------------------

    public function testSendsTheAuthAcceptUserAgentAndContentTypeHeaders(): void
    {
        $http = new MockHttpClient(Reply::json(201, ['id' => 7]));

        $this->client($http, defaultHeaders: ['X-Trace' => 'abc', 'Authorization' => 'Bearer otra'])
            ->tags->create(['name' => 'VIP'], ['headers' => ['X-Extra' => '1', 'Authorization' => 'Bearer otra']]);

        $request = $http->lastRequest();
        self::assertSame('POST', $request->getMethod());
        self::assertSame('Bearer ' . self::TEST_API_KEY, $request->getHeaderLine('Authorization'));
        self::assertSame('application/json', $request->getHeaderLine('Accept'));
        self::assertSame('application/json', $request->getHeaderLine('Content-Type'));
        self::assertSame('funnelchat-php/' . Funnelchat::VERSION . ' php/' . \PHP_VERSION, $request->getHeaderLine('User-Agent'));
        self::assertSame('abc', $request->getHeaderLine('X-Trace'));
        self::assertSame('1', $request->getHeaderLine('X-Extra'));
        self::assertSame(['name' => 'VIP'], self::jsonBody($request));
    }

    public function testDoesNotSendAContentTypeWithoutABody(): void
    {
        $http = new MockHttpClient(Reply::ok([]));

        $this->client($http)->contacts->retrieve(2001);
        self::assertFalse($http->lastRequest()->hasHeader('Content-Type'));
        self::assertSame('', (string) $http->lastRequest()->getBody());
    }

    public function testInterpolatesAndEncodesPathParameters(): void
    {
        $http = new MockHttpClient(Reply::ok([]));

        $this->client($http)->groups->metadata('120363000000000001@g.us', ['device_id' => 3, 'group_uid' => '120363000000000001@g.us']);
        self::assertSame(
            'https://api.funnelchat.com/api/v1/groups/120363000000000001%40g.us/metadata?device_id=3&group_uid=120363000000000001%40g.us',
            (string) $http->lastRequest()->getUri(),
        );
    }

    public function testRefusesToSendARequestWithAMissingPathParameter(): void
    {
        $http = new MockHttpClient(Reply::ok([]));

        try {
            $this->client($http)->request('GET', '/api/v1/contacts/{contact}', ['path' => []]);
            self::fail('should have thrown');
        } catch (FunnelchatException $exception) {
            self::assertStringContainsString('"contact"', $exception->getMessage());
        }
        self::assertCount(0, $http->requests);
    }

    public function testReturnsNullForA204(): void
    {
        $http = new MockHttpClient(Reply::noContent(), Reply::noContent());

        self::assertNull($this->client($http)->request('DELETE', '/api/v1/webhooks/{id}', ['path' => ['id' => '00000000-0000-4000-8000-000000000001']]));
        $this->client($http)->webhooks->delete('00000000-0000-4000-8000-000000000001');
        self::assertSame('DELETE', $http->lastRequest()->getMethod());
    }

    public function testCallsARouteOutsideTheSpecWithTheGenericRequest(): void
    {
        $http = new MockHttpClient(Reply::ok(['ok' => true]));

        $result = $this->client($http)->request('POST', '/api/v1/groups/{group}/something-new', [
            'path' => ['group' => 'g1'],
            'query' => ['dry_run' => true],
            'body' => ['a' => 1],
        ]);
        self::assertSame(['ok' => true], $result);
        self::assertSame('https://api.funnelchat.com/api/v1/groups/g1/something-new?dry_run=1', (string) $http->lastRequest()->getUri());
    }

    public function testSendsAnEmptyArrayAsAnEmptyObjectAndAnOmittedBodyAsNoBody(): void
    {
        $http = new MockHttpClient(Reply::ok([]), Reply::ok([]));
        $client = $this->client($http);

        $client->conversations->markAllRead([]);
        self::assertSame('{}', (string) $http->requests[0]->getBody());
        $client->conversations->markAllRead();
        self::assertSame('', (string) $http->requests[1]->getBody());
    }

    public function testReturnsTheTextOfAnAnswerThatIsNotJson(): void
    {
        $http = new MockHttpClient(Reply::text(200, 'pong', ['Content-Type' => 'text/plain']));

        self::assertSame('pong', $this->client($http)->request('GET', '/api/v1/ping'));
    }

    /**
     * @return array<string, array{array<array-key, mixed>, string}>
     */
    public static function queries(): array
    {
        return [
            'scalars' => [['perPage' => 25, 'name' => 'Ana'], 'perPage=25&name=Ana'],
            'key already with []' => [['tag_ids[]' => [1, 2]], 'tag_ids%5B%5D=1&tag_ids%5B%5D=2'],
            'list' => [['tag_ids' => [1, 2]], 'tag_ids%5B%5D=1&tag_ids%5B%5D=2'],
            'booleans' => [['withContactCount' => true, 'noCampaign' => false], 'withContactCount=1&noCampaign=0'],
            'null is left out' => [['b' => null, 'c' => 'x'], 'c=x'],
            'list of objects' => [['tag_filters' => [['mode' => 'all', 'tag_ids' => [3]]]], 'tag_filters%5B0%5D%5Bmode%5D=all&tag_filters%5B0%5D%5Btag_ids%5D%5B%5D=3'],
            'nested object' => [['filters' => ['campaign_ids' => ['abc123']]], 'filters%5Bcampaign_ids%5D%5B%5D=abc123'],
            'date' => [['created_from' => new \DateTimeImmutable('2026-10-01T00:00:00Z')], 'created_from=2026-10-01T00%3A00%3A00.000Z'],
            'date in another zone, in UTC' => [['created_from' => new \DateTimeImmutable('2026-10-01T00:00:00-03:00')], 'created_from=2026-10-01T03%3A00%3A00.000Z'],
        ];
    }

    /**
     * @param array<array-key, mixed> $query
     */
    #[DataProvider('queries')]
    public function testSerializesTheQueryTheWayLaravelReadsIt(array $query, string $expected): void
    {
        self::assertSame($expected, Url::serializeQuery($query));
    }

    // ---- Resources -----------------------------------------------------------------------------

    /**
     * @param \Closure(Funnelchat): mixed $run
     *
     * @return array{method: string, path: string, query: string, body: array<array-key, mixed>|null}
     */
    private function capture(\Closure $run, ?ResponseInterface $reply = null): array
    {
        $http = new MockHttpClient($reply ?? Reply::ok([]));
        $run($this->client($http));
        $request = $http->lastRequest();

        return ['method' => $request->getMethod(), 'path' => $request->getUri()->getPath(), 'query' => $request->getUri()->getQuery(), 'body' => self::jsonBody($request)];
    }

    public function testMessagesSendFillsContactIdFromTheRouteAndCarriesClientReference(): void
    {
        self::assertSame(
            [
                'method' => 'POST',
                'path' => '/api/v1/contacts/2001/message',
                'query' => '',
                'body' => ['device_id' => 3, 'phone' => '5491100000000', 'text' => '¡Hola! Te paso la info…', 'client_reference' => 'pedido-123', 'contact_id' => 2001],
            ],
            $this->capture(static fn(Funnelchat $c) => $c->messages->send(2001, [
                'device_id' => 3,
                'phone' => '5491100000000',
                'text' => '¡Hola! Te paso la info…',
                'client_reference' => 'pedido-123',
            ]), Reply::noContent()),
        );
        // A contact_id you pass is kept.
        self::assertSame(77, $this->capture(static fn(Funnelchat $c) => $c->messages->send(2001, ['device_id' => 3, 'contact_id' => 77]), Reply::noContent())['body']['contact_id'] ?? null);
    }

    public function testTemplatesSend(): void
    {
        self::assertSame(
            ['method' => 'POST', 'path' => '/api/v1/devices/3/templates/45/send-template', 'query' => '', 'body' => ['phone' => '5491100000000', 'contact_id' => 2001, 'template_variables' => ['body' => ['Lucía']]]],
            $this->capture(static fn(Funnelchat $c) => $c->templates->send(3, '45', ['phone' => '5491100000000', 'contact_id' => 2001, 'template_variables' => ['body' => ['Lucía']]])),
        );
    }

    public function testContactsMetaWindowUpsertAndDelete(): void
    {
        self::assertSame(
            ['method' => 'GET', 'path' => '/api/v1/contacts/2001/meta-window', 'query' => 'device_id=3', 'body' => null],
            $this->capture(static fn(Funnelchat $c) => $c->contacts->metaWindow(2001, ['device_id' => 3])),
        );
        self::assertSame(
            ['method' => 'POST', 'path' => '/api/v1/contacts/update-or-create', 'query' => '', 'body' => ['phone' => '+5491100000099', 'name' => 'Ana']],
            $this->capture(static fn(Funnelchat $c) => $c->contacts->upsert(['phone' => '+5491100000099', 'name' => 'Ana'])),
        );
        self::assertSame(
            ['method' => 'POST', 'path' => '/api/v1/contacts/delete', 'query' => '', 'body' => ['contactIds' => [1, 2]]],
            $this->capture(static fn(Funnelchat $c) => $c->contacts->delete(['contactIds' => [1, 2]])),
        );
    }

    public function testMessagesReactAndRemoveReaction(): void
    {
        self::assertSame(
            ['method' => 'PUT', 'path' => '/api/v1/contacts/2001/messages/99/reaction', 'query' => '', 'body' => ['emoji' => '👍']],
            $this->capture(static fn(Funnelchat $c) => $c->messages->react(2001, 99, ['emoji' => '👍']), Reply::noContent()),
        );
        self::assertSame(
            ['method' => 'DELETE', 'path' => '/api/v1/contacts/2001/messages/99/reaction', 'query' => '', 'body' => null],
            $this->capture(static fn(Funnelchat $c) => $c->messages->removeReaction(2001, 99), Reply::noContent()),
        );
    }

    public function testWebhookSubscriptionsPendingOfTheSpec(): void
    {
        $id = '00000000-0000-4000-8000-000000000001';
        self::assertSame(
            ['method' => 'POST', 'path' => '/api/v1/webhooks', 'query' => '', 'body' => ['url' => 'https://example.com/hooks', 'events' => ['message.received', 'message.status_changed']]],
            $this->capture(static fn(Funnelchat $c) => $c->webhooks->create(['url' => 'https://example.com/hooks', 'events' => ['message.received', 'message.status_changed']]), Reply::json(201, [])),
        );
        self::assertSame(['PUT', "/api/v1/webhooks/{$id}"], \array_slice(array_values($this->capture(static fn(Funnelchat $c) => $c->webhooks->update($id, ['url' => 'https://example.com/v2', 'events' => ['message.received']]))), 0, 2));
        self::assertSame(['POST', "/api/v1/webhooks/{$id}/rotate-secret"], \array_slice(array_values($this->capture(static fn(Funnelchat $c) => $c->webhooks->rotateSecret($id))), 0, 2));
        self::assertSame(['POST', "/api/v1/webhooks/{$id}/test"], \array_slice(array_values($this->capture(static fn(Funnelchat $c) => $c->webhooks->test($id))), 0, 2));
        self::assertSame(
            ['method' => 'GET', 'path' => "/api/v1/webhooks/{$id}/deliveries", 'query' => 'per_page=50', 'body' => null],
            $this->capture(static fn(Funnelchat $c) => $c->webhooks->listDeliveries($id, ['per_page' => 50])->firstPage()),
        );
        self::assertSame(['GET', '/api/v1/webhooks/events'], \array_slice(array_values($this->capture(static fn(Funnelchat $c) => $c->webhooks->events())), 0, 2));
        self::assertSame(['GET', '/api/v1/webhooks'], \array_slice(array_values($this->capture(static fn(Funnelchat $c) => $c->webhooks->list()->firstPage())), 0, 2));
    }

    /**
     * @return array<string, array{\Closure(Funnelchat): mixed, string, string}>
     */
    public static function resourceCalls(): array
    {
        return [
            'devices->retrieve' => [static fn(Funnelchat $c) => $c->devices->retrieve(3), 'GET', '/api/v1/devices/3'],
            'devices->status' => [static fn(Funnelchat $c) => $c->devices->status(3), 'GET', '/api/v1/devices/3/status'],
            'devices->checkPhone' => [static fn(Funnelchat $c) => $c->devices->checkPhone(3, ['phone' => '5491100000000']), 'GET', '/api/v1/devices/3/check-phone'],
            'groups->retrieve' => [static fn(Funnelchat $c) => $c->groups->retrieve('xK9mQz'), 'GET', '/api/v1/groups/xK9mQz'],
            'groups->listMessages' => [static fn(Funnelchat $c) => $c->groups->listMessages('xK9mQz')->firstPage(), 'GET', '/api/v1/groups/xK9mQz/messages'],
            'groups->create' => [static fn(Funnelchat $c) => $c->groups->create(['device_id' => 4, 'name' => 'VIP', 'participants' => ['5491100000000']]), 'POST', '/api/v1/groups'],
            'groups->action' => [static fn(Funnelchat $c) => $c->groups->action('5b1f3c2e-7d4a-4f8e-9a6b-1c2d3e4f5a6b'), 'GET', '/api/v1/groups/actions/5b1f3c2e-7d4a-4f8e-9a6b-1c2d3e4f5a6b'],
            'groups->updateName' => [static fn(Funnelchat $c) => $c->groups->updateName('xK9mQz', ['device_id' => 4, 'name' => 'VIP']), 'PUT', '/api/v1/groups/xK9mQz/name'],
            'groups->inviteLink' => [static fn(Funnelchat $c) => $c->groups->inviteLink('xK9mQz', ['device_id' => 4]), 'GET', '/api/v1/groups/xK9mQz/invite-link/live'],
            'groups->approveJoinRequests' => [static fn(Funnelchat $c) => $c->groups->approveJoinRequests('xK9mQz', ['device_id' => 3, 'phones' => ['5491100000000']]), 'POST', '/api/v1/groups/xK9mQz/join-requests/approve'],
            'messages->get' => [static fn(Funnelchat $c) => $c->messages->get(2001, 9001), 'GET', '/api/v1/contacts/2001/messages/9001'],
            'forms->retrieve' => [static fn(Funnelchat $c) => $c->forms->retrieve(31), 'GET', '/api/v1/forms/31'],
            'forms->publish' => [static fn(Funnelchat $c) => $c->forms->publish(31), 'POST', '/api/v1/forms/31/publish'],
            'forms->removeNumber' => [static fn(Funnelchat $c) => $c->forms->removeNumber(31, 3), 'DELETE', '/api/v1/forms/31/numbers/3'],
            'forms->listResponses' => [static fn(Funnelchat $c) => $c->forms->listResponses(12)->firstPage(), 'GET', '/api/v1/forms/families/12/responses'],
            'customFields->retrieve' => [static fn(Funnelchat $c) => $c->customFields->retrieve(5), 'GET', '/api/v1/custom-fields/5'],
            'tags->retrieve' => [static fn(Funnelchat $c) => $c->tags->retrieve(8), 'GET', '/api/v1/tags/8'],
            'tags->delete' => [static fn(Funnelchat $c) => $c->tags->delete(['tagIds' => ['8']]), 'POST', '/api/v1/tags/delete'],
            'templates->retrieve' => [static fn(Funnelchat $c) => $c->templates->retrieve(45), 'GET', '/api/v1/templates/45'],
            'templates->delete' => [static fn(Funnelchat $c) => $c->templates->delete(3, 45), 'DELETE', '/api/v1/devices/3/templates/45'],
            'conversations->countUnread' => [static fn(Funnelchat $c) => $c->conversations->countUnread(), 'GET', '/api/v1/conversations/count/unread'],
            'conversations->update' => [static fn(Funnelchat $c) => $c->conversations->update(2001, ['active_conversation' => false]), 'PUT', '/api/v1/contacts/2001/update'],
            'contacts->createNote' => [static fn(Funnelchat $c) => $c->contacts->createNote(2001, ['body' => 'nota']), 'POST', '/api/v1/contacts/2001/notes'],
            'contacts->deleteNote' => [static fn(Funnelchat $c) => $c->contacts->deleteNote(2001, 9), 'DELETE', '/api/v1/contacts/2001/notes/9'],
        ];
    }

    /**
     * @param \Closure(Funnelchat): mixed $run
     */
    #[DataProvider('resourceCalls')]
    public function testResourcesBuildTheRightRequests(\Closure $run, string $method, string $path): void
    {
        $request = $this->capture($run);
        self::assertSame([$method, $path], [$request['method'], $request['path']]);
    }

    public function testAcceptsAPsr7RequestFactoryOfYourChoice(): void
    {
        $http = new MockHttpClient(Reply::ok([]));
        $factory = new class implements \Psr\Http\Message\RequestFactoryInterface {
            public int $created = 0;

            public function createRequest(string $method, $uri): RequestInterface
            {
                ++$this->created;

                return new Request($method, $uri);
            }
        };

        (new Funnelchat(apiKey: self::TEST_API_KEY, httpClient: $http, requestFactory: $factory))->devices->retrieve(3);
        self::assertSame(1, $factory->created);
    }
}
