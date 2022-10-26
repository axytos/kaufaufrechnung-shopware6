<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Shopware\Core\CreateInvoiceEventSubscriber;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContext;
use Axytos\KaufAufRechnung\Shopware\Core\InvoiceOrderContextFactory;
use Axytos\Shopware\Order\OrderCheckProcessStateMachine;
use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Axytos\Shopware\DataAbstractionLayer\DocumentEntityRepository;
use Axytos\Shopware\ErrorReporting\ErrorHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvocationOrder;
use Shopware\Core\Checkout\Document\Aggregate\DocumentType\DocumentTypeEntity;
use Shopware\Core\Checkout\Document\DocumentEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;

class InvoiceEntityWrittenSubscriberTest extends TestCase
{
    const ORDER_ID = 'orderId';
    const DOCUMENT_ID = 'documentId';

    /** @var InvoiceClientInterface&MockObject*/
    private InvoiceClientInterface $invoiceClient;

    /** @var ErrorHandler&MockObject*/
    private ErrorHandler $errorHandler;

    /** @var PluginConfigurationValidator&MockObject*/
    private PluginConfigurationValidator $pluginConfigurationValidator;

    /** @var OrderCheckProcessStateMachine&MockObject*/
    private OrderCheckProcessStateMachine $orderCheckProcessStateMachine;

    /** @var DocumentEntityRepository&MockObject*/
    private DocumentEntityRepository $documentEntityRepository;

    /** @var InvoiceOrderContextFactory&MockObject*/
    private InvoiceOrderContextFactory $invoiceOrderContextFactory;

    private CreateInvoiceEventSubscriber $sut;

    /** @var EntityWrittenEvent&MockObject*/
    private EntityWrittenEvent $event;

    /** @var Context&MockObject*/
    private Context $context;

    /** @var InvoiceOrderContext&MockObject*/
    private InvoiceOrderContext $invoiceOrderContext;

    public function setUp(): void
    {
        $this->invoiceClient = $this->createMock(InvoiceClientInterface::class);
        $this->errorHandler = $this->createMock(ErrorHandler::class);
        $this->pluginConfigurationValidator = $this->createMock(PluginConfigurationValidator::class);
        $this->orderCheckProcessStateMachine = $this->createMock(OrderCheckProcessStateMachine::class);
        $this->documentEntityRepository = $this->createMock(DocumentEntityRepository::class);
        $this->invoiceOrderContextFactory = $this->createMock(InvoiceOrderContextFactory::class);

        $this->sut = new CreateInvoiceEventSubscriber(
            $this->invoiceClient,
            $this->errorHandler,
            $this->pluginConfigurationValidator,
            $this->orderCheckProcessStateMachine,
            $this->documentEntityRepository,
            $this->invoiceOrderContextFactory
        );

        $this->event = $this->createMock(EntityWrittenEvent::class);
        $this->context = $this->createMock(Context::class);
        $this->invoiceOrderContext = $this->createMock(InvoiceOrderContext::class);

        $this->setUpContext();
    }

    private function setUpContext(): void
    {
        $this->event
            ->method('getContext')
            ->willReturn($this->context);
    }

    private function setUpPluginConfigurationIsInvalid(bool $isValid): void
    {
        $this->pluginConfigurationValidator
            ->method('isInvalid')
            ->willReturn($isValid);
    }

    public function setUpOrderState(string $orderState): void
    {
        $this->orderCheckProcessStateMachine
            ->method('getState')
            ->with(self::ORDER_ID, $this->context)
            ->willReturn($orderState);
    }

    /**
     * @param OrderEntity|null $order
     */
    private function setUpDocument(bool $hasDocumentType, $order, string $documentTypeTechnicalName, string $documentNumber): void
    {
        /** @var EntityWriteResult&MockObject*/
        $writeResult = $this->createMock(EntityWriteResult::class);
        $writeResults = [
            $writeResult
        ];

        /** @var DocumentEntity&MockObject*/
        $document = $this->createMock(DocumentEntity::class);

        $documentConfig = [
            'documentNumber' => $documentNumber
        ];

        $this->event
            ->method('getWriteResults')
            ->willReturn($writeResults);

        $writeResult
            ->method('getProperty')
            ->with('id')
            ->willReturn(self::DOCUMENT_ID);

        $this->documentEntityRepository
            ->method('findDocument')
            ->with(strval(self::DOCUMENT_ID), $this->context)
            ->willReturn($document);

        $document
            ->method('getOrder')
            ->willReturn($order);

        $document
            ->method('getConfig')
            ->willReturn($documentConfig);

        if ($hasDocumentType) {
            /** @var DocumentTypeEntity&MockObject*/
            $documentType = $this->createMock(DocumentTypeEntity::class);

            $document
                ->method('getDocumentType')
                ->willReturn($documentType);

            $documentType
                ->method('getTechnicalName')
                ->willReturn($documentTypeTechnicalName);
        } else {
            $document
                ->method('getDocumentType')
                ->willReturn(null);
        }
    }

    /**
     * @return OrderEntity&MockObject
     */
    private function setUpOrder()
    {
        /** @var OrderEntity&MockObject*/
        $order = $this->createMock(OrderEntity::class);

        $order
            ->method('getId')
            ->willReturn(self::ORDER_ID);

        return $order;
    }

    private function setUpOrderContext(string $documentNumber, InvocationOrder $expectedInvocationOrder): void
    {
        $this->invoiceOrderContextFactory
            ->method('getInvoiceOrderContext')
            ->with(self::ORDER_ID, $this->context)
            ->willReturn($this->invoiceOrderContext);

        $this->invoiceOrderContext
            ->expects($expectedInvocationOrder)
            ->method('setOrderInvoiceNumber')
            ->with($documentNumber);
    }

    /**
     * @dataProvider dataProvider_test_onEntityWritten_calls_invoiceClient
     */
    public function test_onEntityWritten_calls_invoiceClient(bool $configIsInvalid, bool $hasDocumentType, bool $hasOrder, string $documentTypeTechnicalName, string $orderState, InvocationOrder $invoiceOrderContextInvocationOrder, InvocationOrder $createInvoiceInvocationOrder): void
    {
        $documentNumber = 'documentNumber';
        $this->setUpPluginConfigurationIsInvalid($configIsInvalid);
        if ($hasOrder) {
            $order = $this->setUpOrder();
            $this->setUpDocument($hasDocumentType, $order, $documentTypeTechnicalName, $documentNumber);
        } else {
            $this->setUpDocument($hasDocumentType, null, $documentTypeTechnicalName, $documentNumber);
        }
        $this->setUpOrderState($orderState);
        $this->setUpOrderContext($documentNumber, $invoiceOrderContextInvocationOrder);

        $this->invoiceClient
            ->expects($createInvoiceInvocationOrder)
            ->method('createInvoice')
            ->with($this->invoiceOrderContext);

        $this->sut->onEntityWritten($this->event);
    }

    public function dataProvider_test_onEntityWritten_calls_invoiceClient(): array
    {
        return [
            [true, true, true, 'invoice', OrderCheckProcessStates::CONFIRMED, $this->never(), $this->never()],
            [false, false, true, 'invoice', OrderCheckProcessStates::CONFIRMED, $this->never(), $this->never()],
            [false, true, false, 'invoice', OrderCheckProcessStates::CONFIRMED, $this->never(), $this->never()],
            [false, true, true, 'notinvoice', OrderCheckProcessStates::CONFIRMED, $this->never(), $this->never()],
            [false, true, true, 'invoice', OrderCheckProcessStates::CHECKED, $this->never(), $this->never()],
            [false, true, true, 'invoice', OrderCheckProcessStates::CONFIRMED, $this->once(), $this->once()],
        ];
    }
}
