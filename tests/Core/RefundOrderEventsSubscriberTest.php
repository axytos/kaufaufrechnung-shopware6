<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContextFactory;
use Axytos\Shopware\Order\OrderCheckProcessStateMachine;
use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Axytos\KaufAufRechnung\Shopware\Core\RefundOrderEventsSubscriber;
use Axytos\Shopware\DataAbstractionLayer\DocumentEntityRepository;
use Axytos\Shopware\ErrorReporting\ErrorHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeEntity;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;

class RefundOrderEventsSubscriberTest extends TestCase
{
    const ORDER_ID = 'orderId';
    const INVOICE_NUMBER = 'invoiceNumber';

    /** @var ErrorHandler&MockObject */
    private ErrorHandler $errorHandler;

    /** @var InvoiceClientInterface&MockObject */
    private InvoiceClientInterface $invoiceClient;

    /** @var InvoiceOrderContextFactory&MockObject */
    private InvoiceOrderContextFactory $invoiceOrderContextFactory;

    /** @var OrderCheckProcessStateMachine&MockObject */
    private OrderCheckProcessStateMachine $orderCheckProcessStateMachine;

    /** @var DocumentEntityRepository&MockObject */
    private DocumentEntityRepository $documentEntityRepository;

    /** @var PluginConfigurationValidator&MockObject */
    private PluginConfigurationValidator $pluginConfigurationValidator;

    /** @var RefundOrderEventsSubscriber */
    private RefundOrderEventsSubscriber $sut;

    /** @var EntityWrittenEvent&MockObject */
    private EntityWrittenEvent $event;

    /** @var InvoiceOrderContext&MockObject */
    private InvoiceOrderContext $invoiceOrderContext;

    /** @var EntityWriteResult&MockObject */
    private EntityWriteResult $writeResult;

    /** @var Context&MockObject */
    private Context $context;

    public function setUp(): void
    {
        $this->errorHandler = $this->createMock(ErrorHandler::class);
        $this->invoiceClient = $this->createMock(InvoiceClientInterface::class);
        $this->invoiceOrderContextFactory = $this->createMock(InvoiceOrderContextFactory::class);
        $this->orderCheckProcessStateMachine = $this->createMock(OrderCheckProcessStateMachine::class);
        $this->documentEntityRepository = $this->createMock(DocumentEntityRepository::class);
        $this->pluginConfigurationValidator = $this->createMock(PluginConfigurationValidator::class);

        $this->sut = new RefundOrderEventsSubscriber(
            $this->errorHandler,
            $this->invoiceClient,
            $this->invoiceOrderContextFactory,
            $this->orderCheckProcessStateMachine,
            $this->documentEntityRepository,
            $this->pluginConfigurationValidator
        );

        $this->event = $this->createMock(EntityWrittenEvent::class);
        $this->writeResult = $this->createMock(EntityWriteResult::class);
        $this->invoiceOrderContext = $this->createMock(InvoiceOrderContext::class);
        $this->context = $this->createMock(Context::class);
        $this->setUpInvoiceOrderContext();
    }

    private function setUpInvoiceOrderContext(): void
    {
        $this->event
            ->method('getWriteResults')
            ->willReturn([$this->writeResult]);

        $this->event
            ->method('getContext')
            ->willReturn($this->context);

        $this->invoiceOrderContextFactory
            ->method('getInvoiceOrderContext')
            ->with(self::ORDER_ID, $this->context)
            ->willReturn($this->invoiceOrderContext);
    }

    private function setUpOrderState(string $orderCheckProcessState): void
    {
        $this->orderCheckProcessStateMachine
            ->method('getState')
            ->with(self::ORDER_ID, $this->context)
            ->willReturn($orderCheckProcessState);
    }

    private function setupWriteResultPayload(string $name): void
    {
        $documentConfig = [
            'custom' => [
                'invoiceNumber' => self::INVOICE_NUMBER
            ]
        ];

        $documentId = 'documentId';

        /** @var DocumentTypeEntity&MockObject */
        $documentType = $this->createMock(DocumentTypeEntity::class);
        $documentType->method('getTechnicalName')->willReturn($name);

        /** @var DocumentEntity&MockObject */
        $document = $this->createMock(DocumentEntity::class);
        $document->method('getId')->willReturn($documentId);
        $document->method('getOrderId')->willReturn(self::ORDER_ID);
        $document->method('getConfig')->willReturn($documentConfig);
        $document->method('getDocumentType')->willReturn($documentType);

        $this->writeResult
            ->method('getProperty')
            ->with('id')
            ->willReturn($documentId);

        $this->documentEntityRepository
            ->method('findDocument')
            ->with($documentId)
            ->willReturn($document);
    }

    public function test_onDocumentWritten_does_not_call_invoiceClient_when_event_document_name_not_credit_note(): void
    {
        $this->setupWriteResultPayload('invoice');
        $this->setUpOrderState(OrderCheckProcessStates::CONFIRMED);

        $this->invoiceClient->expects($this->never())->method('refund');
        $this->errorHandler->expects($this->never())->method('handle');

        $this->sut->onDocumentWritten($this->event);
    }

    /**
     * @dataProvider dataProvider_test_onOrderStateNotConfirmend
     */
    public function test_onDocumentWritten_does_not_call_invoiceClient_when_event_document_name_credit_note_and_order_not_confirmed(string $orderState): void
    {
        $this->setupWriteResultPayload('credit_note');
        $this->setUpOrderState($orderState);

        $this->invoiceClient->expects($this->never())->method('refund');
        $this->errorHandler->expects($this->never())->method('handle');

        $this->sut->onDocumentWritten($this->event);
    }

    public function dataProvider_test_onOrderStateNotConfirmend(): array
    {
        return [
            [OrderCheckProcessStates::UNCHECKED],
            [OrderCheckProcessStates::CHECKED],
            [OrderCheckProcessStates::FAILED],
        ];
    }

    public function test_onDocumentWritten_does_calls_invoiceClient_when_event_document_name_credit_note_and_order_confirmed(): void
    {
        $this->setupWriteResultPayload('credit_note');
        $this->setUpOrderState(OrderCheckProcessStates::CONFIRMED);

        $this->invoiceClient->expects($this->once())->method('refund')->with($this->invoiceOrderContext);
        $this->errorHandler->expects($this->never())->method('handle');

        $this->sut->onDocumentWritten($this->event);
    }

    public function test_onDocumentWritten_does_not_call_invoiceClient_when_plugin_configuration_is_invalid(): void
    {
        $this->setupWriteResultPayload('credit_note');
        $this->setUpOrderState(OrderCheckProcessStates::CONFIRMED);

        $this->pluginConfigurationValidator->method('isInvalid')->willReturn(true);

        $this->invoiceClient->expects($this->never())->method('refund');

        $this->sut->onDocumentWritten($this->event);
    }
}
