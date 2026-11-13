<?php

declare(strict_types=1);

namespace Funnelchat\Tests;

use Funnelchat\Generated\Routes;
use Funnelchat\Generated\WebhookEvents;
use Funnelchat\Tests\Support\MockHttpClient;
use Funnelchat\Tests\Support\TestCase;

/**
 * The generated layer and the pending-of-the-spec layer. Counts come from the spec files, so a
 * regeneration does not need to touch this test. The spec is the one the developers portal
 * publishes: the SDK has every operation and event it has, and nothing else; the resources are
 * those of spec/resources.json (the Node SDK's), and every other operation is called with request().
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

    /**
     * @return array<string, array<array-key, mixed>> "METHOD /path" => the operation, of a `paths` object
     */
    private static function operationsWithData(mixed $paths): array
    {
        $operations = [];
        foreach (\is_array($paths) ? $paths : [] as $path => $item) {
            foreach (['get', 'put', 'post', 'delete', 'patch'] as $method) {
                if (\is_array($item) && \is_array($item[$method] ?? null)) {
                    $operations[strtoupper($method) . " {$path}"] = $item[$method];
                }
            }
        }

        return $operations;
    }

    public function testHasEveryOperationOfTheSpecAndNoOther(): void
    {
        $snapshot = self::operations(self::readJson('funnelchat.json')['paths'] ?? null);
        $labels = [];
        foreach (self::constant(Routes::class, 'ALL') as $route) {
            self::assertIsArray($route);
            self::assertIsString($route['method']);
            self::assertIsString($route['path']);
            $labels[] = "{$route['method']} {$route['path']}";
        }

        self::assertGreaterThan(0, \count($snapshot));
        // spec/pending.json never adds an operation: only what the portal publishes.
        self::assertEqualsCanonicalizing($snapshot, $labels);
    }

    public function testTheRouteTableSaysTheGroupTheCategoryAndWhatCannotBeUndone(): void
    {
        $spec = self::readJson('funnelchat.json');
        // The category of a group is the id of one of `x-fc-categorias`; the table says its title.
        $titles = [];
        foreach ((array) ($spec['x-fc-categorias'] ?? []) as $category) {
            self::assertIsArray($category);
            self::assertIsString($category['id']);
            self::assertIsString($category['title']);
            $titles[$category['id']] = $category['title'];
        }
        $groups = [];
        $expectedGroups = [];
        foreach ((array) ($spec['x-tagGroups'] ?? []) as $group) {
            self::assertIsArray($group);
            $category = $group['x-fc-categoria'] ?? $group['x-fc-category'] ?? $group['x-fc-bloque'] ?? null;
            $category = \is_string($category) ? $titles[$category] ?? $category : null;
            $expectedGroups[] = [$group['name'] ?? null, $category];
            foreach ((array) ($group['tags'] ?? []) as $tag) {
                self::assertIsString($tag);
                $groups[$tag] ??= [$group['name'] ?? null, $category];
            }
        }
        $actualGroups = [];
        foreach (self::constant(Routes::class, 'GROUPS') as $group) {
            self::assertIsArray($group);
            $actualGroups[] = [$group['name'], $group['category']];
        }
        self::assertSame($expectedGroups, $actualGroups);
        $routes = [];
        foreach (self::constant(Routes::class, 'ALL') as $route) {
            self::assertIsArray($route);
            self::assertIsString($route['method']);
            self::assertIsString($route['path']);
            $routes["{$route['method']} {$route['path']}"] = $route;
        }
        $destructive = 0;
        foreach (self::operationsWithData($spec['paths'] ?? null) as $label => $op) {
            $route = $routes[$label] ?? null;
            self::assertIsArray($route, $label);
            $tags = $op['tags'] ?? [];
            $tag = \is_array($tags) && \is_string($tags[0] ?? null) ? $tags[0] : null;
            self::assertSame([$groups[$tag ?? ''][0] ?? null, $groups[$tag ?? ''][1] ?? null], [$route['group'], $route['category']], $label);
            $flag = $op['x-fc-destructive'] ?? null;
            self::assertSame(\is_bool($flag) ? $flag : null, $route['destructive'], $label);
            if ($flag === true && \is_string($route['sdk'])) {
                // What cannot be undone, and has a method, says so in the method's PHPDoc.
                [$property, $name] = explode('->', $route['sdk']);
                $doc = (string) (new \ReflectionMethod('Funnelchat\\Resources\\' . ucfirst($property), $name))->getDocComment();
                self::assertStringContainsString('**It cannot be undone**', $doc, $route['sdk']);
                ++$destructive;
            }
        }
        // `devices->delete()`, at least.
        self::assertGreaterThan(0, $destructive);
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
        // The published spec of 11-11 still needs the corrections: the four sends, the QR, the links…
        self::assertGreaterThanOrEqual(4, $checked);
    }

    public function testTheSendsAnswerTheMessageNeverNull(): void
    {
        // conversations v1.19.7: `{"message": <object>}` for an API key.
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

    public function testKnowsEveryEventOfTheSpecAndNoOther(): void
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
        // spec/pending.json only corrects the type of an event the spec publishes; it adds none.
        self::assertEqualsCanonicalizing(array_values(array_unique($types)), array_keys($catalog));
        foreach (array_keys($pendingWebhooks) as $type) {
            self::assertContains((string) $type, $types, "{$type} is published");
        }
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
        // accounts v1.5.97 writes `data.workspace_id` on every event it delivers.
        $source = (string) file_get_contents(self::ROOT . '/src/Generated/WebhookEvents.php');
        preg_match_all('/@phpstan-type (\w+)Data (array\{.*?\n \* \}|array<string, mixed>)\n/s', $source, $matches, \PREG_SET_ORDER);
        self::assertGreaterThan(40, \count($matches));
        $typed = 0;
        foreach ($matches as [, $name, $shape]) {
            if ($shape === 'array<string, mixed>') {
                // A planned event whose `data` is not known yet keeps the open type.
                continue;
            }
            // From spec/pending.json, or from the spec's own example.
            self::assertMatchesRegularExpression('/\n \*     workspace_id: (?:PendingWorkspaceId|int),\n/', $shape, $name);
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

    public function testTheGroupEventsStayTypedFromProductionWhenTheExampleShowsMessageUid(): void
    {
        // Since v0.6.14 the spec's example of the two message events shows `message.uid`; their
        // `data` still has no schema, so the correction of spec/pending.json must still apply.
        $source = (string) file_get_contents(self::ROOT . '/src/Generated/WebhookEvents.php');
        $shape = static function (string $alias) use ($source): string {
            self::assertSame(1, preg_match("/@phpstan-type {$alias} (array\\{.*?\\n \\* \\})\\n/s", $source, $match), $alias);

            return $match[1];
        };
        $participant = "/\\n \\*     participant: array\\{\\s*\\*\\s*identity: string,\\s*\\*\\s*identity_type: 'phone'\\|'whatsapp_user',\\s*\\*\\s*name: string\\|null,/";
        foreach (['GroupMessageReceivedData', 'GroupReactionAddedData'] as $alias) {
            self::assertMatchesRegularExpression('/\\n \\*     message: array\\{\\s*\\*\\s*uid: string,/', $shape($alias), $alias);
            self::assertMatchesRegularExpression($participant, $shape($alias), $alias);
        }
        // Without an identity a join request is not sent: never `null`, never `unknown`.
        foreach (['GroupJoinRequestCreatedData', 'GroupJoinRequestRevokedData'] as $alias) {
            self::assertMatchesRegularExpression($participant, $shape($alias), $alias);
        }
        foreach (['group.message_received', 'group.reaction_added', 'group_join_request.created', 'group_join_request.revoked'] as $type) {
            self::assertTrue(self::event($type)['pending'], $type);
        }
    }

    public function testOnlyTheOperationsWhoseSpecDeclaresTheIdempotencyKeyTakeIt(): void
    {
        $spec = self::readJson('funnelchat.json');
        $naming = [];
        $refusing = [];
        foreach (self::operationsWithData($spec['paths'] ?? null) as $label => $op) {
            $inHeader = false;
            foreach (\is_array($op['parameters'] ?? null) ? $op['parameters'] : [] as $parameter) {
                $inHeader = $inHeader || (\is_array($parameter) && ($parameter['in'] ?? null) === 'header' && \is_string($parameter['name'] ?? null) && strcasecmp($parameter['name'], 'Idempotency-Key') === 0);
            }
            $description = \is_string($op['description'] ?? null) ? $op['description'] : '';
            if ($inHeader || str_contains($description, '`Idempotency-Key`')) {
                $naming[] = $label;
            }
            // The spec also says where the API does not take it (creating a subscription, since v0.6.16).
            if (preg_match('/\bNo acepta `Idempotency-Key`|\bdoes not (?:accept|take) `Idempotency-Key`/i', $description) === 1) {
                $refusing[] = $label;
            }
        }
        $taking = [];
        foreach (self::constant(Routes::class, 'ALL') as $route) {
            self::assertIsArray($route);
            [$method, $path, $takesIt] = [$route['method'] ?? null, $route['path'] ?? null, $route['idempotencyKey'] ?? null];
            self::assertIsString($method);
            self::assertIsString($path);
            self::assertIsBool($takesIt, "{$method} {$path}");
            if ($takesIt) {
                $taking[] = "{$method} {$path}";
            }
        }

        // Only operations whose spec names the header, and never one whose spec says it does not take it.
        self::assertSame([], array_values(array_diff($taking, $naming)));
        foreach ($refusing as $label) {
            self::assertNotContains($label, $taking, "{$label}: its spec says it does not take `Idempotency-Key`");
        }
        // The two creates the SDK documents with a key.
        self::assertContains('POST /api/v1/devices', $taking);
        self::assertContains('POST /api/v1/connect-links', $taking);
    }

    public function testANumberSaysWhatItCanDo(): void
    {
        // The number's capabilities: the spec types them since 04-11, so spec/pending.json no longer does.
        $properties = self::at(self::readJson('funnelchat.json'), 'components', 'schemas', 'DeviceResource', 'properties');
        self::assertIsArray($properties);
        self::assertArrayHasKey('capabilities', $properties);
        $schemas = (string) file_get_contents(self::ROOT . '/src/Generated/Schemas.php');
        self::assertMatchesRegularExpression('/@phpstan-type DeviceResource array\{.*?\n \*     capabilities: array\{/s', $schemas);
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
        $resources = self::at(self::readJson('resources.json'), 'resources');
        self::assertIsArray($resources);
        self::assertCount(11, $resources);
        foreach ($resources as $resource) {
            self::assertIsArray($resource);
            self::assertIsString($resource['property']);
            self::assertTrue(property_exists($client, $resource['property']), $resource['property']);
        }
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

    /**
     * The next spec the portal publishes: a new group, whose category its entry gives by title
     * (`x-fc-bloque`, as the portal's full spec calls it), an operation spec/resources.json names
     * that leaves, and an event spec/pending.json corrects that leaves. The generation runs in a copy
     * of the repository and needs nothing edited by hand.
     */
    public function testANewSpecNeedsNothingEditedByHand(): void
    {
        $spec = self::readJson('funnelchat.json');
        $groups = $spec['x-tagGroups'] ?? null;
        self::assertIsArray($groups);
        $groups[] = ['name' => 'Prueba', 'x-fc-bloque' => 'Funciones de Funnelchat', 'tags' => ['Prueba']];
        $spec['x-tagGroups'] = $groups;
        $tags = $spec['tags'] ?? null;
        self::assertIsArray($tags);
        $tags[] = ['name' => 'Prueba', 'description' => 'Una prueba.'];
        $spec['tags'] = $tags;
        $paths = $spec['paths'] ?? null;
        self::assertIsArray($paths);
        $paths['/api/v1/prueba'] = ['get' => ['operationId' => 'sample.index', 'summary' => 'Ver la prueba', 'tags' => ['Prueba'], 'responses' => ['200' => ['description' => 'OK']]]];
        unset($paths['/api/v1/tags/{tag}/contacts']);
        $spec['paths'] = $paths;
        $webhooks = $spec['webhooks'] ?? null;
        self::assertIsArray($webhooks);
        unset($webhooks['message.received']);
        $spec['webhooks'] = $webhooks;

        $dir = sys_get_temp_dir() . '/funnelchat-php-generate-' . bin2hex(random_bytes(6));
        try {
            foreach (['src', 'spec'] as $tree) {
                self::copyTree(self::ROOT . "/{$tree}", "{$dir}/{$tree}");
            }
            mkdir("{$dir}/scripts");
            copy(self::ROOT . '/scripts/generate.php', "{$dir}/scripts/generate.php");
            copy(self::ROOT . '/README.md', "{$dir}/README.md");
            file_put_contents("{$dir}/spec/funnelchat.json", json_encode($spec, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR));

            $run = static function (string $arguments) use ($dir): array {
                exec(escapeshellarg(\PHP_BINARY) . ' ' . escapeshellarg("{$dir}/scripts/generate.php") . "{$arguments} 2>&1", $output, $code);

                return [$code, implode("\n", $output)];
            };
            [$code, $report] = $run('');
            self::assertSame(0, $code, $report);
            $operations = \count(self::operations($paths));
            self::assertMatchesRegularExpression("/\\d+ of {$operations} operations have a method; the rest are called with request\\(\\)\\./", $report);
            self::assertStringContainsString('Tags::listContacts is left out, the spec no longer has GET /api/v1/tags/{tag}/contacts', $report);
            self::assertMatchesRegularExpression('/does not publish \d+ routes or events of spec\/pending\.json.*\n  - webhook message\.received\n/s', $report);

            $routes = (string) file_get_contents("{$dir}/src/Generated/Routes.php");
            self::assertMatchesRegularExpression("/'path' => '\\/api\\/v1\\/prueba', .*'group' => 'Prueba', 'category' => 'Funciones de Funnelchat', .*'sdk' => null\\]/", $routes);
            self::assertStringContainsString("['name' => 'Prueba', 'category' => 'Funciones de Funnelchat', 'operations' => 1, 'description' => 'Una prueba.']", $routes);
            self::assertStringNotContainsString('/api/v1/tags/{tag}/contacts', $routes);
            self::assertStringNotContainsString('function listContacts(', (string) file_get_contents("{$dir}/src/Resources/Tags.php"));
            self::assertStringNotContainsString("'message.received' =>", (string) file_get_contents("{$dir}/src/Generated/WebhookEvents.php"));

            // The README lists the new group under its category.
            $readme = (string) file_get_contents("{$dir}/README.md");
            self::assertMatchesRegularExpression('/\n- \*\*\[Funciones de Funnelchat\]\([^)]+\)\*\*, \d+ groups: [^\n]*, Prueba\.\n/', $readme);

            // And what it wrote is what it would write again.
            [$code, $report] = $run(' --check');
            self::assertSame(0, $code, $report);
        } finally {
            self::removeTree($dir);
        }
    }

    private static function copyTree(string $from, string $to): void
    {
        mkdir($to, 0o777, true);
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $file) {
            self::assertInstanceOf(\SplFileInfo::class, $file);
            $target = $to . substr($file->getPathname(), \strlen($from));
            if ($file->isDir()) {
                mkdir($target);
            } else {
                copy($file->getPathname(), $target);
            }
        }
    }

    private static function removeTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            self::assertInstanceOf(\SplFileInfo::class, $file);
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($dir);
    }

    public function testTheGeneratedFilesAreUpToDate(): void
    {
        $command = escapeshellarg(\PHP_BINARY) . ' ' . escapeshellarg(self::ROOT . '/scripts/generate.php') . ' --check 2>&1';
        exec($command, $output, $code);

        self::assertSame(0, $code, implode("\n", $output));
    }
}
