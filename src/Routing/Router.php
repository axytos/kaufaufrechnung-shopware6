<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Routing;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class Router
{
    public const CHECKOUT_FAILED_PAGE = 'frontend.checkout.failed.page';
    public const EDIT_ORDER_PAGE = 'frontend.account.edit-order.page';

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function redirectToCheckoutFailedPage(): RedirectResponse
    {
        $url = $this->router->generate(self::CHECKOUT_FAILED_PAGE);

        return new RedirectResponse($url);
    }

    public function redirectToEditOrderPage(string $orderId): RedirectResponse
    {
        $url = $this->router->generate(self::EDIT_ORDER_PAGE, [
            'orderId' => $orderId,
        ]);

        return new RedirectResponse($url);
    }

    public function redirectToEditOrderPageWithError(string $orderId): RedirectResponse
    {
        $url = $this->router->generate(self::EDIT_ORDER_PAGE, [
            'orderId' => $orderId,
            'error-code' => 'AXYTOS-TECHNICAL-ERROR',
        ]);

        return new RedirectResponse($url);
    }
}
