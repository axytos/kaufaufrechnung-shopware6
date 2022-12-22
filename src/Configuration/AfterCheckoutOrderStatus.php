<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Configuration;

use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;

class AfterCheckoutOrderStatus
{
    public const ORDER_STATE_OPEN = 'ORDER_STATE_OPEN';
    public const ORDER_STATE_CANCELLED = 'ORDER_STATE_CANCELLED';
    public const ORDER_STATE_IN_PROGRESS = 'ORDER_STATE_IN_PROGRESS';

    /**
     * @var string
     */
    private static $default = StateMachineTransitionActions::ACTION_REOPEN;

    /**
     * @var array<string,string>
     */
    private static $orderStatusMapping = [
        self::ORDER_STATE_OPEN => StateMachineTransitionActions::ACTION_REOPEN,
        self::ORDER_STATE_CANCELLED => StateMachineTransitionActions::ACTION_CANCEL,
        self::ORDER_STATE_IN_PROGRESS => StateMachineTransitionActions::ACTION_PROCESS
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
        if (!isset(self::$orderStatusMapping[$this->value])) {
            return self::$default;
        }

        return self::$orderStatusMapping[$this->value];
    }
}
