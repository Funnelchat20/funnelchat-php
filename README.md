# funnelchat-php

The official PHP SDK for the Funnelchat public API: contacts, conversations and messages, templates, WhatsApp forms, tags, custom fields, groups, numbers and webhooks, with the resources and their types generated from the OpenAPI spec.

- PHP 8.1 or later, PSR-4.
- Any PSR-18 HTTP client with PSR-17 factories, which the SDK finds by itself. With Guzzle (7 or 8) it also applies the timeout.
- Retries with exponential backoff, pagination with `foreach` (generators), typed exceptions, the `X-Request-Id` of every response and webhook signature verification.
- Responses are arrays (the decoded JSON) typed with PHPDoc: PHPStan understands them and PhpStorm 2025.1 or later completes their keys.

> **Status: v0.** The public API is at v0 and may change. The full reference is on the [developers portal](https://developers-portal.funnelchat.app/docs/). Some types come from the code of the API in production rather than from the published spec: they are listed in [Pending of the spec](#pending-of-the-spec).

## Installation

```bash
composer require funnelchat/funnelchat-php guzzlehttp/guzzle
```

Guzzle is not required: any PSR-18 client with PSR-17 factories works (for example `symfony/http-client` with `nyholm/psr7`). The SDK finds them with [php-http/discovery](https://docs.php-http.org/en/latest/discovery.html); if Composer asks about its plugin, you can say no.

The package is published on [Packagist](https://packagist.org/packages/funnelchat/funnelchat-php).

## First call

You need an API key (`<id>|fc_live_...`) created in the panel, in "Developers" → "Create API key", with the permissions for what you are going to use. The panel groups them by area, one box per permission; the literal is what the code and the API's errors use, and the panel shows it as a tooltip. There are twelve and a key can have all of them; there is no wildcard:

| In the panel | Literal |
|---|---|
| **Contacts and conversations → View** | `conversations:read` |
| **Contacts and conversations → Edit** | `conversations:write` |
| **Contacts and conversations → Send** | `conversations:send` |
| **Groups and campaigns → View** | `communities:read` |
| **Groups and campaigns → Edit** | `communities:write` |
| **Groups and campaigns → Send** | `communities:send` |
| **Groups and campaigns → Manage groups** | `communities:operate` |
| **WhatsApp numbers → View** | `accounts:read` |
| **WhatsApp numbers → Edit** | `accounts:write` |
| **WhatsApp numbers → Create, connect and delete** | `accounts:operate` |
| **Notifications (webhooks) → View** | `webhooks:read` |
| **Notifications (webhooks) → Manage** | `webhooks:write` |

Within each area, **Send** includes **Edit** and **Edit** includes **View**; **Manage groups** and **Create, connect and delete** are separate. Keep the key in an environment variable: the client reads it from `FUNNELCHAT_API_KEY` when you do not pass it.

```php
use Funnelchat\Funnelchat;

$funnelchat = new Funnelchat(apiKey: getenv('FUNNELCHAT_API_KEY'));

// Your WhatsApp numbers (permission WhatsApp numbers → View, `accounts:read`). The `id` of each one is the `device_id` you send with.
foreach ($funnelchat->devices->list() as $device) {
    echo $device['id'], ' ', $device['number'], "\n";
    // What that number can do through the API: messages and the delivery statuses it reports.
    var_dump($device['capabilities']['messages']['templates'], $device['capabilities']['statuses']);
}
```

Client options (named arguments):

| Option | What it is | Default |
|---|---|---|
| `apiKey` | Your key. | `FUNNELCHAT_API_KEY` |
| `environment` | `production` (`https://api.funnelchat.com`) or `staging` (`https://api-staging.funnelchat.com`). | `production` |
| `baseUrl` | Another base URL; it wins over `environment` (for example, the [sandbox](#testing-against-the-sandbox)). | — |
| `timeout` | Maximum seconds of each attempt. Guzzle only (see below). | `30` with the Guzzle the SDK builds |
| `maxRetries` | Automatic retries (see [Retries](#retries)). `0` turns them off. | `2` |
| `defaultHeaders` | Headers sent with every request. | — |
| `httpClient` | Your PSR-18 client. | Guzzle if installed; otherwise the one discovery finds |
| `requestFactory`, `streamFactory` | Your PSR-17 factories. | the ones discovery finds |
| `sleep` | How to wait between retries: `function (int $milliseconds): void`. Useful in tests. | `usleep` |

**The timeout.** PSR-18 has no timeout, so the SDK can only apply one with Guzzle: the Guzzle it builds by itself gets 30 seconds, and if you pass your own, it applies the `timeout` you set (if you set none, your Guzzle configuration rules). With another client, configure the timeout in that client: if you pass `timeout`, the SDK fails right away so nobody believes there is one.

Every request sends `Authorization: Bearer <key>`, `Accept: application/json` and `User-Agent: funnelchat-php/<version> php/<version>`. The key never shows up in a `var_dump`, `print_r`, `var_export`, `dd()` or `serialize` of the client, nor in the message of an error. A connection error carries your HTTP client's exception as `getPrevious()`, which may include the request with its headers: do not log it whole.

In Laravel, register a single client for the whole app: see [examples/laravel/app/Providers/FunnelchatServiceProvider.php](examples/laravel/app/Providers/FunnelchatServiceProvider.php).

## The resources

| Resource | What it has |
|---|---|
| `$funnelchat->contacts` | `list`, `create`, `upsert`, `retrieve`, `update`, `delete`, the tags and fields of a contact, `metaWindow` (the 24-hour window), notes. |
| `$funnelchat->conversations` | `list`, `update`, counters, mark everything as read or unread. |
| `$funnelchat->messages` | `send`, `sendDirect` (the contact in the body), `sendForm` (a form), `get` (one message by id), `list` (a contact's history), `sendContactCard`, `forward`, `react`, `removeReaction`. |
| `$funnelchat->templates` | `list`, `create`, `retrieve`, `update`, `quickReplyButtons`, `send`, `delete`. |
| `$funnelchat->forms` | WhatsApp forms: `list`, `templates`, `create`, `retrieve`, `update`, `delete`, `createVersion`, `sync`, `publish`, `numbers`, `addNumber`, `removeNumber`, `retryNumbers`, `definition`, `versions`, `listResponses`, `retrieveResponse` and `metrics`. |
| `$funnelchat->tags` | `list`, `create`, `retrieve`, `update`, `delete`, `listContacts`. |
| `$funnelchat->customFields` | `list`, `create`, `retrieve`, `update`, `delete`. |
| `$funnelchat->groups` | [Read only](#groups): `list`, `retrieve`, `listByDevice`, `metadata`, `listParticipants`, `listParticipantEvents`, `listMessages`, `listScheduledMessages` and `listActions`. |
| `$funnelchat->devices` | `list` (filter by `external_reference`), `create` (a QR number), `qr`, `retrieve` (with `capabilities`), `update`, `delete`, `status`, `health`, `me`, `checkPhone`, connection history. See [Creating and connecting numbers](#creating-and-connecting-numbers). |
| `$funnelchat->connectLinks` | `create`, `retrieve`, `list`, `revoke`: [connect links](#a-connect-link) for your customer to connect a number. |
| `$funnelchat->webhooks` | Subscriptions (`list`, `create`, `retrieve`, `update`, `delete`, `rotateSecret`, `test`, `listDeliveries`), the catalog (`events`), `verify` and `constructEvent`. |

The resources the [Node SDK](https://github.com/Funnelchat20/funnelchat-node) also has have the same methods, with the arguments in the same order: path parameters first, then the query or the body (arrays), and the call options last.

The resource classes are generated from the spec: the parameters, query, body and response of each method are typed in its PHPDoc with array shapes (`array{id: int, name: string, …}`). The schemas of the spec are PHPStan aliases in `Funnelchat\Generated\Schemas` (`ContactResource`, `TagResource`…), which you can use in your code with `@phpstan-import-type ContactResource from \Funnelchat\Generated\Schemas`. For any other route, see [Any route](#any-route).

## Sending a message

Sending with an API key needs the **Contacts and conversations → Send** permission (`conversations:send`) and follows the WhatsApp rules.

**Open window: free text.** With an official Meta number you can only send free text if the contact wrote to you in the last 24 hours. Check the window first:

```php
$window = $funnelchat->contacts->metaWindow(2001, ['device_id' => 3]);

if ($window['is_open']) {
    $result = $funnelchat->messages->send(2001, [
        'device_id' => 3,
        'phone' => '5491100000000',            // the phone of the same contact, without +
        'text' => 'Hi! Here is the info you asked for.',
        'client_reference' => 'order-8841',    // optional: your reference to follow it
    ]);
    echo $result['message']['id'], ' ', $result['message']['status'], "\n"; // 9001 queued
}
```

- `send` returns `['message' => […]]`: the registered message, with its `id`, `status` `queued` and your `client_reference`. **Queued is not sent:** the next statuses (`sent`, `delivered`, `read` or `failed`) arrive through the `message.status_changed` event, with the same `id` as `message_id`, or you read them with `$funnelchat->messages->get(2001, $id)`.
- If the message could not be queued, it still comes back, with `status` `failed` and `error.reason` `not_queued`. When it fails, `error` has Meta's `code` and `title`, or a `null` `code`, a `title` and a Funnelchat `reason` (`device_unavailable`, `insufficient_balance`, `window_closed`, `recipient_unreachable`…).
- Contact cards (`sendContactCard`) return `['messages' => […]]`, one per card; a forward (`forward`), one item per target with `status` (`queued` or `blocked`), `reason` and its `message`; a template (`templates->send`) and a form (`messages->sendForm`), `['message' => […]]`.
- `client_reference` (1 to 64 visible ASCII characters, no spaces) comes back in the message, in the contact's history and in `message.status_changed`.
- `contact_id` goes in the body, but `send` fills it with the contact of the route when you do not pass it.
- Send the `phone` (or `whatsapp_id`) of the same contact: the API checks it, and if it does not match the contact of the route it answers `422 recipient_mismatch`.
- With `options`, buttons or a list.

**A file by URL.** With `file` (the URL) you send an image, a video, an audio, a document or a sticker. If the URL does not show the format (a signed URL, or one without an extension), say what it is with `file_type` (`image`, `video`, `audio`, `document` or `sticker`); for such a document, also `file_name` with its extension:

```php
$funnelchat->messages->send(2001, [
    'device_id' => 3,
    'phone' => '5491100000000',
    'file' => 'https://files.example/download?signature=…',
    'file_type' => 'document',
    'file_name' => 'invoice.pdf',
]);
```

If one is missing, the API answers `422` with the field in `errors` (`file_type`, `file_name` or `file`), or `415` (`UnsupportedMediaTypeException`, with `supportedFileTypes`) when the extension cannot be sent.

**Closed window: an approved template.** The route takes the number (`3`) and the template id (`45`), which comes from `$funnelchat->templates->list()`:

```php
$funnelchat->templates->send(3, '45', [
    'phone' => '5491100000000',
    'contact_id' => 2001,
    'template_variables' => ['body' => ['Lucía']], // the body variables, in order
]);
```

**A WhatsApp form.** `form_id` is the id of the published form (`family_id`); the answers arrive through `whatsapp_form.completed`:

```php
$funnelchat->messages->sendForm(2001, ['form_id' => 12, 'device_id' => 3, 'button_text' => 'Fill in']);
```

**When a send is stopped.** If a send breaks a WhatsApp rule, it does not go out: you get an exception.

| Error | What happened | What to do |
|---|---|---|
| `422 window_closed` | Free text with the window closed, on an official number. | Send an approved template. |
| `422 antiblock_duplicate_content` | The same content repeated to the same contact. | Change the text or wait. |
| `422 antiblock_unanswered_limit` | Too many messages in a row to someone who does not answer. | Wait until they answer. |
| `422 recipient_mismatch` | The `phone` or `whatsapp_id` is not the one of the contact of the route. | Use that contact's details. |
| `422 recipient_unsupported` | The contact is a group, a broadcast list or a channel. | Through the API you can only write to people. |
| `422 device_unavailable` | The number is not connected to WhatsApp. | Connect it and retry. |
| `429 rate_limited` | You went over your number's pace. It always has `retryAfter`. | Wait those seconds and retry. |
| `429 mac_lockout_24h` | The account is in the 24-hour lockout for using up its MACs (also for a contact that is inactive by MAC). No `retryAfter`. | Do not retry: wait for the lockout to end. |

`$exception->isSendRuleError()` tells you whether the exception is one of these rules or one of the other refusals of a send (`not_sent`, `template_not_found`, `form_not_published`, `provider_unsupported`…; the list is in `Funnelchat\Exception\SendErrors::CODES`). A send is **never retried by itself**, because a retry can deliver the same message twice. If a `429 rate_limited` sent nothing and you want the SDK to retry for you, ask for it in that call (a `mac_lockout_24h` is never retried):

```php
$funnelchat->messages->send(2001, $body, ['retrySend' => true]);
```

The full example is in [examples/plain-php/send-message.php](examples/plain-php/send-message.php).

## Groups

Groups are read only through the API, with **Groups and campaigns → View** (`communities:read`): the account's groups, their participants, joins and leaves, the messages sent to them and their scheduled messages.

```php
foreach ($funnelchat->groups->list(['deviceId' => 4]) as $group) {
    echo $group['name'], "\n";
}

$participants = $funnelchat->groups->listParticipants(['groupUid' => '120363000000000001@g.us'])->firstPage();
$metadata = $funnelchat->groups->metadata('120363000000000001@g.us', ['device_id' => 4, 'group_uid' => '120363000000000001@g.us']);
```

## Creating and connecting numbers

> **The official signup by link (`'type' => 'meta'`) is not available in production yet:** it answers `503 meta_connect_disabled` until Funnelchat registers its domain with Meta. QR numbers and QR links work. Try the Meta flow against [the sandbox](#testing-against-the-sandbox), turning it on there.

A number belongs to the account of the API key and uses the plan's quota, with the same rules as the panel. Creating, deleting, asking for the QR and creating or revoking links need **WhatsApp numbers → Create, connect and delete** (`accounts:operate`); reading links, **WhatsApp numbers → View** (`accounts:read`). Tag each number with your own `external_reference` (your customer's id, for example): it comes back in the number, in every `device.*` event, and filters the list.

### A QR number

```php
$number = $funnelchat->devices->create(
    ['connection' => 'qr', 'alias' => 'Store 12', 'external_reference' => 'customer-8841', 'country' => 'AR'],
    ['idempotencyKey' => 'customer-8841-number'],
)['data'];

$qr = $funnelchat->devices->qr($number['id'])['data'];
// $qr['qr_code'] is a PNG data URI: <img src="…">. Ask again after $qr['expires_in'] seconds
// (5 with a QR, 3 while the session starts, 0 once connected), or wait for device.connected.
```

- The QR lasts 5 seconds: ask for it every 5 seconds, show it at once and scan it right away. An older QR reaches WhatsApp expired and it refuses to link the phone.
- `country` (optional, two letters) is the country of the phone: the WhatsApp session is opened from there.
- The account has at most one QR number not connected yet: creating another answers `409 pending_number_exists` with its `device_id`. Connect that one, or delete it, first.
- `same_number_as` adds QR to an official number in coexistence, without using quota.
- A QR number is not official: WhatsApp can ban it.

### A connect link

A single-use page for your customer, with no Funnelchat session. The number is created when the page ends.

```php
$link = $funnelchat->connectLinks->create([
    'type' => 'meta',                                          // or 'qr'; 'meta' is off in production for now
    'allowed_connection_types' => ['coexistence', 'dedicated'], // only with 'meta'; with both, your customer chooses
    'external_reference' => 'customer-8841',
    'return_url' => 'https://app.example.com/numbers/done',    // https, up to 2048 characters
    'expires_in_minutes' => 1440,                              // 30 to 10080
], ['idempotencyKey' => 'customer-8841-link'])['data'];

sendToYourCustomer($link['url']); // the URL comes only in this answer
```

- `'type' => 'meta'` answers `503 meta_connect_disabled` in production until Funnelchat registers its domain with Meta. A `qr` link works: its page renews the QR every 5 seconds.
- With `'type' => 'qr'`, `country` (two letters) is the country of the phone, as in `devices->create()`. It does not go with `device_id` nor with `meta` (`422 invalid_request`), and the link returns it in lower case (`'ar'`).
- With `'type' => 'meta'`, `provision_us_number: true` offers a US number from the pool. A pool number is always dedicated: it needs `dedicated` in `allowed_connection_types` and does not go with `device_id`; otherwise `422 invalid_request`. Your customer pays Meta directly, with a payment method in their WhatsApp account.
- `'device_id' => 41` reconnects that number instead of creating one, without new quota. Its method must match the link's `type` (`422 connection_type_mismatch`).
- The page claims the link when your customer presses its button, not when it opens: a messenger's link preview does not use it up.
- A `meta` link tolerates retries: after a Facebook or Meta failure it stays `pending` and your customer can try again on the page; the fifth failure makes it `failed` (`facebook_auth_failed` or `meta_error`). `quota_exceeded`, `trial_number_blocked` and `number_not_owned` close it at the first attempt.
- Learn the result three ways: the `device.connected` event (with `connect_link_id` and `external_reference`), `return_url` (it gets `connect_link_id`, `status`, and `device_id` or `error_code`), or reading the link:

```php
$link = $funnelchat->connectLinks->retrieve($link['id'])['data'];
// status: pending, completed, failed (see error_code), expired or revoked; device_id once completed

foreach ($funnelchat->connectLinks->list(['status' => 'pending']) as $open) { … } // newest first
$funnelchat->connectLinks->revoke($open['id']); // only a pending link; otherwise 409 link_not_pending
```

At most 20 links are open per account, and 10 can be created per minute.

### Finding, tagging and deleting your customer's numbers

```php
foreach ($funnelchat->devices->list(['external_reference' => 'customer-8841']) as $number) { … }
$funnelchat->devices->update(41, ['external_reference' => 'customer-9000']);
$funnelchat->devices->delete(41);
```

Deleting is physical and frees the quota: an official number loses the app subscription but stays registered with Meta, and a QR number's session is closed. It emits `device.deleted`. There is no undo.

`devices->list()` now takes the query first, like every other list; the call of 0.1, `devices->list($options)`, still works.

### Idempotency-Key

`devices->create()` and `connectLinks->create()` take `idempotencyKey` in the options (1 to 255 characters), sent as the `Idempotency-Key` header. For 24 hours, the same key with the same body answers the same number or link without creating another, with the header `Idempotent-Replayed: true` (read it in `$funnelchat->lastResponse()->headers`). Only successful answers are replayed: a refusal is evaluated again. With a different body, `422 idempotency_key_mismatch`; while the first call runs, `409 operation_in_progress`. With a key, the SDK also repeats the create after a timeout or a `5xx` (see [Retries](#retries)).

### The events of a number

| Event | `data` |
|---|---|
| `device.connected` | `device`, `connected_at`, `connect_link_id` |
| `device.disconnected` | `device`, `disconnected_at`, `reason`: `qr_logged_out`, `meta_account_not_registered`, `shadow_ban_protection`, `provider_error`, `disconnected_by_user` or `unknown`, `connect_link_id` |
| `device.deleted` | `device`, `deleted_at`, `connect_link_id` |
| `device.health_changed` | `device`, `previous_state`, `state` (`available`, `limited`, `blocked` or `unknown`), `changed_at`, `connect_link_id` |

`device` is `id`, `name`, `number`, `connection` (`qr`, `coexistence`, `dedicated` or `null`) and `external_reference`. `connect_link_id` is, in `device.connected`, the link of that connection; in the other three, the last link that connected the number; `null` when no link did. The fields that production does not send yet (`device.connection`, `device.external_reference`, `connect_link_id` and `reason`) are optional in the types: read them with `?? null`. The types are `DeviceConnectedEvent`, `DeviceDisconnectedEvent`, `DeviceDeletedEvent` and `DeviceHealthChangedEvent` in `Funnelchat\Generated\WebhookEvents`.

```php
$event = $funnelchat->webhooks->constructEvent($body, $signature, $secret);
if ($event['type'] === 'device.connected') {
    markConnected($event['data']['device']['external_reference'] ?? null, $event['data']['device']['id']);
}
```

## Receiving webhooks with Laravel

Verify the signature over the **raw body**, exactly as it arrived: `$request->getContent()`, not `$request->all()` or `$request->json()`. If you decode it and encode it again, the signature changes.

```php
use App\Jobs\ProcessFunnelchatEvent;
use Funnelchat\Exception\WebhookVerificationException;
use Funnelchat\Webhook;
use Illuminate\Http\Request;

// routes/api.php: the `api` group does not ask for the CSRF token, which a webhook cannot send.
Route::post('/hooks/funnelchat', function (Request $request) {
    try {
        $event = Webhook::constructEvent(
            $request->getContent(),                                  // the raw body
            $request->header('X-Funnelchat-Signature'),
            config()->string('services.funnelchat.webhook_secret'),  // the full secret, with whsec_
        );
    } catch (WebhookVerificationException $exception) {
        return response()->noContent(400); // $exception->reason says why
    }

    ProcessFunnelchatEvent::dispatch($event); // process it later; deduplicate by $event['id']

    return response()->noContent(200);        // answer fast (under 10 s)
});
```

[examples/laravel](examples/laravel) has it put together with a controller, a job that deduplicates by `id` and dispatches by type, the client's provider, the route and the configuration. We tested it copied as is into a Laravel 13 app (with PHPStan at the max level, through Larastan).

### With plain PHP

```php
use Funnelchat\Exception\WebhookVerificationException;
use Funnelchat\Webhook;

$rawBody = file_get_contents('php://input');
try {
    $event = Webhook::constructEvent($rawBody, $_SERVER['HTTP_X_FUNNELCHAT_SIGNATURE'] ?? null, getenv('FUNNELCHAT_WEBHOOK_SECRET') ?: null);
} catch (WebhookVerificationException $exception) {
    http_response_code(400);
    exit;
}
http_response_code(200);
// … $event['type'], $event['data']
```

The full example is in [examples/plain-php/webhook.php](examples/plain-php/webhook.php).

### What each function does

- `Webhook::constructEvent($rawBody, $signatureHeader, $secret)` verifies the signature and returns the decoded event: `id`, `type`, `version`, `occurred_at` and `data`. When something fails, it throws `WebhookVerificationException` with a `reason`: `missing_header`, `malformed_header`, `timestamp_out_of_tolerance`, `signature_mismatch`, `missing_secret`, `invalid_body`, `invalid_json`, `invalid_event`.
- If you only want a yes or no, `Webhook::verify($rawBody, $signatureHeader, $secret)` returns `true` or `false` and never throws.
- Both reject a signature whose `t` is more than 300 seconds away from your clock. To change that, use the fourth argument: `Webhook::verify($rawBody, $header, $secret, 600)`.
- The body can be a string or a PSR-7 `StreamInterface`.
- The algorithm is exactly the one of the portal's `snippets/verificar-firma.php`: `X-Funnelchat-Signature: t=<unix>,v1=<hex>`, where `v1` is the HMAC-SHA256 of `"<t>." + raw body` with the full secret, compared in constant time (`hash_equals`), and there can be several `v1`. The tests use the vectors of the portal's `tests/test_firmas.py` and compare, case by case, with a copy of the snippet.
- They are also on the client: `$funnelchat->webhooks->verify(…)` and `$funnelchat->webhooks->constructEvent(…)`.

**The event types.** `type` is a string, because new types can arrive before the SDK knows them: answer `2xx` to the ones you do not handle. Once you checked `type`, the alias of that event gives you the full shape (they are in `Funnelchat\Generated\WebhookEvents`: `MessageReceivedEvent`, `MessageStatusChangedEvent`…; and `XxxData` for its `data`):

```php
/**
 * @phpstan-import-type MessageReceivedEvent from \Funnelchat\Generated\WebhookEvents
 */
final class FunnelchatEvents
{
    /** @param array{type: string} $event */
    public function handle(array $event): void
    {
        switch ($event['type']) {
            case 'message.received':
                /** @var MessageReceivedEvent $event */
                echo $event['data']['contact']['phone'], ': ', $event['data']['message']['text'];
                break;
            default:
                // A type your code does not know yet: not an error.
        }
    }
}
```

The catalog of events the SDK knows, with their status, is `Funnelchat\Generated\WebhookEvents::CATALOG`.

### Subscribing

```php
$subscription = $funnelchat->webhooks->create([
    'url' => 'https://your-server.example/hooks/funnelchat',
    'events' => ['message.received', 'message.status_changed'],
    'description' => 'My integration',
]);
// Store $subscription['id'] and $subscription['secret']: the secret is shown only once.
```

- Subscriptions need **Notifications (webhooks) → View** (`webhooks:read`) and **Notifications (webhooks) → Manage** (`webhooks:write`), and for each event type, the **View** of the event's area (`{area}:read`): **Contacts and conversations → View** (`conversations:read`) for `message.received`. When it is missing, `create` and `update` answer `422` with the problem in `errors['events']`, and the others `403 permission_denied`.
- They belong to the account: `list()` (paginated) also brings the ones of other integrations, except those with events of an area your key cannot read.
- To change the URL use `update($id, […])`, which replaces `url`, `events`, `description` and `filters` (the secret does not change). `test($id)` sends a signed `webhook.test` right now and `listDeliveries($id)` is the history.
- `rotateSecret($id)` **is not open to API keys**: it answers `403 route_not_available`. Rotate the secret from the dashboard.
- `message.status_changed` must be requested by name: `*` does not include it.
- The catalog, with the area and status (`active` or `planned`) of each type, is `$funnelchat->webhooks->events()` (needs **WhatsApp numbers → View**, `accounts:read`). Since 21-10 it includes, among others, `message.status_changed`, `whatsapp_form.completed`, `group.message_received`, `group.reaction_added` and `group_join_request.created`.

## Pagination

The `list…` methods return a lazy `Paginator`: nothing is requested until you iterate it.

```php
// Every item of every page (a generator: it requests each page when it needs it).
foreach ($funnelchat->contacts->list(['perPage' => 100, 'tag_mode' => 'any', 'tag_ids' => [12]]) as $contact) {
    echo $contact['id'], "\n";
}

// Page by page:
$page = $funnelchat->tags->list(['perPage' => 50])->firstPage();
print_r($page->data);
while ($page->hasNextPage()) {
    $page = $page->getNextPage();
}

// Or with a generator of pages:
foreach ($funnelchat->tags->list()->iterPages() as $page) {
    echo count($page), " tags on this page\n";
}
```

The SDK understands the three ways the API paginates: by page number (`meta.current_page` and `meta.last_page`), by cursor (`meta.next_cursor`) and lists that come whole in an array. The page size is requested with each route's parameter (`perPage`, `per_page` or `pageSize`, as the spec says). `$page->body` is the full response of that page, with `meta` and `links`.

## Errors

Everything the SDK throws extends `Funnelchat\Exception\FunnelchatException`. When the API answers with an error, it is an `ApiException`:

| Property | What it is |
|---|---|
| `status` | The HTTP status (also `getCode()`). |
| `errorCode` | The stable code of the body (`window_closed`, `rate_limited`, `route_not_available`, `NUMBER_NOT_ELIGIBLE`…), or `null` when there is none (for example a validation `422` or a `415`). In Node it is `code`; in PHP `getCode()` already exists and returns a number. |
| `getMessage()` | The `message` of the body, meant for people. Decide by `status` and `errorCode`, not by the message. |
| `requestId` | The `X-Request-Id` of the response. **Quote it when you report a problem**, with the approximate time of the call. |
| `retryAfter` | The seconds of `Retry-After`, when present (rounded up). |
| `errors` | In a validation `422`, the messages per field. |
| `body`, `headers`, `method`, `url` | The response and the request (the URL without the query). |

Each HTTP status has its class, in `Funnelchat\Exception`: `BadRequestException` (400), `AuthenticationException` (401), `PermissionDeniedException` (403), `NotFoundException` (404), `ConflictException` (409), `UnsupportedMediaTypeException` (415), `UnprocessableEntityException` (422), `RateLimitException` (429) and `InternalServerException` (5xx). Without a response: `ApiConnectionException`, and `ApiConnectionTimeoutException` when the timeout ran out.

The errors of the API entrypoint itself, which answers without reaching any area, have their own:

| Class | Error | What to do |
|---|---|---|
| `RouteNotFoundException` (a `NotFoundException`) | `404 route_not_found`: the route does not exist. | Check the route and that it starts with `/api/v1/`. |
| `EdgeMisconfiguredException` (an `InternalServerException`) | `500 edge_misconfigured`. | It is on our side: report it quoting `requestId`. |
| `OriginUnreachableException` (an `InternalServerException`) | `502 origin_unreachable`: the area could not be reached. | Treat it like a timeout: a read is repeated; a write, only after reading the state (see [Retries](#retries)). |

```php
use Funnelchat\Exception\ApiException;
use Funnelchat\Exception\RateLimitException;

try {
    $funnelchat->contacts->retrieve(2001);
} catch (RateLimitException $exception) {
    echo "Wait {$exception->retryAfter} s\n";
} catch (ApiException $exception) {
    echo $exception->status, ' ', $exception->errorCode, ' ', $exception->getMessage(), ' (', $exception->requestId, ")\n";
}
```

A `403` with `errorCode` `route_not_available` means that route is not open to API keys, even if your key has the permission.

The refusals of creating or connecting a number are in `Funnelchat\Exception\DeviceErrors`, each with the statuses it comes with, and `$exception->isDeviceError()` tells them apart. The ones you will see most: `403 numbers_not_included`, `403 number_limit_reached` (with `limit`, `used` and `pending_device_ids` in `body`), `409 pending_number_exists` (with `device_id`), `422 method_already_present` (with `device_id`), `422 connection_type_mismatch`, `422 idempotency_key_mismatch`, `429 too_many_open_links` and `503 meta_connect_disabled`. Nothing was created.

### The request id

Every response of the API, success or error, carries `X-Request-Id`: a different UUID per request, with which support finds the call. On an error it is in `$exception->requestId`; on a success, in `$funnelchat->lastResponse()` (the client's last response, with `status`, `requestId` and `headers`) or, for one call in particular, in `onResponse`, which also sees every page of a list:

```php
use Funnelchat\ResponseInfo;

$result = $funnelchat->messages->send(2001, $body);
$logger->info('sent', ['message_id' => $result['message']['id'], 'request_id' => $funnelchat->lastResponse()?->requestId]);

$funnelchat->contacts->list([], ['onResponse' => fn(ResponseInfo $r) => $logger->debug('page', ['request_id' => $r->requestId])]);
```

An id you send yourself in `X-Request-Id` is not used: the API sets its own. There is no route to look a call up by its id.

## Retries

- A request is retried on a `429`, a `5xx` or a network error (also when the timeout ran out), up to `maxRetries` times (2 by default), with the exceptions below.
- With `Retry-After`, the SDK waits exactly what it asks for. If it asks for more than 60 seconds, it does not wait: the exception is thrown right away with its `retryAfter` and you decide.
- Without `Retry-After`, the wait is exponential: 0.5 s, 1 s, 2 s… up to 8 s, with some jitter.
- **A send is never retried by itself**: the `POST`s that need a **Send** permission (`…:send`: **Contacts and conversations → Send** or **Groups and campaigns → Send**) (messages, contact cards, forwards, templates and forms). They are retried only if you pass `'retrySend' => true` in that call. Reactions (`PUT` and `DELETE`) are retried, because repeating them changes nothing.
- **A write that may have been applied is not repeated by itself**: a `POST` or a `PATCH` is retried only on a `429` or a `503`, when it was certainly not applied. On a `500`, `502` or `504`, a timeout or a dropped connection, the exception is thrown right away (a `502 origin_unreachable` does not guarantee the area did not get it): read the state before repeating it. `retrySend` does not change this. Reads, `PUT`s and `DELETE`s are retried on any transient failure. It is the same rule as in the Node SDK.
- **A create with an `idempotencyKey`** (`devices->create()`, `connectLinks->create()`) is retried like a `PUT`: the API answers the same resource to the same key. A send is not, key or no key.
- `429 mac_lockout_24h`, `429 mac_limit_reached`, `429 too_many_open_links` and `503 meta_connect_disabled` are never retried.
- Per call you can change `maxRetries` and the headers, and with Guzzle the `timeout`:

```php
$funnelchat->tags->list([], ['maxRetries' => 0, 'timeout' => 5, 'headers' => ['X-My-Trace' => 'abc']]);
```

## Any route

`request()` calls any route, whether it is in a resource or not. Path parameters go in `path`, the query in `query` and the body in `body`:

```php
$contact = $funnelchat->request('GET', '/api/v1/contacts/{contact}', ['path' => ['contact' => 2001]]);

$stats = $funnelchat->request('GET', '/api/v1/broadcasts/{broadcast}/statistics', ['path' => ['broadcast' => 77]]);

$result = $funnelchat->request('POST', '/api/v1/something-new', ['body' => ['a' => 1]]);
```

It returns the decoded JSON (associative arrays), the text when the response is not JSON, or `null` when it came empty (`204`). Here the SDK does not know the shape of the response: if you want it typed, use the schema alias (`/** @var BroadcastResource $broadcast */`). The body `[]` is sent as `{}`.

`paginate()` is the same for lists: `foreach ($funnelchat->paginate('/api/v1/broadcasts') as $broadcast)`.

The query is built the way Laravel reads it: arrays go as `key[]=a&key[]=b`, nested arrays with brackets, booleans as `1` and `0` and dates (`DateTimeInterface`) in ISO 8601, in UTC.

`Funnelchat\Generated\Routes::ALL` has every operation of the spec with its domain, its permission and the SDK method that calls it (`sdk`), or `null` when it is called with `request()`.

## Regenerating

Everything generated comes from a single script, in PHP:

```bash
# With the portal's new unified spec:
composer generate -- --spec ../developers-portal-worktrees/<branch>/out/specs/funnelchat.json

# With the one already in spec/ (for example, after editing spec/pending.json or spec/resources.json):
composer generate

# To check that the generated code is up to date (useful in CI):
composer generate:check
```

What it does (`scripts/generate.php`):

1. With `--spec`, it copies the spec to `spec/funnelchat.json` and writes `spec/procedencia.json`: repository, branch, commit, date and `sha256` of the file.
2. It adds `spec/pending.json` (the [pending of the spec](#pending-of-the-spec) layer), but **only what the snapshot does not bring**. At the end it lists what the new snapshot already brings, so you can delete it from `spec/pending.json`.
3. It writes `src/Generated/Routes.php` (every operation), `src/Generated/Schemas.php` (every schema as a PHPStan alias), `src/Generated/WebhookEvents.php` (the catalog and the shape of each event, taken from the schema or from the example), `src/Resources/*.php` (one method per operation, with its types) and `src/Generated/ResourceProperties.php` (the client's properties).

`spec/resources.json` says which operation each method of each resource is: they are the same names as in Node. Regenerating never needs code edited by hand. If an operation of that list is no longer in the spec, the generation fails and says which one; if its shape changed, `composer analyse` flags it. After regenerating: `composer check`.

**The current snapshot** is the portal's, branch `feat/openapi-unificada` @ `0ef4abb`, of 15-10: 191 paths and 230 operations of conversations `9d723d52c`, communities `fab0bb10` and accounts `c234b6f8` (13-10). It predates what reached production on 21-10: what is new is in the pending layer. The details are in `spec/procedencia.json`. The snapshot's own texts (summaries and descriptions of its operations and events) are in Spanish, as the portal publishes them.

## Pending of the spec

The spec snapshot is from 13-10. What reached production afterwards is in a separate layer, marked as such in the code, which the next regeneration replaces by itself once a snapshot brings it (and it tells you which entries can be deleted):

- `spec/pending.json`: paths, fields, responses, schemas and events, in OpenAPI format, with the source of each entry (`x-fc-pending`).

All of it comes from the code of the domains at the tags running in production on 22-10 (read only, `git show <tag>:<file>`): conversations `v1.18.10` (`a580a3d0`), communities `v2.34.52` (`6f32f0b4`) and accounts `v1.4.24` (`300cc843`), plus the API entrypoint, api-edge `v0.3.0` (`a1c5b350`).

| What | Where it comes from |
|---|---|
| What the API-key sends answer (`200` with the queued message, never `204`), `messages->get`, `client_reference` (also in the history), `file_type` and `file_name` | conversations `v1.18.10` |
| WhatsApp forms (`/api/v1/forms…`), sending one to a contact and `whatsapp_form.completed` | conversations `v1.18.10` |
| The shape of `message.status_changed` (with `error.reason`), now `live`, and `device_id` in `message.received` | conversations `v1.18.10` |
| The `group.message_received`, `group.reaction_added` and `group_join_request.*` events | communities `v2.34.52` |
| Webhook subscriptions (`/api/v1/webhooks…`, with the **Notifications (webhooks)** permissions, `webhooks:*`), the accounts events and four communities ones | accounts `v1.4.24` |
| `capabilities` and the other new fields of each number in `devices` | accounts `v1.4.24` |
| Creating, connecting and deleting numbers (`devices->create`, `devices->qr`, `devices->delete`'s empty answer, the `external_reference` filter and field, `connectLinks`, `Idempotency-Key`, `country`) and the fields and events of `device.*`. In production since 25-10, except `type: 'meta'` (`503 meta_connect_disabled`) | the contract `1022-alta-de-numeros` v0.2 (23-10) and the changes of 25-10 (the QR every 5 seconds, accounts #1335; `meta` off; `country`), source `accounts-number-onboarding`; to be checked against accounts' OpenAPI |

The error codes of the sends are in `Funnelchat\Exception\SendErrors`, written by hand from the same sources: the spec does not list them. Those of creating and connecting numbers, in `Funnelchat\Exception\DeviceErrors`, from the contract.

## Development

```bash
composer install
composer generate:check   # the generated code is up to date
composer cs               # PHP-CS-Fixer (PER-CS 2.0), in check mode; composer cs:fix applies it
composer analyse          # PHPStan at the max level: src, tests, scripts and examples/plain-php
composer test             # PHPUnit, with no network and no keys: a simulated PSR-18 client
composer check            # all of the above, in order
```

The suite runs on PHP 8.1 (with PHPUnit 10) and on PHP 8.5 (with PHPUnit 12), with Guzzle 7 and with Guzzle 8.

### Testing against the sandbox

[funnelchat-sandbox](https://github.com/Funnelchat20/funnelchat-sandbox) imitates the API and sends signed webhooks, with no real effects. Point the client at it with `baseUrl` and any well-formed key:

```php
$funnelchat = new \Funnelchat\Funnelchat(apiKey: '1|fc_live_sandbox', baseUrl: 'http://127.0.0.1:4010');
```

The Meta sign-up by link starts off in the sandbox, like production: `type: 'meta'` answers `503 meta_connect_disabled`. Turn it on to try it, with `npx funnelchat-sandbox --meta-connect`, or while it runs:

```bash
curl -s -X POST http://127.0.0.1:4010/_sandbox/numbers -H 'Content-Type: application/json' -d '{"meta_connect": true}'
```

The SDK's end-to-end tests run against it, version 0.2.0 or later (a send and its signed notices, which a receiver on PHP's built-in server verifies; the refusals; `messages->get`; a form from build to answer; `X-Request-Id`; a QR number created, scanned, disconnected and deleted, a connect link completed on its page, `country`, the refusals and the four signed `device.*` events). The tests of a `meta` link turn the Meta sign-up on by themselves:

```bash
npx funnelchat-sandbox --port 4010 --status-delays 0.2,0.2,0.2 --quiet
FUNNELCHAT_SANDBOX_URL=http://127.0.0.1:4010 composer test:e2e
```

```
spec/              the spec, its provenance, the pending layer and the resource map
scripts/           generate.php, the only generator
src/Generated/     generated code (not edited)
src/Resources/     the resources, generated (not edited)
src/Core/          HTTP, retries, URL and query
src/Pagination/    Paginator and Page
src/Exception/     the exceptions and the error codes of the sends and of the numbers
src/ResponseInfo.php  the status, headers and X-Request-Id of a response
src/Webhook.php    signature and events
examples/          Laravel and plain PHP
tests/             PHPUnit; tests/E2E, against the sandbox
```

## License

[MIT](LICENSE).
