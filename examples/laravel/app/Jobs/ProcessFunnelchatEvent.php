<?php

declare(strict_types=1);

namespace App\Jobs;

use Funnelchat\Generated\WebhookEvents;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Processes one verified event, outside the request.
 *
 * @phpstan-import-type WebhookEvent from WebhookEvents
 * @phpstan-import-type MessageReceivedEvent from WebhookEvents
 * @phpstan-import-type MessageStatusChangedEvent from WebhookEvents
 */
final class ProcessFunnelchatEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * @param WebhookEvent $event
     */
    public function __construct(public readonly array $event) {}

    public function handle(): void
    {
        // A retry brings the same id: process each event once.
        if (!Cache::add('funnelchat:event:' . $this->event['id'], true, now()->addDays(2))) {
            return;
        }

        $event = $this->event;
        switch ($event['type']) {
            case 'message.received':
                /** @var MessageReceivedEvent $event */
                $this->messageReceived($event);
                break;

            case 'message.status_changed':
                /** @var MessageStatusChangedEvent $event */
                $this->statusChanged($event);
                break;

            default:
                // A type your code does not know yet: not an error.
                break;
        }
    }

    /**
     * @param MessageReceivedEvent $event
     */
    private function messageReceived(array $event): void
    {
        Log::info('Mensaje de Funnelchat', [
            'phone' => $event['data']['contact']['phone'],
            'text' => $event['data']['message']['text'],
            'device_id' => $event['data']['device_id'] ?? null, // pending of the spec (conversations PR #2284)
        ]);
    }

    /**
     * @param MessageStatusChangedEvent $event
     */
    private function statusChanged(array $event): void
    {
        Log::info('Estado de un mensaje', [
            'message_id' => $event['data']['message_id'],
            'status' => $event['data']['status'],
            'client_reference' => $event['data']['client_reference'],
        ]);
    }
}
