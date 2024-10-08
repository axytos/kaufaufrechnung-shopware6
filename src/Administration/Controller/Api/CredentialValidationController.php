<?php

namespace Axytos\KaufAufRechnung\Shopware\Administration\Controller\Api;

use Axytos\ECommerce\Clients\CredentialValidation\CredentialValidationClientInterface;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"administration"})
 */
#[Route(defaults: ['_routeScope' => ['administration']])]
class CredentialValidationController
{
    /**
     * @var CredentialValidationClientInterface
     */
    private $CredentialValidationClient;
    /**
     * @var ErrorHandler
     */
    private $errorHandler;

    public function __construct(
        CredentialValidationClientInterface $CredentialValidationClient,
        ErrorHandler $errorHandler
    ) {
        $this->CredentialValidationClient = $CredentialValidationClient;
        $this->errorHandler = $errorHandler;
    }

    /**
     * @Route(path="/api/AxytosKaufAufRechnung/v1/Credentials/validate")
     */
    #[Route(path: '/api/AxytosKaufAufRechnung/v1/Credentials/validate', name: 'axytos.kaufaufrechnung.credentials.validate', methods: ['POST'])]
    public function validateCredentials(): JsonResponse
    {
        try {
            $success = $this->CredentialValidationClient->validateApiKey();

            return new JsonResponse(['success' => $success]);
        } catch (\Throwable $th) {
            $this->errorHandler->handle($th);

            return new JsonResponse(['success' => false]);
        }
    }
}
