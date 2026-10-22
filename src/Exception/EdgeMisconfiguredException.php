<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/**
 * `500 edge_misconfigured`: the API entrypoint is not set up to reach the area of the route, and did
 * not forward the call. A problem on Funnelchat's side: report it citing `requestId`.
 */
class EdgeMisconfiguredException extends InternalServerException {}
