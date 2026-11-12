# funnelchat-php

The official PHP SDK for the Funnelchat API: connect a WhatsApp number, send messages, know when they were read, receive messages and work with your groups, from PHP.

```bash
composer require funnelchat/funnelchat-php guzzlehttp/guzzle
```

PHP 8.1 or later. The API reference is on the [developers portal](https://developers-portal.funnelchat.app/docs/).

**Every function the portal publishes is in the SDK**: the ones of the first use below and all those of [«Más funciones»](https://developers-portal.funnelchat.app/docs/mas-funciones/), the WhatsApp ones and the Funnelchat ones; the most used as methods of a resource, every one with `request()`. Its topics: numbers (connecting them, QR, connect links, health, username, message queue), messages and templates, groups and their actions (create, participants, admins, invite link, join requests), contacts, conversations, tags, custom fields and notes, automations and their folders, campaigns, scheduled sends, statistics, forms, broadcasts, boards, tickets, quick replies, lead score, sales, custom domains, Conversions API, Meta costs and accounts, your account and team, and webhooks. How many there are, and the portal's groups by category, are in [Every function of the portal](#every-function-of-the-portal).

## First use

### 1. Create your key

In Funnelchat, go to **Developers** → **Create key**. The key (`<id>|fc_live_...`) is shown only once: keep it in the `FUNNELCHAT_API_KEY` environment variable, where the client reads it.

```php
$funnelchat = new Funnelchat\Funnelchat(); // reads FUNNELCHAT_API_KEY
```

For the four steps, tick **WhatsApp numbers → Create, connect and delete**, **Contacts and conversations → Send** and **Notifications (webhooks) → View** and **Manage** (all of them: [API keys and permissions](#api-keys-and-permissions)).

### 2. Connect a number

```php
$number = $funnelchat->devices->create(['connection' => 'qr', 'alias' => 'My first number'])['data'];

do {
    $qr = $funnelchat->devices->qr($number['id'])['data'];
    if ($qr['qr_code'] !== null) {
        file_put_contents('qr.html', "<img src=\"{$qr['qr_code']}\">"); // open it and scan it: WhatsApp → Linked devices
    }
    sleep(max(1, $qr['expires_in']));
} while (!$qr['connected']);
```

The QR changes every 5 seconds: show it and scan it right away. `$number['id']` is the `device_id` you send with. To let your customer connect their own number on a page of ours, send them a [connect link](#a-connect-link).

### 3. Send a message

```php
$sent = $funnelchat->messages->sendDirect([
    'device_id' => $number['id'],
    'phone' => '5491100000000', // digits, without +
    'text' => 'Hi! This is my first message from the API.',
]);
echo $sent['message']['id'], ' ', $sent['message']['status']; // 9001 queued
```

One call: if the phone is not a contact yet, Funnelchat creates it, and `$sent['message']['contact_id']` says which one. **Queued is not sent**: the next statuses arrive in step 4. With an official (WhatsApp Cloud) number you can write free text only within 24 hours of the contact's last message; after that, an [approved template](#closed-window-an-approved-template).

### 4. Know when it was read

Subscribe a URL of yours, once:

```php
$subscription = $funnelchat->webhooks->create([
    'url' => 'https://your-server.example/hooks/funnelchat',
    'events' => ['message.status_changed', 'message.received'],
]);
// $subscription['secret'] (whsec_…) is shown only once: keep it in FUNNELCHAT_WEBHOOK_SECRET.
```

At that URL, verify the signature and read the status:

```php
use Funnelchat\Webhook;

$event = Webhook::constructEvent(
    file_get_contents('php://input'),               // the raw body, as it arrived
    $_SERVER['HTTP_X_FUNNELCHAT_SIGNATURE'] ?? null,
    getenv('FUNNELCHAT_WEBHOOK_SECRET') ?: null,
);

if ($event['type'] === 'message.status_changed' && $event['data']['status'] === 'read') {
    // $event['data']['message_id'] is the $sent['message']['id'] of step 3.
}
http_response_code(200);
```

`status` goes `sent` → `delivered` → `read`, or `failed` (with `error`). A wrong signature throws `WebhookVerificationException`: answer `400`. Without a server, ask for the message: `$funnelchat->messages->get($sent['message']['contact_id'], $sent['message']['id'])['status']`.

The four steps put together, ready to run: [examples/plain-php/first-use.php](examples/plain-php/first-use.php).

## Receive messages

The same subscription brings `message.received` when someone writes to one of your numbers:

```php
if ($event['type'] === 'message.received') {
    $contactId = $event['data']['contact']['id'];
    $text = $event['data']['message']['text'];
    $deviceId = $event['data']['device_id']; // the number that received it (null if none): reply from that one

    $funnelchat->messages->send($contactId, ['device_id' => $deviceId, 'phone' => ltrim((string) $event['data']['contact']['phone'], '+'), 'text' => 'Got it, thanks!']);
}
```

On an official number, a message received opens the 24-hour window for free text. Answer the webhook with a `2xx` within 10 seconds and do the slow work after (in Laravel, a queued job: [Receiving webhooks with Laravel](#receiving-webhooks-with-laravel)).

## Groups

Start from the groups of your number, read live from WhatsApp, and write to one:

```php
foreach ($funnelchat->groups->listByDevice($number['id']) as $group) {
    echo $group['uid'], ' ', $group['name'], "\n"; // 120363000000000001@g.us Customers
}

$accepted = $funnelchat->groups->sendMessage('120363000000000001@g.us', [
    'device_id' => $number['id'],
    'type' => 'text',
    'text' => 'Hi, everyone!',
]);

$action = $funnelchat->groups->retrieveAction($accepted['action_id'])['data'];
echo $action['status']; // queued, running, completed or failed
```

- `listByDevice()` lists every group of the number, also those that are not in your account yet (for a key it is reused for a minute: a group just created can take that long to appear). `groups->list()` is the groups already in the account.
- Writing to a group that is not in the account follows it first (see below), so the key also needs **Groups and campaigns → Manage groups** besides **Send**.
- Reading groups needs **Groups and campaigns → View**; sending to a group, **Groups and campaigns → Send**.
- Sending is asynchronous: `sendMessage()` answers `['action_id' => …, 'status' => 'queued']` and the message goes out right after, at the number's pace. Learn how it ended with `retrieveAction()` (`result.message.uid` is the message's id in WhatsApp; a failure has its `reason`) or with the `group_action.completed` and `group_action.failed` events, whose `action.uuid` is the same `action_id`.
- A file: `type` `image`, `video`, `audio` or `document`, with `media_url` (a public `https` URL, up to 255 characters); `caption` with an image or a video, `file_name` with a document.
- A QR number must be an admin of the group; an official number, the group's own number. Otherwise `422 device_not_group_admin` or `422 device_not_group_number`.
- A send to a group is never retried by itself and has no idempotency key: after a timeout or a `5xx`, read the group's actions (`groups->listActions($group, ['orderBy' => 'created_at', 'orderByMethod' => 'desc'])`) before sending it again: a send is one of them.

**Follow a group** to know who joins and who leaves, without writing to it:

```php
$followed = $funnelchat->groups->follow('120363000000000001@g.us', ['device_id' => $number['id']])['data'];
// ['uid' => …, 'name' => …, 'imported' => true the first time, 'is_admin' => whether your number is an admin]
```

It is the panel's import: the group enters your account and its participants are synced. QR numbers only (`422 provider_unsupported` otherwise), with **Groups and campaigns → Manage groups** (`403 import_permission_required`); up to 5 groups per number per minute (`429 import_rate_limited`, which the SDK waits out by itself when `Retry-After` is at most 60 seconds).

Who joins and who leaves a group arrives in `group_participant.joined` and `group_participant.left` (subscribe to them like any other, with **Groups and campaigns → View** in the key):

```php
if ($event['type'] === 'group_participant.joined') {
    $groupUid = $event['data']['group']['uid'];
    $who = $event['data']['participant']['identity']; // the phone, or the WhatsApp id when there is no phone
    $campaign = $event['data']['campaign'];           // null when the group is not in a campaign
}
```

More about groups: [Groups](#groups-reference).

## Workspaces

- Each key belongs to the workspace where it was created, and sees and operates only that workspace: its numbers, contacts, groups and subscriptions. A record of another workspace answers `404`, as if it did not exist.
- A subscription receives only the events of its workspace, and every event says which one in `data.workspace_id`.
- An agency gives each customer a workspace and uses one key per workspace. Workspaces are created in the panel, not through the API.
- With a single workspace nothing changes: `data.workspace_id` is the id of your default workspace.

```php
$workspaceId = $event['data']['workspace_id']; // the same for every event of one subscription
```

---

The rest is reference.

- [API keys and permissions](#api-keys-and-permissions) · [The client](#the-client) · [Every function of the portal](#every-function-of-the-portal) · [The resources](#the-resources)
- [Sending a message](#sending-a-message) · [Groups](#groups-reference) · [Creating and connecting numbers](#creating-and-connecting-numbers)
- [Receiving webhooks with Laravel](#receiving-webhooks-with-laravel) · [Pagination](#pagination) · [Errors](#errors) · [Retries](#retries) · [Any route](#any-route)
- [Regenerating](#regenerating) · [Pending of the spec](#pending-of-the-spec) · [Development](#development) · [Testing against the sandbox](#testing-against-the-sandbox)

## API keys and permissions

The key (`<id>|fc_live_...`) is created in the panel, in **Developers** → **Create key**, with the permissions for what you are going to use. The panel groups them by area, one box per permission. There are twelve and a key can have all of them; there is no wildcard:

| Area | Permissions |
|---|---|
| **Contacts and conversations** | **View**, **Edit**, **Send** |
| **Groups and campaigns** | **View**, **Edit**, **Send**, **Manage groups** |
| **WhatsApp numbers** | **View**, **Edit**, **Create, connect and delete** |
| **Notifications (webhooks)** | **View**, **Manage** |

This README names each one as the panel does: **WhatsApp numbers → View** is the **View** box of the **WhatsApp numbers** area.

Within each area, **Send** includes **Edit** and **Edit** includes **View**; **Manage groups** and **Create, connect and delete** are separate. Keep the key in an environment variable: the client reads it from `FUNNELCHAT_API_KEY` when you do not pass it. A key acts only in the workspace where it was created ([Workspaces](#workspaces)).

## The client

```php
use Funnelchat\Funnelchat;

$funnelchat = new Funnelchat(apiKey: getenv('FUNNELCHAT_API_KEY'));

// Your WhatsApp numbers (permission WhatsApp numbers → View). The `id` of each one is the `device_id` you send with.
foreach ($funnelchat->devices->list() as $device) {
    echo $device['id'], ' ', $device['number'], "\n";
    // What that number can do through the API: messages and the delivery statuses it reports.
    var_dump($device['capabilities']['messages']['templates'], $device['capabilities']['statuses']);
}
```

- Any PSR-18 HTTP client with PSR-17 factories, which the SDK finds by itself. With Guzzle (7 or 8) it also applies the timeout. Guzzle is not required: any PSR-18 client with PSR-17 factories works (for example `symfony/http-client` with `nyholm/psr7`). The SDK finds them with [php-http/discovery](https://docs.php-http.org/en/latest/discovery.html); if Composer asks about its plugin, you can say no.
- Retries with exponential backoff, pagination with `foreach` (generators), typed exceptions, the `X-Request-Id` of every response and webhook signature verification.
- Responses are arrays (the decoded JSON) typed with PHPDoc: PHPStan understands them and PhpStorm 2025.1 or later completes their keys.
- The package is published on [Packagist](https://packagist.org/packages/funnelchat/funnelchat-php).

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

## Every function of the portal

The SDK covers every function the developers portal publishes for an API key, those of [«Más funciones»](https://developers-portal.funnelchat.app/docs/mas-funciones/) included:

<!-- BEGIN GENERATED: functions (composer generate; do not edit by hand) -->
**253 operations and 56 webhook event types**, in the 30 groups of the portal (named as the portal names them), by category:

- **[Primer uso](https://developers-portal.funnelchat.app/)**, 1 group: Mensajes.
- **[Funciones de WhatsApp](https://developers-portal.funnelchat.app/docs/mas-funciones/whatsapp/)**, 9 groups: Números, Grupos, Atender una conversación, Cola de mensajes de un número, Acciones de grupo, Plantillas, Formularios, Conversions API, Costos y cuentas de Meta.
- **[Funciones de Funnelchat](https://developers-portal.funnelchat.app/docs/mas-funciones/funnelchat/)**, 20 groups: Contactos, Conversaciones, Etiquetas, Campos personalizados, Automatizaciones, Campañas, Envíos programados, Estadísticas, Difusiones, Tableros, Tickets, Notas, Respuestas rápidas, Carpetas de flujos, Puntaje, Ventas, Dominios propios, Tu cuenta y tu equipo, Alertas, Webhooks.
<!-- END GENERATED: functions -->

The resources ([The resources](#the-resources)) wrap the most used ones, with the same names as the Node SDK. Any of them is a call with [`request()`](#any-route), or `paginate()` for a list, by the method and path the portal shows:

```php
foreach ($funnelchat->paginate('/api/v1/broadcasts', ['query' => ['perPage' => 50]]) as $broadcast) {
    echo $broadcast['id'], ' ', $broadcast['name'], "\n";
}

$tickets = $funnelchat->request('GET', '/api/v1/tickets')['data'];
```

`Funnelchat\Generated\Routes::ALL` lists every operation with its group as the portal names it (`group`), the category of the group (`category`), its permission and its summary; `Routes::GROUPS` has the groups in the portal's order. An operation with `destructive: true` cannot be undone, such as deleting a number: a script or an AI agent acting on its own should ask a person to confirm before calling it. `destructive: null` means the spec does not say.

## The resources

| Resource | What it has |
|---|---|
| `$funnelchat->contacts` | `list`, `create`, `upsert`, `retrieve`, `update`, `delete`, the tags and fields of a contact, `metaWindow` (the 24-hour window), notes. |
| `$funnelchat->conversations` | `list`, counters, mark everything as read or unread. |
| `$funnelchat->messages` | `send`, `sendDirect` (to a phone, no contact needed), `sendForm` (a form), `get` (one message by id), `list` (a contact's history), `sendContactCard`, `forward`, `react`, `removeReaction`. |
| `$funnelchat->templates` | `list`, `create`, `retrieve`, `update`, `send`, `sync` (bring a number's templates from Meta), `delete`. |
| `$funnelchat->forms` | WhatsApp forms: `list`, `templates`, `create`, `retrieve`, `update`, `delete`, `createVersion`, `sync`, `publish`, `numbers`, `addNumber`, `removeNumber`, `retryNumbers`, `definition`, `versions`, `listResponses`, `retrieveResponse` and `metrics`. |
| `$funnelchat->tags` | `list`, `create`, `retrieve`, `update`, `delete`, `listContacts`. |
| `$funnelchat->customFields` | `list`, `create`, `retrieve`, `update`, `delete`. |
| `$funnelchat->groups` | `list`, `retrieve`, `listByDevice` (the number's groups, live), `follow` (start receiving who joins and leaves), `metadata`, `listParticipants`, `listParticipantEvents`, `listScheduledMessages`, `listActions`, `sendMessage` (to a group, now) and `retrieveAction` (how a send ended). See [Groups](#groups-reference). |
| `$funnelchat->devices` | `list` (filter by `external_reference`), `create` (a QR number), `qr`, `retrieve` (with `capabilities`), `update`, `delete`, `status`, `health`, connection history. See [Creating and connecting numbers](#creating-and-connecting-numbers). |
| `$funnelchat->connectLinks` | `create`, `get`, `list`, `revoke`: [connect links](#a-connect-link) for your customer to connect a number. |
| `$funnelchat->webhooks` | Subscriptions (`list`, `create`, `retrieve`, `update`, `delete`, `test`, `listDeliveries`), the catalog (`events`), `verify` and `constructEvent`. |

They have the same methods as the [Node SDK](https://github.com/Funnelchat20/funnelchat-node), with the arguments in the same order: path parameters first, then the query or the body (arrays), and the call options last.

The resource classes are generated from the spec: the parameters, query, body and response of each method are typed in its PHPDoc with array shapes (`array{id: int, name: string, …}`). The schemas of the spec are PHPStan aliases in `Funnelchat\Generated\Schemas` (`ContactResource`, `TagResource`…), which you can use in your code with `@phpstan-import-type ContactResource from \Funnelchat\Generated\Schemas`. For any other function, see [Every function of the portal](#every-function-of-the-portal).

## Sending a message

Sending with an API key needs the **Contacts and conversations → Send** permission and follows the WhatsApp rules.

**To a phone.** `messages->sendDirect()` takes `device_id` and `phone` (digits, without `+`), with no contact first:

- If the phone is not a contact yet, it is created in your key's workspace, with the same rules and `422`s as `contacts->upsert()`. It stays created even if a WhatsApp rule (the 24-hour window, the antiblock, the MACs, a disconnected number) then stops the send.
- The answer is the same as `send()`'s, with the contact in `message.contact_id`.
- Instead of `phone`, `contact_id`; or `whatsapp_id` (Meta's BSUID) with an official number only: with a QR number, with `phone`, or without the shape of a BSUID it answers `422`.
- With `phone` and `contact_id`, they must be the same contact (`422 recipient_mismatch`). The phone of a contact of another workspace of the account answers `404`: nothing is created or sent.

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

### Closed window: an approved template

The route takes the number (`3`) and the template id (`45`), which comes from `$funnelchat->templates->list()`. If a template you created in Meta is not in that list yet, bring the number's templates from Meta with `$funnelchat->templates->sync(3)` and list again (official numbers only; `['status' => 'in_progress']` means the rest are still coming, so list again in a few seconds):

```php
$funnelchat->templates->send(3, '45', [
    'phone' => '5491100000000',                     // alone: the contact is created if it does not exist
    'template_variables' => ['body' => ['Lucía']], // the body variables, in order
]);
```

With `contact_id` instead of `phone`, or with both if they are the same contact.

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
| `422 recipient_unsupported` | The contact is a group, a broadcast list or a channel. | Write to a group with `groups->sendMessage()`. |
| `422 device_unavailable` | The number is not connected to WhatsApp. | Connect it and retry. |
| `429 rate_limited` | You went over your number's pace. It always has `retryAfter`. | Wait those seconds and retry. |
| `429 mac_lockout_24h` | The account is in the 24-hour lockout for using up its MACs (also for a contact that is inactive by MAC). No `retryAfter`. | Do not retry: wait for the lockout to end. |

`$exception->isSendRuleError()` tells you whether the exception is one of these rules or one of the other refusals of a send (`not_sent`, `template_not_found`, `form_not_published`, `provider_unsupported`, the refusals of a send to a group…; the list is in `Funnelchat\Exception\SendErrors::CODES`). A send is **never retried by itself**, because a retry can deliver the same message twice. If a `429 rate_limited` sent nothing and you want the SDK to retry for you, ask for it in that call (a `mac_lockout_24h` is never retried):

```php
$funnelchat->messages->send(2001, $body, ['retrySend' => true]);
```

The full example is in [examples/plain-php/send-message.php](examples/plain-php/send-message.php).

## Groups (reference)

With **Groups and campaigns → View**: the groups of your key's workspace, their participants, joins and leaves, the messages sent to them and their scheduled messages. With **Groups and campaigns → Send**: a message to a group, now.

```php
foreach ($funnelchat->groups->list(['deviceId' => 4]) as $group) {
    echo $group['name'], "\n";
}

$participants = $funnelchat->groups->listParticipants(['groupUid' => '120363000000000001@g.us'])->firstPage();
$metadata = $funnelchat->groups->metadata('120363000000000001@g.us', ['device_id' => 4, 'group_uid' => '120363000000000001@g.us']);
```

**Sending to a group.** `groups->sendMessage($groupUid, $body)` and `groups->retrieveAction($actionId)`, as in [Groups](#groups) above. The refusals, with nothing sent (all of them in `SendErrors::CODES`, so `isSendRuleError()` is `true`):

| Error | What happened |
|---|---|
| `404 group_not_found`, `404 device_not_found` | The group or the number is not of your key's workspace. |
| `422 device_not_connected` | The number is not connected. |
| `422 device_not_group_admin` | A QR number that is not an admin of the group. |
| `422 device_not_group_number` | An official number that is not the group's own number. |
| `422 unsupported_group_type` | A community or a channel, not a group. |
| `422 unsupported_type`, `422 provider_unsupported` | The number cannot send that type, or the account lacks what it needs (`requires` says what). |
| `422 window_closed`, `422 number_restricted` | Official number: the 24-hour window is closed, or its quality does not let it send. |
| `429 too_many_pending_actions` | The number already has 50 group actions open. Never retried. |
| `403 import_permission_required`, `429 import_rate_limited`, `409 import_in_progress` | The group was not in the account and following it first failed: the key lacks **Groups and campaigns → Manage groups**, the number followed 5 groups in the last minute, or another call is following it. |

A QR number sends at the pace of the Low-speed scheduled messages; there is no volume cap of the plan. The `result` of `retrieveAction()` for a send is `['message' => ['uid' => …]]`.

**The group events.** `group_participant.joined` and `group_participant.left`: `group` (`uid`, `name`), `campaign` (`uuid`, or `null` for a group without a campaign) and `participant` (`identity`, `identity_type` `phone` or `whatsapp_user`, `name`). Also `group_action.completed` and `group_action.failed` (the result of a send), `group.message_received`, `group.reaction_added` and the other group events of `Funnelchat\Generated\WebhookEvents::CATALOG`.

## Creating and connecting numbers

> **The official signup by link (`'type' => 'meta'`) is not available in production yet:** it answers `503 meta_connect_disabled` until Funnelchat registers its domain with Meta. QR numbers and QR links work. Try the Meta flow against [the sandbox](#testing-against-the-sandbox), turning it on there.

A number belongs to the workspace of the API key and uses the plan's quota, with the same rules as the panel. Creating, deleting, asking for the QR and creating or revoking links need **WhatsApp numbers → Create, connect and delete**; reading links, **WhatsApp numbers → View**. Tag each number with your own `external_reference` (your customer's id, for example): it comes back in the number, in every `device.*` event, and filters the list.

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
$link = $funnelchat->connectLinks->get($link['id'])['data'];
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

`device` is `id`, `name`, `number`, `connection` (`qr`, `coexistence`, `dedicated` or `null`) and `external_reference`. `connect_link_id` is, in `device.connected`, the link of that connection; in the other three, the last link that connected the number; `null` when no link did. Like every event, they carry `workspace_id`. The types are `DeviceConnectedEvent`, `DeviceDisconnectedEvent`, `DeviceDeletedEvent` and `DeviceHealthChangedEvent` in `Funnelchat\Generated\WebhookEvents`.

```php
$event = $funnelchat->webhooks->constructEvent($body, $signature, $secret);
if ($event['type'] === 'device.connected') {
    markConnected($event['data']['device']['external_reference'], $event['data']['device']['id']);
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
- The algorithm is exactly the one of the portal's `snippets/verificar-firma.php`: `X-Funnelchat-Signature: t=<unix>,v1=<hex>`, where `v1` is the HMAC-SHA256 of `"<t>." + raw body` with the full secret, compared in constant time (`hash_equals`), and there can be several `v1`. The tests use the vectors with which the portal tests its signature code, and compare, case by case, with a copy of the snippet.
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

Every event's `data` carries `workspace_id`, the workspace it belongs to ([Workspaces](#workspaces)). The catalog of events the SDK knows, with their status, is `Funnelchat\Generated\WebhookEvents::CATALOG`.

### Subscribing

```php
$subscription = $funnelchat->webhooks->create([
    'url' => 'https://your-server.example/hooks/funnelchat',
    'events' => ['message.received', 'message.status_changed'],
    'description' => 'My integration',
]);
// Store $subscription['id'] and $subscription['secret']: the secret is shown only once.
```

- Subscriptions need **Notifications (webhooks) → View** and **Notifications (webhooks) → Manage**, and for each event type, the **View** of the event's area: **Contacts and conversations → View** for `message.received` and `message.status_changed`, **Groups and campaigns → View** for the group events, **WhatsApp numbers → View** for `device.*`. When it is missing, `create` and `update` answer `422` with the problem in `errors['events']`, and the others `403 permission_denied`.
- They belong to the workspace of the key: `list()` (paginated) also brings the ones of other integrations of that workspace, except those with events of an area your key cannot read. A subscription receives only the events of its workspace. At most 50 per workspace.
- To change the URL use `update($id, […])`, which replaces `url`, `events`, `description` and `filters` (the secret does not change). `test($id)` sends a signed `webhook.test` right now and `listDeliveries($id)` is the history.
- Rotating the secret is not open to API keys, so it is not in the SDK: rotate it from the dashboard.
- List every type you want by name: there is no wildcard (`*` answers `422`, accounts v1.5.97 accepts only the subscribable types of `events()`).
- The catalog, with the area and status (`active` or `planned`) of each type, is `$funnelchat->webhooks->events()` (needs **WhatsApp numbers → View**).

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

A `403` with `errorCode` `route_not_available` means that route is not open to API keys, even if your key has the permission. A record of another workspace answers `404`, the same as one that does not exist.

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
- **A send is never retried by itself**: the `POST`s that need a **Send** permission (**Contacts and conversations → Send** or **Groups and campaigns → Send**: messages, contact cards, forwards, templates, forms and messages to a group). They are retried only if you pass `'retrySend' => true` in that call. Reactions (`PUT` and `DELETE`) are retried, because repeating them changes nothing.
- **A write that may have been applied is not repeated by itself**: a `POST` or a `PATCH` is retried only on a `429` or a `503`, when it was certainly not applied. On a `500`, `502` or `504`, a timeout or a dropped connection, the exception is thrown right away (a `502 origin_unreachable` does not guarantee the area did not get it): read the state before repeating it. `retrySend` does not change this. Reads, `PUT`s and `DELETE`s are retried on any transient failure. It is the same rule as in the Node SDK.
- **A create with an `idempotencyKey`** (`devices->create()`, `connectLinks->create()`) is retried like a `PUT`: the API answers the same resource to the same key. A send is not, key or no key.
- `429 mac_lockout_24h`, `429 mac_limit_reached`, `429 too_many_pending_actions`, `429 too_many_open_links` and `503 meta_connect_disabled` are never retried.
- Per call you can change `maxRetries` and the headers, and with Guzzle the `timeout`:

```php
$funnelchat->tags->list([], ['maxRetries' => 0, 'timeout' => 5, 'headers' => ['X-My-Trace' => 'abc']]);
```

## Any route

`request()` calls any route, whether it is in a resource or not: every function of the portal without a method, one the spec adds after this version of the SDK, or one you want to call with a body the spec does not document. Path parameters go in `path`, the query in `query` and the body in `body`:

```php
$contact = $funnelchat->request('GET', '/api/v1/contacts/{contact}', ['path' => ['contact' => 2001]]);

$stats = $funnelchat->request('GET', '/api/v1/broadcasts/{broadcast}/statistics', ['path' => ['broadcast' => 77]]);

$result = $funnelchat->request('POST', '/api/v1/something-new', ['body' => ['a' => 1]]);
```

It returns the decoded JSON (associative arrays), the text when the response is not JSON, or `null` when it came empty (`204`). Here the SDK does not know the shape of the response: if you want it typed, use the schema alias (`/** @var BroadcastResource $broadcast */`). The body `[]` is sent as `{}`.

`paginate()` is the same for lists: `foreach ($funnelchat->paginate('/api/v1/broadcasts') as $broadcast)`.

The query is built the way Laravel reads it: arrays go as `key[]=a&key[]=b`, nested arrays with brackets, booleans as `1` and `0` and dates (`DateTimeInterface`) in ISO 8601, in UTC.

`Funnelchat\Generated\Routes::ALL` has every operation of the spec with its domain, its permission, its group in the portal (`group`) and the group's category (`category`, `null` while the spec does not say it), whether it cannot be undone (`destructive`) and the SDK method that calls it (`sdk`).

## Regenerating

Everything generated comes from a single script, in PHP, and from the spec **the developers portal publishes**: only what the portal shows, which is what an API key can use (the portal's full unified spec also has internal routes and routes closed to keys, and the SDK does not take it).

```bash
# With the spec the portal publishes, downloaded (say where it came from, and the portal's version):
curl -fsSL -o funnelchat.json https://developers-portal.funnelchat.app/specs/funnelchat.json
composer generate -- --spec funnelchat.json \
  --source url=https://developers-portal.funnelchat.app/specs/funnelchat.json --source portal_version=v0.6.13

# With the one already in spec/ (for example, after editing spec/pending.json or spec/resources.json):
composer generate

# To check that the generated code is up to date (useful in CI):
composer generate:check
```

What it does (`scripts/generate.php`):

1. With `--spec`, it copies the spec to `spec/funnelchat.json` and writes `spec/procedencia.json`: what `--source` declares (the public URL and the portal's version; from a checkout of a repository, its repository, branch and commit are read from git), the date and `sha256` of the file, and what the spec says of each domain. A local path is never recorded.
2. It applies `spec/pending.json` (the [pending of the spec](#pending-of-the-spec) layer): corrections to shapes of what the spec publishes, **never an operation or an event it does not publish**. At the end it lists the entries the new spec made obsolete, and those it does not publish, so you can delete them from `spec/pending.json`.
3. It writes `src/Generated/Routes.php` (every operation, and the portal's groups), `src/Generated/Schemas.php` (every schema as a PHPStan alias), `src/Generated/WebhookEvents.php` (the catalog and the shape of each event, taken from the schema or from the example), `src/Resources/*.php` (the methods of `spec/resources.json`, with their types), `src/Generated/ResourceProperties.php` (the client's properties) and the block of [Every function of the portal](#every-function-of-the-portal) in this README.

**Regenerating never needs anything edited by hand.** `spec/resources.json` says which operation each method of each resource is, with the same resources and names as the Node SDK. A new operation of the spec is in `Routes::ALL`, called with `request()`; an operation of that list the spec no longer has leaves the SDK, and the generation says which one, so it can be deleted from there. The category of each group comes from the spec: `x-fc-categoria` on its `x-tagGroups` entry (or `x-fc-bloque`, as the portal's full spec calls it), the id of a category of `x-fc-categorias` (`whatsapp` → «Funciones de WhatsApp»); `Routes::ALL`, `Routes::GROUPS`, the resources' PHPDoc and the block of this README take it. After regenerating: `composer check` (`composer analyse` flags a method whose shape changed under your code).

**The current spec** is the one the developers portal publishes (since 11-11, when the SDK started taking it), `https://developers-portal.funnelchat.app/specs/funnelchat.json`: the functions of the first use and those of «Más funciones». Its URL, the portal's version, when it was taken and its `sha256` are in `spec/procedencia.json`. Its own texts (summaries, purposes and descriptions of its operations and events) are in Spanish, as the portal publishes them.

### What left in 0.4.0

The SDK has only what the portal publishes. Up to 0.3.3 it was generated from the portal's full unified spec, plus the pending layer; these were there and are not published, because they are internal or closed to API keys in production:

- The methods `conversations->update()` (`PUT /api/v1/contacts/{contact}/update`), `templates->quickReplyButtons()`, `groups->listMessages()`, `devices->me()`, `devices->checkPhone()` and `webhooks->rotateSecret()`.
- 30 routes without a method, now neither in `Routes::ALL`: the writes of the alert destinations (create, edit, delete, its code and its verification, and the test: 6), the old webhook endpoints (`/api/v1/webhooks/endpoints…`, 7), the logs of the numbers (`/api/v1/devices/logs`, `/api/v1/devices/{device}/logs`, `…/logs/health`), `extension-token`, `disconnect-survey` (2), `chats`, `open-conversations`, `update-webhook-received` and `update-webhook-received-and-delivery` of a number, `POST /api/v1/lead-score/events`, `GET /api/v1/flows/{flow}/active-contacts-list`, a campaign's `participants`, `campaign-participants`, `webhooks` and `webhooks/logs`, and `GET /api/v1/stats/countries`.
- The events `billing.plan_blocked`, `subscription.expiring`, `ai_quota.low_balance`, `ai_quota.topup_succeeded` and `ai_quota.topup_failed`.

And one name changed, to be the Node SDK's: `connectLinks->retrieve()` is `connectLinks->get()`.

If the portal publishes one of them again, `composer generate -- --spec` brings it back.

## Pending of the spec

What production answers with another shape than the spec says is in a separate layer, marked as such in the code (`pending` in `Routes::ALL` and in `WebhookEvents::CATALOG`, and "Pending of the spec" in the method's PHPDoc):

- `spec/pending.json`: fields, query parameters, responses, schemas and the type of some events, in OpenAPI format, with the source of each entry (`x-fc-pending`, explained in its `sources`). It never adds an operation or an event: an entry for one the spec does not publish is left out, and the generation lists it.
- Each correction carries the condition that ends it (`while`, `x-fc-replace-while`): a top-level key, or a JSON pointer into the spec's operation or event (`/responses/200/content/application~1json/schema/type`). When a new spec changes the very thing a correction corrects, the generation stops applying it and lists it as obsolete.

All of it was checked against the versions in production on 04-11 (sending by phone, against conversations `v1.19.21` of 05-11, as the spec has it): accounts `v1.5.97`, conversations `v1.19.7`, communities `v2.35.5` and platform-gate `v0.9.1`, plus the API entrypoint deployed to production on 25-10. With the published spec of 11-11, the forms routes are the spec's, so they left this layer, and so did what the spec does not publish (`rotateSecret()` and five events); every correction below still applies, because the published spec still has the shape it corrects. That communities sends `message.uid` in the group events was checked again on 11-11, against communities `v2.35.28`.

| What stays in the pending layer | Why |
|---|---|
| The answer of `groups->retrieveAction()` | The spec types `data` as a list; communities answers the action as an object. |
| `data.workspace_id` in every event whose shape lacks it | accounts writes it in every event it delivers. |
| `message.received`, `message.status_changed`, `whatsapp_form.completed`, `group_participant.joined` and `.left` typed from production | The spec types them only by an example: every id an `int`, `error` and `client_reference` always there, `campaign` never `null`. |
| The events accounts delivers typed from production: `device.*`, billing, subscription, MAC, `account.blocked`, `group_action.*`, `daily_report.generated`, `campaign.no_groups_available`, `group.full`, `group.message_received`, `group.reaction_added` and `group_join_request.*` | The spec has them since 11-11, but only by an example (and three without one), which loses what can be `null` (`connect_link_id`, `external_reference`, a group's `name`…) and the values of `reason`, `state` and `identity_type`. For `group.message_received` and `group.reaction_added` its example says `message.id`; communities sends `message.uid`, so they are typed with `uid` while the example lacks it. |
| The answer of the four sends (`message` is never `null` for an API key), the QR (`{data}` only), a connect link read (`url` is `null`), `connectLinks->revoke()` and `devices->delete()` (empty body), `webhooks->update()`, `templates->list()` and `tags->listContacts()` | The spec infers them from the code and gets them wrong: a nullable message, three extra QR shapes, a `string` body, an empty object, `object` and `string` items. |
| `perPage` of `webhooks->list()` and `connectLinks->list()`; `per_page`, `event_type` and `event_id` of `webhooks->listDeliveries()` | Production accepts them; the spec does not declare them. |

The error codes of the sends are in `Funnelchat\Exception\SendErrors`, written by hand from the same sources: the spec does not list them. Those of creating and connecting numbers, in `Funnelchat\Exception\DeviceErrors`.

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

[funnelchat-sandbox](https://github.com/Funnelchat20/funnelchat-sandbox) (`@funnelchat-hq/sandbox` on npm) imitates the API and sends signed webhooks, with no real effects. Point the client at it with `baseUrl` and any well-formed key:

```php
$funnelchat = new \Funnelchat\Funnelchat(apiKey: '1|fc_live_sandbox', baseUrl: 'http://127.0.0.1:4010');
```

The first use runs against it as is: `FUNNELCHAT_API_KEY='1|fc_live_sandbox' FUNNELCHAT_BASE_URL=http://127.0.0.1:4010 php examples/plain-php/first-use.php …`, and `POST /_sandbox/devices/{id}/connect` is the scan of the QR. Sending by phone (`messages->sendDirect()` without `contact_id`) needs `@funnelchat-hq/sandbox` 0.3.1 or later.

The Meta sign-up by link starts off in the sandbox, like production: `type: 'meta'` answers `503 meta_connect_disabled`. Turn it on to try it, with `npx @funnelchat-hq/sandbox --meta-connect`, or while it runs:

```bash
curl -s -X POST http://127.0.0.1:4010/_sandbox/numbers -H 'Content-Type: application/json' -d '{"meta_connect": true}'
```

The SDK's end-to-end tests run against it, version 0.2.0 or later: the first use (a QR number connected, a contact, a send and its signed `read`), a send and its signed notices, which a receiver on PHP's built-in server verifies; the refusals; `messages->get`; a form from build to answer; `X-Request-Id`; a QR number created, scanned, disconnected and deleted, a connect link completed on its page, `country`, the refusals and the four signed `device.*` events; and the signed participant events of a group. The tests of a `meta` link turn the Meta sign-up on by themselves:

```bash
npx @funnelchat-hq/sandbox --port 4010 --status-delays 0.2,0.2,0.2 --quiet
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
examples/          the first use, Laravel and plain PHP
tests/             PHPUnit; tests/E2E, against the sandbox
```

## License

[MIT](LICENSE).
