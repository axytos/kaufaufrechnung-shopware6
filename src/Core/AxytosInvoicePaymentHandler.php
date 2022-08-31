<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Core;

use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\DefaultPayment;

class AxytosInvoicePaymentHandler extends DefaultPayment
{
    public const NAME = 'Kauf auf Rechnung';
    public const DESCRIPTION = 'Axytos Kauf auf Rechnung';
}
