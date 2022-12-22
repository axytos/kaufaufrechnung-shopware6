<?php

namespace Axytos\KaufAufRechnung\Shopware\Administration\Controller\Api;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Axytos\ECommerce\Clients\CredentialValidation\CredentialValidationClientInterface;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;

/**
 * @RouteScope(scopes={"administration"})
 */
class CredentialValidationController
{
    /**
     * @var \Axytos\ECommerce\Clients\CredentialValidation\CredentialValidationClientInterface
     */
    private $CredentialValidationClient;
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler
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
     * @Route(path="/api/v1/AxytosKaufAufRechnung/Credentials/validate")
     */
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
