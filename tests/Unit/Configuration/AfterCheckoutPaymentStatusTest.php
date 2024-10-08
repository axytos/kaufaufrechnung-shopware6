<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\Configuration;

use Axytos\KaufAufRechnung\Shopware\Configuration\AfterCheckoutPaymentStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;

/**
 * @internal
 */
class AfterCheckoutPaymentStatusTest extends TestCase
{
    /**
     * @dataProvider getStateMachineTransactionActionCases
     */
    #[DataProvider('getStateMachineTransactionActionCases')]
    public function test_get_state_machine_transaction_action_cases_returns_correct_value(string $value, string $expectedStatusCode): void
    {
        $afterCheckoutOrderStatus = new AfterCheckoutPaymentStatus($value);

        $this->assertEquals($expectedStatusCode, $afterCheckoutOrderStatus->getStateMachineTransactionAction());
    }

    /**
     * @return array<array<mixed>>
     */
    public static function getStateMachineTransactionActionCases(): array
    {
        return [
            [AfterCheckoutPaymentStatus::PAYMENT_STATE_OPEN, StateMachineTransitionActions::ACTION_REOPEN],
            [AfterCheckoutPaymentStatus::PAYMENT_STATE_PAID, StateMachineTransitionActions::ACTION_PAID],
            [AfterCheckoutPaymentStatus::PAYMENT_STATE_PAID_PARTIALLY, StateMachineTransitionActions::ACTION_PAID_PARTIALLY],
            [AfterCheckoutPaymentStatus::PAYMENT_STATE_REMINDED, StateMachineTransitionActions::ACTION_REMIND],
            [AfterCheckoutPaymentStatus::PAYMENT_STATE_CANCELLED, StateMachineTransitionActions::ACTION_CANCEL],
            [AfterCheckoutPaymentStatus::PAYMENT_STATE_AUTHORIZED, StateMachineTransitionActions::ACTION_AUTHORIZE],
        ];
    }

    public function test_get_state_machine_transaction_action_returns_actio_n_reope_n_as_default(): void
    {
        $afterCheckoutOrderStatus = new AfterCheckoutPaymentStatus('');

        $this->assertEquals(StateMachineTransitionActions::ACTION_REOPEN, $afterCheckoutOrderStatus->getStateMachineTransactionAction());
    }
}
