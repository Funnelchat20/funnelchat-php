<?php

declare(strict_types=1);

namespace Funnelchat\Tests;

use Funnelchat\Exception\WebhookVerificationException;
use Funnelchat\Generated\WebhookEvents;
use Funnelchat\Tests\Fixtures\PortalSignatureVectors as V;
use Funnelchat\Tests\Support\MockHttpClient;
use Funnelchat\Tests\Support\TestCase;
use Funnelchat\Webhook;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/Fixtures/portal/verificar-firma.php';

/**
 * @phpstan-import-type MessageReceivedEvent from WebhookEvents
 */
final class WebhookSignatureTest extends TestCase
{
    /**
     * Calls a method of Webhook with arguments its PHPDoc rejects (a decoded body), the way a
     * caller without static analysis would.
     */
    private static function callUntyped(string $method, mixed ...$args): mixed
    {
        return (new \ReflectionMethod(Webhook::class, $method))->invoke(null, ...$args);
    }

    /**
     * @return array<string, array{string, string, string, bool}>
     */
    public static function portalCases(): array
    {
        $cases = [];
        foreach (V::cases(time()) as $name => $case) {
            $cases[$name] = [$case['body'], $case['header'], $case['secret'], $case['expected']];
        }

        return $cases;
    }

    #[DataProvider('portalCases')]
    public function testThePortalVectors(string $body, string $header, string $secret, bool $expected): void
    {
        self::assertSame($expected, Webhook::verify($body, $header, $secret));
        // The same body as a PSR-7 stream gives the same answer.
        self::assertSame($expected, Webhook::verify(Utils::streamFor($body), $header, $secret));
    }

    #[DataProvider('portalCases')]
    public function testAnswersLikeThePortalSnippetOnThePortalVectors(string $body, string $header, string $secret, bool $expected): void
    {
        self::assertSame(verificarFirma($body, $header, $secret), Webhook::verify($body, $header, $secret));
        self::assertSame($expected, verificarFirma($body, $header, $secret), 'the copy of the portal snippet still answers as test_firmas.py expects');
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function edgeCases(): array
    {
        $cases = [];
        foreach (V::edgeCases(time()) as $name => $case) {
            $cases[$name] = [$case['body'], $case['header'], $case['secret']];
        }

        return $cases;
    }

    #[DataProvider('edgeCases')]
    public function testAnswersLikeThePortalSnippetOnEdgeCases(string $body, string $header, string $secret): void
    {
        self::assertSame(verificarFirma($body, $header, $secret), Webhook::verify($body, $header, $secret));
        self::assertSame(verificarFirma($body, $header, $secret, 600), Webhook::verify($body, $header, $secret, 600));
    }

    public function testTheCopyOfThePortalSnippetIsTheOneRecordedInItsProvenance(): void
    {
        $provenance = json_decode((string) file_get_contents(__DIR__ . '/Fixtures/portal/procedencia.json'), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($provenance);
        self::assertSame($provenance['sha256'], hash_file('sha256', __DIR__ . '/Fixtures/portal/verificar-firma.php'));
    }

    public function testPinsTheHmacOfTheTimestampAndTheRawBodyWithTheFullSecret(): void
    {
        $t = V::FIXED_T;
        self::assertTrue(Webhook::verify(V::BODY, "t={$t},v1=" . V::FIXED_V1_BODY, V::SECRET, now: $t));
        self::assertTrue(Webhook::verify(V::BODY, "t={$t},v1=" . V::FIXED_V1_BODY_SECRET_WITHOUT_PREFIX, V::SECRET_WITHOUT_PREFIX, now: $t));
        self::assertTrue(Webhook::verify(V::UTF8_BODY, "t={$t},v1=" . V::FIXED_V1_UTF8_BODY, V::SECRET, now: $t));
        self::assertSame("t={$t},v1=" . V::FIXED_V1_BODY, V::sign(V::BODY, V::SECRET, $t));
    }

    public function testAcceptsAnyOfSeveralV1AndToleratesSpaces(): void
    {
        $t = V::FIXED_T;
        $other = str_repeat('a', 64);
        self::assertTrue(Webhook::verify(V::BODY, "t={$t}, v1={$other} , v1=" . V::FIXED_V1_BODY, V::SECRET, now: $t));
        self::assertTrue(Webhook::verify(V::BODY, 'v1=' . V::FIXED_V1_BODY . ",t={$t}", V::SECRET, now: $t));
    }

    public function testAppliesTheToleranceOnBothSidesAndACustomOne(): void
    {
        $t = V::FIXED_T;
        $header = "t={$t},v1=" . V::FIXED_V1_BODY;
        self::assertTrue(Webhook::verify(V::BODY, $header, V::SECRET, now: $t + 300));
        self::assertFalse(Webhook::verify(V::BODY, $header, V::SECRET, now: $t + 301));
        self::assertFalse(Webhook::verify(V::BODY, $header, V::SECRET, now: $t - 301));
        self::assertTrue(Webhook::verify(V::BODY, $header, V::SECRET, 600, now: $t + 600));
        self::assertFalse(Webhook::verify(V::BODY, $header, V::SECRET, 600, now: $t + 601));
    }

    public function testNeverThrowsFromVerify(): void
    {
        $t = V::FIXED_T;
        $header = "t={$t},v1=" . V::FIXED_V1_BODY;
        self::assertFalse(Webhook::verify(V::BODY, $header, '', now: $t));
        self::assertFalse(Webhook::verify(V::BODY, $header, null, now: $t));
        self::assertFalse(Webhook::verify(V::BODY, null, V::SECRET, now: $t));
        self::assertFalse(Webhook::verify(V::BODY, 't=abc,v1=' . V::FIXED_V1_BODY, V::SECRET, now: $t));
        // A decoded body ($request->all(), json_decode) is not the raw body.
        self::assertFalse(self::callUntyped('verify', json_decode(V::BODY, true), $header, V::SECRET, 300, $t));
        self::assertFalse(self::callUntyped('verify', json_decode(V::BODY), $header, V::SECRET, 300, $t));
    }

    public function testConstructEventReturnsTheDecodedEvent(): void
    {
        $t = V::FIXED_T;
        $event = Webhook::constructEvent(V::BODY, "t={$t},v1=" . V::FIXED_V1_BODY, V::SECRET, now: $t);

        self::assertSame('evt_000000000000000000000001', $event['id']);
        self::assertSame(1, $event['version']);
        self::assertSame('2026-10-01T12:00:00Z', $event['occurred_at']);
        self::assertSame('message.received', $event['type']);
        // Once `type` is checked, the alias of that type gives the shape of the event.
        /** @var MessageReceivedEvent $event */
        self::assertSame('+5491100000000', $event['data']['contact']['phone']);
        self::assertSame('Hola, quiero más información', $event['data']['message']['text']);
    }

    public function testIsTheSameOnTheClient(): void
    {
        $t = V::FIXED_T;
        $header = "t={$t},v1=" . V::FIXED_V1_BODY;
        $client = $this->client(new MockHttpClient());

        self::assertSame(
            Webhook::constructEvent(V::BODY, $header, V::SECRET, now: $t),
            $client->webhooks->constructEvent(V::BODY, $header, V::SECRET, now: $t),
        );
        self::assertTrue($client->webhooks->verify(V::BODY, $header, V::SECRET, now: $t));
    }

    /**
     * @return array<string, array{string, mixed, string|null, string|null, int}>
     */
    public static function failures(): array
    {
        $t = V::FIXED_T;
        $header = "t={$t},v1=" . V::FIXED_V1_BODY;

        return [
            'missing_header' => [WebhookVerificationException::MISSING_HEADER, V::BODY, null, V::SECRET, $t],
            'malformed_header' => [WebhookVerificationException::MALFORMED_HEADER, V::BODY, 'sha256=abc', V::SECRET, $t],
            'signature_mismatch' => [WebhookVerificationException::SIGNATURE_MISMATCH, V::BODY, $header, V::SECRET . 'x', $t],
            'missing_secret' => [WebhookVerificationException::MISSING_SECRET, V::BODY, $header, '', $t],
            'invalid_body' => [WebhookVerificationException::INVALID_BODY, ['parsed' => true], $header, V::SECRET, $t],
            'timestamp_out_of_tolerance' => [WebhookVerificationException::TIMESTAMP_OUT_OF_TOLERANCE, V::BODY, $header, V::SECRET, $t + 3600],
        ];
    }

    #[DataProvider('failures')]
    public function testConstructEventSaysWhichCheckFailed(string $reason, mixed $body, ?string $header, ?string $secret, int $now): void
    {
        try {
            self::callUntyped('constructEvent', $body, $header, $secret, 300, $now);
            self::fail('constructEvent should have thrown');
        } catch (WebhookVerificationException $exception) {
            self::assertSame($reason, $exception->reason);
            self::assertStringNotContainsString(V::SECRET, $exception->getMessage());
        }
    }

    public function testRejectsAValidlySignedBodyThatIsNotJsonOrNotAnEvent(): void
    {
        $t = V::FIXED_T;
        $cases = [
            'hola' => WebhookVerificationException::INVALID_JSON,
            '{"hola":1}' => WebhookVerificationException::INVALID_EVENT,
            '[1,2]' => WebhookVerificationException::INVALID_EVENT,
            '{"id":"evt_1","type":"x","data":{}}' => WebhookVerificationException::INVALID_EVENT,
        ];
        foreach ($cases as $body => $reason) {
            try {
                Webhook::constructEvent((string) $body, V::sign((string) $body, V::SECRET, $t), V::SECRET, now: $t);
                self::fail("constructEvent should have thrown for {$body}");
            } catch (WebhookVerificationException $exception) {
                self::assertSame($reason, $exception->reason);
            }
        }
    }
}
