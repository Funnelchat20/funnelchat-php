<?php

declare(strict_types=1);

namespace Funnelchat\Tests;

use Funnelchat\Generated\Routes;
use Funnelchat\Generated\WebhookEvents;
use Funnelchat\Tests\Support\MockHttpClient;
use Funnelchat\Tests\Support\TestCase;

/**
 * The generated layer and the pending-of-the-spec layer. Counts come from the spec files, so a
 * regeneration does not need to touch this test.
 */
final class GeneratedTest extends TestCase
{
    private const ROOT = __DIR__ . '/..';

    /**
     * A generated constant, read at run time (PHPStan would fold the constant and call every
     * assertion on it redundant).
     *
     * @param class-string $class
     *
     * @return array<array-key, mixed>
     */
    private static function constant(string $class, string $name): array
    {
        $value = (new \ReflectionClassConstant($class, $name))->getValue();
        self::assertIsArray($value);

        return $value;
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function event(string $type): array
    {
        $event = self::constant(WebhookEvents::class, 'CATALOG')[$type] ?? null;
        self::assertIsArray($event, $type);

        return $event;
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function readJson(string $file): array
    {
        $json = json_decode((string) file_get_contents(self::ROOT . "/spec/{$file}"), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($json);

        return $json;
    }

    private static function countOperations(mixed $paths): int
    {
        $n = 0;
        foreach (\is_array($paths) ? $paths : [] as $item) {
            foreach (['get', 'put', 'post', 'delete', 'patch'] as $method) {
                if (\is_array($item) && isset($item[$method])) {
                    ++$n;
                }
            }
        }

        return $n;
    }

    public function testHasEveryOperationOfTheSnapshotPlusThePendingOnesFlagged(): void
    {
        $snapshotOps = self::countOperations(self::readJson('funnelchat.json')['paths'] ?? null);
        $pendingWebhookOps = \count(array_filter(Routes::ALL, static fn(array $r): bool => $r['pending'] && str_starts_with($r['path'], '/api/v1/webhooks')));

        self::assertGreaterThan(0, $snapshotOps);
        self::assertCount($snapshotOps + $pendingWebhookOps, Routes::ALL);
        self::assertLessThanOrEqual(self::countOperations(self::readJson('pending.json')['paths'] ?? null), $pendingWebhookOps);

        $pending = array_map(static fn(array $r): string => "{$r['method']} {$r['path']}", array_values(array_filter(Routes::ALL, static fn(array $r): bool => $r['pending'])));
        foreach ([
            'GET /api/v1/webhooks',
            'POST /api/v1/webhooks',
            'GET /api/v1/webhooks/{id}',
            'PUT /api/v1/webhooks/{id}',
            'DELETE /api/v1/webhooks/{id}',
            'POST /api/v1/webhooks/{id}/rotate-secret',
            'POST /api/v1/webhooks/{id}/test',
            'GET /api/v1/webhooks/{id}/deliveries',
            // Patched with client_reference while the snapshot says send_pending.
            'POST /api/v1/contacts/{contact}/message',
        ] as $route) {
            self::assertContains($route, $pending);
        }
    }

    public function testKnowsEveryEventOfTheSnapshotAndThePendingOnes(): void
    {
        foreach (['message.received', 'group.created', 'webhook.test', 'message.status_changed', 'device.connected', 'device.disconnected'] as $type) {
            self::assertArrayHasKey($type, self::constant(WebhookEvents::class, 'CATALOG'));
        }
        self::assertSame('planned', self::event('message.status_changed')['status']);
        self::assertTrue(self::event('message.status_changed')['pending']);
        self::assertSame(['conversations', 'communities'], self::event('webhook.test')['domains']);
        // device_id of message.received comes from the pending layer while the snapshot lacks it.
        self::assertSame(['device_id'], self::event('message.received')['pendingFields']);
    }

    public function testHasTheSameResourcesAsTheNodeSdk(): void
    {
        $client = $this->client(new MockHttpClient());
        foreach (['contacts', 'conversations', 'messages', 'templates', 'tags', 'customFields', 'groups', 'devices', 'webhooks'] as $property) {
            self::assertTrue(property_exists($client, $property), $property);
        }
        self::assertSame(['verify', 'constructEvent'], array_values(array_intersect(get_class_methods($client->webhooks), ['verify', 'constructEvent'])));
    }

    public function testEverySdkMethodOfTheRouteTableExists(): void
    {
        $client = $this->client(new MockHttpClient());
        $mapped = 0;
        foreach (Routes::ALL as $route) {
            if ($route['sdk'] === null) {
                continue;
            }
            [$property, $method] = explode('->', $route['sdk']);
            self::assertTrue(property_exists($client, $property), $route['sdk']);
            $resource = $client->{$property};
            self::assertIsObject($resource);
            self::assertTrue(method_exists($resource, $method), $route['sdk']);
            ++$mapped;
        }
        self::assertGreaterThanOrEqual(70, $mapped);
    }

    public function testTheGeneratedFilesAreUpToDate(): void
    {
        $command = escapeshellarg(\PHP_BINARY) . ' ' . escapeshellarg(self::ROOT . '/scripts/generate.php') . ' --check 2>&1';
        exec($command, $output, $code);

        self::assertSame(0, $code, implode("\n", $output));
    }
}
