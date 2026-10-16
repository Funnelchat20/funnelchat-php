<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/** `403`: the key lacks the permission, or the route is not open to keys (`route_not_available`). */
class PermissionDeniedException extends ApiException {}
