<?php

/**
 * The single generation script of the SDK (`composer generate`).
 *
 *   php scripts/generate.php                  regenerate src/Generated and src/Resources from spec/
 *   php scripts/generate.php --spec <file>    copy a new unified spec into spec/, record where it came
 *                                             from in spec/procedencia.json, then regenerate
 *   php scripts/generate.php --check          generate in memory and fail if anything on disk differs
 *
 * Steps:
 *   1. (with --spec) copy the spec and write spec/procedencia.json (repository, branch, commit, sha256).
 *   2. Merge spec/pending.json (the "pending of the spec" layer) into the snapshot: routes, request-body
 *      fields, schema fields, responses and events. Every pending entry is applied only while the
 *      snapshot lacks it; the report lists the entries a new snapshot made
 *      obsolete so they can be deleted from spec/pending.json.
 *   3. src/Generated/Routes.php            every operation: method, path, domain, permission, SDK method.
 *   4. src/Generated/Schemas.php           every schema of the spec as a PHPStan type alias (array shape).
 *   5. src/Generated/WebhookEvents.php     the event catalog and the type of each event.
 *   6. src/Resources/*.php                 one class per resource of spec/resources.json, one method per
 *                                          operation, with its path parameters, query, body and response
 *                                          typed from the spec.
 *   7. src/Generated/ResourceProperties.php  the resource properties of the client.
 *
 * Nothing in src/Generated or src/Resources is edited by hand.
 */

declare(strict_types=1);

namespace Funnelchat\Scripts;

const HTTP_METHODS = ['get', 'put', 'post', 'delete', 'patch'];
const SUCCESS_STATUSES = ['200', '201', '202', '203', '204', '206', '2XX'];

/**
 * @phpstan-type Json array<array-key, mixed>
 * @phpstan-type Report array{applied: list<string>, superseded: list<string>, missing: list<string>}
 */
final class Generator
{
    private const SPEC_FILE = 'spec/funnelchat.json';
    private const PROVENANCE_FILE = 'spec/procedencia.json';
    private const PENDING_FILE = 'spec/pending.json';
    private const RESOURCES_FILE = 'spec/resources.json';
    private const GENERATED_DIRS = ['src/Generated', 'src/Resources'];
    private const MARKER = 'GENERATED FILE — do not edit. Run `composer generate`.';

    private string $root;

    private string $specHash = '';

    public function __construct()
    {
        $this->root = \dirname(__DIR__);
    }

    /**
     * @param list<string> $argv
     */
    public static function main(array $argv): int
    {
        try {
            return (new self())->run(\array_slice($argv, 1));
        } catch (GeneratorError $error) {
            fwrite(\STDERR, $error->getMessage() . "\n");

            return 1;
        }
    }

    /**
     * @param list<string> $args
     */
    private function run(array $args): int
    {
        $spec = null;
        $check = false;
        for ($i = 0; $i < \count($args); ++$i) {
            $arg = $args[$i];
            if ($arg === '--check') {
                $check = true;
            } elseif ($arg === '--spec') {
                $spec = $args[++$i] ?? throw new GeneratorError('--spec necesita la ruta del archivo');
            } elseif (str_starts_with($arg, '--spec=')) {
                $spec = substr($arg, \strlen('--spec='));
            } else {
                throw new GeneratorError("Argumento desconocido: {$arg}");
            }
        }
        if ($spec !== null) {
            if ($check) {
                throw new GeneratorError('--check y --spec no se combinan');
            }
            $this->importSpec($spec);
        }

        $specPath = $this->path(self::SPEC_FILE);
        if (!is_file($specPath)) {
            throw new GeneratorError('Falta spec/funnelchat.json: corré `composer generate -- --spec <archivo>`.');
        }
        $bytes = self::read($specPath);
        $this->specHash = hash('sha256', $bytes);
        $provenancePath = $this->path(self::PROVENANCE_FILE);
        if (is_file($provenancePath)) {
            $provenance = self::decode(self::read($provenancePath), self::PROVENANCE_FILE);
            if (($provenance['sha256'] ?? null) !== $this->specHash) {
                fwrite(\STDERR, "aviso: spec/funnelchat.json no coincide con el sha256 de spec/procedencia.json (¿la editaron a mano?).\n");
            }
        }

        $snapshot = self::decode($bytes, self::SPEC_FILE);
        $pendingPath = $this->path(self::PENDING_FILE);
        $pending = is_file($pendingPath) ? self::decode(self::read($pendingPath), self::PENDING_FILE) : [];
        $resources = self::decode(self::read($this->path(self::RESOURCES_FILE)), self::RESOURCES_FILE);

        [$merged, $report] = self::mergePending($snapshot, $pending);
        $types = new Types(self::obj(self::obj($merged['components'] ?? null)['schemas'] ?? null));

        [$resourceFiles, $sdkMethods, $mapErrors] = $this->generateResources($merged, $resources, $types);
        if ($mapErrors !== []) {
            throw new GeneratorError("spec/resources.json no coincide con la especificación:\n  - " . implode("\n  - ", $mapErrors));
        }

        $files = [
            'src/Generated/Routes.php' => $this->generateRoutes($merged, $sdkMethods),
            'src/Generated/Schemas.php' => $this->generateSchemas($types),
            'src/Generated/WebhookEvents.php' => $this->generateEvents($merged, $types),
            'src/Generated/ResourceProperties.php' => $this->generateResourceProperties($resources),
        ] + $resourceFiles;
        ksort($files);

        echo 'pendiente de la especificación: ' . \count($report['applied']) . " entradas aplicadas.\n";
        if ($report['superseded'] !== []) {
            echo 'la instantánea ya trae ' . \count($report['superseded']) . " entradas de spec/pending.json; se pueden borrar de ahí:\n";
            foreach ($report['superseded'] as $label) {
                echo "  - {$label}\n";
            }
        }
        if ($report['missing'] !== []) {
            fwrite(\STDERR, 'aviso: ' . \count($report['missing']) . " parches no encontraron su operación o su evento en la instantánea:\n");
            foreach ($report['missing'] as $label) {
                fwrite(\STDERR, "  - {$label}\n");
            }
        }
        $operations = self::countOperations($merged);
        echo 'recursos: ' . \count($sdkMethods) . " de {$operations} operaciones tienen un método; el resto se llama con request().\n";

        $stale = $this->staleFiles($files);
        if ($check) {
            $different = [];
            foreach ($files as $relative => $content) {
                $file = $this->path($relative);
                if (!is_file($file) || self::read($file) !== $content) {
                    $different[] = $relative;
                }
            }
            $different = [...$different, ...$stale];
            if ($different !== []) {
                fwrite(\STDERR, "Lo generado está desactualizado: corré `composer generate`.\n  - " . implode("\n  - ", $different) . "\n");

                return 1;
            }
            echo "src/Generated y src/Resources están al día.\n";

            return 0;
        }

        foreach ($files as $relative => $content) {
            $file = $this->path($relative);
            if (!is_dir(\dirname($file))) {
                mkdir(\dirname($file), 0o777, true);
            }
            file_put_contents($file, $content);
        }
        foreach ($stale as $relative) {
            unlink($this->path($relative));
        }
        echo 'generado: ' . \count($files) . ' archivos en ' . implode(' y ', self::GENERATED_DIRS) . ($stale === [] ? '' : '; borrados: ' . implode(', ', $stale)) . ".\n";

        return 0;
    }

    // ---- Step 1: import a spec ------------------------------------------------------------------

    private function importSpec(string $source): void
    {
        $sourcePath = realpath($source);
        if ($sourcePath === false || !is_file($sourcePath)) {
            throw new GeneratorError("No existe el archivo {$source}");
        }
        $bytes = self::read($sourcePath);
        $spec = self::decode($bytes, $source);
        $dir = \dirname($sourcePath);
        $top = self::git($dir, 'rev-parse', '--show-toplevel');
        $relPath = $top !== null ? ltrim(substr($sourcePath, \strlen((string) realpath($top))), '/') : null;
        $tracked = $top !== null && $relPath !== null ? self::git($top, 'ls-files', '--error-unmatch', $relPath) !== null : null;
        $dirty = $top !== null ? self::git($top, 'status', '--porcelain', '--untracked-files=no') : null;
        $mtime = filemtime($sourcePath);
        $info = self::obj($spec['info'] ?? null);

        $domains = [];
        foreach (self::arr($spec['x-fc-sources'] ?? null) as $source) {
            $source = self::obj($source);
            $domains[] = [
                'domain' => $source['domain'] ?? null,
                'sha' => $source['sha'] ?? null,
                'branch' => $source['branch'] ?? null,
                'snapshot_at' => $source['snapshot_at'] ?? null,
            ];
        }

        $provenance = [
            'description' => 'De dónde salió spec/funnelchat.json. Lo escribe `composer generate -- --spec <archivo>`; no lo edites a mano.',
            'file' => self::SPEC_FILE,
            'sha256' => hash('sha256', $bytes),
            'bytes' => \strlen($bytes),
            'source' => [
                'repository' => $top !== null ? self::git($top, 'remote', 'get-url', 'origin') : null,
                'path' => $relPath,
                'branch' => $top !== null ? self::git($top, 'branch', '--show-current') : null,
                'commit' => $top !== null ? self::git($top, 'rev-parse', 'HEAD') : null,
                'commit_date' => $top !== null ? self::git($top, 'log', '-1', '--format=%cI') : null,
                'file_tracked_in_git' => $tracked,
                'tracked_files_clean' => $dirty === null ? null : $dirty === '',
                'file_modified_at' => $mtime === false ? null : gmdate('Y-m-d\TH:i:s\Z', $mtime),
            ],
            'imported_at' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.v\Z'),
            'spec' => [
                'openapi' => $spec['openapi'] ?? null,
                'title' => $info['title'] ?? null,
                'version' => $info['version'] ?? null,
                'paths' => \count(self::obj($spec['paths'] ?? null)),
                'operations' => self::countOperations($spec),
                'webhooks' => \count(self::obj($spec['webhooks'] ?? null)),
                'domains' => $domains,
            ],
        ];

        file_put_contents($this->path(self::SPEC_FILE), $bytes);
        file_put_contents($this->path(self::PROVENANCE_FILE), json_encode($provenance, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR) . "\n");
        $commit = $provenance['source']['commit'];
        echo 'spec: copiada de ' . ($relPath ?? $sourcePath) . ' (' . ($provenance['source']['branch'] ?? '?') . ' @ ' . ($commit !== null ? substr($commit, 0, 7) : '?') . ")\n";
    }

    private static function git(string $cwd, string ...$args): ?string
    {
        $command = 'git -C ' . escapeshellarg($cwd) . ' ' . implode(' ', array_map('escapeshellarg', $args)) . ' 2>/dev/null';
        $output = [];
        $code = 0;
        exec($command, $output, $code);

        return $code === 0 ? trim(implode("\n", $output)) : null;
    }

    // ---- Step 2: the pending layer --------------------------------------------------------------

    /**
     * @param Json $spec
     * @param Json $pending
     *
     * @return array{Json, Report}
     */
    private static function mergePending(array $spec, array $pending): array
    {
        $merged = $spec;
        $report = ['applied' => [], 'superseded' => [], 'missing' => []];
        $components = self::obj($merged['components'] ?? null);
        $schemas = self::obj($components['schemas'] ?? null);
        $paths = self::obj($merged['paths'] ?? null);
        $webhooks = self::obj($merged['webhooks'] ?? null);

        foreach (self::obj(self::obj($pending['components'] ?? null)['schemas'] ?? null) as $name => $schema) {
            if (\array_key_exists($name, $schemas)) {
                throw new GeneratorError("spec/pending.json: el esquema {$name} ya existe en la instantánea; renombralo.");
            }
            $schemas[$name] = $schema;
        }

        foreach (self::obj($pending['paths'] ?? null) as $path => $item) {
            $item = self::obj($item);
            foreach (HTTP_METHODS as $method) {
                if (!isset($item[$method])) {
                    continue;
                }
                $label = strtoupper($method) . " {$path}";
                $current = self::obj($paths[$path] ?? null);
                if (isset($current[$method])) {
                    $report['superseded'][] = $label;

                    continue;
                }
                $current[$method] = $item[$method];
                $paths[$path] = $current;
                $report['applied'][] = $label;
            }
        }

        foreach (self::arr($pending['operationPatches'] ?? null) as $patch) {
            $patch = self::obj($patch);
            $method = self::str($patch['method'] ?? null) ?? '';
            $path = self::str($patch['path'] ?? null) ?? '';
            $properties = self::obj($patch['requestBodyProperties'] ?? null);
            $label = strtoupper($method) . " {$path} (parche: " . implode(', ', array_keys($properties)) . ')';
            $item = self::obj($paths[$path] ?? null);
            $op = self::obj($item[$method] ?? null);
            if ($op === []) {
                $report['missing'][] = $label;

                continue;
            }
            if (!self::matches($op, self::obj($patch['while'] ?? null))) {
                $report['superseded'][] = $label;

                continue;
            }
            $requestBody = self::obj($op['requestBody'] ?? null);
            $content = self::obj($requestBody['content'] ?? null);
            $json = self::obj($content['application/json'] ?? null);
            $schema = self::obj($json['schema'] ?? null);
            $refName = self::refName($schema);
            if ($refName !== null) {
                $target = self::obj($schemas[$refName] ?? null);
                if ($target === []) {
                    $report['missing'][] = $label;

                    continue;
                }
                $target['properties'] = self::obj($target['properties'] ?? null) + $properties;
                $schemas[$refName] = $target;
            } elseif ($schema !== []) {
                $schema['properties'] = self::obj($schema['properties'] ?? null) + $properties;
                $json['schema'] = $schema;
                $content['application/json'] = $json;
                $requestBody['content'] = $content;
                $op['requestBody'] = $requestBody;
            } else {
                $report['missing'][] = $label;

                continue;
            }
            $op['x-fc-pending'] = $patch['x-fc-pending'] ?? true;
            $op['x-fc-pending-fields'] = array_keys($properties);
            $item[$method] = $op;
            $paths[$path] = $item;
            $report['applied'][] = $label;
        }

        foreach (self::arr($pending['schemaPatches'] ?? null) as $patch) {
            $patch = self::obj($patch);
            $name = self::str($patch['schema'] ?? null) ?? '';
            $properties = self::obj($patch['properties'] ?? null);
            $label = "esquema {$name} (parche: " . implode(', ', array_keys($properties)) . ')';
            $target = self::obj($schemas[$name] ?? null);
            if ($target === []) {
                $report['missing'][] = $label;

                continue;
            }
            $missing = array_diff_key($properties, self::obj($target['properties'] ?? null));
            if ($missing === []) {
                $report['superseded'][] = $label;

                continue;
            }
            $target['properties'] = self::obj($target['properties'] ?? null) + $missing;
            $required = array_values(array_intersect(self::strings($patch['required'] ?? null), array_keys($missing)));
            if ($required !== []) {
                $target['required'] = array_values(array_unique([...self::strings($target['required'] ?? null), ...$required]));
            }
            $target['x-fc-pending-fields'] = [...self::strings($target['x-fc-pending-fields'] ?? null), ...array_keys($missing)];
            $schemas[$name] = $target;
            $report['applied'][] = $label;
        }

        foreach (self::arr($pending['responsePatches'] ?? null) as $patch) {
            $patch = self::obj($patch);
            $method = self::str($patch['method'] ?? null) ?? '';
            $path = self::str($patch['path'] ?? null) ?? '';
            $replacement = self::obj($patch['responses'] ?? null);
            $label = strtoupper($method) . " {$path} (respuesta)";
            $item = self::obj($paths[$path] ?? null);
            $op = self::obj($item[$method] ?? null);
            if ($op === []) {
                $report['missing'][] = $label;

                continue;
            }
            if (!self::matches($op, self::obj($patch['while'] ?? null))) {
                $report['superseded'][] = $label;

                continue;
            }
            $responses = self::obj($op['responses'] ?? null);
            if (array_intersect(array_keys($replacement), SUCCESS_STATUSES) !== []) {
                // The pending answer is the whole 2xx: a `204` of the snapshot would say "no body".
                $responses = array_diff_key($responses, array_flip(SUCCESS_STATUSES));
            }
            $op['responses'] = $replacement + $responses;
            $op['x-fc-pending-response'] = $patch['x-fc-pending'] ?? true;
            $item[$method] = $op;
            $paths[$path] = $item;
            $report['applied'][] = $label;
        }

        $snapshotTypes = [];
        foreach (array_keys($webhooks) as $key) {
            $snapshotTypes[self::eventTypeOfKey((string) $key)] = (string) $key;
        }
        foreach (self::obj($pending['webhooks'] ?? null) as $type => $item) {
            $type = (string) $type;
            $item = self::obj($item);
            $label = "webhook {$type}";
            $currentKey = $snapshotTypes[$type] ?? null;
            if ($currentKey !== null) {
                $replaceWhile = self::obj(self::obj($item['post'] ?? null)['x-fc-replace-while'] ?? null);
                $currentPost = self::obj(self::obj($webhooks[$currentKey] ?? null)['post'] ?? null);
                if ($replaceWhile === [] || !self::matches($currentPost, $replaceWhile)) {
                    $report['superseded'][] = $label;

                    continue;
                }
                unset($webhooks[$currentKey]);
            }
            $webhooks[$type] = $item;
            $snapshotTypes[$type] = $type;
            $report['applied'][] = $label;
        }

        foreach (self::arr($pending['webhookPatches'] ?? null) as $patch) {
            $patch = self::obj($patch);
            $type = self::str($patch['type'] ?? null) ?? '';
            $properties = self::obj($patch['dataProperties'] ?? null);
            $label = "webhook {$type} (parche: " . implode(', ', array_keys($properties)) . ')';
            $key = $snapshotTypes[$type] ?? null;
            if ($key === null) {
                $report['missing'][] = $label;

                continue;
            }
            $item = self::obj($webhooks[$key] ?? null);
            $post = self::obj($item['post'] ?? null);
            $requestBody = self::obj($post['requestBody'] ?? null);
            $content = self::obj($requestBody['content'] ?? null);
            $json = self::obj($content['application/json'] ?? null);
            $schema = self::obj($json['schema'] ?? null);
            $schemaProperties = self::obj($schema['properties'] ?? null);
            $data = self::obj($schemaProperties['data'] ?? null);
            $dataProperties = self::obj($data['properties'] ?? null);
            $example = self::obj(self::obj($json['example'] ?? null)['data'] ?? null);
            $missing = array_diff_key($properties, $dataProperties, $example);
            if ($missing === []) {
                $report['superseded'][] = $label;

                continue;
            }
            $data['type'] ??= 'object';
            $data['properties'] = $dataProperties + $missing;
            $schemaProperties['data'] = $data;
            $schema['type'] ??= 'object';
            $schema['properties'] = $schemaProperties;
            $json['schema'] = $schema;
            $content['application/json'] = $json;
            $requestBody['content'] = $content;
            $post['requestBody'] = $requestBody;
            $post['x-fc-pending-fields'] = array_keys($missing);
            $item['post'] = $post;
            $webhooks[$key] = $item;
            $report['applied'][] = $label;
        }

        $components['schemas'] = $schemas;
        $merged['components'] = $components;
        $merged['paths'] = $paths;
        $merged['webhooks'] = $webhooks;

        return [$merged, $report];
    }

    /**
     * @param Json $target
     * @param Json $condition
     */
    private static function matches(array $target, array $condition): bool
    {
        foreach ($condition as $key => $value) {
            if (!\array_key_exists($key, $target) || $target[$key] !== $value) {
                return false;
            }
        }

        return true;
    }

    /** The event type a `webhooks` key stands for: "webhook.test (conversations)" -> "webhook.test". */
    private static function eventTypeOfKey(string $key): string
    {
        return (string) preg_replace('/\s*\(.*\)\s*$/', '', $key);
    }

    // ---- Step 3: routes -------------------------------------------------------------------------

    /**
     * @param Json                  $merged
     * @param array<string, string> $sdkMethods "METHOD path" => "resource->method"
     */
    private function generateRoutes(array $merged, array $sdkMethods): string
    {
        $rows = [];
        foreach (self::obj($merged['paths'] ?? null) as $path => $item) {
            $item = self::obj($item);
            foreach (HTTP_METHODS as $method) {
                if (!isset($item[$method])) {
                    continue;
                }
                $op = self::obj($item[$method]);
                $tags = self::arr($op['tags'] ?? null);
                $label = strtoupper($method) . " {$path}";
                $rows[] = [
                    'method' => strtoupper($method),
                    'path' => (string) $path,
                    'operationId' => self::str($op['operationId'] ?? null),
                    'domain' => self::str($op['x-fc-domain'] ?? null),
                    'permission' => self::str($op['x-fc-permission'] ?? null),
                    'tag' => self::str($tags[0] ?? null),
                    'summary' => self::str($op['summary'] ?? null),
                    'pending' => isset($op['x-fc-pending']) || isset($op['x-fc-pending-response']),
                    'sdk' => $sdkMethods[$label] ?? null,
                ];
            }
        }
        usort($rows, static fn(array $a, array $b): int => [$a['path'], $a['method']] <=> [$b['path'], $b['method']]);

        $lines = [];
        foreach ($rows as $row) {
            $parts = [];
            foreach ($row as $key => $value) {
                $parts[] = "'{$key}' => " . Php::value($value);
            }
            $lines[] = '        [' . implode(', ', $parts) . '],';
        }
        $count = \count($rows);

        return $this->header('Funnelchat\Generated') . <<<PHP

            /**
             * The route table of the public API.
             */
            final class Routes
            {
                /**
                 * Every operation of the spec ({$count}), sorted by path. `pending` = taken from
                 * spec/pending.json, not from the snapshot. `sdk` = the resource method that calls it
                 * (`contacts->list`), or `null`: call it with `\$funnelchat->request()`.
                 *
                 * @var list<array{method: 'GET'|'POST'|'PUT'|'PATCH'|'DELETE', path: string, operationId: string|null, domain: string|null, permission: string|null, tag: string|null, summary: string|null, pending: bool, sdk: string|null}>
                 */
                public const ALL = [

            PHP . implode("\n", $lines) . <<<'PHP'

                ];

                private function __construct() {}
            }

            PHP;
    }

    // ---- Step 4: schemas ------------------------------------------------------------------------

    private function generateSchemas(Types $types): string
    {
        $tags = [];
        foreach ($types->aliases() as $name => $alias) {
            $tags[] = Php::docTag('@phpstan-type ' . $alias, $types->aliasBody($name));
        }

        return $this->header('Funnelchat\Generated') . "\n/**\n"
            . " * The schemas of the spec as PHPStan type aliases (array shapes): the decoded JSON of the API.\n"
            . " * The resource methods use them in their PHPDoc. To use one in your code, import it:\n"
            . " * `@phpstan-import-type ContactResource from \\Funnelchat\\Generated\\Schemas`.\n"
            . " *\n"
            . " * Schemas that start with `Pending` come from spec/pending.json.\n"
            . " *\n"
            . implode("\n", $tags) . "\n"
            . " */\n"
            . "final class Schemas\n{\n    private function __construct() {}\n}\n";
    }

    // ---- Step 5: webhook events -----------------------------------------------------------------

    /**
     * @param Json $merged
     */
    private function generateEvents(array $merged, Types $types): string
    {
        /** @var array<string, array{type: string, domains: list<string>, status: string, pending: bool, pendingFields: list<string>, description: ?string, schema: Json, example: mixed}> $byType */
        $byType = [];
        foreach (self::obj($merged['webhooks'] ?? null) as $key => $item) {
            $type = self::eventTypeOfKey((string) $key);
            $post = self::obj(self::obj($item)['post'] ?? null);
            $content = self::obj(self::obj(self::obj($post['requestBody'] ?? null)['content'] ?? null)['application/json'] ?? null);
            $entry = $byType[$type] ?? [
                'type' => $type,
                'domains' => [],
                'status' => self::eventStatus($post),
                'pending' => isset($post['x-fc-pending']),
                'pendingFields' => array_values(array_filter(self::arr($post['x-fc-pending-fields'] ?? null), 'is_string')),
                'description' => self::str($post['description'] ?? null),
                'schema' => self::obj(self::obj(self::obj($content['schema'] ?? null)['properties'] ?? null)['data'] ?? null),
                'example' => self::obj($content['example'] ?? null)['data'] ?? null,
            ];
            $domain = self::str($post['x-fc-domain'] ?? null);
            if ($domain !== null && !\in_array($domain, $entry['domains'], true)) {
                $entry['domains'][] = $domain;
            }
            $byType[$type] = $entry;
        }
        ksort($byType);

        $catalog = [];
        $tags = [];
        $typeLiterals = [];
        $usedNames = [];
        $types->startUsage();
        foreach ($byType as $type => $entry) {
            $base = Php::studly($type);
            $name = $base;
            for ($n = 2; isset($usedNames[$name]); ++$n) {
                $name = $base . $n;
            }
            $usedNames[$name] = true;

            if (\is_array($entry['example']) && $entry['example'] !== []) {
                $dataType = $types->fromExample($entry['example'], $entry['schema'], 0);
                $source = $entry['schema'] !== [] ? 'example + schema' : 'example';
            } elseif ($entry['schema'] !== []) {
                $dataType = $types->render($entry['schema'], 0);
                $source = 'schema';
            } else {
                $dataType = 'array<string, mixed>';
                $source = 'no example or schema yet';
            }
            $tags[] = Php::docTag("@phpstan-type {$name}Data", $dataType);
            $tags[] = Php::docTag("@phpstan-type {$name}Event", "array{\n    id: string,\n    type: " . Php::literal($type) . ",\n    version: int,\n    occurred_at: string,\n    data: {$name}Data,\n}");
            $typeLiterals[] = Php::literal($type);
            $catalog[] = '        ' . Php::value($type) . ' => [' . implode(', ', [
                "'domains' => " . Php::value($entry['domains']),
                "'status' => " . Php::value($entry['status']),
                "'pending' => " . Php::value($entry['pending']),
                "'pendingFields' => " . Php::value($entry['pendingFields']),
                "'typedFrom' => " . Php::value($source),
                "'description' => " . Php::value($entry['description']),
            ]) . '],';
        }
        $imports = [];
        foreach ($types->usedAliases() as $alias) {
            $imports[] = " * @phpstan-import-type {$alias} from Schemas";
        }
        $count = \count($byType);

        return $this->header('Funnelchat\Generated') . "\n/**\n"
            . " * The webhook events of the spec: the catalog and the type of each delivery.\n"
            . " *\n"
            . " * `WebhookEvent` is what `Webhook::constructEvent()` returns: the envelope every delivery shares.\n"
            . " * Its `type` is a string, because new types can arrive before this SDK knows them. Once you\n"
            . " * checked `type`, the `XxxEvent` alias of that type (or `XxxData` for its `data`) gives the shape:\n"
            . " * `@phpstan-import-type MessageReceivedEvent from \\Funnelchat\\Generated\\WebhookEvents`.\n"
            . " * When the spec only has an example, the type is inferred from it: keys in the example are\n"
            . " * required and `null` values are `mixed`. `WebhookEventType` is every type the spec knows.\n"
            . " *\n"
            . ($imports === [] ? '' : implode("\n", $imports) . "\n *\n")
            . implode("\n", $tags) . "\n"
            . Php::docTag('@phpstan-type WebhookEventType', implode('|', $typeLiterals)) . "\n"
            . Php::docTag('@phpstan-type WebhookEvent', "array{\n    id: string,\n    type: string,\n    version: int,\n    occurred_at: string,\n    data: mixed,\n}") . "\n"
            . " */\n"
            . "final class WebhookEvents\n{\n"
            . "    /**\n"
            . "     * Every webhook event type known to the spec ({$count}). `status`: `live` is emitted today;\n"
            . "     * `planned` can be subscribed to, but nothing is emitted yet. `pending` = taken from\n"
            . "     * spec/pending.json; `pendingFields` = fields of `data` added by spec/pending.json.\n"
            . "     *\n"
            . "     * @var array<string, array{domains: list<string>, status: 'live'|'planned', pending: bool, pendingFields: list<string>, typedFrom: string, description: string|null}>\n"
            . "     */\n"
            . "    public const CATALOG = [\n"
            . implode("\n", $catalog) . "\n"
            . "    ];\n\n"
            . "    private function __construct() {}\n"
            . "}\n";
    }

    /**
     * @param Json $post
     */
    private static function eventStatus(array $post): string
    {
        $status = $post['x-fc-status'] ?? null;
        if ($status === 'planned' || $status === 'live') {
            return $status;
        }
        if ($status === 'active') {
            return 'live';
        }

        return preg_match('/planificad/i', self::str($post['summary'] ?? null) ?? '') === 1 ? 'planned' : 'live';
    }

    // ---- Step 6: resources ----------------------------------------------------------------------

    /**
     * @param Json $merged
     * @param Json $map
     *
     * @return array{array<string, string>, array<string, string>, list<string>}
     */
    private function generateResources(array $merged, array $map, Types $types): array
    {
        $paths = self::obj($merged['paths'] ?? null);
        $files = [];
        $sdkMethods = [];
        $errors = [];
        foreach (self::arr($map['resources'] ?? null) as $resource) {
            $resource = self::obj($resource);
            $class = self::str($resource['class'] ?? null) ?? '';
            $property = self::str($resource['property'] ?? null) ?? '';
            if (preg_match('/^[A-Z][A-Za-z0-9]*$/', $class) !== 1 || preg_match('/^[a-z][A-Za-z0-9]*$/', $property) !== 1) {
                $errors[] = "recurso con class \"{$class}\" o property \"{$property}\" inválidos";

                continue;
            }
            $types->startUsage();
            $methods = [];
            $usesPaginator = false;
            foreach (self::arr($resource['methods'] ?? null) as $method) {
                $method = self::obj($method);
                $name = self::str($method['name'] ?? null) ?? '';
                $label = self::str($method['op'] ?? null) ?? '';
                if (preg_match('/^(GET|POST|PUT|PATCH|DELETE) (\/\S*)$/', $label, $m) !== 1) {
                    $errors[] = "{$class}::{$name}: op \"{$label}\" no tiene la forma \"MÉTODO /ruta\"";

                    continue;
                }
                $op = self::obj(self::obj($paths[$m[2]] ?? null)[strtolower($m[1])] ?? null);
                if ($op === []) {
                    $errors[] = "{$class}::{$name}: la operación {$label} ya no está en la especificación";

                    continue;
                }
                $doc = self::str($method['doc'] ?? null) ?? '';
                if (!str_starts_with($doc, "`{$label}`")) {
                    $errors[] = "{$class}::{$name}: el doc tiene que empezar con `{$label}`";

                    continue;
                }
                $paginate = ($method['paginate'] ?? false) === true;
                $usesPaginator = $usesPaginator || $paginate;
                $query = $method['query'] ?? null;
                $methods[] = $this->generateMethod(
                    $name,
                    $m[1],
                    $m[2],
                    $op,
                    $doc,
                    $paginate,
                    \is_bool($query) ? $query : null,
                    ($method['bodyOptional'] ?? false) === true,
                    self::obj($method['bodyDefaults'] ?? null),
                    $types,
                );
                $sdkMethods[$label] = "{$property}->{$name}";
            }

            $traits = array_values(array_filter(self::arr($resource['traits'] ?? null), 'is_string'));
            $uses = ['Funnelchat\Core\AbstractResource', 'Funnelchat\Funnelchat', ...$traits];
            $aliases = $types->usedAliases();
            if ($aliases !== []) {
                $uses[] = 'Funnelchat\Generated\Schemas';
            }
            if ($usesPaginator) {
                $uses[] = 'Funnelchat\Pagination\Paginator';
            }
            sort($uses);
            $imports = [' * @phpstan-import-type RequestOptions from Funnelchat'];
            foreach ($aliases as $alias) {
                $imports[] = " * @phpstan-import-type {$alias} from Schemas";
            }
            $traitLines = array_map(static fn(string $trait): string => '    use ' . substr((string) strrchr('\\' . $trait, '\\'), 1) . ";\n\n", $traits);

            $files["src/Resources/{$class}.php"] = $this->header('Funnelchat\Resources')
                . "\n" . implode('', array_map(static fn(string $use): string => "use {$use};\n", $uses))
                . "\n/**\n" . Php::docText(self::str($resource['doc'] ?? null) ?? '', '') . " *\n"
                . implode("\n", $imports) . "\n */\n"
                . "final class {$class} extends AbstractResource\n{\n"
                . implode('', $traitLines)
                . implode("\n", $methods)
                . "}\n";
        }

        return [$files, $sdkMethods, $errors];
    }

    /**
     * @param Json      $op
     * @param bool|null $withQuery    force the `$query` parameter on or off; default: when the spec declares query parameters, or on lists
     * @param Json      $bodyDefaults body field => path parameter
     */
    private function generateMethod(
        string $name,
        string $httpMethod,
        string $path,
        array $op,
        string $doc,
        bool $paginate,
        ?bool $withQuery,
        bool $bodyOptional,
        array $bodyDefaults,
        Types $types,
    ): string {
        preg_match_all('/\{([^}]+)\}/', $path, $placeholders);
        $parameters = [];
        foreach (self::arr($op['parameters'] ?? null) as $parameter) {
            $parameter = $types->resolveParameter(self::obj($parameter));
            $parameters[] = $parameter;
        }

        /** @var list<array{name: string, doc: string, native: string, required: bool, variable: string}> $args */
        $args = [];
        $call = [];
        $pathEntries = [];
        $taken = ['query' => true, 'body' => true, 'options' => true, 'result' => true];
        foreach ($placeholders[1] as $placeholder) {
            $schema = [];
            foreach ($parameters as $parameter) {
                if (($parameter['in'] ?? null) === 'path' && ($parameter['name'] ?? null) === $placeholder) {
                    $schema = self::obj($parameter['schema'] ?? null);
                }
            }
            $variable = Php::variable($placeholder, $taken);
            $taken[$variable] = true;
            $docType = $types->render($schema, 0);
            $docType = match ($docType) {
                'int', 'string' => $docType,
                default => 'int|string',
            };
            $args[] = ['name' => $placeholder, 'doc' => $docType, 'native' => 'int|string', 'required' => true, 'variable' => $variable];
            $pathEntries[] = Php::value($placeholder) . " => \${$variable}";
        }
        if ($pathEntries !== []) {
            $call[] = "'path' => [" . implode(', ', $pathEntries) . ']';
        }

        // Query: the declared query parameters, plus `page` and `cursor` on lists.
        $queryShape = [];
        $queryRequired = false;
        foreach ($parameters as $parameter) {
            if (($parameter['in'] ?? null) !== 'query') {
                continue;
            }
            $key = self::str($parameter['name'] ?? null) ?? '';
            $required = ($parameter['required'] ?? false) === true;
            $queryRequired = $queryRequired || $required;
            $shapeKey = str_ends_with($key, '[]') ? substr($key, 0, -2) : $key;
            if (isset($queryShape[$shapeKey])) {
                $shapeKey = $key;
            }
            $queryShape[$shapeKey] = ['type' => $types->render(self::obj($parameter['schema'] ?? null), 1), 'required' => $required];
        }
        if ($paginate) {
            $queryShape += ['page' => ['type' => 'int', 'required' => false], 'cursor' => ['type' => 'string', 'required' => false]];
        }
        if ($withQuery ?? $queryShape !== []) {
            $args[] = ['name' => 'query', 'doc' => Types::shape($queryShape, 0), 'native' => 'array', 'required' => $queryRequired, 'variable' => 'query'];
            $call[] = "'query' => \$query";
        }

        // Body: the JSON request body.
        $requestBody = self::obj($op['requestBody'] ?? null);
        $bodySchema = self::obj(self::obj(self::obj($requestBody['content'] ?? null)['application/json'] ?? null)['schema'] ?? null);
        $bodyDefaultLines = [];
        if ($requestBody !== []) {
            if ($bodyDefaults !== []) {
                $bodySchema = $types->resolve($bodySchema);
                $bodySchema['required'] = array_values(array_diff(self::strings($bodySchema['required'] ?? null), array_keys($bodyDefaults)));
                foreach ($bodyDefaults as $field => $placeholder) {
                    $variable = null;
                    foreach ($args as $arg) {
                        if ($arg['name'] === $placeholder) {
                            $variable = $arg['variable'];
                        }
                    }
                    if ($variable === null) {
                        throw new GeneratorError("{$name}: bodyDefaults usa el parámetro de ruta \"" . Php::value($placeholder) . "\", que {$path} no tiene");
                    }
                    $bodyDefaultLines[] = '        $body[' . Php::value((string) $field) . "] ??= \${$variable};";
                }
            }
            $bodyType = $types->render($bodySchema, 0);
            // Like the Node SDK: the body is an argument you pass, unless spec/resources.json says
            // it is optional (an empty array is sent as `{}`).
            $required = !$bodyOptional;
            $isArray = str_starts_with($bodyType, 'array') || str_starts_with($bodyType, 'list') || $types->isAliasOfArray($bodyType);
            $args[] = [
                'name' => 'body',
                'doc' => $required ? $bodyType : "{$bodyType}|null",
                'native' => $isArray ? ($required ? 'array' : '?array') : 'mixed',
                'required' => $required,
                'variable' => 'body',
            ];
            $call[] = "'body' => \$body";
        }

        // A parameter followed by a required one cannot have a default.
        $mustBeRequired = false;
        for ($i = \count($args) - 1; $i >= 0; --$i) {
            if ($args[$i]['required']) {
                $mustBeRequired = true;
            } elseif ($mustBeRequired) {
                $args[$i]['required'] = true;
            }
        }

        // Response.
        [$responseType, $hasContent, $hasEmpty] = $this->responseType($op, $types);
        if ($paginate) {
            $returnDoc = 'Paginator<' . $this->listItemType($op, $types) . '>';
            $native = 'Paginator';
        } elseif (!$hasContent) {
            // Never `void`: when the spec documents no body, whatever the API answers is still
            // returned (a regeneration must not drop a body the API started sending).
            $returnDoc = 'mixed';
            $native = 'mixed';
        } else {
            $returnDoc = $hasEmpty ? "{$responseType}|null" : $responseType;
            $native = 'mixed';
        }

        // PHPDoc.
        $lines = [Php::docText($doc, '    ')];
        $notes = [];
        $permission = self::str($op['x-fc-permission'] ?? null);
        if ($permission !== null) {
            $notes[] = "Permission: `{$permission}`.";
        }
        $pending = $op['x-fc-pending'] ?? null;
        if ($pending !== null) {
            $fields = array_filter(self::arr($op['x-fc-pending-fields'] ?? null), 'is_string');
            $notes[] = 'Pending of the spec' . (\is_string($pending) ? " (`{$pending}` in spec/pending.json)" : '')
                . ($fields === [] ? '.' : ': `' . implode('`, `', $fields) . '`.');
        }
        $pendingResponse = $op['x-fc-pending-response'] ?? null;
        if ($pendingResponse !== null) {
            $notes[] = 'Response: pending of the spec' . (\is_string($pendingResponse) ? " (`{$pendingResponse}` in spec/pending.json)" : '')
                . '; it may change.';
        }
        if (!$hasContent && !$paginate) {
            $notes[] = 'The spec documents no body: returns `null`, or the decoded body if the API sends one.';
        }
        if ($notes !== []) {
            $lines[] = "     *\n" . Php::docText(implode("\n", $notes), '    ');
        }
        $tags = [];
        foreach ($args as $arg) {
            $tags[] = Php::docTag("@param {$arg['doc']}", '', '    ', " \${$arg['variable']}");
        }
        $tags[] = '     * @param RequestOptions $options';
        $docblock = "    /**\n" . implode('', $lines) . "     *\n" . implode("\n", $tags) . "\n";
        $docblock .= "     *\n" . Php::docTag("@return {$returnDoc}", '', '    ') . "\n     */\n";

        $signature = [];
        foreach ($args as $arg) {
            $default = '';
            if (!$arg['required']) {
                $default = $arg['native'] === '?array' || $arg['native'] === 'mixed' ? ' = null' : ' = []';
            }
            $signature[] = "{$arg['native']} \${$arg['variable']}{$default}";
        }
        $signature[] = 'array $options = []';

        $request = '[' . implode(', ', $call) . '] + $options';
        if ($call === []) {
            $request = '$options';
        }
        $body = implode('', array_map(static fn(string $line): string => "{$line}\n", $bodyDefaultLines)) . ($bodyDefaultLines === [] ? '' : "\n");
        if ($paginate) {
            $body .= Php::docTag("@var {$returnDoc} \$result", '', '        ', '', true) . "\n"
                . '        $result = $this->client->paginate(' . Php::value($path) . ", {$request});\n\n"
                . "        return \$result;\n";
        } elseif ($returnDoc === 'mixed') {
            $body .= '        return $this->client->request(' . Php::value($httpMethod) . ', ' . Php::value($path) . ", {$request});\n";
        } else {
            $body .= Php::docTag("@var {$returnDoc} \$result", '', '        ', '', true) . "\n"
                . '        $result = $this->client->request(' . Php::value($httpMethod) . ', ' . Php::value($path) . ", {$request});\n\n"
                . "        return \$result;\n";
        }

        return $docblock
            . "    public function {$name}(" . implode(', ', $signature) . "): {$native}\n"
            . "    {\n{$body}    }\n";
    }

    /**
     * The type of the 2xx answers of an operation.
     *
     * @param Json $op
     *
     * @return array{string, bool, bool} the type, whether some answer has content, whether some has none
     */
    private function responseType(array $op, Types $types): array
    {
        $variants = [];
        $hasEmpty = false;
        foreach ($this->successSchemas($op, $types) as $schema) {
            if ($schema === null) {
                $hasEmpty = true;
            } else {
                $variants[] = $types->render($schema, 0);
            }
        }

        return [Types::union($variants), $variants !== [], $hasEmpty];
    }

    /**
     * @param Json $op
     */
    private function listItemType(array $op, Types $types): string
    {
        $items = [];
        foreach ($this->successSchemas($op, $types) as $schema) {
            if ($schema !== null) {
                $items = [...$items, ...$types->listItems($schema)];
            }
        }

        return $items === [] ? 'mixed' : Types::union($items);
    }

    /**
     * The JSON schema of every 2xx answer (`null` for an answer without content).
     *
     * @param Json $op
     *
     * @return list<Json|null>
     */
    private function successSchemas(array $op, Types $types): array
    {
        $responses = self::obj($op['responses'] ?? null);
        $result = [];
        foreach (SUCCESS_STATUSES as $status) {
            if (!isset($responses[$status])) {
                continue;
            }
            $response = $types->resolveResponse(self::obj($responses[$status]));
            $schema = self::obj(self::obj(self::obj($response['content'] ?? null)['application/json'] ?? null)['schema'] ?? null);
            $result[] = $schema === [] && !isset(self::obj($response['content'] ?? null)['application/json']) ? null : $schema;
        }

        return $result;
    }

    // ---- Step 7: the client's resource properties -----------------------------------------------

    /**
     * @param Json $map
     */
    private function generateResourceProperties(array $map): string
    {
        $uses = [];
        $properties = [];
        $inits = [];
        foreach (self::arr($map['resources'] ?? null) as $resource) {
            $resource = self::obj($resource);
            $class = self::str($resource['class'] ?? null) ?? '';
            $property = self::str($resource['property'] ?? null) ?? '';
            $uses[] = "Funnelchat\\Resources\\{$class}";
            $summary = explode("\n", self::str($resource['doc'] ?? null) ?? '')[0];
            $properties[] = "    /**\n" . Php::docText($summary, '    ') . "     */\n    public readonly {$class} \${$property};\n";
            $inits[] = "        \$this->{$property} = new {$class}(\$this);";
        }
        sort($uses);

        return $this->header('Funnelchat\Generated')
            . "\n" . implode('', array_map(static fn(string $use): string => "use {$use};\n", $uses))
            . "\n/**\n * The resources of the client, one per piece of the API (spec/resources.json).\n */\n"
            . "trait ResourceProperties\n{\n"
            . implode("\n", $properties)
            . "\n    private function initResources(): void\n    {\n"
            . implode("\n", $inits) . "\n    }\n}\n";
    }

    // ---- Helpers --------------------------------------------------------------------------------

    private function header(string $namespace): string
    {
        return "<?php\n\ndeclare(strict_types=1);\n\n/*\n * " . self::MARKER . "\n"
            . ' * Source: spec/funnelchat.json (sha256 ' . substr($this->specHash, 0, 16) . "…) + spec/pending.json + spec/resources.json.\n */\n\n"
            . "namespace {$namespace};\n";
    }

    /**
     * Generated files on disk that the current generation does not produce.
     *
     * @param array<string, string> $files
     *
     * @return list<string>
     */
    private function staleFiles(array $files): array
    {
        $stale = [];
        foreach (self::GENERATED_DIRS as $dir) {
            foreach (glob($this->path($dir) . '/*.php') ?: [] as $file) {
                $relative = $dir . '/' . basename($file);
                if (!isset($files[$relative]) && str_contains(self::read($file), self::MARKER)) {
                    $stale[] = $relative;
                }
            }
        }

        return $stale;
    }

    /**
     * @param Json $spec
     */
    private static function countOperations(array $spec): int
    {
        $n = 0;
        foreach (self::obj($spec['paths'] ?? null) as $item) {
            foreach (HTTP_METHODS as $method) {
                if (isset(self::obj($item)[$method])) {
                    ++$n;
                }
            }
        }

        return $n;
    }

    private function path(string $relative): string
    {
        return $this->root . '/' . $relative;
    }

    private static function read(string $file): string
    {
        $content = file_get_contents($file);
        if ($content === false) {
            throw new GeneratorError("No se pudo leer {$file}");
        }

        return $content;
    }

    /**
     * @return Json
     */
    private static function decode(string $json, string $label): array
    {
        try {
            $value = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new GeneratorError("{$label} no es JSON válido: {$exception->getMessage()}");
        }
        if (!\is_array($value)) {
            throw new GeneratorError("{$label} no es un objeto JSON");
        }

        return $value;
    }

    /**
     * @param Json $schema
     */
    private static function refName(array $schema): ?string
    {
        $ref = self::str($schema['$ref'] ?? null);

        return $ref !== null && str_starts_with($ref, '#/components/schemas/') ? substr($ref, \strlen('#/components/schemas/')) : null;
    }

    /**
     * @return Json
     */
    public static function arr(mixed $value): array
    {
        return \is_array($value) ? $value : [];
    }

    /**
     * An object of the spec (a list is not one).
     *
     * @return array<string, mixed>
     */
    public static function obj(mixed $value): array
    {
        if (!\is_array($value) || ($value !== [] && array_is_list($value))) {
            return [];
        }
        $result = [];
        foreach ($value as $key => $item) {
            $result[(string) $key] = $item;
        }

        return $result;
    }

    public static function str(mixed $value): ?string
    {
        return \is_string($value) ? $value : null;
    }

    /**
     * The strings of a value that may be a string or a list (`type`, `required`).
     *
     * @return list<string>
     */
    public static function strings(mixed $value): array
    {
        if (\is_string($value)) {
            return [$value];
        }

        return array_values(array_filter(self::arr($value), 'is_string'));
    }
}

/**
 * JSON Schema -> PHPStan types (the subset the spec uses).
 *
 * @phpstan-import-type Json from Generator
 */
final class Types
{
    /** @var array<string, string> schema name => alias name */
    private array $aliases = [];

    /** @var array<string, true> */
    private array $used = [];

    /** @var array<string, true> "From->To" references that close a cycle: rendered as a plain array */
    private array $cycleBreaks = [];

    private ?string $current = null;

    /**
     * @param array<string, mixed> $schemas the merged components.schemas
     */
    public function __construct(private readonly array $schemas)
    {
        $taken = [];
        foreach (array_keys($schemas) as $name) {
            $alias = (string) preg_replace('/[^A-Za-z0-9_]/', '', ucwords(str_replace(['.', '-'], ' ', $name)));
            if ($alias === '' || ctype_digit($alias[0])) {
                $alias = 'Schema' . $alias;
            }
            if (\in_array(strtolower($alias), Php::RESERVED_TYPES, true)) {
                $alias .= 'Schema';
            }
            $base = $alias;
            for ($n = 2; isset($taken[$alias]); ++$n) {
                $alias = $base . $n;
            }
            $taken[$alias] = true;
            $this->aliases[$name] = $alias;
        }
        $this->findCycles();
    }

    /** @return array<string, string> */
    public function aliases(): array
    {
        return $this->aliases;
    }

    public function aliasBody(string $name): string
    {
        $this->current = $name;
        try {
            return $this->render(Generator::obj($this->schemas[$name] ?? null), 0);
        } finally {
            $this->current = null;
        }
    }

    public function startUsage(): void
    {
        $this->used = [];
    }

    /** @return list<string> the aliases referenced since startUsage(), sorted */
    public function usedAliases(): array
    {
        $aliases = array_keys($this->used);
        sort($aliases);

        return $aliases;
    }

    public function isAliasOfArray(string $type): bool
    {
        $name = array_search($type, $this->aliases, true);
        if ($name === false) {
            return false;
        }
        $schema = $this->resolve(['$ref' => "#/components/schemas/{$name}"]);
        $types = Generator::strings($schema['type'] ?? (isset($schema['properties']) ? 'object' : null));

        return array_diff($types, ['object', 'array']) === [] && $types !== [];
    }

    /**
     * Follows `$ref`s (and merges `allOf`) until a concrete schema.
     *
     * @param Json $schema
     *
     * @return array<string, mixed>
     */
    public function resolve(array $schema): array
    {
        for ($depth = 0; $depth < 20; ++$depth) {
            $ref = Generator::str($schema['$ref'] ?? null);
            if ($ref === null || !str_starts_with($ref, '#/components/schemas/')) {
                break;
            }
            $schema = Generator::obj($this->schemas[substr($ref, \strlen('#/components/schemas/'))] ?? null);
        }
        $merged = $this->mergeAllOf(Generator::obj($schema));

        return $merged ?? Generator::obj($schema);
    }

    /**
     * @param Json $parameter
     *
     * @return array<string, mixed>
     */
    public function resolveParameter(array $parameter): array
    {
        return Generator::obj($parameter);
    }

    /**
     * @param Json $response
     *
     * @return array<string, mixed>
     */
    public function resolveResponse(array $response): array
    {
        $ref = Generator::str($response['$ref'] ?? null);
        if ($ref === null) {
            return Generator::obj($response);
        }

        // Only #/components/responses/* exist in the spec; anything else is treated as empty.
        return [];
    }

    /**
     * The item types of a list response: `data[]`, or the array itself.
     *
     * @param Json $schema
     *
     * @return list<string>
     */
    public function listItems(array $schema): array
    {
        $schema = $this->resolve($schema);
        $variants = Generator::arr($schema['anyOf'] ?? $schema['oneOf'] ?? null);
        if ($variants !== []) {
            $items = [];
            foreach ($variants as $variant) {
                $items = [...$items, ...$this->listItems(Generator::obj($variant))];
            }

            return $items;
        }
        $types = Generator::strings($schema['type'] ?? null);
        if (\in_array('array', $types, true) && isset($schema['items'])) {
            return [$this->render(Generator::obj($schema['items']), 0)];
        }
        $data = Generator::obj(Generator::obj($schema['properties'] ?? null)['data'] ?? null);
        if ($data !== []) {
            $data = $this->resolve($data);
            if (\in_array('array', Generator::strings($data['type'] ?? null), true) && isset($data['items'])) {
                return [$this->render(Generator::obj($data['items']), 0)];
            }
        }

        return [];
    }

    /**
     * @param Json $schema
     */
    public function render(array $schema, int $depth): string
    {
        $schema = Generator::obj($schema);
        if ($schema === []) {
            return 'mixed';
        }
        $ref = Generator::str($schema['$ref'] ?? null);
        if ($ref !== null) {
            $name = str_starts_with($ref, '#/components/schemas/') ? substr($ref, \strlen('#/components/schemas/')) : null;
            if ($name === null || !isset($this->aliases[$name])) {
                return 'mixed';
            }
            if ($this->current !== null && isset($this->cycleBreaks["{$this->current}->{$name}"])) {
                return 'array<string, mixed>';
            }
            $this->used[$this->aliases[$name]] = true;

            return $this->aliases[$name];
        }
        if (\array_key_exists('const', $schema)) {
            return Php::literal($schema['const']);
        }
        // An `enum` next to `type: array` (a quirk of some query parameters, `status[]`) describes the
        // items, not the array: it is rendered with the array below.
        $isArray = \in_array('array', Generator::strings($schema['type'] ?? null), true);
        if (isset($schema['enum']) && \is_array($schema['enum']) && $schema['enum'] !== [] && !$isArray) {
            return self::union(array_map([Php::class, 'literal'], array_values($schema['enum'])));
        }
        $variants = Generator::arr($schema['anyOf'] ?? $schema['oneOf'] ?? null);
        if ($variants !== []) {
            return self::union(array_map(fn(mixed $variant): string => $this->render(Generator::obj($variant), $depth), array_values($variants)));
        }
        if (isset($schema['allOf'])) {
            $merged = $this->mergeAllOf($schema);
            $parts = Generator::arr($schema['allOf']);

            return $merged !== null ? $this->renderObject($merged, $depth) : $this->render(Generator::obj($parts[0] ?? null), $depth);
        }
        $types = $schema['type'] ?? (isset($schema['properties']) ? 'object' : null);
        if ($types === null) {
            return 'mixed';
        }
        $result = [];
        foreach ((array) $types as $type) {
            $result[] = match ($type) {
                'string' => 'string',
                'integer' => 'int',
                'number' => 'float|int',
                'boolean' => 'bool',
                'null' => 'null',
                'array' => isset($schema['items']) && !isset($schema['prefixItems']) ? 'list<' . $this->render(Generator::obj($schema['items']), $depth) . '>' : 'list<mixed>',
                'object' => $this->renderObject($schema, $depth),
                default => 'mixed',
            };
        }

        return self::union($result);
    }

    /**
     * The type of an example value, using the schema at the same place (if any) for enums, nulls
     * and keys the example does not show. Keys present in the example are required; `null` values
     * take the schema's type, or `mixed`.
     *
     * @param Json $hint
     */
    public function fromExample(mixed $value, array $hint, int $depth): string
    {
        $hint = $hint !== [] ? $this->resolve($hint) : [];
        if (isset($hint['enum']) || \array_key_exists('const', $hint)) {
            return $this->render($hint, $depth);
        }
        if ($value === null) {
            return isset($hint['type']) || isset($hint['$ref']) ? $this->render($hint, $depth) : 'mixed';
        }
        if (\is_array($value) && array_is_list($value)) {
            if ($value === []) {
                if (isset($hint['items'])) {
                    return 'list<' . $this->render(Generator::obj($hint['items']), $depth) . '>';
                }

                return isset($hint['properties']) ? $this->render($hint, $depth) : 'array<mixed>';
            }

            return 'list<' . $this->fromExample($value[0], Generator::obj($hint['items'] ?? null), $depth) . '>';
        }
        if (\is_string($value)) {
            return 'string';
        }
        if (\is_int($value)) {
            return 'int';
        }
        if (\is_float($value)) {
            return 'float|int';
        }
        if (\is_bool($value)) {
            return 'bool';
        }
        if (!\is_array($value)) {
            return 'mixed';
        }
        $hintProperties = Generator::obj($hint['properties'] ?? null);
        $shape = [];
        foreach ($value as $key => $item) {
            $shape[(string) $key] = ['type' => $this->fromExample($item, Generator::obj($hintProperties[(string) $key] ?? null), $depth + 1), 'required' => true];
        }
        foreach ($hintProperties as $key => $schema) {
            if (!isset($shape[$key])) {
                $shape[$key] = ['type' => $this->render(Generator::obj($schema), $depth + 1), 'required' => false];
            }
        }

        return self::shape($shape, $depth);
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function renderObject(array $schema, int $depth): string
    {
        $properties = Generator::obj($schema['properties'] ?? null);
        if ($properties === []) {
            $additional = $schema['additionalProperties'] ?? null;
            if (\is_array($additional) && $additional !== []) {
                return 'array<string, ' . $this->render($additional, $depth) . '>';
            }

            return 'array<string, mixed>';
        }
        $required = array_flip(array_filter(Generator::arr($schema['required'] ?? null), 'is_string'));
        $shape = [];
        foreach ($properties as $key => $property) {
            $shape[$key] = ['type' => $this->render(Generator::obj($property), $depth + 1), 'required' => isset($required[$key])];
        }

        return self::shape($shape, $depth);
    }

    /**
     * Merges the parts of an `allOf` when every part is an object schema.
     *
     * @param array<string, mixed> $schema
     *
     * @return array<string, mixed>|null
     */
    private function mergeAllOf(array $schema): ?array
    {
        if (!isset($schema['allOf'])) {
            return null;
        }
        $merged = ['type' => 'object', 'properties' => [], 'required' => []];
        foreach (Generator::arr($schema['allOf']) as $part) {
            $part = $this->resolve(Generator::obj($part));
            $types = Generator::strings($part['type'] ?? (isset($part['properties']) ? 'object' : null));
            if (!\in_array('object', $types, true)) {
                return null;
            }
            $merged['properties'] = Generator::obj($merged['properties']) + Generator::obj($part['properties'] ?? null);
            $merged['required'] = [...Generator::arr($merged['required']), ...Generator::arr($part['required'] ?? null)];
        }
        foreach (Generator::obj($schema['properties'] ?? null) as $key => $property) {
            $properties = Generator::obj($merged['properties']);
            $properties[$key] ??= $property;
            $merged['properties'] = $properties;
        }

        return $merged;
    }

    /**
     * @param array<string, array{type: string, required: bool}> $shape
     */
    public static function shape(array $shape, int $depth): string
    {
        $pad = str_repeat('    ', $depth + 1);
        $lines = [];
        foreach ($shape as $key => $entry) {
            $lines[] = $pad . Php::shapeKey($key) . ($entry['required'] ? '' : '?') . ': ' . $entry['type'] . ',';
        }

        return "array{\n" . implode("\n", $lines) . "\n" . str_repeat('    ', $depth) . '}';
    }

    /**
     * @param list<string> $types
     */
    public static function union(array $types): string
    {
        $flat = [];
        foreach ($types as $type) {
            foreach (self::topLevelMembers($type) as $member) {
                if ($member === 'mixed') {
                    return 'mixed';
                }
                $flat[$member] = true;
            }
        }
        $members = array_keys($flat);
        $hasNull = isset($flat['null']);
        $members = array_values(array_filter($members, static fn(string $m): bool => $m !== 'null'));
        if ($hasNull) {
            $members[] = 'null';
        }

        return $members === [] ? 'mixed' : implode('|', $members);
    }

    /**
     * The members of a union at the top level: `array{a: int|string}|null` -> `array{a: int|string}`, `null`.
     *
     * @return list<string>
     */
    private static function topLevelMembers(string $type): array
    {
        $members = [];
        $depth = 0;
        $current = '';
        foreach (str_split($type) as $char) {
            if ($char === '{' || $char === '<' || $char === '(') {
                ++$depth;
            } elseif ($char === '}' || $char === '>' || $char === ')') {
                --$depth;
            } elseif ($char === '|' && $depth === 0) {
                $members[] = $current;
                $current = '';

                continue;
            }
            $current .= $char;
        }
        $members[] = $current;

        return $members;
    }

    /** Self-references and longer cycles between schemas: PHPStan does not accept circular aliases. */
    private function findCycles(): void
    {
        $state = [];
        $visit = function (string $name, array $stack) use (&$visit, &$state): void {
            $state[$name] = 1;
            $stack[] = $name;
            foreach ($this->references($this->schemas[$name] ?? null) as $target) {
                if (($state[$target] ?? 0) === 1) {
                    $this->cycleBreaks["{$name}->{$target}"] = true;
                } elseif (!isset($state[$target]) && isset($this->schemas[$target])) {
                    $visit($target, $stack);
                }
            }
            $state[$name] = 2;
        };
        foreach (array_keys($this->schemas) as $name) {
            if (!isset($state[$name])) {
                $visit($name, []);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function references(mixed $node): array
    {
        if (!\is_array($node)) {
            return [];
        }
        $found = [];
        $ref = $node['$ref'] ?? null;
        if (\is_string($ref) && str_starts_with($ref, '#/components/schemas/')) {
            $found[] = substr($ref, \strlen('#/components/schemas/'));
        }
        foreach ($node as $child) {
            $found = [...$found, ...$this->references($child)];
        }

        return $found;
    }
}

/** Small helpers to write PHP source. */
final class Php
{
    public const RESERVED_TYPES = [
        'array', 'bool', 'boolean', 'callable', 'float', 'int', 'integer', 'iterable', 'list', 'mixed', 'never',
        'null', 'object', 'resource', 'self', 'static', 'string', 'true', 'false', 'void', 'scalar', 'number',
    ];

    /** A PHP literal for a scalar, `null` or a list of them. */
    public static function value(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }
        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (\is_int($value)) {
            return (string) $value;
        }
        if (\is_float($value)) {
            return var_export($value, true);
        }
        if (\is_string($value)) {
            if (preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
                return '"' . strtr($value, ['\\' => '\\\\', '"' => '\"', '$' => '\$', "\n" => '\n', "\r" => '\r', "\t" => '\t']) . '"';
            }

            return "'" . strtr($value, ['\\' => '\\\\', "'" => "\\'"]) . "'";
        }
        if (\is_array($value) && array_is_list($value)) {
            return '[' . implode(', ', array_map([self::class, 'value'], $value)) . ']';
        }

        throw new GeneratorError('No se puede escribir como literal: ' . get_debug_type($value));
    }

    /** A PHPStan literal type, or the general type when the literal cannot be written safely. */
    public static function literal(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            $value === true => 'true',
            $value === false => 'false',
            \is_int($value) => (string) $value,
            \is_float($value) => 'float',
            \is_string($value) && \strlen($value) <= 60 && preg_match('/^[^\x00-\x1F\x7F\'\\\\*]*$/u', $value) === 1 => "'{$value}'",
            \is_string($value) => 'string',
            default => 'mixed',
        };
    }

    public static function shapeKey(string $key): string
    {
        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key) === 1 && !\in_array(strtolower($key), self::RESERVED_TYPES, true)) {
            return $key;
        }
        if (preg_match('/^[^\x00-\x1F\x7F\'\\\\*]*$/u', $key) !== 1) {
            throw new GeneratorError("No se puede escribir la clave \"{$key}\" en un tipo");
        }

        return "'{$key}'";
    }

    /** `message.received` -> `MessageReceived`. */
    public static function studly(string $text): string
    {
        $words = preg_split('/[^A-Za-z0-9]+/', $text, -1, \PREG_SPLIT_NO_EMPTY) ?: [];

        return implode('', array_map(static fn(string $word): string => ucfirst($word), $words));
    }

    /**
     * A PHP variable name for a path parameter.
     *
     * @param array<string, true> $taken
     */
    public static function variable(string $name, array $taken): string
    {
        $variable = lcfirst(self::studly($name));
        if ($variable === '' || ctype_digit($variable[0])) {
            $variable = 'p' . $variable;
        }
        $base = $variable;
        for ($n = 2; isset($taken[$variable]); ++$n) {
            $variable = $base . $n;
        }

        return $variable;
    }

    /**
     * A PHPDoc tag whose type may span several lines: the first line carries the tag, the next
     * ones are indented inside the docblock, and `$suffix` (` $name`) goes after the last line.
     */
    public static function docTag(string $tag, string $type, string $indent = '', string $suffix = '', bool $inline = false): string
    {
        $text = $type === '' ? $tag : "{$tag} {$type}";
        $lines = explode("\n", $text . $suffix);
        if ($inline && \count($lines) === 1) {
            return "{$indent}/** {$lines[0]} */";
        }
        $body = implode("\n", array_map(static fn(string $line): string => rtrim("{$indent} * {$line}"), $lines));

        return $inline ? "{$indent}/**\n{$body}\n{$indent} */" : $body;
    }

    /** Free text as docblock lines, wrapped at 100 columns. */
    public static function docText(string $text, string $indent): string
    {
        $out = '';
        foreach (explode("\n", str_replace('*/', '*\/', $text)) as $paragraph) {
            if (trim($paragraph) === '') {
                $out .= "{$indent} *\n";

                continue;
            }
            $line = '';
            foreach (preg_split('/\s+/', trim($paragraph)) ?: [] as $word) {
                if ($line !== '' && \strlen($indent) + 3 + \strlen($line) + 1 + \strlen($word) > 100) {
                    $out .= "{$indent} * {$line}\n";
                    $line = $word;
                } else {
                    $line = $line === '' ? $word : "{$line} {$word}";
                }
            }
            $out .= "{$indent} * {$line}\n";
        }

        return $out;
    }
}

final class GeneratorError extends \RuntimeException {}

/** @var list<string> $argv */
exit(Generator::main($argv));
