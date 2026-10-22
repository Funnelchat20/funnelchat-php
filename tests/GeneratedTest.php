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

    /** The value at a path of decoded JSON, or `null` when some step is missing. */
    private static function at(mixed $json, string ...$keys): mixed
    {
        foreach ($keys as $key) {
            if (!\is_array($json) || !\array_key_exists($key, $json)) {
                return null;
            }
            $json = $json[$key];
        }

        return $json;
    }

    /**
     * @return list<string> "METHOD /path" of every operation of a `paths` object
     */
    private static function operations(mixed $paths): array
    {
        $operations = [];
        foreach (\is_array($paths) ? $paths : [] as $path => $item) {
            foreach (['get', 'put', 'post', 'delete', 'patch'] as $method) {
                if (\is_array($item) && isset($item[$method])) {
                    $operations[] = strtoupper($method) . " {$path}";
                }
            }
        }

        return $operations;
    }

    public function testHasEveryOperationOfTheSnapshotPlusThePendingOnesFlagged(): void
    {
        $snapshot = self::operations(self::readJson('funnelchat.json')['paths'] ?? null);
        $pendingOnly = array_values(array_diff(self::operations(self::readJson('pending.json')['paths'] ?? null), $snapshot));
        $routes = self::constant(Routes::class, 'ALL');

        self::assertGreaterThan(0, \count($snapshot));
        self::assertCount(\count($snapshot) + \count($pendingOnly), $routes);
        foreach ($routes as $route) {
            self::assertIsArray($route);
            self::assertIsString($route['method']);
            self::assertIsString($route['path']);
            $label = "{$route['method']} {$route['path']}";
            if (\in_array($label, $pendingOnly, true)) {
                self::assertTrue($route['pending'], "{$label} comes from spec/pending.json");
            }
        }
    }

    public function testFlagsThePatchedOperationsWhileTheSnapshotSaysSendPending(): void
    {
        $spec = self::readJson('funnelchat.json');
        $routes = [];
        foreach (self::constant(Routes::class, 'ALL') as $route) {
            self::assertIsArray($route);
            self::assertIsString($route['method']);
            self::assertIsString($route['path']);
            $routes["{$route['method']} {$route['path']}"] = $route;
        }
        $patches = self::readJson('pending.json')['operationPatches'] ?? [];
        self::assertIsArray($patches);
        foreach ($patches as $patch) {
            self::assertIsArray($patch);
            self::assertIsString($patch['method']);
            self::assertIsString($patch['path']);
            $op = self::at($spec, 'paths', $patch['path'], $patch['method']);
            $label = strtoupper($patch['method']) . " {$patch['path']}";
            if (\is_array($op) && ($op['x-portal-v0'] ?? null) === 'send_pending') {
                self::assertTrue(($routes[$label] ?? [])['pending'] ?? false, "{$label} is patched with client_reference");
            }
        }
    }

    public function testNoResourceMethodDropsTheAnswer(): void
    {
        // A regeneration must never bring back `void`: a body the API sends is always returned.
        foreach (glob(self::ROOT . '/src/Resources/*.php') ?: [] as $file) {
            $class = 'Funnelchat\\Resources\\' . basename($file, '.php');
            self::assertTrue(class_exists($class), $class);
            foreach ((new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $class) {
                    continue;
                }
                $type = $method->getReturnType();
                self::assertNotSame('void', $type instanceof \ReflectionNamedType ? $type->getName() : null, "{$class}::{$method->getName()}");
            }
        }
    }

    public function testTypesTheAnswerOfTheSendsWhileTheSnapshotSaysSendPending(): void
    {
        $spec = self::readJson('funnelchat.json');
        $patches = self::at(self::readJson('pending.json'), 'responsePatches');
        self::assertIsArray($patches);
        $routes = [];
        foreach (self::constant(Routes::class, 'ALL') as $route) {
            self::assertIsArray($route);
            self::assertIsString($route['method']);
            self::assertIsString($route['path']);
            $routes["{$route['method']} {$route['path']}"] = $route;
        }
        foreach ($patches as $patch) {
            self::assertIsArray($patch);
            self::assertIsString($patch['method']);
            self::assertIsString($patch['path']);
            if (self::at($spec, 'paths', $patch['path'], $patch['method'], 'x-portal-v0') !== 'send_pending') {
                continue;
            }
            $route = $routes[strtoupper($patch['method']) . " {$patch['path']}"] ?? null;
            self::assertIsArray($route, $patch['path']);
            self::assertTrue($route['pending'], $patch['path']);
            if (!\is_string($route['sdk'])) {
                continue;
            }
            // The resource method documents the pending answer, not "no body".
            [$property, $name] = explode('->', $route['sdk']);
            $doc = (string) (new \ReflectionMethod('Funnelchat\\Resources\\' . ucfirst($property), $name))->getDocComment();
            self::assertStringContainsString('`conversations-send`', $doc, $route['sdk']);
            self::assertStringContainsString('Pending', $doc, $route['sdk']);
        }
    }

    public function testKnowsEveryEventOfTheSnapshotAndThePendingOnes(): void
    {
        $catalog = self::constant(WebhookEvents::class, 'CATALOG');
        $types = [];
        $snapshotWebhooks = self::at(self::readJson('funnelchat.json'), 'webhooks');
        $pendingWebhooks = self::at(self::readJson('pending.json'), 'webhooks');
        self::assertIsArray($snapshotWebhooks);
        self::assertIsArray($pendingWebhooks);
        foreach (array_keys($snapshotWebhooks) as $key) {
            $types[] = (string) preg_replace('/\s*\(.*\)\s*$/', '', (string) $key);
        }
        foreach (array_keys($pendingWebhooks) as $type) {
            $types[] = (string) $type;
            self::assertArrayHasKey((string) $type, $catalog);
        }
        self::assertEqualsCanonicalizing(array_values(array_unique($types)), array_keys($catalog));
        foreach (['message.received', 'webhook.test', 'message.status_changed', 'device.connected'] as $type) {
            self::assertArrayHasKey($type, $catalog);
        }
        self::assertSame(['conversations', 'communities'], self::event('webhook.test')['domains']);
    }

    public function testAddsThePendingFieldsOfAnEventOnlyWhileTheSnapshotLacksThem(): void
    {
        $content = self::at(self::readJson('funnelchat.json'), 'webhooks', 'message.received', 'post', 'requestBody', 'content', 'application/json');
        // Present means the key is there, even with a null value: the same rule as the generator.
        $exampleData = self::at($content, 'example', 'data');
        $schemaData = self::at($content, 'schema', 'properties', 'data', 'properties');
        $inSnapshot = (\is_array($exampleData) && \array_key_exists('device_id', $exampleData))
            || (\is_array($schemaData) && \array_key_exists('device_id', $schemaData));

        self::assertSame($inSnapshot ? [] : ['device_id'], self::event('message.received')['pendingFields']);
    }

    public function testAddsThePendingFieldsOfASchemaOnlyWhileTheSnapshotLacksThem(): void
    {
        $properties = self::at(self::readJson('funnelchat.json'), 'components', 'schemas', 'DeviceResource', 'properties');
        self::assertIsArray($properties);
        $schemas = (string) file_get_contents(self::ROOT . '/src/Generated/Schemas.php');
        if (!\array_key_exists('capabilities', $properties)) {
            // The number's capabilities (accounts v1.4.24), typed from spec/pending.json.
            self::assertMatchesRegularExpression('/@phpstan-type DeviceResource array\{.*?capabilities: PendingDeviceCapabilities,/s', $schemas);
        }
        self::assertStringContainsString('@phpstan-type PendingDeviceCapabilities', $schemas);
    }

    public function testHasEveryResource(): void
    {
        $client = $this->client(new MockHttpClient());
        foreach (['contacts', 'conversations', 'messages', 'templates', 'forms', 'tags', 'customFields', 'groups', 'devices', 'webhooks'] as $property) {
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
