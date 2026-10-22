<?php

/**
 * Receiving Funnelchat webhooks with plain PHP (no framework).
 *
 * Point a subscription to this file (for example https://your-server.example/webhook.php) and set
 * FUNNELCHAT_WEBHOOK_SECRET to the full secret of the subscription, `whsec_` included.
 *
 *   php -S localhost:8000 examples/plain-php/webhook.php
 */

declare(strict_types=1);

use Funnelchat\Exception\WebhookVerificationException;
use Funnelchat\Generated\WebhookEvents;
use Funnelchat\Webhook;

require __DIR__ . '/../../vendor/autoload.php'; // in your project: vendor/autoload.php

/**
 * What to do with each event. Answer fast: the delivery is confirmed by any 2xx within 10 seconds.
 *
 * @phpstan-import-type WebhookEvent from WebhookEvents
 * @phpstan-import-type MessageReceivedEvent from WebhookEvents
 * @phpstan-import-type MessageStatusChangedEvent from WebhookEvents
 */
final class FunnelchatEvents
{
    /**
     * @param WebhookEvent $event
     */
    public static function handle(array $event): void
    {
        // A retry brings the same id: deduplicate by it before doing anything twice.
        if (self::alreadySeen($event['id'])) {
            return;
        }

        switch ($event['type']) {
            case 'message.received':
                /** @var MessageReceivedEvent $event */
                self::messageReceived($event);
                break;

            case 'message.status_changed':
                /** @var MessageStatusChangedEvent $event */
                self::statusChanged($event);
                break;

            default:
                // A type your code does not know yet: not an error, the answer is still 2xx.
                break;
        }
    }

    /**
     * @param MessageReceivedEvent $event
     */
    private static function messageReceived(array $event): void
    {
        error_log(sprintf('%s wrote: %s', $event['data']['contact']['phone'], $event['data']['message']['text']));
    }

    /**
     * @param MessageStatusChangedEvent $event
     */
    private static function statusChanged(array $event): void
    {
        error_log(sprintf('message %d: %s (%s)', $event['data']['message_id'], $event['data']['status'], $event['data']['client_reference'] ?? 'no reference'));
    }

    private static function alreadySeen(string $eventId): bool
    {
        // Use your database or cache in a real integration; a file is enough for the example.
        $marker = sys_get_temp_dir() . '/funnelchat-' . hash('sha256', $eventId);
        if (file_exists($marker)) {
            return true;
        }
        touch($marker);

        return false;
    }
}

// The raw body, exactly as it arrived: never json_decode() it and encode it again before verifying.
$rawBody = (string) file_get_contents('php://input');
$signature = isset($_SERVER['HTTP_X_FUNNELCHAT_SIGNATURE']) && is_string($_SERVER['HTTP_X_FUNNELCHAT_SIGNATURE']) ? $_SERVER['HTTP_X_FUNNELCHAT_SIGNATURE'] : null;
$secret = getenv('FUNNELCHAT_WEBHOOK_SECRET');

try {
    $event = Webhook::constructEvent($rawBody, $signature, $secret === false ? null : $secret);
} catch (WebhookVerificationException $exception) {
    // $exception->reason says why: missing_header, signature_mismatch, timestamp_out_of_tolerance…
    error_log('Funnelchat webhook rejected: ' . $exception->reason);
    http_response_code(400);

    exit;
}

http_response_code(200);
FunnelchatEvents::handle($event);
