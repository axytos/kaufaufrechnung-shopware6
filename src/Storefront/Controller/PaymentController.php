<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Storefront\Controller;

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\PaymentStatus;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Shopware\Configuration\PluginConfiguration;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use Axytos\KaufAufRechnung\Shopware\Order\OrderStateMachine;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
class PaymentController extends StorefrontController
{
    /**
     * @var ErrorHandler
     */
    private $errorHandler;
    /**
     * @var InvoiceClientInterface
     */
    private $invoiceClient;
    /**
     * @var PluginConfigurationValidator
     */
    private $pluginConfigurationValidator;
    /**
     * @var PluginConfiguration
     */
    private $pluginConfiguration;
    /**
     * @var OrderStateMachine
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
    #[Route(path: '/AxytosKaufAufRechnung/Payment/{paymentId}', name: 'axytos.kaufaufrechnung.payment', options: ['seo' => false], methods: ['POST'])]
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

        if (PaymentStatus::PAID === $paymentStatus || PaymentStatus::OVERPAID === $paymentStatus) {
            $this->orderStateMachine->payOrder($orderId, $context->getContext());
        }

        if (PaymentStatus::PARTIALLY_PAID === $paymentStatus) {
            $this->orderStateMachine->payOrderPartially($orderId, $context->getContext());
        }
    }

    private function isClientSecretInvalid(Request $request): bool
    {
        $configClientSecret = $this->pluginConfiguration->getClientSecret();

        $headerClientSecret = $request->headers->get('X-secret');

        return is_null($configClientSecret) || $configClientSecret !== $headerClientSecret;
    }
}
