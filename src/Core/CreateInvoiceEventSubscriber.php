<?php declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\Shopware\DataAbstractionLayer\DocumentEntityRepository;
use Axytos\Shopware\ErrorReporting\ErrorHandler;
use Axytos\Shopware\Order\OrderCheckProcessStateMachine;
use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

class CreateInvoiceEventSubscriber implements EventSubscriberInterface
{
    private InvoiceClientInterface $invoiceClient;
    private ErrorHandler $errorHandler;
    private PluginConfigurationValidator $pluginConfigurationValidator;
    private OrderCheckProcessStateMachine $orderCheckProcessStateMachine;
    private DocumentEntityRepository $documentEntityRepository;
    private InvoiceOrderContextFactory $invoiceOrderContextFactory;
    
    public function __construct(
        InvoiceClientInterface $invoiceClient,
        ErrorHandler $errorHandler,
        PluginConfigurationValidator $pluginConfigurationValidator,
        OrderCheckProcessStateMachine $orderCheckProcessStateMachine,
        DocumentEntityRepository $documentEntityRepository,
        InvoiceOrderContextFactory $invoiceOrderContextFactory)
    {
        $this->invoiceClient = $invoiceClient;
        $this->errorHandler = $errorHandler;
        $this->pluginConfigurationValidator = $pluginConfigurationValidator;
        $this->orderCheckProcessStateMachine = $orderCheckProcessStateMachine;
        $this->documentEntityRepository = $documentEntityRepository;
        $this->invoiceOrderContextFactory = $invoiceOrderContextFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'document.written' => 'onEntityWritten'
        ];
    }

    public function onEntityWritten(EntityWrittenEvent $event): void
    {
        try 
        {
            if ($this->pluginConfigurationValidator->isInvalid())
            {
                return;
            }
    
            $writeResults = $event->getWriteResults();
            $context = $event->getContext();
    
            $documentId = $writeResults[0]->getProperty('id');
            $document = $this->documentEntityRepository->findDocument(strval($documentId), $context);
            
            $documentType = $document->getDocumentType();
            if ($documentType === null)
            {
                return;
            }

            $technicalName = $documentType->getTechnicalName();
            if ($technicalName !== 'invoice')
            {
                return;
            }

            $order = $document->getOrder();
            if ($order === null)
            {
                return;
            }

            $orderId = $order->getId();

            $paymentControlOrderState = $this->orderCheckProcessStateMachine->getState($orderId, $context);
            if ($paymentControlOrderState !== OrderCheckProcessStates::CONFIRMED)
            {
                return;
            }

            $invoiceOrderContext = $this->invoiceOrderContextFactory->getInvoiceOrderContext($orderId, $context);

            $documentNumber = $document->getConfig()['documentNumber'];

            $invoiceOrderContext->setOrderInvoiceNumber($documentNumber);

            $this->invoiceClient->createInvoice($invoiceOrderContext);
        }
        catch (Throwable $t)
        {
            $this->errorHandler->handle($t);
        }
    }
}
