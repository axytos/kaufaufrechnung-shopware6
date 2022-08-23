<?php declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Storefront\Controller;

use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Response;

class StorefrontViewRenderer extends StorefrontController
{
    /**
     * @param mixed[] $parameters
     */
    public function renderStorefrontView(string $view, array $parameters = []): Response
    {
        return parent::renderStorefront($view, $parameters);
    }
}