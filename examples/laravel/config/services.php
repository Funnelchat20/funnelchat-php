<?php

declare(strict_types=1);

// config/services.php — add the `funnelchat` entry to the array your app already has.
// The values come from .env: never commit them.

return [
    // …

    'funnelchat' => [
        'key' => env('FUNNELCHAT_API_KEY'),                       // <id>|fc_live_...
        'webhook_secret' => env('FUNNELCHAT_WEBHOOK_SECRET'),     // the full secret, with whsec_
        'environment' => env('FUNNELCHAT_ENVIRONMENT', 'production'),
    ],
];
