<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/**
 * Base class of every exception the SDK throws.
 *
 *   FunnelchatException                 base class of everything the SDK throws
 *   ├── ApiException                    the API answered with a non-2xx status
 *   │   ├── BadRequestException         400
 *   │   ├── AuthenticationException     401
 *   │   ├── PermissionDeniedException   403
 *   │   ├── NotFoundException           404
 *   │   │   └── RouteNotFoundException  404 route_not_found (the route does not exist)
 *   │   ├── ConflictException           409
 *   │   ├── UnsupportedMediaTypeException 415 (a file type the API does not send)
 *   │   ├── UnprocessableEntityException 422
 *   │   ├── RateLimitException          429
 *   │   └── InternalServerException     5xx
 *   │       ├── EdgeMisconfiguredException 500 edge_misconfigured
 *   │       └── OriginUnreachableException 502 origin_unreachable (a write may have been applied)
 *   ├── ApiConnectionException          no response (network error)
 *   │   └── ApiConnectionTimeoutException
 *   └── WebhookVerificationException    a webhook delivery failed signature verification
 */
class FunnelchatException extends \RuntimeException {}
