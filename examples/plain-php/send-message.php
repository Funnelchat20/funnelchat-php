<?php

/**
 * The first calls: your numbers, the 24-hour window and a send (free text or a template).
 *
 *   FUNNELCHAT_API_KEY='<id>|fc_live_...' php examples/plain-php/send-message.php 2001 5491100000000
 *
 * Sending with an API key opens with the `conversations:send` permission (conversations PR #2284,
 * not merged yet): until it is deployed, the send routes answer `403 route_not_available`.
 */

declare(strict_types=1);

use Funnelchat\Exception\ApiException;
use Funnelchat\Exception\RateLimitException;
use Funnelchat\Funnelchat;

require __DIR__ . '/../../vendor/autoload.php'; // in your project: vendor/autoload.php

$contactId = (int) ($argv[1] ?? 0);
$phone = (string) ($argv[2] ?? '');
if ($contactId <= 0 || $phone === '') {
    fwrite(\STDERR, "Uso: php send-message.php <contact_id> <teléfono sin +>\n");

    exit(1);
}

// The key comes from FUNNELCHAT_API_KEY. Never print it or commit it.
$funnelchat = new Funnelchat();

// Your WhatsApp numbers (permission accounts:read). The `id` of each one is the `device_id` you send with.
$deviceId = null;
foreach ($funnelchat->devices->list() as $device) {
    echo "número {$device['id']}: {$device['number']}\n";
    $deviceId ??= $device['id'];
}
if ($deviceId === null) {
    fwrite(\STDERR, "La cuenta no tiene números.\n");

    exit(1);
}

try {
    $window = $funnelchat->contacts->metaWindow($contactId, ['device_id' => $deviceId]);

    if ($window['is_open']) {
        // Open window: free text. `send` answers 204, with no body.
        $funnelchat->messages->send($contactId, [
            'device_id' => $deviceId,
            'phone' => $phone,                 // the phone of the same contact, without +
            'text' => '¡Hola! Te paso la info…',
            'client_reference' => 'pedido-8841', // optional: your reference to follow it
        ]);
        echo "Enviado.\n";
    } else {
        // Closed window: an approved template (the id comes from $funnelchat->templates->list()).
        $funnelchat->templates->send($deviceId, '45', [
            'phone' => $phone,
            'contact_id' => $contactId,
            'template_variables' => ['body' => ['Lucía']],
        ]);
        echo "Plantilla enviada.\n";
    }
} catch (RateLimitException $exception) {
    // A send is never retried by itself: wait retryAfter seconds and decide.
    fwrite(\STDERR, "Esperá {$exception->retryAfter} s: {$exception->getMessage()}\n");
} catch (ApiException $exception) {
    // window_closed, antiblock_*, recipient_*, route_not_available… Decide by status and errorCode.
    fwrite(\STDERR, "{$exception->status} {$exception->errorCode}: {$exception->getMessage()}\n");
}
