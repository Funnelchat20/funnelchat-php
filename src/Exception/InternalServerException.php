<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/** `5xx`: a server error. Retried automatically, except sends and writes (`POST`, `PATCH`: only a `503`, since the others may have been applied). */
class InternalServerException extends ApiException {}
