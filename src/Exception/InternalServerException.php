<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/** `5xx`: a server error. Retried automatically, except sends, and a `502` of a `POST` or `PATCH` (it may have been applied). */
class InternalServerException extends ApiException {}
