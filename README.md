# funnelchat-php

SDK oficial de PHP para la API pública de Funnelchat: contactos, conversaciones y mensajes, plantillas, formularios de WhatsApp, etiquetas, campos personalizados, grupos, números y webhooks, con los recursos y sus tipos generados de la especificación OpenAPI.

- PHP 8.1 o posterior, PSR-4.
- Cualquier cliente HTTP PSR-18 con fábricas PSR-17, que el SDK encuentra solo. Con Guzzle (7 u 8), además, aplica el timeout.
- Reintentos con espera exponencial, paginación con `foreach` (generadores), excepciones tipadas, el `X-Request-Id` de cada respuesta y la verificación de la firma de los webhooks.
- Las respuestas son arrays (el JSON decodificado) tipados con PHPDoc: PHPStan los entiende y PhpStorm 2025.1 o posterior completa sus claves.

> **Estado: v0.** La API pública está en v0 y puede cambiar. La referencia completa está en el [portal de desarrolladores](https://developers-portal.funnelchat.app/docs/). Algunos tipos vienen del código de la API en producción y no de la especificación publicada: están en [Pendiente de la especificación](#pendiente-de-la-especificación).

## Instalación

```bash
composer require funnelchat/funnelchat-php guzzlehttp/guzzle
```

Guzzle no es obligatorio: sirve cualquier cliente PSR-18 con fábricas PSR-17 (por ejemplo `symfony/http-client` con `nyholm/psr7`). El SDK los encuentra con [php-http/discovery](https://docs.php-http.org/en/latest/discovery.html); si Composer te pregunta por su plugin, podés decir que no.

Antes de que salga en Packagist, se instala desde GitHub. En el `composer.json` de tu proyecto:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/Funnelchat20/funnelchat-php" }
    ]
}
```

```bash
composer require funnelchat/funnelchat-php:^0.1@dev guzzlehttp/guzzle
```

## Primer llamado

Necesitás una clave de API (`<id>|fc_live_...`) creada en «Desarrolladores», con los permisos de lo que vas a usar: hay catorce (`conversations:read`, `conversations:write`, `conversations:send`, `communities:read`, `communities:write`, `communities:send`, `communities:operate`, `accounts:read`, `accounts:write`, `accounts:operate`, `wha-link:read`, `wha-link:write`, `webhooks:read`, `webhooks:write`) y una clave puede tenerlos todos; no hay comodín. Dentro de cada área, `send` incluye `write` y `write` incluye `read`; `operate` va aparte. Guardala en una variable de entorno: el cliente la lee de `FUNNELCHAT_API_KEY` si no se la pasás.

```php
use Funnelchat\Funnelchat;

$funnelchat = new Funnelchat(apiKey: getenv('FUNNELCHAT_API_KEY'));

// Tus números de WhatsApp (permiso accounts:read). El `id` de cada uno es el `device_id` con el que enviás.
foreach ($funnelchat->devices->list() as $device) {
    echo $device['id'], ' ', $device['number'], "\n";
    // Qué puede hacer ese número por la API: mensajes, grupos y los estados de entrega que informa.
    var_dump($device['capabilities']['messages']['templates'], $device['capabilities']['groups']['send']);
}
```

Opciones del cliente (argumentos con nombre):

| Opción | Qué es | Por defecto |
|---|---|---|
| `apiKey` | Tu clave. | `FUNNELCHAT_API_KEY` |
| `environment` | `production` (`https://api.funnelchat.com`) o `staging` (`https://api-staging.funnelchat.com`). | `production` |
| `baseUrl` | Otra URL base; gana sobre `environment` (por ejemplo, el [sandbox](#probar-contra-el-sandbox)). | — |
| `timeout` | Segundos máximos de cada intento. Solo con Guzzle (ver abajo). | `30` con el Guzzle que arma el SDK |
| `maxRetries` | Reintentos automáticos (ver [Reintentos](#reintentos)). `0` los apaga. | `2` |
| `defaultHeaders` | Cabeceras que van en todos los pedidos. | — |
| `httpClient` | Tu cliente PSR-18. | Guzzle si está instalado; si no, el que encuentre discovery |
| `requestFactory`, `streamFactory` | Tus fábricas PSR-17. | las que encuentre discovery |
| `sleep` | Cómo esperar entre reintentos: `function (int $milisegundos): void`. Sirve para tests. | `usleep` |

**El timeout.** PSR-18 no tiene timeout, así que el SDK solo lo puede aplicar con Guzzle: al Guzzle que arma solo le pone 30 segundos, y si le pasás el tuyo, aplica el `timeout` que indiques (si no indicás ninguno, rige la configuración de tu Guzzle). Con otro cliente, configurá el timeout en ese cliente: si pasás `timeout`, el SDK falla enseguida para que nadie crea que hay uno.

Cada pedido manda `Authorization: Bearer <clave>`, `Accept: application/json` y `User-Agent: funnelchat-php/<versión> php/<versión>`. La clave nunca aparece en un `var_dump`, `print_r`, `var_export`, `dd()` ni `serialize` del cliente, ni en el mensaje de un error. Un error de conexión trae como `getPrevious()` la excepción de tu cliente HTTP, que puede traer el pedido con sus cabeceras: no la registres entera.

En Laravel, registrá un solo cliente para toda la app: está en [examples/laravel/app/Providers/FunnelchatServiceProvider.php](examples/laravel/app/Providers/FunnelchatServiceProvider.php).

## Las piezas

| Recurso | Qué tiene |
|---|---|
| `$funnelchat->contacts` | `list`, `create`, `upsert`, `retrieve`, `update`, `delete`, etiquetas y campos de un contacto, `metaWindow` (la ventana de 24 horas), notas. |
| `$funnelchat->conversations` | `list`, `update`, contadores, marcar todo como leído o no leído. |
| `$funnelchat->messages` | `send`, `get` (un mensaje por id), `list` (el historial de un contacto), `sendContactCard`, `forward`, `react`, `removeReaction`. |
| `$funnelchat->templates` | `list`, `create`, `retrieve`, `update`, `quickReplyButtons`, `send`, `delete`. |
| `$funnelchat->forms` | Formularios de WhatsApp: `list`, `templates`, `create`, `retrieve`, `update`, `delete`, `createVersion`, `sync`, `publish`, números, `definition`, `listVersions`, `listResponses`, `retrieveResponse`, `metrics` y `send` (a un contacto). |
| `$funnelchat->tags` | `list`, `create`, `retrieve`, `update`, `delete`, `listContacts`. |
| `$funnelchat->customFields` | `list`, `create`, `retrieve`, `update`, `delete`. |
| `$funnelchat->groups` | Leer (`list`, `retrieve`, `listByDevice`, `metadata`, participantes, eventos, mensajes, acciones) y [actuar sobre grupos](#grupos): `create`, participantes y administradores, nombre, descripción, foto, ajustes, link de invitación, `sendMessage`, solicitudes de unión y `retrieveAction`. |
| `$funnelchat->devices` | `list`, `retrieve` (con `capabilities`), `update`, `status`, `health`, `me`, `checkPhone`, historial de conexión. |
| `$funnelchat->webhooks` | Suscripciones (`list`, `create`, `retrieve`, `update`, `delete`, `rotateSecret`, `test`, `listDeliveries`), el catálogo (`events`), `verify` y `constructEvent`. |

Los recursos que también tiene el [SDK de Node](https://github.com/Funnelchat20/funnelchat-node) tienen los mismos métodos, con los argumentos en el mismo orden: primero los parámetros de ruta, después la query o el cuerpo (arrays), y al final las opciones del llamado.

Las clases de los recursos se generan de la especificación: los parámetros, la query, el cuerpo y la respuesta de cada método están tipados en su PHPDoc con formas de array (`array{id: int, name: string, …}`). Los esquemas de la especificación son alias de PHPStan en `Funnelchat\Generated\Schemas` (`ContactResource`, `TagResource`…), que podés usar en tu código con `@phpstan-import-type ContactResource from \Funnelchat\Generated\Schemas`. Para cualquier otra ruta, ver [Cualquier ruta](#cualquier-ruta).

## Enviar un mensaje

El envío con clave pide el permiso `conversations:send` y cumple las reglas de WhatsApp.

**Ventana abierta: texto libre.** Con un número oficial de Meta solo podés mandar texto libre si el contacto te escribió en las últimas 24 horas. Mirá la ventana antes:

```php
$window = $funnelchat->contacts->metaWindow(2001, ['device_id' => 3]);

if ($window['is_open']) {
    $result = $funnelchat->messages->send(2001, [
        'device_id' => 3,
        'phone' => '5491100000000',            // el teléfono del mismo contacto, sin +
        'text' => '¡Hola! Te paso la info…',
        'client_reference' => 'pedido-8841',   // opcional: tu referencia para seguirlo
    ]);
    echo $result['message']['id'], ' ', $result['message']['status'], "\n"; // 9001 queued
}
```

- `send` devuelve `['message' => […]]`: el mensaje registrado, con su `id`, `status` `queued` y tu `client_reference`. **En cola no es enviado:** los estados siguientes (`sent`, `delivered`, `read` o `failed`) llegan por el evento `message.status_changed`, con el mismo `id` como `message_id`, o los leés con `$funnelchat->messages->get(2001, $id)`.
- Si el mensaje no se pudo encolar, llega igual, con `status` `failed` y `error.reason` `not_queued`. Cuando falla, `error` trae `code` y `title` de Meta, o `code` `null`, un `title` y un `reason` de Funnelchat (`device_unavailable`, `insufficient_balance`, `window_closed`, `recipient_unreachable`…).
- Las tarjetas (`sendContactCard`) devuelven `['messages' => […]]`, uno por tarjeta; el reenvío (`forward`), un ítem por destino con `status` (`queued` o `blocked`), `reason` y su `message`; la plantilla (`templates->send`) y el formulario (`forms->send`), `['message' => […]]`.
- `client_reference` (de 1 a 64 caracteres ASCII visibles, sin espacios) vuelve en el mensaje, en el historial del contacto y en `message.status_changed`.
- `contact_id` va en el cuerpo, pero `send` lo completa con el contacto de la ruta si no lo pasás.
- Mandá `phone` (o `whatsapp_id`) del mismo contacto: la API lo valida, y si no corresponde al contacto de la ruta responde `422 recipient_mismatch`.
- Con `options`, botones o una lista.

**Un archivo por URL.** Con `file` (la URL) mandás una imagen, un video, un audio, un documento o un sticker. Si la URL no deja ver el formato (una URL firmada, o sin extensión), decile cuál es con `file_type` (`image`, `video`, `audio`, `document` o `sticker`); para un documento así, `file_name` con su extensión:

```php
$funnelchat->messages->send(2001, [
    'device_id' => 3,
    'phone' => '5491100000000',
    'file' => 'https://archivos.example/descarga?firma=…',
    'file_type' => 'document',
    'file_name' => 'factura.pdf',
]);
```

Si falta, la API responde `422` con el campo en `errors` (`file_type`, `file_name` o `file`), o `415` (`UnsupportedMediaTypeException`, con `supportedFileTypes`) si la extensión no se puede mandar.

**Ventana cerrada: una plantilla aprobada.** En la ruta van el número (`3`) y el id de la plantilla (`45`), que sale de `$funnelchat->templates->list()`:

```php
$funnelchat->templates->send(3, '45', [
    'phone' => '5491100000000',
    'contact_id' => 2001,
    'template_variables' => ['body' => ['Lucía']], // las variables del cuerpo, en orden
]);
```

**Un formulario de WhatsApp.** `form_id` es el id del formulario publicado (`family_id`); las respuestas llegan por `whatsapp_form.completed`:

```php
$funnelchat->forms->send(2001, ['form_id' => 12, 'device_id' => 3, 'button_text' => 'Completar']);
```

**Si te frenamos.** Si un envío rompe una regla de WhatsApp, no sale: te llega una excepción.

| Error | Qué pasó | Qué hacer |
|---|---|---|
| `422 window_closed` | Texto libre con la ventana cerrada, en un número oficial. | Mandá una plantilla aprobada. |
| `422 antiblock_duplicate_content` | El mismo contenido repetido al mismo contacto. | Cambiá el texto o esperá. |
| `422 antiblock_unanswered_limit` | Demasiados mensajes seguidos a alguien que no contesta. | Esperá a que responda. |
| `422 recipient_mismatch` | El `phone` o `whatsapp_id` no es el del contacto de la ruta. | Usá los datos de ese contacto. |
| `422 recipient_unsupported` | El contacto es un grupo, una lista de difusión o un canal. | Por la API solo se escribe a personas. |
| `422 device_unavailable` | El número no está conectado a WhatsApp. | Conectalo y reintentá. |
| `429 rate_limited` | Superaste el ritmo de tu número. Siempre trae `retryAfter`. | Esperá esos segundos y reintentá. |
| `429 mac_lockout_24h` | La cuenta está en el bloqueo de 24 horas por agotar los MAC (también para un contacto inactivo por MAC). Sin `retryAfter`. | No reintentes: esperá a que pase el bloqueo. |

`$exception->isSendRuleError()` te dice si la excepción es una de estas reglas o de los otros rechazos de un envío (`not_sent`, `template_not_found`, `form_not_published`, `provider_unsupported`…; la lista está en `Funnelchat\Exception\SendErrors::CODES`). Un envío **nunca se reintenta solo**, porque un reintento puede mandar el mismo mensaje dos veces. Si un `429 rate_limited` no envió nada y querés que el SDK reintente por vos, pedilo en ese llamado (un `mac_lockout_24h` no se reintenta nunca):

```php
$funnelchat->messages->send(2001, $body, ['retrySend' => true]);
```

El ejemplo completo está en [examples/plain-php/send-message.php](examples/plain-php/send-message.php).

## Grupos

Las acciones sobre grupos son asincrónicas: cada una responde `202` con `action_id` y `status` `queued`, y el resultado llega después, en `$funnelchat->groups->retrieveAction($actionId)` o por los eventos `group_action.completed` y `group_action.failed`.

```php
$accepted = $funnelchat->groups->create(['device_id' => 4, 'name' => 'Clientes VIP', 'participants' => ['5491100000000']]);
$action = $funnelchat->groups->retrieveAction($accepted['action_id'])['data']; // queued, running, completed o failed

$funnelchat->groups->sendMessage('120363000000000001@g.us', ['device_id' => 4, 'type' => 'text', 'text' => 'Hola a todos']);
```

- **Están apagadas por defecto.** Hasta que Funnelchat las habilite, responden `403 route_not_available` a cualquier clave.
- Piden `communities:operate` (crear, participantes, administradores, nombre, descripción, foto, ajustes, link), `communities:send` (`sendMessage`, que nunca se reintenta solo) o `communities:read` (`retrieveAction`, `inviteLink`, `listJoinRequests`). `operate` no incluye `read`: para pedir una acción y seguirla necesitás los dos.
- `capabilities.groups` de cada número (`$funnelchat->devices->retrieve($id)`) dice qué puede hacer: un número por QR suma participantes directo; uno oficial los invita, necesita la beta `cloud-api-groups` (si no, `422 provider_unsupported`) y no cambia administradores, foto ni ajustes.
- Las acciones con teléfonos (hasta 20 por pedido) terminan `completed` aunque fallen todos: mirá `result.participants[].status`.
- Hasta 50 acciones abiertas por número: más responde `429 too_many_pending_actions`.

## Recibir webhooks con Laravel

Verificá la firma sobre el **cuerpo crudo**, tal como llegó: `$request->getContent()`, no `$request->all()` ni `$request->json()`. Si lo decodificás y lo volvés a codificar, cambia la firma.

```php
use App\Jobs\ProcessFunnelchatEvent;
use Funnelchat\Exception\WebhookVerificationException;
use Funnelchat\Webhook;
use Illuminate\Http\Request;

// routes/api.php: el grupo `api` no pide el token CSRF, que un webhook no puede mandar.
Route::post('/hooks/funnelchat', function (Request $request) {
    try {
        $event = Webhook::constructEvent(
            $request->getContent(),                                  // el cuerpo crudo
            $request->header('X-Funnelchat-Signature'),
            config()->string('services.funnelchat.webhook_secret'),  // el secreto completo, con whsec_
        );
    } catch (WebhookVerificationException $exception) {
        return response()->noContent(400); // $exception->reason dice por qué
    }

    ProcessFunnelchatEvent::dispatch($event); // procesá después; deduplicá por $event['id']

    return response()->noContent(200);        // respondé rápido (menos de 10 s)
});
```

En [examples/laravel](examples/laravel) está armado con un controlador, un job que deduplica por `id` y despacha por tipo, el proveedor del cliente, la ruta y la configuración. Lo probamos copiado tal cual en una app de Laravel 13 (con PHPStan en el nivel máximo, vía Larastan).

### Con PHP plano

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

El ejemplo completo está en [examples/plain-php/webhook.php](examples/plain-php/webhook.php).

### Qué hace cada función

- `Webhook::constructEvent($rawBody, $signatureHeader, $secret)` verifica la firma y devuelve el evento decodificado: `id`, `type`, `version`, `occurred_at` y `data`. Si algo falla, tira `WebhookVerificationException` con `reason`: `missing_header`, `malformed_header`, `timestamp_out_of_tolerance`, `signature_mismatch`, `missing_secret`, `invalid_body`, `invalid_json`, `invalid_event`.
- Si solo querés saber sí o no, `Webhook::verify($rawBody, $signatureHeader, $secret)` devuelve `true` o `false` y nunca tira.
- Las dos rechazan una firma con un `t` a más de 300 segundos de tu reloj. Para cambiarlo, el cuarto argumento: `Webhook::verify($rawBody, $header, $secret, 600)`.
- El cuerpo puede ser un string o un `StreamInterface` de PSR-7.
- El algoritmo es exactamente el de `snippets/verificar-firma.php` del portal: `X-Funnelchat-Signature: t=<unix>,v1=<hex>`, donde `v1` es el HMAC-SHA256 con el secreto completo de `"<t>." + cuerpo crudo`, comparado en tiempo constante (`hash_equals`), y puede haber varias `v1`. Los tests usan los vectores de `tests/test_firmas.py` del portal y comparan, caso por caso, con una copia del snippet.
- También están en el cliente: `$funnelchat->webhooks->verify(…)` y `$funnelchat->webhooks->constructEvent(…)`.

**Los tipos de los eventos.** `type` es un string, porque pueden llegar tipos nuevos antes de que el SDK los conozca: respondé `2xx` a los que no manejás. Cuando ya miraste `type`, el alias de ese evento te da la forma completa (están en `Funnelchat\Generated\WebhookEvents`: `MessageReceivedEvent`, `MessageStatusChangedEvent`…; y `XxxData` para su `data`):

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
                // Un tipo que tu código no conoce todavía: no es un error.
        }
    }
}
```

El catálogo de eventos que conoce el SDK, con su estado, es `Funnelchat\Generated\WebhookEvents::CATALOG`.

### Suscribirte

```php
$subscription = $funnelchat->webhooks->create([
    'url' => 'https://tu-servidor.example/hooks/funnelchat',
    'events' => ['message.received', 'message.status_changed'],
    'description' => 'Mi integración',
]);
// Guardá $subscription['id'] y $subscription['secret']: el secreto se muestra una sola vez.
```

- Las suscripciones piden `webhooks:read` y `webhooks:write`, y para cada tipo de evento, `{área}:read` del área del evento (`conversations:read` para `message.received`). Si falta, `create` y `update` responden `422` con el problema en `errors['events']`, y las demás, `403 permission_denied`.
- Son de la cuenta: `list()` (paginado) trae también las de otras integraciones, salvo las que tienen eventos de un área que tu clave no puede leer.
- Para cambiar la URL usá `update($id, […])`, que reemplaza `url`, `events`, `description` y `filters` (el secreto no cambia). `test($id)` manda un `webhook.test` firmado ahora mismo y `listDeliveries($id)` es el historial.
- `rotateSecret($id)` **no está abierta a claves**: responde `403 route_not_available`. Rotá el secreto desde el panel.
- `message.status_changed` hay que pedirlo por nombre: `*` no lo incluye.
- El catálogo, con el área y el estado (`active` o `planned`) de cada tipo, es `$funnelchat->webhooks->events()` (pide `accounts:read`). Desde el 21-10 están, entre otros, `message.status_changed`, `whatsapp_form.completed`, `group.message_received`, `group.reaction_added`, `group_action.completed` y `group_join_request.created`.

## Paginación

Los métodos `list…` devuelven un `Paginator` perezoso: no se pide nada hasta que lo recorrés.

```php
// Todos los ítems de todas las páginas (un generador: pide cada página cuando la necesita).
foreach ($funnelchat->contacts->list(['perPage' => 100, 'tag_mode' => 'any', 'tag_ids' => [12]]) as $contact) {
    echo $contact['id'], "\n";
}

// Página por página:
$page = $funnelchat->tags->list(['perPage' => 50])->firstPage();
print_r($page->data);
while ($page->hasNextPage()) {
    $page = $page->getNextPage();
}

// O con un generador de páginas:
foreach ($funnelchat->tags->list()->iterPages() as $page) {
    echo count($page), " etiquetas en esta página\n";
}
```

El SDK entiende las tres formas de paginar de la API: por número de página (`meta.current_page` y `meta.last_page`), por cursor (`meta.next_cursor`) y las listas que vienen enteras en un array. El tamaño de página se pide con el parámetro de cada ruta (`perPage`, `per_page` o `pageSize`, según la especificación). `$page->body` es la respuesta completa de esa página, con `meta` y `links`.

## Errores

Todo lo que tira el SDK hereda de `Funnelchat\Exception\FunnelchatException`. Cuando la API responde con un error, es un `ApiException`:

| Propiedad | Qué es |
|---|---|
| `status` | El código HTTP (también `getCode()`). |
| `errorCode` | El código estable del cuerpo (`window_closed`, `rate_limited`, `route_not_available`, `NUMBER_NOT_ELIGIBLE`…), o `null` si no trae (por ejemplo un `422` de validación o un `415`). En Node es `code`; en PHP `getCode()` ya existe y devuelve un número. |
| `getMessage()` | El `message` del cuerpo, para personas. Decidí por `status` y `errorCode`, no por el mensaje. |
| `requestId` | El `X-Request-Id` de la respuesta. **Citalo al reportar un problema**, con la hora aproximada de la llamada. |
| `retryAfter` | Los segundos de `Retry-After`, si vino (redondeados hacia arriba). |
| `errors` | En un `422` de validación, los mensajes por campo. |
| `body`, `headers`, `method`, `url` | La respuesta y el pedido (la URL sin la query). |

Cada código HTTP tiene su clase, en `Funnelchat\Exception`: `BadRequestException` (400), `AuthenticationException` (401), `PermissionDeniedException` (403), `NotFoundException` (404), `ConflictException` (409), `UnsupportedMediaTypeException` (415), `UnprocessableEntityException` (422), `RateLimitException` (429) e `InternalServerException` (5xx). Sin respuesta: `ApiConnectionException`, y `ApiConnectionTimeoutException` si se pasó del timeout.

Los errores propios de la entrada de la API, que contesta sin llegar a ningún área, tienen la suya:

| Clase | Error | Qué hacer |
|---|---|---|
| `RouteNotFoundException` (un `NotFoundException`) | `404 route_not_found`: la ruta no existe. | Revisá la ruta y que empiece con `/api/v1/`. |
| `EdgeMisconfiguredException` (un `InternalServerException`) | `500 edge_misconfigured`. | Es de nuestro lado: reportalo citando `requestId`. |
| `OriginUnreachableException` (un `InternalServerException`) | `502 origin_unreachable`: no se pudo llegar al área. | Tratalo como un timeout: una lectura se repite; una escritura, solo después de leer el estado (ver [Reintentos](#reintentos)). |

```php
use Funnelchat\Exception\ApiException;
use Funnelchat\Exception\RateLimitException;

try {
    $funnelchat->contacts->retrieve(2001);
} catch (RateLimitException $exception) {
    echo "Esperá {$exception->retryAfter} s\n";
} catch (ApiException $exception) {
    echo $exception->status, ' ', $exception->errorCode, ' ', $exception->getMessage(), ' (', $exception->requestId, ")\n";
}
```

Un `403` con `errorCode` `route_not_available` quiere decir que esa ruta no está abierta a claves de API, aunque tu clave tenga el permiso.

### El id del pedido

Cada respuesta de la API, de éxito o de error, trae `X-Request-Id`: un UUID distinto por pedido, con el que soporte encuentra la llamada. En un error está en `$exception->requestId`; en un éxito, en `$funnelchat->lastResponse()` (la última respuesta del cliente, con `status`, `requestId` y `headers`) o, para un llamado en particular, en `onResponse`, que también ve cada página de una lista:

```php
use Funnelchat\ResponseInfo;

$result = $funnelchat->messages->send(2001, $body);
$logger->info('enviado', ['message_id' => $result['message']['id'], 'request_id' => $funnelchat->lastResponse()?->requestId]);

$funnelchat->contacts->list([], ['onResponse' => fn(ResponseInfo $r) => $logger->debug('página', ['request_id' => $r->requestId])]);
```

Un id que mandes vos en `X-Request-Id` no se usa: la API pone el suyo. No hay una ruta para buscar una llamada por su id.

## Reintentos

- Se reintenta ante un `429`, un `5xx` o un error de red (también si se pasó el timeout), hasta `maxRetries` veces (2 por defecto).
- Con `Retry-After`, se espera exactamente lo que pide. Si pide más de 60 segundos, no se espera: la excepción sale enseguida con su `retryAfter` y decidís vos.
- Sin `Retry-After`, la espera es exponencial: 0,5 s, 1 s, 2 s… hasta 8 s, con un poco de azar.
- **Un envío nunca se reintenta solo**: los `POST` con un permiso `…:send` (mensajes, tarjetas de contacto, reenvíos, plantillas, formularios y mensajes a grupos). Se reintentan solo si pasás `'retrySend' => true` en ese llamado. Las reacciones (`PUT` y `DELETE`) sí, porque repetirlas da lo mismo.
- **Una escritura que pudo haberse aplicado no se repite sola**: un `POST` o `PATCH` que terminó en timeout o en `502` (la API no garantiza que el área no la haya recibido). Leé el estado antes de repetirla, o pasá `'retrySend' => true` si repetirla es seguro. Las lecturas, los `PUT` y los `DELETE` sí se reintentan.
- `429 mac_lockout_24h` y `429 mac_limit_reached` no se reintentan nunca.
- Por llamado podés cambiar `maxRetries` y las cabeceras, y con Guzzle el `timeout`:

```php
$funnelchat->tags->list([], ['maxRetries' => 0, 'timeout' => 5, 'headers' => ['X-Mi-Traza' => 'abc']]);
```

## Cualquier ruta

`request()` llama a cualquier ruta, esté o no en un recurso. Los parámetros de ruta van en `path`, la query en `query` y el cuerpo en `body`:

```php
$contact = $funnelchat->request('GET', '/api/v1/contacts/{contact}', ['path' => ['contact' => 2001]]);

$stats = $funnelchat->request('GET', '/api/v1/broadcasts/{broadcast}/statistics', ['path' => ['broadcast' => 77]]);

$result = $funnelchat->request('POST', '/api/v1/algo-nuevo', ['body' => ['a' => 1]]);
```

Devuelve el JSON decodificado (arrays asociativos), el texto si la respuesta no es JSON, o `null` si vino vacía (`204`). Acá el SDK no sabe la forma de la respuesta: si la querés tipada, usá el alias del esquema (`/** @var BroadcastResource $broadcast */`). El cuerpo `[]` se manda como `{}`.

`paginate()` es lo mismo para listas: `foreach ($funnelchat->paginate('/api/v1/broadcasts') as $broadcast)`.

La query se arma como la lee Laravel: los arrays van como `clave[]=a&clave[]=b`, los arrays anidados con corchetes, los booleanos como `1` y `0` y las fechas (`DateTimeInterface`) en ISO 8601, en UTC.

`Funnelchat\Generated\Routes::ALL` tiene cada operación de la especificación con su dominio, su permiso y el método del SDK que la llama (`sdk`), o `null` si se llama con `request()`.

## Cómo regenerar

Todo lo generado sale de un solo script, en PHP:

```bash
# Con la especificación unificada nueva del portal:
composer generate -- --spec ../developers-portal-worktrees/<rama>/out/specs/funnelchat.json

# Con la que ya está en spec/ (por ejemplo, después de tocar spec/pending.json o spec/resources.json):
composer generate

# Para comprobar que lo generado está al día (sirve en CI):
composer generate:check
```

Qué hace (`scripts/generate.php`):

1. Con `--spec`, copia la especificación a `spec/funnelchat.json` y escribe `spec/procedencia.json`: repositorio, rama, commit, fecha y `sha256` del archivo.
2. Suma `spec/pending.json` (la capa [pendiente de la especificación](#pendiente-de-la-especificación)), pero **solo lo que la instantánea no trae**. Al terminar lista lo que la instantánea nueva ya trae, para que lo borres de `spec/pending.json`.
3. Escribe `src/Generated/Routes.php` (cada operación), `src/Generated/Schemas.php` (cada esquema como alias de PHPStan), `src/Generated/WebhookEvents.php` (el catálogo y la forma de cada evento, sacada del esquema o del ejemplo), `src/Resources/*.php` (un método por operación, con sus tipos) y `src/Generated/ResourceProperties.php` (las propiedades del cliente).

`spec/resources.json` dice qué operación es cada método de cada recurso: son los mismos nombres que en Node. No hace falta tocar código a mano para regenerar. Si una operación de esa lista ya no está en la especificación, la generación falla y dice cuál; si cambió de forma, `composer analyse` lo marca. Después de regenerar: `composer check`.

**La instantánea actual** es la del portal, rama `feat/openapi-unificada` @ `0ef4abb`, del 15-10: 191 rutas y 230 operaciones de conversations `9d723d52c`, communities `fab0bb10` y accounts `c234b6f8` (13-10). Es anterior a lo que salió a producción el 21-10: lo nuevo está en la capa pendiente. El detalle está en `spec/procedencia.json`.

## Pendiente de la especificación

La instantánea de la especificación es del 13-10. Lo que salió después a producción está en una capa aparte, marcada así en el código, que la próxima regeneración reemplaza sola cuando una instantánea lo traiga (y avisa qué entradas ya se pueden borrar):

- `spec/pending.json`: rutas, campos, respuestas, esquemas y eventos, en formato OpenAPI, con la fuente de cada entrada (`x-fc-pending`).

Todo sale del código de los dominios en los tags que corren en producción el 22-10 (solo lectura, `git show <tag>:<archivo>`): conversations `v1.18.10` (`a580a3d0`), communities `v2.34.52` (`6f32f0b4`) y accounts `v1.4.24` (`300cc843`), más la entrada de la API, api-edge `v0.3.0` (`a1c5b350`).

| Qué | De dónde sale |
|---|---|
| Lo que responden los envíos con clave (`200` con el mensaje en cola, nunca `204`), `messages->get`, `client_reference` (también en el historial), `file_type` y `file_name` | conversations `v1.18.10` |
| Los formularios de WhatsApp (`/api/v1/forms…`), su envío a un contacto y `whatsapp_form.completed` | conversations `v1.18.10` |
| La forma de `message.status_changed` (con `error.reason`), ahora `live`, y `device_id` en `message.received` | conversations `v1.18.10` |
| Las acciones de grupo, las solicitudes de unión y los eventos `group.message_received`, `group.reaction_added`, `group_action.completed` y `group_join_request.*` | communities `v2.34.52` |
| Las suscripciones de webhooks (`/api/v1/webhooks…`, con `webhooks:*`), los eventos de accounts y cuatro de communities | accounts `v1.4.24` |
| `capabilities` y los demás campos nuevos de cada número en `devices` | accounts `v1.4.24` |

Los códigos de error de los envíos están en `Funnelchat\Exception\SendErrors`, escritos a mano desde las mismas fuentes: la especificación no los enumera.

## Desarrollo

```bash
composer install
composer generate:check   # lo generado está al día
composer cs               # PHP-CS-Fixer (PER-CS 2.0), en modo verificación; composer cs:fix lo aplica
composer analyse          # PHPStan en el nivel máximo: src, tests, scripts y examples/plain-php
composer test             # PHPUnit, sin red ni claves: un cliente PSR-18 simulado
composer check            # todo lo de arriba, en orden
```

La suite corre en PHP 8.1 (con PHPUnit 10) y en PHP 8.5 (con PHPUnit 12), con Guzzle 7 y con Guzzle 8.

### Probar contra el sandbox

[funnelchat-sandbox](https://github.com/Funnelchat20/funnelchat-sandbox) imita la API y manda webhooks firmados, sin efectos reales. Apuntá el cliente a él con `baseUrl` y cualquier clave bien formada:

```php
$funnelchat = new \Funnelchat\Funnelchat(apiKey: '1|fc_live_sandbox', baseUrl: 'http://127.0.0.1:4010');
```

Los e2e del SDK corren contra él (un envío y sus avisos firmados, que verifica un receptor en el servidor embebido de PHP; los rechazos; `messages->get`; un formulario; `X-Request-Id`):

```bash
npx funnelchat-sandbox --port 4010 --status-delays 0.2,0.2,0.2 --quiet
FUNNELCHAT_SANDBOX_URL=http://127.0.0.1:4010 composer test:e2e
```

```
spec/              la especificación, su procedencia, la capa pendiente y el mapa de recursos
scripts/           generate.php, el único generador
src/Generated/     lo generado (no se edita)
src/Resources/     los recursos, generados (no se editan)
src/Core/          HTTP, reintentos, URL y query
src/Pagination/    Paginator y Page
src/Exception/     las excepciones y los códigos de error de los envíos
src/ResponseInfo.php  el status, las cabeceras y el X-Request-Id de una respuesta
src/Webhook.php    firma y eventos
examples/          Laravel y PHP plano
tests/             PHPUnit; tests/E2E, contra el sandbox
```

## Licencia

[MIT](LICENSE).
