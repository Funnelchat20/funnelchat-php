<?php

declare(strict_types=1);

namespace App\Providers;

use Funnelchat\Funnelchat;
use Illuminate\Support\ServiceProvider;

/**
 * One client for the whole app: inject `Funnelchat` wherever you need it.
 *
 * Register it in bootstrap/providers.php (Laravel 11+) or in config/app.php.
 */
final class FunnelchatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Funnelchat::class, static fn(): Funnelchat => new Funnelchat(
            apiKey: config()->string('services.funnelchat.key'), // Laravel 11+; before: (string) config(...)
            environment: config('services.funnelchat.environment') === 'staging' ? 'staging' : 'production',
        ));
    }
}
