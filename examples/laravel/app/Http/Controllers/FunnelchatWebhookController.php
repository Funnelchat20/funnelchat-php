<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessFunnelchatEvent;
use Funnelchat\Exception\WebhookVerificationException;
use Funnelchat\Webhook;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Receives Funnelchat webhooks: verifies the signature on the raw body, queues the event and
 * answers right away (any 2xx within 10 seconds confirms the delivery).
 */
final class FunnelchatWebhookController
{
    public function __invoke(Request $request): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),                       // the raw body: not $request->all()
                $request->header(Webhook::SIGNATURE_HEADER),  // X-Funnelchat-Signature
                config()->string('services.funnelchat.webhook_secret'), // Laravel 11+; before: (string) config(...)
            );
        } catch (WebhookVerificationException $exception) {
            // $exception->reason: missing_header, signature_mismatch, timestamp_out_of_tolerance…
            Log::warning('Funnelchat webhook rejected', ['reason' => $exception->reason]);

            return response()->noContent(400);
        }

        ProcessFunnelchatEvent::dispatch($event);

        return response()->noContent(200);
    }
}
