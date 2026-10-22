<?php

declare(strict_types=1);

namespace Funnelchat\Tests;

use Funnelchat\Tests\Support\MockHttpClient;
use Funnelchat\Tests\Support\Reply;
use Funnelchat\Tests\Support\TestCase;

/**
 * What the sends return, as conversations v1.18.10 answers an API key (`ApiKeySendResponse`,
 * `PublicMessageResource`): `200` with the registered message, never `204`. In spec/pending.json
 * (`conversations-send`) until a snapshot brings them.
 */
final class SendResultTest extends TestCase
{
    /** The value as `mixed`, to check what comes at run time whatever the generated type says. */
    private static function raw(mixed $value): mixed
    {
        return $value;
    }

    /**
     * The public object of a message, as `PublicMessageResource` builds it: always these ten keys.
     *
     * @param array{code: int|null, title: string|null, reason: string|null}|null $error
     *
     * @return array<string, mixed>
     */
    private static function message(int $id, string $type = 'text', ?string $status = 'queued', ?array $error = null): array
    {
        return [
            'id' => $id,
            'contact_id' => 2001,
            'device_id' => 3,
            'direction' => 'outbound',
            'type' => $type,
            'status' => $status,
            'error' => $error,
            'client_reference' => 'pedido-8841',
            'created_at' => '2026-10-22T12:00:00Z',
            'updated_at' => '2026-10-22T12:00:00Z',
        ];
    }

    public function testMessagesSendReturnsTheQueuedMessage(): void
    {
        $http = new MockHttpClient(Reply::ok(['message' => self::message(9001)]));

        $result = $this->client($http)->messages->send(2001, [
            'device_id' => 3,
            'phone' => '5491100000000',
            'text' => 'Hola',
            'client_reference' => 'pedido-8841',
        ]);

        self::assertSame(9001, $result['message']['id']);
        self::assertSame('queued', $result['message']['status']);
        self::assertSame('pedido-8841', $result['message']['client_reference']);
        self::assertSame(self::message(9001), $result['message']);
    }

    public function testAFileFromASignedUrlCarriesItsTypeAndName(): void
    {
        $http = new MockHttpClient(Reply::ok(['message' => self::message(9006, 'document')]));

        $this->client($http)->messages->send(2001, [
            'device_id' => 3,
            'phone' => '5491100000000',
            'file' => 'https://files.example/descarga?firma=abc',
            'file_type' => 'document',
            'file_name' => 'factura.pdf',
        ]);

        $body = self::jsonBody($http->lastRequest());
        self::assertNotNull($body);
        self::assertSame('document', $body['file_type']);
        self::assertSame('factura.pdf', $body['file_name']);
        self::assertSame(2001, $body['contact_id']);
    }

    public function testAMessageThatCouldNotBeQueuedComesFailedWithItsReason(): void
    {
        $failed = self::message(9007, 'text', 'failed', ['code' => null, 'title' => 'No se pudo encolar.', 'reason' => 'not_queued']);
        $http = new MockHttpClient(Reply::ok(['message' => $failed]));

        $message = $this->client($http)->messages->send(2001, ['device_id' => 3, 'phone' => '5491100000000', 'text' => 'Hola'])['message'];

        self::assertSame('failed', $message['status']);
        self::assertSame('not_queued', $message['error']['reason'] ?? null);
    }

    public function testMessagesGetReadsOneMessageById(): void
    {
        $http = new MockHttpClient(Reply::ok(self::message(9001, 'text', 'delivered')));

        $message = $this->client($http)->messages->get(2001, 9001);

        self::assertSame('delivered', $message['status']);
        self::assertSame('GET', $http->lastRequest()->getMethod());
        self::assertSame('/api/v1/contacts/2001/messages/9001', $http->lastRequest()->getUri()->getPath());
    }

    public function testSendContactCardReturnsOneMessagePerCard(): void
    {
        $http = new MockHttpClient(Reply::ok(['messages' => [self::message(9002, 'contact'), self::message(9003, 'contact')]]));

        $result = $this->client($http)->messages->sendContactCard(2001, ['device_id' => 3, 'contact_card_ids' => [11, 12]]);

        self::assertCount(2, $result['messages']);
        self::assertSame([9002, 9003], array_column($result['messages'], 'id'));
    }

    public function testForwardReturnsOneItemPerDestinationWithItsMessage(): void
    {
        $http = new MockHttpClient(Reply::ok([
            ['contact_id' => 2002, 'mode' => 'internal', 'status' => 'queued', 'reason' => null, 'message' => self::message(9003)],
            ['contact_id' => 2003, 'mode' => 'native', 'status' => 'blocked', 'reason' => 'mac_lockout_24h', 'message' => null],
        ]));

        $items = $this->client($http)->messages->forward(2001, 77, ['targets' => [['contact_id' => 2002, 'device_id' => 3], ['contact_id' => 2003, 'device_id' => 3]]]);

        self::assertCount(2, $items);
        self::assertSame(9003, $items[0]['message']['id'] ?? null);
        self::assertSame('blocked', $items[1]['status']);
        self::assertSame('mac_lockout_24h', $items[1]['reason']);
        self::assertNull($items[1]['message']);
    }

    public function testTemplatesSendReturnsTheMessage(): void
    {
        $http = new MockHttpClient(Reply::ok(['message' => self::message(9004, 'template')]));

        $result = $this->client($http)->templates->send(3, '45', ['phone' => '5491100000000', 'contact_id' => 2001, 'client_reference' => 'pedido-8841']);

        self::assertSame('template', $result['message']['type']);
        self::assertSame('pedido-8841', self::jsonBody($http->lastRequest())['client_reference'] ?? null);
    }

    public function testFormsSendReturnsTheMessage(): void
    {
        $http = new MockHttpClient(Reply::ok(['message' => self::message(9008, 'whatsapp_form')]));

        $result = $this->client($http)->forms->send(2001, ['form_id' => 12, 'device_id' => 3, 'button_text' => 'Completar']);

        self::assertSame('whatsapp_form', $result['message']['type']);
        self::assertSame('/api/v1/contacts/2001/message/form', $http->lastRequest()->getUri()->getPath());
    }

    public function testAGroupMessageIsAnActionInTheQueue(): void
    {
        $http = new MockHttpClient(Reply::json(202, ['action_id' => '5b1f3c2e-7d4a-4f8e-9a6b-1c2d3e4f5a6b', 'status' => 'queued']));

        $accepted = $this->client($http)->groups->sendMessage('120363000000000001@g.us', ['device_id' => 4, 'type' => 'text', 'text' => 'Hola a todos']);

        self::assertSame('5b1f3c2e-7d4a-4f8e-9a6b-1c2d3e4f5a6b', $accepted['action_id']);
        self::assertSame('/api/v1/groups/120363000000000001%40g.us/messages', $http->lastRequest()->getUri()->getPath());
    }

    public function testAMethodWhoseSpecDocumentsNoBodyStillReturnsTheBodyTheApiSends(): void
    {
        $http = new MockHttpClient(Reply::json(202, ['ok' => true]), Reply::text(202, ''), Reply::noContent());
        $client = $this->client($http);

        self::assertSame(['ok' => true], $client->messages->react(2001, 99, ['emoji' => '👍']));
        self::assertNull(self::raw($client->messages->react(2001, 99, ['emoji' => '👍'])));
        self::assertNull($client->contacts->deleteNote(2001, 9));
    }
}
