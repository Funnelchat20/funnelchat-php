<?php
// Verifica la cabecera X-Funnelchat-Signature de una entrega de webhook (PHP 8.0+).
// $cuerpoCrudo: el cuerpo tal como llegó (file_get_contents('php://input')), sin volver a codificar.
// $secreto: el secreto completo de la suscripción, tal como se mostró al crearla o rotarla.

function verificarFirma(string $cuerpoCrudo, string $cabecera, string $secreto, int $toleranciaSegundos = 300): bool
{
    if ($secreto === '') {
        return false;
    }

    $t = null;
    $firmas = [];
    foreach (explode(',', $cabecera) as $parte) {
        $i = strpos($parte, '=');
        if ($i === false) {
            continue;
        }
        $clave = trim(substr($parte, 0, $i));
        $valor = trim(substr($parte, $i + 1));
        if ($clave === 't') {
            $t = $valor;
        } elseif ($clave === 'v1') {
            $firmas[] = $valor;
        }
    }
    if ($t === null || ! ctype_digit($t) || $firmas === []) {
        return false;
    }

    // Rechazá entregas viejas o del futuro (protección contra repeticiones).
    if (abs(time() - (int) $t) > $toleranciaSegundos) {
        return false;
    }

    $esperada = hash_hmac('sha256', $t.'.'.$cuerpoCrudo, $secreto);
    foreach ($firmas as $v1) {
        if (hash_equals($esperada, $v1)) {
            return true;
        }
    }

    return false;
}

// Uso:
// $cuerpo = file_get_contents('php://input');
// if (! verificarFirma($cuerpo, $_SERVER['HTTP_X_FUNNELCHAT_SIGNATURE'] ?? '', (string) getenv('FUNNELCHAT_WEBHOOK_SECRET'))) {
//     http_response_code(400);
//     exit;
// }
// $evento = json_decode($cuerpo, true); // tomá type e id de acá, no de las cabeceras
// http_response_code(200); // respondé rápido; procesá después
