<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\InvoiceOrderContextInterface;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\Shopware\DataAbstractionLayer\DocumentEntityRepository;
use Axytos\Shopware\ErrorReporting\ErrorHandler;
use Axytos\Shopware\Order\OrderCheckProcessStateMachine;
use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Throwable;

class RefundOrderEventsSubscriber implements EventSubscriberInterface
{
    private ErrorHandler $errorHandler;
    private InvoiceClientInterface $invoiceClient;
    private InvoiceOrderContextFactory $invoiceOrderContextFactory;
    private OrderCheckProcessStateMachine $orderCheckProcessStateMachine;
    private DocumentEntityRepository $documentEntityRepository;
    private PluginConfigurationValidator $pluginConfigurationValidator;

    public function __construct(
        ErrorHandler $errorHandler,
        InvoiceClientInterface $invoiceClient,
        InvoiceOrderContextFactory $invoiceOrderContextFactory,
        OrderCheckProcessStateMachine $orderCheckProcessStateMachine,
        DocumentEntityRepository $documentEntityRepository,
        PluginConfigurationValidator $pluginConfigurationValidator
    ) {
        $this->errorHandler = $errorHandler;
        $this->invoiceClient = $invoiceClient;
        $this->invoiceOrderContextFactory = $invoiceOrderContextFactory;
        $this->orderCheckProcessStateMachine = $orderCheckProcessStateMachine;
        $this->documentEntityRepository = $documentEntityRepository;
        $this->pluginConfigurationValidator = $pluginConfigurationValidator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'document.written' => 'onDocumentWritten'
        ];
    }

    public function onDocumentWritten(EntityWrittenEvent $event): void
    {
        try {
            if ($this->pluginConfigurationValidator->isInvalid()) {
                return;
            }

            $document = $this->findDocument($event);

            if ($this->isNotCreditNote($document)) {
                return;
            }

            if ($this->isNotConfirmed($document, $event)) {
                return;
            }

            $orderContext = $this->getInvoiceOrderContext($document, $event);
            $this->invoiceClient->refund($orderContext);
        } catch (Throwable $t) {
            $this->errorHandler->handle($t);
        }
    }

    private function getInvoiceOrderContext(DocumentEntity $document, EntityWrittenEvent $event): InvoiceOrderContextInterface
    {
        $orderId = $document->getOrderId();
        $context = $event->getContext();
        $orderContext = $this->invoiceOrderContextFactory->getInvoiceOrderContext($orderId, $context);

        $invoiceNumber = $this->extractInvoiceNumber($document);
        $orderContext->setOrderInvoiceNumber($invoiceNumber);

        return $orderContext;
    }

    private function extractInvoiceNumber(DocumentEntity $document): string
    {
        $config = $document->getConfig();
        return $config['custom']['invoiceNumber'];
    }

    private function isNotConfirmed(DocumentEntity $document, EntityWrittenEvent $event): bool
    {
        $orderId = $document->getOrderId();
        $context = $event->getContext();
        $orderState = $this->orderCheckProcessStateMachine->getState($orderId, $context);

        return $orderState !== OrderCheckProcessStates::CONFIRMED;
    }

    private function isNotCreditNote(DocumentEntity $document): bool
    {
        $documentType = $document->getDocumentType();

        return !is_null($documentType)
            && $documentType->getTechnicalName() !== 'credit_note';
    }

    private function findDocument(EntityWrittenEvent $event): DocumentEntity
    {
        list($writeResult) = $event->getWriteResults();
        $documentId = strval($writeResult->getProperty('id'));
        $context = $event->getContext();
        return $this->documentEntityRepository->findDocument($documentId, $context);
    }
}
