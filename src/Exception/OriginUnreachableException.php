<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/**
 * `502 origin_unreachable`: the API entrypoint could not reach the area that serves the route.
 *
 * Treat it like a timeout: nothing guarantees that the area did not get the call. A read can be
 * repeated (the SDK does it, with backoff); a write (`POST`, `PATCH`) is not repeated automatically:
 * read the state before repeating it. If it persists, report it citing `requestId`.
 */
class OriginUnreachableException extends InternalServerException {}
