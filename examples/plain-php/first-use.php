<?php

/**
 * The first use: connect a number by QR, send a message and know when it was read.
 *
 *   FUNNELCHAT_API_KEY='<id>|fc_live_...' php examples/plain-php/first-use.php 5491100000000 Ana
 *
 * 1. It creates a QR number and writes its QR to qr.html every few seconds: open that file in a
 *    browser and scan it with WhatsApp (Linked devices) until the number connects.
 * 2. It creates the contact (or finds it, if the phone is already there) and sends it a text.
 * 3. It asks for the message until WhatsApp says it was read. In your app that arrives by itself,
 *    as the `message.status_changed` webhook: see webhook.php.
 *
 * The key needs **WhatsApp numbers → Create, connect and delete** and **Contacts and
 * conversations → Send**. Against the sandbox, add FUNNELCHAT_BASE_URL=http://127.0.0.1:4010 (any
 * key with the right shape works there) and scan with `POST /_sandbox/devices/{id}/connect`.
 */

declare(strict_types=1);

use Funnelchat\Exception\ApiException;
use Funnelchat\Exception\ConflictException;
use Funnelchat\Funnelchat;

require __DIR__ . '/../../vendor/autoload.php'; // in your project: vendor/autoload.php

$phone = ltrim((string) ($argv[1] ?? ''), '+');
$name = (string) ($argv[2] ?? '');
if ($phone === '') {
    fwrite(\STDERR, "Usage: php first-use.php <phone without +> [name]\n");

    exit(1);
}

// The key comes from FUNNELCHAT_API_KEY. Never print it or commit it.
$baseUrl = getenv('FUNNELCHAT_BASE_URL');
$funnelchat = new Funnelchat(baseUrl: is_string($baseUrl) && $baseUrl !== '' ? $baseUrl : null);

try {
    // 2. Connect a number. The account has at most one QR number waiting to be connected: reuse it.
    try {
        $deviceId = $funnelchat->devices->create(['connection' => 'qr', 'alias' => 'My first number'])['data']['id'];
    } catch (ConflictException $exception) {
        $pending = is_array($exception->body) ? ($exception->body['device_id'] ?? null) : null;
        if ($exception->errorCode !== 'pending_number_exists' || !is_int($pending)) {
            throw $exception;
        }
        $deviceId = $pending;
    }
    echo "Number {$deviceId}: open qr.html and scan it with WhatsApp → Linked devices.\n";

    do {
        $qr = $funnelchat->devices->qr($deviceId)['data'];
        if ($qr['qr_code'] !== null) {
            // The QR changes every 5 seconds: show the newest one.
            file_put_contents('qr.html', '<meta http-equiv="refresh" content="3"><img src="' . htmlspecialchars($qr['qr_code']) . '">');
        }
        sleep(max(1, $qr['expires_in']));
    } while (!$qr['connected']);
    echo "Connected.\n";

    // 3. Send a message: the contact first, then the text, from the number you just connected.
    $contact = $funnelchat->contacts->create(['phone' => $phone, 'name' => $name !== '' ? $name : null]);
    $sent = $funnelchat->messages->send($contact['id'], [
        'device_id' => $deviceId,
        'phone' => $phone,
        'text' => 'Hi! This is my first message from the Funnelchat API.',
    ]);
    $messageId = $sent['message']['id'];
    echo "Message {$messageId}: {$sent['message']['status']}\n"; // queued: not sent yet

    // 4. Know when it was read: sent → delivered → read, or failed.
    $status = $sent['message']['status'];
    for ($deadline = time() + 600; time() < $deadline && $status !== 'read' && $status !== 'failed'; sleep(5)) {
        $message = $funnelchat->messages->get($contact['id'], $messageId);
        if ($message['status'] !== $status) {
            $status = $message['status'];
            echo "Message {$messageId}: {$status}\n";
        }
    }
} catch (ApiException $exception) {
    // Decide by status and errorCode; cite requestId (the X-Request-Id) if you report it.
    fwrite(\STDERR, "{$exception->status} {$exception->errorCode}: {$exception->getMessage()} (request {$exception->requestId})\n");

    exit(1);
}
