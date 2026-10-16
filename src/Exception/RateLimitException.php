<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/** `429`: rate limit. `retryAfter` says how many seconds to wait. */
class RateLimitException extends ApiException {}
