<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Core;

use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\DefaultPayment;

class AxytosInvoicePaymentHandler extends DefaultPayment
{
    public const NAME = 'Kauf auf Rechnung';
    public const DESCRIPTION = 'Sie zahlen bequem die Rechnung, sobald Sie die Ware erhalten haben, innerhalb der Zahlfrist';
    public const TECHNICAL_NAME = 'payment_axytos_kaufaufrechnung';
}
