<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/** `402 payment_required`: the account's payment is overdue. Settle it in the Funnelchat panel; repeating the call does not help. */
class PaymentRequiredException extends ApiException {}
