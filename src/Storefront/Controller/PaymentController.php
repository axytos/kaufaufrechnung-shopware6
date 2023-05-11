<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Storefront\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\PaymentStatus;
use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use Axytos\KaufAufRechnung\Shopware\Order\OrderStateMachine;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * @RouteScope(scopes={"storefront"})
 */
class PaymentController extends StorefrontController
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler
     */
    private $errorHandler;
    /**
     * @var \Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface
     */
    private $invoiceClient;
    /**
     * @var \Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator
     */
    private $pluginConfigurationValidator;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration
     */
    private $pluginConfiguration;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\Order\OrderStateMachine
     */
    private $orderStateMachine;

    public function __construct(
        ErrorHandler $errorHandler,
        InvoiceClientInterface $invoiceClient,
        PluginConfigurationValidator $pluginConfigurationValidator,
        PluginConfiguration $pluginConfiguration,
        OrderStateMachine $orderStateMachine
    ) {
        $this->errorHandler = $errorHandler;
        $this->invoiceClient = $invoiceClient;
        $this->pluginConfigurationValidator = $pluginConfigurationValidator;
        $this->pluginConfiguration = $pluginConfiguration;
        $this->orderStateMachine = $orderStateMachine;
    }

    /**
     * @Route("/AxytosKaufAufRechnung/Payment/{paymentId}", name="axytos.kaufaufrechnung.payment", options={"seo"="false"}, defaults={"csrf_protected"=false}, methods={"POST"})
     */
    public function payment(string $paymentId, Request $request, SalesChannelContext $context): Response
    {
        try {
            if ($this->pluginConfigurationValidator->isInvalid()) {
                return new Response('', 500);
            }

            if ($this->isClientSecretInvalid($request)) {
                return new Response('', 401);
            }

            $this->updatePaymentStatus($paymentId, $context);

            return new Response();
        } catch (\Throwable $th) {
            $this->errorHandler->handle($th);
            return new Response('', 500);
        }
    }

    private function updatePaymentStatus(string $paymentId, SalesChannelContext $context): void
    {
        $invoiceOrderPaymentUpdate = $this->invoiceClient->getInvoiceOrderPaymentUpdate($paymentId);
        $orderId = $invoiceOrderPaymentUpdate->orderId;
        $paymentStatus = $invoiceOrderPaymentUpdate->paymentStatus;

        if ($paymentStatus === PaymentStatus::PAID || $paymentStatus === PaymentStatus::OVERPAID) {
            $this->orderStateMachine->payOrder($orderId, $context);
        }

        if ($paymentStatus === PaymentStatus::PARTIALLY_PAID) {
            $this->orderStateMachine->payOrderPartially($orderId, $context);
        }
    }

    private function isClientSecretInvalid(Request $request): bool
    {
        $configClientSecret = $this->pluginConfiguration->getClientSecret();

        $headerClientSecret = $request->headers->get("X-secret");

        return is_null($configClientSecret) || $configClientSecret !== $headerClientSecret;
    }
}
