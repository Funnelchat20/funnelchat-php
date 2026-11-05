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

    /**
     * The condition of a patch, as the generator reads it: top-level keys, or JSON pointers
     * (`/responses/200/…`) where `null` stands for "absent or null".
     *
     * @param array<array-key, mixed> $target
     * @param array<array-key, mixed> $condition
     */
    private static function holds(array $target, array $condition): bool
    {
        foreach ($condition as $key => $expected) {
            $key = (string) $key;
            $tokens = str_starts_with($key, '/') ? array_map(static fn(string $token): string => str_replace(['~1', '~0'], ['/', '~'], $token), \array_slice(explode('/', $key), 1)) : [$key];
            if (self::at($target, ...$tokens) !== $expected) {
                return false;
            }
        }

        return true;
    }

    public function testEveryCorrectionOfTheSnapshotThatStillHoldsFlagsItsRoute(): void
    {
        $spec = self::readJson('funnelchat.json');
        $pending = self::readJson('pending.json');
        $routes = [];
        foreach (self::constant(Routes::class, 'ALL') as $route) {
            self::assertIsArray($route);
            self::assertIsString($route['method']);
            self::assertIsString($route['path']);
            $routes["{$route['method']} {$route['path']}"] = $route;
        }
        $checked = 0;
        foreach (['operationPatches', 'responsePatches'] as $kind) {
            $patches = $pending[$kind] ?? [];
            self::assertIsArray($patches);
            foreach ($patches as $patch) {
                self::assertIsArray($patch);
                self::assertIsString($patch['method']);
                self::assertIsString($patch['path']);
                $label = strtoupper($patch['method']) . " {$patch['path']}";
                $op = self::at($spec, 'paths', $patch['path'], $patch['method']);
                self::assertIsArray($op, "{$label}: the operation of a {$kind} entry is in the snapshot");
                $while = $patch['while'] ?? [];
                self::assertIsArray($while);
                if (!self::holds($op, $while)) {
                    continue;
                }
                self::assertTrue(($routes[$label] ?? [])['pending'] ?? false, "{$label} is corrected by spec/pending.json");
                ++$checked;
            }
        }
        // The full spec of 04-11 still needs the corrections: the four sends, the QR, the links…
        self::assertGreaterThanOrEqual(4, $checked);
    }

    public function testTheSendsAnswerTheMessageNeverNull(): void
    {
        // conversations v1.19.7 (ApiKeySendResponse::message): `{"message": <object>}` for an API key.
        foreach (['send', 'sendDirect', 'sendForm'] as $method) {
            $doc = (string) (new \ReflectionMethod(\Funnelchat\Resources\Messages::class, $method))->getDocComment();
            self::assertMatchesRegularExpression('/@return array\{\s*\*\s*message: PublicMessageResource,\s*\*\s*\}/', $doc, $method);
        }
        $doc = (string) (new \ReflectionMethod(\Funnelchat\Resources\Templates::class, 'send'))->getDocComment();
        self::assertMatchesRegularExpression('/@return array\{\s*\*\s*message: PublicMessageResource,\s*\*\s*\}/', $doc);
        $doc = (string) (new \ReflectionMethod(\Funnelchat\Resources\Devices::class, 'qr'))->getDocComment();
        self::assertMatchesRegularExpression('/@return array\{\s*\*\s*data: PendingDeviceQr,\s*\*\s*\}/', $doc);
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
        $inSnapshot = static fn(string $field): bool => (\is_array($exampleData) && \array_key_exists($field, $exampleData))
            || (\is_array($schemaData) && \array_key_exists($field, $schemaData));
        $expected = array_values(array_filter(['device_id', 'workspace_id'], static fn(string $field): bool => !$inSnapshot($field)));

        self::assertSame($expected, self::event('message.received')['pendingFields']);
    }

    public function testEveryEventWithAKnownShapeSaysItsWorkspace(): void
    {
        // accounts v1.5.97 writes `data.workspace_id` on every event it delivers (Q35 of 04-11).
        $source = (string) file_get_contents(self::ROOT . '/src/Generated/WebhookEvents.php');
        preg_match_all('/@phpstan-type (\w+)Data (array\{.*?\n \* \}|array<string, mixed>)\n/s', $source, $matches, \PREG_SET_ORDER);
        self::assertGreaterThan(40, \count($matches));
        $typed = 0;
        foreach ($matches as [, $name, $shape]) {
            if ($shape === 'array<string, mixed>') {
                // A planned event whose `data` is not known yet keeps the open type.
                continue;
            }
            self::assertMatchesRegularExpression('/\n \*     workspace_id: PendingWorkspaceId,\n \* \}$/', $shape, $name);
            ++$typed;
        }
        self::assertGreaterThan(40, $typed);
        foreach (['message.received', 'message.status_changed', 'device.connected', 'device.disconnected', 'group_participant.joined', 'group_participant.left', 'webhook.test'] as $type) {
            $pendingFields = self::event($type)['pendingFields'];
            self::assertIsArray($pendingFields, $type);
            self::assertContains('workspace_id', $pendingFields, $type);
        }
        self::assertStringContainsString('@phpstan-type PendingWorkspaceId int|null', (string) file_get_contents(self::ROOT . '/src/Generated/Schemas.php'));
    }

    public function testTheParticipantEventsAreTheOnesOfProduction(): void
    {
        $source = (string) file_get_contents(self::ROOT . '/src/Generated/WebhookEvents.php');
        foreach (['GroupParticipantJoinedData', 'GroupParticipantLeftData'] as $alias) {
            // communities v2.35.5: any group of the account, so `campaign` can be null.
            self::assertMatchesRegularExpression("/@phpstan-type {$alias} array\\{\\s*\\*\\s*group: PendingEventGroup,\\s*\\*\\s*campaign: array\\{\\s*\\*\\s*uuid: string,\\s*\\*\\s*\\}\\|null,\\s*\\*\\s*participant: PendingEventParticipant,/s", $source, $alias);
        }
        self::assertTrue(self::event('group_participant.joined')['pending']);
        self::assertSame('live', self::event('group_action.completed')['status']);
        $schemas = (string) file_get_contents(self::ROOT . '/src/Generated/Schemas.php');
        self::assertMatchesRegularExpression("/@phpstan-type PendingEventParticipant array\\{\\s*\\*\\s*identity: string,\\s*\\*\\s*identity_type: 'phone'\\|'whatsapp_user',\\s*\\*\\s*name: string\\|null,/s", $schemas);
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

    public function testAddsThePendingQueryParametersOnlyWhileTheSnapshotLacksThem(): void
    {
        $patches = self::at(self::readJson('pending.json'), 'parameterPatches');
        self::assertIsArray($patches);
        $spec = self::readJson('funnelchat.json');
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
            self::assertIsArray($patch['parameters']);
            $declared = [];
            foreach ((array) self::at($spec, 'paths', $patch['path'], $patch['method'], 'parameters') as $parameter) {
                self::assertIsArray($parameter);
                $declared[] = $parameter['name'] ?? null;
            }
            $route = $routes[strtoupper($patch['method']) . " {$patch['path']}"] ?? null;
            self::assertIsArray($route, $patch['path']);
            self::assertIsString($route['sdk']);
            [$property, $name] = explode('->', $route['sdk']);
            $doc = (string) (new \ReflectionMethod('Funnelchat\\Resources\\' . ucfirst($property), $name))->getDocComment();
            foreach ($patch['parameters'] as $parameter) {
                self::assertIsArray($parameter);
                self::assertIsString($parameter['name']);
                if (!\in_array($parameter['name'], $declared, true)) {
                    self::assertTrue($route['pending'], $patch['path']);
                    self::assertMatchesRegularExpression('/@param array\{.*?' . preg_quote($parameter['name'], '/') . '\?:/s', $doc, $route['sdk']);
                }
            }
        }
    }

    public function testHasEveryResource(): void
    {
        $client = $this->client(new MockHttpClient());
        foreach (['contacts', 'conversations', 'messages', 'templates', 'forms', 'tags', 'customFields', 'groups', 'devices', 'connectLinks', 'webhooks'] as $property) {
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
