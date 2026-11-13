<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/** `404`, `not_found` with a key: the record does not exist, or it is of another account or another workspace. */
class NotFoundException extends ApiException {}
