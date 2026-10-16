<?php

declare(strict_types=1);

namespace Funnelchat\Tests\Fixtures;

/**
 * The signature vectors of the developer portal, replicated case by case.
 *
 * Source: developers-portal `tests/test_firmas.py` (branch feat/armar-funnelchat-portal @ dd7cd42;
 * identical in feat/openapi-unificada @ 0ef4abb), which tests `snippets/verificar-firma.js`,
 * `verificar-firma.php` and `verificar_firma.py`. Same secret, same body bytes, same cases (see
 * tests/Fixtures/portal/procedencia.json). The secret is a test value published in the portal, not
 * a real one.
 */
final class PortalSignatureVectors
{
    public const SECRET = 'whsec_prueba_9f3a1c7e5b2d4a6c8e0f1a3b5c7d9e1f';

    /** `json.dumps(..., ensure_ascii=False, separators=(",", ":"))` of the portal's example event. */
    public const BODY = '{"id":"evt_000000000000000000000001","type":"message.received","version":1,"occurred_at":"2026-10-01T12:00:00Z","data":{"message":{"id":1001,"type":"text","text":"Hola, quiero más información","media":null},"contact":{"id":2001,"name":"Contacto de ejemplo","phone":"+5491100000000"},"receiver":{"phone":"+5491100000001"}}}';

    public const UTF8_BODY = '{"texto":"Ñandú 🚀 — acentos: áéíóú"}';

    public const SECRET_WITHOUT_PREFIX = '9f3a1c7e5b2d4a6c8e0f1a3b5c7d9e1f';

    /**
     * Fixed vectors computed independently with Python's `hmac` (not with this SDK) at
     * t = 1790856000 (2026-10-01T12:00:00Z), to pin the algorithm itself.
     */
    public const FIXED_T = 1790856000;

    public const FIXED_V1_BODY = 'c78b061f77e36cc12c72392f1507bf147bd55c0d90848ef8dab47769607aeb44';

    public const FIXED_V1_BODY_SECRET_WITHOUT_PREFIX = '903a0477d8b423f83ce85c3936bbe4b8682011df1355e3f4660098edcb0fad6f';

    public const FIXED_V1_UTF8_BODY = 'af96d19b25835145e88625eaf33ba41f72cd0c25e741918340db190e7c98e213';

    /** `firmar()` of test_firmas.py. */
    public static function sign(string $body, string $secret, int $t): string
    {
        return "t={$t},v1=" . hash_hmac('sha256', "{$t}." . $body, $secret);
    }

    /**
     * `casos()` of test_firmas.py, for a given current time `t`.
     *
     * @return array<string, array{body: string, header: string, secret: string, expected: bool}>
     */
    public static function cases(int $t): array
    {
        $ok = self::sign(self::BODY, self::SECRET, $t);
        $v1 = explode('v1=', $ok)[1];
        $v1Altered = ($v1[0] !== '0' ? '0' : '1') . substr($v1, 1);
        $alteredBody = str_replace('Hola', 'Hole', self::BODY);
        $reserialized = json_encode(json_decode(self::BODY, true), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR);

        return [
            'firma correcta' => ['body' => self::BODY, 'header' => $ok, 'secret' => self::SECRET, 'expected' => true],
            'cuerpo alterado (un carácter)' => ['body' => $alteredBody, 'header' => $ok, 'secret' => self::SECRET, 'expected' => false],
            'cuerpo re-serializado (mismo JSON, otros espacios)' => ['body' => $reserialized, 'header' => $ok, 'secret' => self::SECRET, 'expected' => false],
            'firma alterada (un dígito)' => ['body' => self::BODY, 'header' => "t={$t},v1={$v1Altered}", 'secret' => self::SECRET, 'expected' => false],
            'secreto equivocado' => ['body' => self::BODY, 'header' => $ok, 'secret' => self::SECRET . 'x', 'expected' => false],
            'timestamp alterado en la cabecera' => ['body' => self::BODY, 'header' => 't=' . ($t + 1) . ",v1={$v1}", 'secret' => self::SECRET, 'expected' => false],
            'timestamp viejo (hace 10 min), bien firmado' => ['body' => self::BODY, 'header' => self::sign(self::BODY, self::SECRET, $t - 600), 'secret' => self::SECRET, 'expected' => false],
            'cabecera mal formada' => ['body' => self::BODY, 'header' => "sha256={$v1}", 'secret' => self::SECRET, 'expected' => false],
            'cabecera sin v1' => ['body' => self::BODY, 'header' => "t={$t}", 'secret' => self::SECRET, 'expected' => false],
            'cabecera vacía' => ['body' => self::BODY, 'header' => '', 'secret' => self::SECRET, 'expected' => false],
            'cuerpo UTF-8 (ñ, emoji) firmado sobre los bytes crudos' => ['body' => self::UTF8_BODY, 'header' => self::sign(self::UTF8_BODY, self::SECRET, $t), 'secret' => self::SECRET, 'expected' => true],
            'secreto sin prefijo whsec_ (se usa tal cual)' => ['body' => self::BODY, 'header' => self::sign(self::BODY, self::SECRET_WITHOUT_PREFIX, $t), 'secret' => self::SECRET_WITHOUT_PREFIX, 'expected' => true],
            'secreto con prefijo recortado por error' => ['body' => self::BODY, 'header' => $ok, 'secret' => self::SECRET_WITHOUT_PREFIX, 'expected' => false],
        ];
    }

    /**
     * Edge cases beyond the portal's thirteen, to compare the SDK with the portal snippet: the order
     * of the parts, spaces, repeated keys, key case, leading zeros, a future `t`.
     *
     * @return array<string, array{body: string, header: string, secret: string}>
     */
    public static function edgeCases(int $t): array
    {
        $v1 = explode('v1=', self::sign(self::BODY, self::SECRET, $t))[1];
        $other = str_repeat('a', 64);

        return [
            'v1 antes que t' => ['body' => self::BODY, 'header' => "v1={$v1},t={$t}", 'secret' => self::SECRET],
            'varias v1, la buena al final y con espacios' => ['body' => self::BODY, 'header' => "t={$t}, v1={$other} , v1={$v1}", 'secret' => self::SECRET],
            'solo v1 equivocadas' => ['body' => self::BODY, 'header' => "t={$t},v1={$other},v1=" . strtoupper($v1), 'secret' => self::SECRET],
            't repetida: gana la última' => ['body' => self::BODY, 'header' => 't=1,t=' . $t . ",v1={$v1}", 'secret' => self::SECRET],
            'claves en mayúsculas' => ['body' => self::BODY, 'header' => "T={$t},V1={$v1}", 'secret' => self::SECRET],
            't con ceros a la izquierda' => ['body' => self::BODY, 'header' => "t=0{$t},v1=" . explode('v1=', self::sign(self::BODY, self::SECRET, (int) "0{$t}"))[1], 'secret' => self::SECRET],
            't no numérico' => ['body' => self::BODY, 'header' => "t=abc,v1={$v1}", 'secret' => self::SECRET],
            't negativo' => ['body' => self::BODY, 'header' => "t=-{$t},v1={$v1}", 'secret' => self::SECRET],
            't del futuro (5 min y 1 s)' => ['body' => self::BODY, 'header' => self::sign(self::BODY, self::SECRET, $t + 301), 'secret' => self::SECRET],
            't del futuro (4 min)' => ['body' => self::BODY, 'header' => self::sign(self::BODY, self::SECRET, $t + 240), 'secret' => self::SECRET],
            'partes vacías y sin =' => ['body' => self::BODY, 'header' => ",,basura,t={$t},,v1={$v1},", 'secret' => self::SECRET],
            'valor con = adentro' => ['body' => self::BODY, 'header' => "t={$t},v1={$v1}=", 'secret' => self::SECRET],
            'cuerpo vacío firmado' => ['body' => '', 'header' => self::sign('', self::SECRET, $t), 'secret' => self::SECRET],
            'secreto vacío' => ['body' => self::BODY, 'header' => self::sign(self::BODY, '', $t), 'secret' => ''],
        ];
    }
}
