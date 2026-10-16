<?php

declare(strict_types=1);

namespace Funnelchat\Core;

use Funnelchat\Funnelchat;

/**
 * Base of every resource: a group of operations of one piece of the API. The resources themselves
 * are generated (src/Resources) from spec/resources.json and the spec.
 */
abstract class AbstractResource
{
    public function __construct(protected readonly Funnelchat $client) {}

    /**
     * Dumping a resource does not dump the whole client again.
     *
     * @return array{}
     */
    public function __debugInfo(): array
    {
        return [];
    }
}
