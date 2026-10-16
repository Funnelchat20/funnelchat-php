<?php

declare(strict_types=1);

namespace Funnelchat\Tests;

use Funnelchat\Exception\InternalServerException;
use Funnelchat\Tests\Support\MockHttpClient;
use Funnelchat\Tests\Support\Reply;
use Funnelchat\Tests\Support\TestCase;

/**
 * What the sends return. PENDING OF THE SPEC: the shape is the one of conversations
 * `feat/envio-devuelve-id` @ d3ac33994 (local branch, no PR yet), in spec/pending.json
 * (`conversations-send-result`); it may change.
 */
final class SendResultTest extends TestCase
{
    /**
     * Fails unless the value is there. Generic, so it stays right both while the pending layer types
     * the answer as `…|null` (the API may still answer `204`) and once a snapshot drops the `null`.
     *
     * @template T
     *
     * @param T|null $value
     *
     * @return T
     */
    private static function notNull(mixed $value): mixed
    {
        self::assertNotNull($value);

        return $value;
    }

    /** The value as `mixed`, to check what comes at run time whatever the generated type says. */
    private static function raw(mixed $value): mixed
    {
        return $value;
    }

    /**
     * The public object of a message, as `PublicMessageResource` builds it: always these ten keys.
     *
     * @return array<string, mixed>
     */
    private static function message(int $id, string $type = 'text', ?string $status = 'queued'): array
    {
        return [
            'id' => $id,
            'contact_id' => 2001,
            'device_id' => 3,
            'direction' => 'outbound',
            'type' => $type,
            'status' => $status,
            'error' => null,
            'client_reference' => 'pedido-8841',
            'created_at' => '2026-10-16T05:52:46Z',
            'updated_at' => '2026-10-16T05:52:46Z',
        ];
    }

    public function testMessagesSendReturnsTheQueuedMessage(): void
    {
        $http = new MockHttpClient(Reply::ok(['message' => self::message(9001)]));

        $result = self::notNull($this->client($http)->messages->send(2001, [
            'device_id' => 3,
            'phone' => '5491100000000',
            'text' => 'Hola',
            'client_reference' => 'pedido-8841',
        ]));

        self::assertNotNull($result['message']);
        self::assertSame(9001, $result['message']['id']);
        self::assertSame('queued', $result['message']['status']);
        self::assertSame('pedido-8841', $result['message']['client_reference']);
        self::assertSame(self::message(9001), $result['message']);
    }

    public function testMessagesSendReturnsNullWhileTheApiAnswers204(): void
    {
        $http = new MockHttpClient(Reply::noContent());

        self::assertNull(self::raw($this->client($http)->messages->send(2001, ['device_id' => 3, 'phone' => '5491100000000', 'text' => 'Hola'])));
    }

    public function testAMessageThatCouldNotBeReadBackIsNull(): void
    {
        $http = new MockHttpClient(Reply::ok(['message' => null]));

        $result = $this->client($http)->messages->send(2001, ['device_id' => 3, 'phone' => '5491100000000', 'text' => 'Hola']);
        self::assertSame(['message' => null], $result);
    }

    public function testSendContactCardReturnsOneEntryPerCard(): void
    {
        $marker = ['id' => null] + self::message(0, 'contact');
        $http = new MockHttpClient(Reply::ok(['messages' => [self::message(9002, 'contact'), $marker]]));

        $result = self::notNull($this->client($http)->messages->sendContactCard(2001, ['device_id' => 3, 'contact_card_ids' => [11, 12]]));

        self::assertCount(2, $result['messages']);
        [$first, $second] = $result['messages'];
        self::assertNotNull($first);
        self::assertNotNull($second);
        self::assertSame(9002, $first['id']);
        // The cards after the first are markers: the same keys, no id.
        self::assertNull($second['id']);
        self::assertSame('queued', $second['status']);
    }

    public function testForwardReturnsOneItemPerDestinationWithItsMessage(): void
    {
        $http = new MockHttpClient(Reply::ok([
            ['contact_id' => 2002, 'mode' => 'internal', 'status' => 'queued', 'reason' => null, 'message' => self::message(9003)],
            ['contact_id' => 2003, 'mode' => 'internal', 'status' => 'blocked', 'reason' => 'window_closed', 'message' => null],
        ]));

        $items = $this->client($http)->messages->forward(2001, 77, ['targets' => [['contact_id' => 2002, 'device_id' => 3], ['contact_id' => 2003, 'device_id' => 3]]]);

        self::assertCount(2, $items);
        self::assertSame(9003, $items[0]['message']['id'] ?? null);
        self::assertSame('blocked', $items[1]['status']);
        self::assertNull($items[1]['message']);
    }

    public function testTemplatesSendReturnsTheMessage(): void
    {
        $http = new MockHttpClient(Reply::ok(['message' => self::message(9004, 'template')]));

        $result = $this->client($http)->templates->send(3, '45', ['phone' => '5491100000000', 'contact_id' => 2001]);

        self::assertNotNull($result['message']);
        self::assertSame('template', $result['message']['type']);
    }

    public function testATemplateRejectedByMetaIsA502WithItsBodyAndIsNotRetried(): void
    {
        $rejected = ['error' => 'template_rejected', 'error_code' => '132000', 'placeholders' => ['body.1'], 'message' => self::message(9005, 'template', 'failed')];
        $http = new MockHttpClient(Reply::json(502, $rejected), Reply::ok());

        try {
            $this->client($http, maxRetries: 2)->templates->send(3, '45', ['phone' => '5491100000000', 'contact_id' => 2001]);
            self::fail('should have thrown');
        } catch (InternalServerException $exception) {
            self::assertSame(502, $exception->status);
            self::assertSame('template_rejected', $exception->errorCode);
            self::assertSame($rejected, $exception->body);
        }
        self::assertCount(1, $http->requests);
    }

    public function testAMethodWhoseSpecDocumentsNoBodyStillReturnsTheBodyTheApiSends(): void
    {
        $http = new MockHttpClient(Reply::json(202, ['ok' => true]), Reply::noContent());
        $client = $this->client($http);

        self::assertSame(['ok' => true], $client->messages->react(2001, 99, ['emoji' => '👍']));
        self::assertNull($client->contacts->deleteNote(2001, 9));
    }
}
