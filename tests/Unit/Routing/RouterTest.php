<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\Routing;

use Axytos\KaufAufRechnung\Shopware\Routing\Router;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
class RouterTest extends TestCase
{
    /** @var RouterInterface&MockObject */
    private $router;

    /**
     * @var Router
     */
    private $sut;

    public function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);

        $this->sut = new Router(
            $this->router
        );
    }

    public function test_redirect_to_checkout_failed_page_returns_redirect_response_with_correct_url(): void
    {
        $url = 'url';

        $this->router
            ->method('generate')
            ->with(Router::CHECKOUT_FAILED_PAGE)
            ->willReturn($url)
        ;

        $response = $this->sut->redirectToCheckoutFailedPage();

        $this->assertSame($url, $response->getTargetUrl());
    }

    public function test_redirect_to_edit_order_page_returns_redirect_response_with_correct_url(): void
    {
        $orderId = 'orderId';
        $url = 'url';

        $this->router
            ->method('generate')
            ->with(Router::EDIT_ORDER_PAGE, [
                'orderId' => $orderId,
            ])
            ->willReturn($url)
        ;

        $response = $this->sut->redirectToEditOrderPage($orderId);

        $this->assertSame($url, $response->getTargetUrl());
    }

    public function test_redirect_to_edit_order_page_with_error_returns_redirect_response_with_correct_url(): void
    {
        $orderId = 'orderId';
        $url = 'url';

        $this->router
            ->method('generate')
            ->with(Router::EDIT_ORDER_PAGE, [
                'orderId' => $orderId,
                'error-code' => 'AXYTOS-TECHNICAL-ERROR',
            ])
            ->willReturn($url)
        ;

        $response = $this->sut->redirectToEditOrderPageWithError($orderId);

        $this->assertSame($url, $response->getTargetUrl());
    }
}
