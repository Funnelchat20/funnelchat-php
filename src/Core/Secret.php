<?php

declare(strict_types=1);

namespace Funnelchat\Core;

use Funnelchat\Exception\FunnelchatException;

/**
 * A secret (the API key, the secret of a webhook subscription) that never shows: not in a stack
 * trace, a `var_dump`, `print_r`, `var_export`, `dd()` or `serialize`.
 *
 * `#[\SensitiveParameter]` hides a string argument from stack traces only from PHP 8.2 on, and a
 * stack trace shows each argument as it is when the exception is created. So the SDK takes a secret
 * out of its argument as soon as it arrives (it sets the argument to `null`), keeps it here, and from
 * then on passes this object. The value is not a property of the object: it lives in a static map
 * keyed by it.
 *
 * @internal
 */
final class Secret
{
    /** @var \WeakMap<self, string>|null */
    private static ?\WeakMap $values = null;

    private function __construct() {}

    public static function of(#[\SensitiveParameter] string $value): self
    {
        $secret = new self();
        self::$values ??= new \WeakMap();
        self::$values[$secret] = $value;

        return $secret;
    }

    public function reveal(): string
    {
        return self::$values[$this] ?? '';
    }

    /** @return array{} */
    public function __debugInfo(): array
    {
        return [];
    }

    /** @return never */
    public function __serialize(): array
    {
        throw new FunnelchatException('A secret cannot be serialized.');
    }

    private function __clone() {}
}
