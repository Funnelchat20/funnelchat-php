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
        return Webhook::verify($rawBody, $signatureHeader, $secret, $toleranceSeconds, $now);
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
        return Webhook::constructEvent($rawBody, $signatureHeader, $secret, $toleranceSeconds, $now);
    }
}
