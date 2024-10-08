<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\Configuration;

use Axytos\KaufAufRechnung\Shopware\Configuration\AfterCheckoutOrderStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;

/**
 * @internal
 */
class AfterCheckoutOrderStatusTest extends TestCase
{
    /**
     * @dataProvider getStateMachineTransactionActionTestCases
     */
    #[DataProvider('getStateMachineTransactionActionTestCases')]
    public function test_get_state_machine_transaction_action_returns_correct_value(string $value, string $expectedValue): void
    {
        $afterCheckoutOrderStatus = new AfterCheckoutOrderStatus($value);

        $this->assertEquals($expectedValue, $afterCheckoutOrderStatus->getStateMachineTransactionAction());
    }

    /**
     * @return array<array<mixed>>
     */
    public static function getStateMachineTransactionActionTestCases(): array
    {
        return [
            [AfterCheckoutOrderStatus::ORDER_STATE_OPEN, StateMachineTransitionActions::ACTION_REOPEN],
            [AfterCheckoutOrderStatus::ORDER_STATE_CANCELLED, StateMachineTransitionActions::ACTION_CANCEL],
            [AfterCheckoutOrderStatus::ORDER_STATE_IN_PROGRESS, StateMachineTransitionActions::ACTION_PROCESS],
        ];
    }

    public function test_get_state_machine_transaction_action_returns_actio_n_reope_n_as_default(): void
    {
        $afterCheckoutOrderStatus = new AfterCheckoutOrderStatus('');

        $this->assertEquals(StateMachineTransitionActions::ACTION_REOPEN, $afterCheckoutOrderStatus->getStateMachineTransactionAction());
    }
}
