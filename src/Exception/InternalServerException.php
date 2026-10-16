<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/** `5xx`: a server error. Retried automatically (except sends). */
class InternalServerException extends ApiException {}
