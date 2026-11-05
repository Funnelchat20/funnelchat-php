<?php

declare(strict_types=1);

namespace Funnelchat\Tests;

use Funnelchat\Exception\InternalServerException;
use Funnelchat\Exception\PermissionDeniedException;
use Funnelchat\Exception\RateLimitException;
use Funnelchat\Exception\SendErrors;
use Funnelchat\Exception\UnprocessableEntityException;
use Funnelchat\Generated\Routes;
use Funnelchat\Tests\Support\MockHttpClient;
use Funnelchat\Tests\Support\Reply;
use Funnelchat\Tests\Support\TestCase;
use Funnelchat\Webhook;

/**
 * Sending a message to a group and reading how it ended (communities v2.35.5, `ApiGroupActionController`),
 * the participant events and the workspace of every event (accounts v1.5.97, `EventWorkspaceRouting`).
 * In spec/pending.json (`communities-group-messages`, `communities-participant-events`,
 * `accounts-workspace-id`): the full spec of 04-11 does not bring them yet.
 */
final class GroupsAndWorkspacesTest extends TestCase
{
    private const GROUP = '120363000000000001@g.us';

    private const ACTION = '00000000-0000-4000-8000-000000000001';

    private const SECRET = 'whsec_test_0000000000000000000000000000';

    /** A code as the API would give it: any string, not a literal PHPStan already knows. */
    private static function code(mixed $code): ?string
    {
        return \is_string($code) ? $code : null;
    }

    public function testSendsATextToAGroupByItsUid(): void
    {
        $http = new MockHttpClient(Reply::json(202, ['action_id' => self::ACTION, 'status' => 'queued']));

        $accepted = $this->client($http)->groups->sendMessage(self::GROUP, ['device_id' => 4, 'type' => 'text', 'text' => 'Hi, everyone!']);

        self::assertSame(['action_id' => self::ACTION, 'status' => 'queued'], $accepted);
        $request = $http->lastRequest();
        self::assertSame('POST', $request->getMethod());
        // The uid goes in the path, encoded: `@` is `%40`.
        self::assertSame('/api/v1/groups/120363000000000001%40g.us/messages', $request->getUri()->getPath());
        self::assertSame(['device_id' => 4, 'type' => 'text', 'text' => 'Hi, everyone!'], self::jsonBody($request));
    }

    public function testFollowsAGroupOfTheNumber(): void
    {
        // communities v2.35.7: POST groups/{group}/import, 201 the first time, inside `data`.
        $http = new MockHttpClient(Reply::json(201, ['data' => ['uid' => self::GROUP, 'name' => 'Customers', 'imported' => true, 'is_admin' => true]]));

        $followed = $this->client($http)->groups->follow(self::GROUP, ['device_id' => 4])['data'];

        self::assertSame('/api/v1/groups/120363000000000001%40g.us/import', $http->lastRequest()->getUri()->getPath());
        self::assertSame(['device_id' => 4], self::jsonBody($http->lastRequest()));
        self::assertTrue($followed['imported']);
        self::assertTrue($followed['is_admin']);
    }

    public function testSendsAFileToAGroup(): void
    {
        $http = new MockHttpClient(Reply::json(202, ['action_id' => self::ACTION, 'status' => 'queued']));

        $this->client($http)->groups->sendMessage(self::GROUP, [
            'device_id' => 4,
            'type' => 'image',
            'media_url' => 'https://files.example/flyer.png',
            'caption' => 'Saturday at 10',
        ]);

        self::assertSame(['device_id' => 4, 'type' => 'image', 'media_url' => 'https://files.example/flyer.png', 'caption' => 'Saturday at 10'], self::jsonBody($http->lastRequest()));
    }

    public function testASendToAGroupIsNeverRetriedByItself(): void
    {
        $http = new MockHttpClient(Reply::serverError(503), Reply::json(202, ['action_id' => self::ACTION, 'status' => 'queued']));

        try {
            $this->client($http, maxRetries: 2)->groups->sendMessage(self::GROUP, ['device_id' => 4, 'type' => 'text', 'text' => 'Hi']);
            self::fail('A 503 of a send is thrown, not retried.');
        } catch (InternalServerException $exception) {
            self::assertSame(503, $exception->status);
        }
        self::assertCount(1, $http->requests);
    }

    public function testTooManyPendingActionsIsASendRefusalThatIsNeverRetried(): void
    {
        $http = new MockHttpClient(
            Reply::json(429, ['error' => 'too_many_pending_actions', 'message' => 'El número ya tiene el máximo de acciones abiertas.', 'max' => 50]),
            Reply::json(202, ['action_id' => self::ACTION, 'status' => 'queued']),
        );

        try {
            $this->client($http, maxRetries: 2)->groups->sendMessage(self::GROUP, ['device_id' => 4, 'type' => 'text', 'text' => 'Hi'], ['retrySend' => true]);
            self::fail('too_many_pending_actions is thrown.');
        } catch (RateLimitException $exception) {
            self::assertSame('too_many_pending_actions', $exception->errorCode);
            self::assertTrue($exception->isSendRuleError());
        }
        self::assertCount(1, $http->requests);
    }

    public function testTheRefusalsOfAGroupSendAreSendErrors(): void
    {
        $http = new MockHttpClient(Reply::json(422, ['error' => 'device_not_group_admin', 'message' => 'El número no es administrador del grupo.']));

        try {
            $this->client($http)->groups->sendMessage(self::GROUP, ['device_id' => 4, 'type' => 'text', 'text' => 'Hi']);
            self::fail('A refusal is thrown.');
        } catch (UnprocessableEntityException $exception) {
            self::assertSame('device_not_group_admin', $exception->errorCode);
            self::assertTrue($exception->isSendRuleError());
        }
        foreach (['group_not_found', 'device_not_connected', 'device_not_group_number', 'unsupported_group_type', 'unsupported_type', 'number_restricted', 'window_closed', 'provider_unsupported'] as $code) {
            self::assertTrue(SendErrors::isSendRuleError(self::code($code)), $code);
        }
    }

    public function testAKeyWithoutThePermissionGetsPermissionDenied(): void
    {
        $http = new MockHttpClient(Reply::json(403, ['error' => 'permission_denied', 'message' => 'La clave no tiene el permiso.']));

        $this->expectException(PermissionDeniedException::class);
        $this->client($http)->groups->sendMessage(self::GROUP, ['device_id' => 4, 'type' => 'text', 'text' => 'Hi']);
    }

    public function testReadsHowTheSendEnded(): void
    {
        $action = [
            'action_id' => self::ACTION,
            'type' => 'send_message',
            'status' => 'completed',
            'device_id' => 4,
            'group' => ['uid' => self::GROUP, 'name' => 'Customers'],
            'result' => ['message' => ['uid' => '3EB0C431C26A1916E2BA']],
            'reason' => null,
            'created_at' => '2026-11-04T12:00:00+00:00',
            'started_at' => '2026-11-04T12:00:01+00:00',
            'completed_at' => '2026-11-04T12:00:03+00:00',
            'failed_at' => null,
        ];
        $http = new MockHttpClient(Reply::ok(['data' => $action]));

        $result = $this->client($http)->groups->retrieveAction(self::ACTION);

        self::assertSame('GET', $http->lastRequest()->getMethod());
        self::assertSame('/api/v1/groups/actions/' . self::ACTION, $http->lastRequest()->getUri()->getPath());
        self::assertSame('completed', $result['data']['status']);
        self::assertSame('3EB0C431C26A1916E2BA', $result['data']['result']['message']['uid'] ?? null);
    }

    public function testTheRouteTableKnowsTheGroupSendAsASend(): void
    {
        $routes = [];
        foreach (Routes::ALL as $route) {
            $routes["{$route['method']} {$route['path']}"] = $route;
        }
        $send = $routes['POST /api/v1/groups/{group}/messages'] ?? null;
        self::assertNotNull($send);
        self::assertSame('groups->sendMessage', $send['sdk']);
        self::assertSame('communities:send', $send['permission']);
        // In the full spec since 05-11 (communities snapshotted with group actions on).
        self::assertFalse($send['pending']);
        $read = $routes['GET /api/v1/groups/actions/{action}'] ?? null;
        self::assertNotNull($read);
        self::assertSame('groups->retrieveAction', $read['sdk']);
        self::assertSame('communities:read', $read['permission']);
    }

    public function testAParticipantEventOfAGroupWithoutACampaignCarriesItsWorkspace(): void
    {
        $body = json_encode([
            'id' => 'evt_0f8e4b526c1d4c7a9a3e2b5d',
            'type' => 'group_participant.joined',
            'version' => 1,
            'occurred_at' => '2026-11-04T12:00:00Z',
            'data' => [
                'group' => ['uid' => self::GROUP, 'name' => 'Customers'],
                'campaign' => null,
                'participant' => ['identity' => '5491100000000', 'identity_type' => 'phone', 'name' => 'Ana'],
                'workspace_id' => 12,
            ],
        ], \JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = 't=' . $timestamp . ',v1=' . hash_hmac('sha256', "{$timestamp}.{$body}", self::SECRET);

        $event = Webhook::constructEvent($body, $signature, self::SECRET);

        self::assertSame('group_participant.joined', $event['type']);
        self::assertIsArray($event['data']);
        self::assertNull($event['data']['campaign']);
        self::assertSame(12, $event['data']['workspace_id']);
    }
}
