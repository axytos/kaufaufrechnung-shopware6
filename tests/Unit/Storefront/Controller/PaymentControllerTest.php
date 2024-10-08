<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit;

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\InvoiceOrderPaymentUpdate;
use Axytos\ECommerce\Clients\Invoice\PaymentStatus;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use Axytos\KaufAufRechnung\Shopware\Order\OrderStateMachine;
use Axytos\KaufAufRechnung\Shopware\Storefront\Controller\PaymentController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class PaymentControllerTest extends TestCase
{
    /**
     * @var PaymentController
     */
    private $paymentController;

    /** @var ErrorHandler&MockObject */
    private $errorHandler;

    /** @var InvoiceClientInterface&MockObject */
    private $invoiceClient;

    /** @var PluginConfigurationValidator&MockObject */
    private $pluginConfigurationValidator;

    /** @var PluginConfiguration&MockObject */
    private $pluginConfiguration;

    /** @var OrderStateMachine&MockObject */
    private $orderStateMachine;

    public function setUp(): void
    {
        $this->errorHandler = $this->createMock(ErrorHandler::class);
        $this->invoiceClient = $this->createMock(InvoiceClientInterface::class);
        $this->pluginConfigurationValidator = $this->createMock(PluginConfigurationValidator::class);
        $this->pluginConfiguration = $this->createMock(PluginConfiguration::class);
        $this->orderStateMachine = $this->createMock(OrderStateMachine::class);
        $this->paymentController = new PaymentController(
            $this->errorHandler,
            $this->invoiceClient,
            $this->pluginConfigurationValidator,
            $this->pluginConfiguration,
            $this->orderStateMachine
        );
    }

    /**
     * @dataProvider dataProvider_test_responses
     */
    #[DataProvider('dataProvider_test_responses')]
    public function test_responses(bool $configInvalid, string $xSecretHeader, string $clientSecret, int $expectedStatusCode): void
    {
        $orderId = 'orderId';
        $paymentStatus = 'paymentStatus';
        $paymentId = 'paymentId';
        $request = $this->createRequest($xSecretHeader);
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $context = $this->createMock(Context::class);
        $salesChannelContext->method('getContext')->willReturn($context);

        $this->setUpPluginConfigurationValidator($configInvalid);
        $this->setUpInvoiceOrderPaymentUpdate($paymentId, $orderId, $paymentStatus);
        $this->setUpClientSecret($clientSecret);

        $response = $this->paymentController->payment($paymentId, $request, $salesChannelContext);

        $this->assertEmpty($response->getContent());
        $this->assertEquals($expectedStatusCode, $response->getStatusCode());
    }

    /**
     * @return array<array<mixed>>
     */
    public static function dataProvider_test_responses(): array
    {
        return [
            [true, 'secret', 'other secret', 500],
            [true, 'secret', 'secret', 500],
            [false, 'secret', 'other secret', 401],
            [false, 'secret', 'secret', 200],
        ];
    }

    /**
     * @dataProvider dataProvider_test_order_state_updates
     */
    #[DataProvider('dataProvider_test_order_state_updates')]
    public function test_order_state_updates(
        bool $configInvalid,
        string $xSecretHeader,
        string $clientSecret,
        string $paymentStatus,
        int $expectedPayOrderCount,
        int $expectedPayOrderPartiallyCount
    ): void {
        $orderId = 'orderId';
        $paymentId = 'paymentId';
        $request = $this->createRequest($xSecretHeader);
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $context = $this->createMock(Context::class);
        $salesChannelContext->method('getContext')->willReturn($context);

        $this->setUpPluginConfigurationValidator($configInvalid);
        $this->setUpInvoiceOrderPaymentUpdate($paymentId, $orderId, $paymentStatus);
        $this->setUpClientSecret($clientSecret);

        $this->orderStateMachine
            ->expects($this->exactly($expectedPayOrderCount))
            ->method('payOrder')
            ->with($orderId, $context)
        ;

        $this->orderStateMachine
            ->expects($this->exactly($expectedPayOrderPartiallyCount))
            ->method('payOrderPartially')
            ->with($orderId, $context)
        ;

        $this->paymentController->payment($paymentId, $request, $salesChannelContext);
    }

    /**
     * @return array<array<mixed>>
     */
    public static function dataProvider_test_order_state_updates(): array
    {
        return [
            [true, 'secret', 'other secret', PaymentStatus::PAID, 0, 0],
            [true, 'secret', 'secret', PaymentStatus::PAID, 0, 0],
            [false, 'secret', 'other secret', PaymentStatus::PAID, 0, 0],
            [false, 'secret', 'secret', PaymentStatus::PAID, 1, 0],

            [true, 'secret', 'other secret', PaymentStatus::OVERPAID, 0, 0],
            [true, 'secret', 'secret', PaymentStatus::OVERPAID, 0, 0],
            [false, 'secret', 'other secret', PaymentStatus::OVERPAID, 0, 0],
            [false, 'secret', 'secret', PaymentStatus::OVERPAID, 1, 0],

            [true, 'secret', 'other secret', PaymentStatus::PARTIALLY_PAID, 0, 0],
            [true, 'secret', 'secret', PaymentStatus::PARTIALLY_PAID, 0, 0],
            [false, 'secret', 'other secret', PaymentStatus::PARTIALLY_PAID, 0, 0],
            [false, 'secret', 'secret', PaymentStatus::PARTIALLY_PAID, 0, 1],
        ];
    }

    private function setUpPluginConfigurationValidator(bool $isInvalid): void
    {
        $this->pluginConfigurationValidator
            ->method('isInvalid')
            ->willReturn($isInvalid)
        ;
    }

    private function setUpInvoiceOrderPaymentUpdate(string $paymentId, string $orderId, string $paymentStatus): void
    {
        $invoiceOrderPaymentUpdate = new InvoiceOrderPaymentUpdate();
        $invoiceOrderPaymentUpdate->orderId = $orderId;
        $invoiceOrderPaymentUpdate->paymentStatus = $paymentStatus;

        $this->invoiceClient->method('getInvoiceOrderPaymentUpdate')
            ->with($paymentId)
            ->willReturn($invoiceOrderPaymentUpdate)
        ;
    }

    private function setUpClientSecret(string $clientSecret): void
    {
        $this->pluginConfiguration
            ->method('getClientSecret')
            ->willReturn($clientSecret)
        ;
    }

    private function createRequest(string $xSecretHeader): Request
    {
        /** @var Request&MockObject */
        $request = $this->createMock(Request::class);
        /** @var HeaderBag&MockObject */
        $headers = $this->createMock(HeaderBag::class);

        $headers
            ->method('get')
            ->with('X-secret')
            ->willReturn($xSecretHeader)
        ;

        $request->headers = $headers;

        return $request;
    }
}
