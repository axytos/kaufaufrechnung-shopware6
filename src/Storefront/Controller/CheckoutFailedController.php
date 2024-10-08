<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Storefront\Controller;

use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\ErrorController;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\GenericPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
class CheckoutFailedController extends StorefrontController
{
    const CHECKOUT_FAILED_VIEW = '@AxytosKaufAufRechnung/storefront/page/checkout/failed/index.html.twig';

    /**
     * @var GenericPageLoader
     */
    private $genericPageLoader;
    /**
     * @var StorefrontViewRenderer
     */
    private $storefrontViewRenderer;
    /**
     * @var ErrorController
     */
    private $errorController;
    /**
     * @var ErrorHandler
     */
    private $errorHandler;

    public function __construct(
        GenericPageLoader $genericPageLoader,
        StorefrontViewRenderer $storefrontViewRenderer,
        ErrorController $errorController,
        ErrorHandler $errorHandler
    ) {
        $this->genericPageLoader = $genericPageLoader;
        $this->storefrontViewRenderer = $storefrontViewRenderer;
        $this->errorController = $errorController;
        $this->errorHandler = $errorHandler;
    }

    /**
     * @Route("/checkout/failed", name="frontend.checkout.failed.page", options={"seo"="false"}, methods={"GET"})
     */
    #[Route(path: '/checkout/failed', name: 'frontend.checkout.failed.page', options: ['seo' => false], methods: ['GET'])]
    public function failed(Request $request, SalesChannelContext $context): Response
    {
        try {
            $page = $this->genericPageLoader->load($request, $context);

            return $this->storefrontViewRenderer->renderStorefrontView(
                self::CHECKOUT_FAILED_VIEW,
                [
                    'page' => $page,
                ]
            );
        } catch (\Throwable $th) {
            $this->errorHandler->handle($th);

            return $this->errorController->error($th, $request, $context);
        }
    }
}
