<?php

declare(strict_types=1);

// routes/api.php — the `api` group has no CSRF check, which a webhook cannot pass.
// (Laravel 11+: `php artisan install:api` creates this file.) If you put the route in routes/web.php,
// exclude it from CSRF in bootstrap/app.php: $middleware->validateCsrfTokens(except: ['hooks/funnelchat']).

use App\Http\Controllers\FunnelchatWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/hooks/funnelchat', FunnelchatWebhookController::class);
