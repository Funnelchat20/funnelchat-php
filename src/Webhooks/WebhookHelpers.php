<?php

declare(strict_types=1);

namespace Funnelchat\Webhooks;

use Funnelchat\Exception\WebhookVerificationException;
use Funnelchat\Generated\WebhookEvents;
use Funnelchat\Webhook;
use Psr\Http\Message\StreamInterface;

/**
 * `verify()` and `constructEvent()` on `$funnelchat->webhooks`: the same as {@see Webhook}, which
 * needs no client (no API key).
 *
 * @phpstan-import-type WebhookEvent from WebhookEvents
 */
trait WebhookHelpers
{
    /**
     * Same as {@see Webhook::verify()}.
     *
     * @param string|StreamInterface $rawBody
     */
    public function verify(
        mixed $rawBody,
        ?string $signatureHeader,
        #[\SensitiveParameter]
        ?string $secret,
        int $toleranceSeconds = Webhook::DEFAULT_TOLERANCE_SECONDS,
        ?int $now = null,
    ): bool {
        // Not an argument of this frame while Webhook checks it: a stack trace shows the arguments as
        // they are when the exception is created, and PHP 8.1 has no #[\SensitiveParameter].
        $given = $secret;
        $secret = null;

        return Webhook::verify($rawBody, $signatureHeader, $given, $toleranceSeconds, $now);
    }

    /**
     * Same as {@see Webhook::constructEvent()}.
     *
     * @param string|StreamInterface $rawBody
     *
     * @return WebhookEvent
     *
     * @throws WebhookVerificationException
     */
    public function constructEvent(
        mixed $rawBody,
        ?string $signatureHeader,
        #[\SensitiveParameter]
        ?string $secret,
        int $toleranceSeconds = Webhook::DEFAULT_TOLERANCE_SECONDS,
        ?int $now = null,
    ): array {
        $given = $secret;
        $secret = null;

        return Webhook::constructEvent($rawBody, $signatureHeader, $given, $toleranceSeconds, $now);
    }
}
