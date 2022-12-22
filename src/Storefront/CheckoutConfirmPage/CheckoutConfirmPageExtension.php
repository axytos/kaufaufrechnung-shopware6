<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Storefront\CheckoutConfirmPage;

use Shopware\Core\Framework\Struct\Struct;

class CheckoutConfirmPageExtension extends Struct
{
    /**
     * @var bool
     */
    public $showCreditCheckAgreement;
    /**
     * @var string
     */
    public $creditCheckAgreementInfo;
}
