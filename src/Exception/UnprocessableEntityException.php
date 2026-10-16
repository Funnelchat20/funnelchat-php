<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/** `422`: validation (`errors`) or a WhatsApp rule (`window_closed`, `antiblock_*`, `recipient_*`…). */
class UnprocessableEntityException extends ApiException {}
