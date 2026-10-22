<?php

declare(strict_types=1);

namespace Funnelchat\Tests;

use Funnelchat\Exception\ApiException;
use Funnelchat\Exception\AuthenticationException;
use Funnelchat\Exception\BadRequestException;
use Funnelchat\Exception\ConflictException;
use Funnelchat\Exception\EdgeMisconfiguredException;
use Funnelchat\Exception\InternalServerException;
use Funnelchat\Exception\NotFoundException;
use Funnelchat\Exception\OriginUnreachableException;
use Funnelchat\Exception\PermissionDeniedException;
use Funnelchat\Exception\RateLimitException;
use Funnelchat\Exception\RouteNotFoundException;
use Funnelchat\Exception\SendErrors;
use Funnelchat\Exception\UnprocessableEntityException;
use Funnelchat\Exception\UnsupportedMediaTypeException;
use Funnelchat\Tests\Support\MockHttpClient;
use Funnelchat\Tests\Support\Reply;
use Funnelchat\Tests\Support\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

final class ErrorsTest extends TestCase
{
    private function errorFor(ResponseInterface $reply): ApiException
    {
        try {
            $this->client(new MockHttpClient($reply))->request('GET', '/api/v1/tags');
        } catch (ApiException $exception) {
            return $exception;
        }
        self::fail('should have thrown an ApiException');
    }

    /**
     * @return array<string, array{int, class-string<ApiException>}>
     */
    public static function statuses(): array
    {
        return [
            'HTTP 400' => [400, BadRequestException::class],
            'HTTP 401' => [401, AuthenticationException::class],
            'HTTP 403' => [403, PermissionDeniedException::class],
            'HTTP 404' => [404, NotFoundException::class],
            'HTTP 409' => [409, ConflictException::class],
            'HTTP 415' => [415, UnsupportedMediaTypeException::class],
            'HTTP 422' => [422, UnprocessableEntityException::class],
            'HTTP 429' => [429, RateLimitException::class],
            'HTTP 500' => [500, InternalServerException::class],
            'HTTP 502' => [502, InternalServerException::class],
            'HTTP 418 (no class of its own)' => [418, ApiException::class],
        ];
    }

    /**
     * @param class-string<ApiException> $class
     */
    #[DataProvider('statuses')]
    public function testEachStatusHasItsClass(int $status, string $class): void
    {
        $exception = $this->errorFor(Reply::json($status, ['error' => 'some_code', 'message' => 'Algo pasó.']));

        self::assertSame($class, $exception::class);
        self::assertSame($status, $exception->status);
        self::assertSame($status, $exception->getCode());
    }

    public function testTakesTheCodeFromErrorAndTheMessageFromMessage(): void
    {
        $exception = $this->errorFor(Reply::json(422, ['error' => 'window_closed', 'message' => 'No se envió: pasaron más de 24 horas desde el último mensaje del contacto.']));

        self::assertSame('window_closed', $exception->errorCode);
        self::assertSame('No se envió: pasaron más de 24 horas desde el último mensaje del contacto.', $exception->getMessage());
        self::assertNull($exception->retryAfter);
        self::assertTrue($exception->isSendRuleError());
        self::assertTrue(SendErrors::isSendRuleError($exception->errorCode));
    }

    public function testExposesRetryAfterOfA429(): void
    {
        $exception = $this->errorFor(Reply::json(429, ['error' => 'rate_limited', 'message' => 'Espera.'], ['Retry-After' => '12']));

        self::assertInstanceOf(RateLimitException::class, $exception);
        self::assertSame([429, 'rate_limited', 'Espera.', 12], [$exception->status, $exception->errorCode, $exception->getMessage(), $exception->retryAfter]);
    }

    public function testKeepsTheFieldErrorsOfALaravel422WithoutCode(): void
    {
        $exception = $this->errorFor(Reply::json(422, [
            'message' => 'The phone field is required when whatsapp id is not present.',
            'errors' => ['phone' => ['The phone field is required when whatsapp id is not present.']],
        ]));

        self::assertNull($exception->errorCode);
        self::assertSame(['phone' => ['The phone field is required when whatsapp id is not present.']], $exception->errors);
        self::assertFalse($exception->isSendRuleError());
    }

    public function testHandlesTheGateRejectionOfARouteClosedToKeys(): void
    {
        $exception = $this->errorFor(Reply::json(403, ['error' => 'route_not_available', 'message' => 'Esta ruta no está disponible para claves de API.']));

        self::assertInstanceOf(PermissionDeniedException::class, $exception);
        self::assertSame('route_not_available', $exception->errorCode);
    }

    /**
     * @return array<string, array{int, string, class-string<ApiException>, class-string<ApiException>}>
     */
    public static function edgeErrors(): array
    {
        return [
            '404 route_not_found' => [404, 'route_not_found', RouteNotFoundException::class, NotFoundException::class],
            '500 edge_misconfigured' => [500, 'edge_misconfigured', EdgeMisconfiguredException::class, InternalServerException::class],
            '502 origin_unreachable' => [502, 'origin_unreachable', OriginUnreachableException::class, InternalServerException::class],
        ];
    }

    /**
     * The API entrypoint's own errors (api-edge v0.3.0): `{error, message}` and `X-Request-Id`.
     *
     * @param class-string<ApiException> $class
     * @param class-string<ApiException> $parent
     */
    #[DataProvider('edgeErrors')]
    public function testTheEntrypointErrorsHaveTheirClass(int $status, string $code, string $class, string $parent): void
    {
        $id = '3f2b8c1e-9a4d-4e7b-8c21-5d6f0a9b1c3e';
        $exception = $this->errorFor(Reply::json($status, ['error' => $code, 'message' => 'La API no está disponible en este momento.'], ['X-Request-Id' => $id]));

        self::assertSame($class, $exception::class);
        self::assertInstanceOf($parent, $exception);
        self::assertSame($code, $exception->errorCode);
        self::assertSame($id, $exception->requestId);
    }

    public function testA502FromTheOriginWithAnotherBodyIsAPlainServerError(): void
    {
        $exception = $this->errorFor(Reply::json(502, ['message' => 'Internal server error']));

        self::assertSame(InternalServerException::class, $exception::class);
    }

    public function testA415HasNoCodeAndListsTheSupportedTypes(): void
    {
        $exception = $this->errorFor(Reply::json(415, ['error' => 'Unsupported file type: heic', 'supported_file_types' => ['jpg', 'pdf']]));

        self::assertInstanceOf(UnsupportedMediaTypeException::class, $exception);
        self::assertNull($exception->errorCode);
        self::assertSame('Unsupported file type: heic', $exception->getMessage());
        self::assertSame(['jpg', 'pdf'], $exception->supportedFileTypes);

        $plain = $this->errorFor(Reply::json(415, ['status' => 'error', 'error' => 'Unsupported file type: heic']));
        self::assertInstanceOf(UnsupportedMediaTypeException::class, $plain);
        self::assertSame([], $plain->supportedFileTypes);
    }

    /**
     * @return array<string, array{array<string, mixed>, string|null, string}>
     */
    public static function bodiesWithoutAPlainCode(): array
    {
        return [
            'a sentence in error' => [['status' => 'error', 'error' => 'Contact not found.'], null, 'Contact not found.'],
            'one word in error' => [['status' => 'error', 'error' => 'Unauthorized'], null, 'Unauthorized'],
            'error_code next to a sentence' => [['error' => 'La plantilla no pertenece al número actual', 'error_code' => 'template_waba_mismatch'], 'template_waba_mismatch', 'La plantilla no pertenece al número actual'],
            'reason without error' => [['reason' => 'provider_unsupported'], 'provider_unsupported', 'provider_unsupported'],
            'error and reason' => [['error' => 'not_forwardable', 'reason' => 'no_provider_uid'], 'not_forwardable', 'not_forwardable'],
            'an upper-case code of forms' => [['error' => 'NUMBER_NOT_ELIGIBLE', 'message' => 'El número no puede usar formularios.'], 'NUMBER_NOT_ELIGIBLE', 'El número no puede usar formularios.'],
        ];
    }

    /**
     * @param array<string, mixed> $body
     */
    #[DataProvider('bodiesWithoutAPlainCode')]
    public function testReadsTheCodeAndTheMessageOfEveryShape(array $body, ?string $code, string $message): void
    {
        $exception = $this->errorFor(Reply::json(422, $body));

        self::assertSame($code, $exception->errorCode);
        self::assertSame($message, $exception->getMessage());
    }

    public function testWorksWithABodyThatIsNotJson(): void
    {
        $exception = $this->errorFor(Reply::text(502, '<html>Bad Gateway</html>', ['Content-Type' => 'text/html']));

        self::assertSame(502, $exception->status);
        self::assertNull($exception->errorCode);
        self::assertSame('<html>Bad Gateway</html>', $exception->body);
        self::assertSame('<html>Bad Gateway</html>', $exception->getMessage());
    }

    public function testHasAFallbackMessageForAnEmptyBody(): void
    {
        $exception = $this->errorFor(Reply::text(500, ''));

        self::assertSame('La API respondió 500.', $exception->getMessage());
        self::assertNull($exception->body);
    }

    public function testRecordsMethodAndUrlWithoutTheQueryAndNeverTheKey(): void
    {
        try {
            $this->client(new MockHttpClient(Reply::json(404, ['message' => 'No encontrado.'])))->contacts->list(['phone' => '5491100000000'])->firstPage();
            self::fail('should have thrown');
        } catch (NotFoundException $exception) {
            self::assertSame('GET', $exception->method);
            self::assertSame('https://api.funnelchat.com/api/v1/contacts', $exception->url);
            self::assertStringNotContainsString('fc_live', print_r($exception, true));
            self::assertStringNotContainsString('fc_live', (string) $exception);
            self::assertStringNotContainsString('fc_live', var_export($exception->headers, true));
        }
    }

    /**
     * @return array<string, array{string|null, int|null}>
     */
    public static function retryAfterValues(): array
    {
        return [
            'five seconds' => ['5', 5],
            'spaces' => [' 30 ', 30],
            'zero' => ['0', 0],
            'decimals round up' => ['1.5', 2],
            'HTTP date in 10 s' => ['Fri, 16 Oct 2026 12:00:10 GMT', 10],
            'HTTP date in the past' => ['Fri, 16 Oct 2026 11:59:00 GMT', 0],
            'empty' => ['', null],
            'a word' => ['soon', null],
            'now is not a date' => ['now', null],
            'absent' => [null, null],
        ];
    }

    #[DataProvider('retryAfterValues')]
    public function testParseRetryAfter(?string $value, ?int $expected): void
    {
        $now = (int) strtotime('2026-10-16T12:00:00Z');
        self::assertSame($expected, ApiException::parseRetryAfter($value, $now));
    }

    /**
     * @return array<string, array{string|null, bool}>
     */
    public static function sendRuleCodes(): array
    {
        return [
            'rate_limited' => ['rate_limited', true],
            'window_closed' => ['window_closed', true],
            'antiblock_duplicate_content' => ['antiblock_duplicate_content', true],
            'antiblock_unanswered_limit' => ['antiblock_unanswered_limit', true],
            'recipient_mismatch' => ['recipient_mismatch', true],
            'recipient_unsupported' => ['recipient_unsupported', true],
            'mac_lockout_24h' => ['mac_lockout_24h', true],
            'device_unavailable' => ['device_unavailable', true],
            'provider_unsupported' => ['provider_unsupported', true],
            'form_not_published' => ['form_not_published', true],
            'not_forwardable' => ['not_forwardable', true],
            'route_not_available' => ['route_not_available', false],
            'no code' => [null, false],
        ];
    }

    #[DataProvider('sendRuleCodes')]
    public function testTheSendRuleCodes(?string $code, bool $expected): void
    {
        self::assertSame($expected, SendErrors::isSendRuleError($code));
        // Read through reflection: the constant is what the PHP SDK exposes, like SEND_ERROR_CODES in Node.
        $codes = (new \ReflectionClassConstant(SendErrors::class, 'CODES'))->getValue();
        self::assertIsArray($codes);
        self::assertSame($expected, $code !== null && \array_key_exists($code, $codes));
    }
}
