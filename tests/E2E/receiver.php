<?php

/**
 * The webhook receiver of the end-to-end tests, for PHP's built-in server:
 *
 *   FUNNELCHAT_E2E_INBOX=<dir> php -S 127.0.0.1:<port> tests/E2E/receiver.php
 *
 * It answers 200 and writes each delivery, as it came (raw body and signature header), to a file
 * of the inbox directory. The tests verify the signature themselves, with the SDK.
 */

declare(strict_types=1);

$inbox = getenv('FUNNELCHAT_E2E_INBOX');
if (!is_string($inbox) || !is_dir($inbox)) {
    http_response_code(500);

    exit;
}

$delivery = [
    'signature' => $_SERVER['HTTP_X_FUNNELCHAT_SIGNATURE'] ?? null,
    'event' => $_SERVER['HTTP_X_FUNNELCHAT_EVENT'] ?? null,
    'body' => (string) file_get_contents('php://input'),
];
file_put_contents($inbox . '/' . hrtime(true) . '.json', json_encode($delivery, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES));
http_response_code(200);
