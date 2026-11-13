<?php

declare(strict_types=1);

namespace Funnelchat\Exception;

/**
 * `402`: what the account pays for does not allow the call. Repeating it does not help.
 * - `payment_required`: the account's payment is overdue. Settle it in the Funnelchat panel.
 * - `plan_cap_reached`: the plan's cap of official groups is reached (creating one). Nothing was registered.
 */
class PaymentRequiredException extends ApiException {}
