<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Controller;

use Axytos\KaufAufRechnung\Shopware\Configuration\OrderLineItemFieldService;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['api']])]
class RefreshOrderLineItemFieldsController extends AbstractController
{
    /** @var OrderLineItemFieldService */
    private $service;

    public function __construct(OrderLineItemFieldService $service)
    {
        $this->service = $service;
    }

    #[Route(
        path: '/api/_action/order-line-column-refresher/refresh-fields',
        name: 'api.action.order-line-column-refresher.refresh_fields',
        methods: ['POST'],
        defaults: ['_routeScope' => ['api']]
    )]
    public function refresh(Context $context): JsonResponse
    {
        try {
            $fields = $this->service->getSelectableFields($context);

            return new JsonResponse([
                'success' => true,
                'fields' => $fields,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'fields' => [],
                'message' => 'Error refreshing order line item fields',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
