<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Configuration;

use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;

class AfterCheckoutPaymentStatus
{
    public const PAYMENT_STATE_OPEN = 'PAYMENT_STATE_OPEN';
    public const PAYMENT_STATE_PAID = 'PAYMENT_STATE_PAID';
    public const PAYMENT_STATE_PAID_PARTIALLY = 'PAYMENT_STATE_PAID_PARTIALLY';
    public const PAYMENT_STATE_REMINDED = 'PAYMENT_STATE_REMINDED';
    public const PAYMENT_STATE_CANCELLED = 'PAYMENT_STATE_CANCELLED';
    public const PAYMENT_STATE_AUTHORIZED = 'PAYMENT_STATE_AUTHORIZED';

    /**
     * @var string
     */
    private static $default = StateMachineTransitionActions::ACTION_REOPEN;

    /**
     * @var array<string,string>
     */
    private static $paymentStatusMapping = [
        self::PAYMENT_STATE_OPEN => StateMachineTransitionActions::ACTION_REOPEN,
        self::PAYMENT_STATE_PAID => StateMachineTransitionActions::ACTION_PAID,
        self::PAYMENT_STATE_PAID_PARTIALLY => StateMachineTransitionActions::ACTION_PAID_PARTIALLY,
        self::PAYMENT_STATE_REMINDED => StateMachineTransitionActions::ACTION_REMIND,
        self::PAYMENT_STATE_CANCELLED => StateMachineTransitionActions::ACTION_CANCEL,
        self::PAYMENT_STATE_AUTHORIZED => StateMachineTransitionActions::ACTION_AUTHORIZE,
    ];

    /**
     * @var string
     */
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getStateMachineTransactionAction(): string
    {
        if (!isset(self::$paymentStatusMapping[$this->value])) {
            return self::$default;
        }
        return self::$paymentStatusMapping[$this->value];
    }
}
