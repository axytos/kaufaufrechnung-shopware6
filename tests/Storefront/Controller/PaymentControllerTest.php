<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests;

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\InvoiceOrderPaymentUpdate;
use Axytos\ECommerce\Clients\Invoice\PaymentStatus;
use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Shopware\Storefront\Controller\PaymentController;
use Axytos\KaufAufRechnung\Shopware\Order\OrderStateMachine;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use Error;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;

class PaymentControllerTest extends TestCase
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Storefront\Controller\PaymentController
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
    public function test_responses(bool $configInvalid, string $xSecretHeader, string $clientSecret, int $expectedStatusCode): void
    {
        $orderId = 'orderId';
        $paymentStatus = 'paymentStatus';
        $paymentId = 'paymentId';
        $request = $this->createRequest($xSecretHeader);
        $context = $this->createMock(SalesChannelContext::class);

        $this->setUpPluginConfigurationValidator($configInvalid);
        $this->setUpInvoiceOrderPaymentUpdate($paymentId, $orderId, $paymentStatus);
        $this->setUpClientSecret($clientSecret);

        $response = $this->paymentController->payment($paymentId, $request, $context);

        $this->assertEmpty($response->getContent());
        $this->assertEquals($expectedStatusCode, $response->getStatusCode());
    }

    public function dataProvider_test_responses(): array
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
    public function test_order_state_updates(
        bool $configInvalid,
        string $xSecretHeader,
        string $clientSecret,
        string $paymentStatus,
        InvokedCount $expectedPayOrderCount,
        InvokedCount $expectedPayOrderPartially
    ): void {
        $orderId = 'orderId';
        $paymentId = 'paymentId';
        $request = $this->createRequest($xSecretHeader);
        $context = $this->createMock(SalesChannelContext::class);

        $this->setUpPluginConfigurationValidator($configInvalid);
        $this->setUpInvoiceOrderPaymentUpdate($paymentId, $orderId, $paymentStatus);
        $this->setUpClientSecret($clientSecret);

        $this->orderStateMachine
            ->expects($expectedPayOrderCount)
            ->method('payOrder')
            ->with($orderId, $context);

        $this->orderStateMachine
            ->expects($expectedPayOrderPartially)
            ->method('payOrderPartially')
            ->with($orderId, $context);

        $this->paymentController->payment($paymentId, $request, $context);
    }

    public function dataProvider_test_order_state_updates(): array
    {
        return [
            [true, 'secret', 'other secret', PaymentStatus::PAID, $this->never(), $this->never()],
            [true, 'secret', 'secret', PaymentStatus::PAID, $this->never(), $this->never()],
            [false, 'secret', 'other secret', PaymentStatus::PAID, $this->never(), $this->never()],
            [false, 'secret', 'secret', PaymentStatus::PAID, $this->once(), $this->never()],

            [true, 'secret', 'other secret', PaymentStatus::OVERPAID, $this->never(), $this->never()],
            [true, 'secret', 'secret', PaymentStatus::OVERPAID, $this->never(), $this->never()],
            [false, 'secret', 'other secret', PaymentStatus::OVERPAID, $this->never(), $this->never()],
            [false, 'secret', 'secret', PaymentStatus::OVERPAID, $this->once(), $this->never()],

            [true, 'secret', 'other secret', PaymentStatus::PARTIALLY_PAID, $this->never(), $this->never()],
            [true, 'secret', 'secret', PaymentStatus::PARTIALLY_PAID, $this->never(), $this->never()],
            [false, 'secret', 'other secret', PaymentStatus::PARTIALLY_PAID, $this->never(), $this->never()],
            [false, 'secret', 'secret', PaymentStatus::PARTIALLY_PAID, $this->never(), $this->once()],
        ];
    }

    private function setUpPluginConfigurationValidator(bool $isInvalid): void
    {
        $this->pluginConfigurationValidator
            ->method('isInvalid')
            ->willReturn($isInvalid);
    }

    private function setUpInvoiceOrderPaymentUpdate(string $paymentId, string $orderId, string $paymentStatus): void
    {
        $invoiceOrderPaymentUpdate = new InvoiceOrderPaymentUpdate();
        $invoiceOrderPaymentUpdate->orderId = $orderId;
        $invoiceOrderPaymentUpdate->paymentStatus = $paymentStatus;

        $this->invoiceClient->method('getInvoiceOrderPaymentUpdate')
            ->with($paymentId)
            ->willReturn($invoiceOrderPaymentUpdate);
    }

    private function setUpClientSecret(string $clientSecret): void
    {
        $this->pluginConfiguration
            ->method('getClientSecret')
            ->willReturn($clientSecret);
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
            ->willReturn($xSecretHeader);

        $request->headers = $headers;

        return $request;
    }
}
