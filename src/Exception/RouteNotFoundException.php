<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/** `404 route_not_found`: the route does not exist in the public API. The API entrypoint answered without reaching any area. */
class RouteNotFoundException extends NotFoundException {}
