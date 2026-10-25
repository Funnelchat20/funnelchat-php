<?php

/**
 * The first calls: your numbers, the 24-hour window and a send (free text or a template).
 *
 *   FUNNELCHAT_API_KEY='<id>|fc_live_...' php examples/plain-php/send-message.php 2001 5491100000000
 *
 * Sending needs a key with the **Contacts and conversations → Send** permission (`conversations:send`).
 */

declare(strict_types=1);

use Funnelchat\Exception\ApiException;
use Funnelchat\Exception\RateLimitException;
use Funnelchat\Funnelchat;

require __DIR__ . '/../../vendor/autoload.php'; // in your project: vendor/autoload.php

$contactId = (int) ($argv[1] ?? 0);
$phone = (string) ($argv[2] ?? '');
if ($contactId <= 0 || $phone === '') {
    fwrite(\STDERR, "Usage: php send-message.php <contact_id> <phone without +>\n");

    exit(1);
}

// The key comes from FUNNELCHAT_API_KEY. Never print it or commit it.
$funnelchat = new Funnelchat();

// Your WhatsApp numbers (permission WhatsApp numbers → View, `accounts:read`). The `id` of each one is the `device_id` you send with.
$deviceId = null;
foreach ($funnelchat->devices->list() as $device) {
    echo "number {$device['id']}: {$device['number']}\n";
    $deviceId ??= $device['id'];
}
if ($deviceId === null) {
    fwrite(\STDERR, "The account has no numbers.\n");

    exit(1);
}

try {
    $window = $funnelchat->contacts->metaWindow($contactId, ['device_id' => $deviceId]);

    if ($window['is_open']) {
        // Open window: free text. `send` returns the registered message, `queued`: the next states
        // arrive by `message.status_changed`, or read them with $funnelchat->messages->get().
        $result = $funnelchat->messages->send($contactId, [
            'device_id' => $deviceId,
            'phone' => $phone,                 // the phone of the same contact, without +
            'text' => 'Hi! Here is the info you asked for.',
            'client_reference' => 'pedido-8841', // optional: your reference to follow it
        ]);
        echo "Queued: {$result['message']['id']} ({$result['message']['status']})\n";
    } else {
        // Closed window: an approved template (the id comes from $funnelchat->templates->list()).
        $result = $funnelchat->templates->send($deviceId, '45', [
            'phone' => $phone,
            'contact_id' => $contactId,
            'template_variables' => ['body' => ['Lucía']],
        ]);
        echo "Template queued: {$result['message']['id']}\n";
    }
} catch (RateLimitException $exception) {
    // A send is never retried by itself: wait retryAfter seconds (mac_lockout_24h has none) and decide.
    fwrite(\STDERR, "{$exception->errorCode}: {$exception->getMessage()} (wait {$exception->retryAfter} s; request {$exception->requestId})\n");
} catch (ApiException $exception) {
    // window_closed, antiblock_*, recipient_*, device_unavailable… Decide by status and errorCode.
    // Cite requestId (the X-Request-Id) if you report it.
    fwrite(\STDERR, "{$exception->status} {$exception->errorCode}: {$exception->getMessage()} (request {$exception->requestId})\n");
}
