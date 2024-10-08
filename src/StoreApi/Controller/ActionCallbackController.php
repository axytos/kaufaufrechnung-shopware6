<?php

namespace Axytos\KaufAufRechnung\Shopware\StoreApi\Controller;

use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Core\Abstractions\Model\Actions\ActionExecutorInterface;
use Axytos\KaufAufRechnung\Core\AxytosActionControllerTrait;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Logging\LoggerAdapterInterface;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * URL of this controll: http://localhost/store-api/AxytosKaufAufRechnung/v1/action/execute.
 *
 * For controller development see:
 * - https://developer.shopware.com/docs/v6.5/guides/plugins/plugins/framework/store-api/add-store-api-route.html
 *
 * @RouteScope(scopes={"store-api"}, defaults={"auth_required"=false})
 */
#[Route(defaults: ['_routeScope' => ['store-api'], 'auth_required' => false])]
class ActionCallbackController
{
    use AxytosActionControllerTrait;

    /**
     * @var ErrorHandler
     */
    private $errorHandler;

    /**
     * @var Request
     */
    private $request;
    /**
     * @var JsonResponse
     */
    private $response;

    public function __construct(
        ErrorHandler $errorHandler,
        PluginConfigurationValidator $pluginConfigurationValidator,
        ActionExecutorInterface $actionExecutor,
        LoggerAdapterInterface $logger
    ) {
        $this->errorHandler = $errorHandler;
        $this->pluginConfigurationValidator = $pluginConfigurationValidator;
        $this->actionExecutor = $actionExecutor;
        $this->logger = $logger;
    }

    /**
     * @Route(path="/api/AxytosKaufAufRechnung/v1/action/execute")
     */
    #[Route(path: '/store-api/AxytosKaufAufRechnung/v1/action/execute', name: 'axytos.kaufaufrechnung.action.execute', methods: ['POST'])]
    public function execute(Request $request): JsonResponse
    {
        try {
            $this->request = $request;
            $this->response = new JsonResponse();

            $this->executeActionInternal();

            return $this->response;
        } catch (\Throwable $th) {
            $this->setErrorResult();
            $this->errorHandler->handle($th);
        } catch (\Exception $th) { // @phpstan-ignore-line | php5.6 compatibility
            $this->setErrorResult();
            $this->errorHandler->handle($th);
        }

        return $this->response;
    }

    /**
     * @return string
     */
    protected function getRequestBody()
    {
        $rawBody = $this->request->getContent();
        if (!is_string($rawBody)) {
            return '';
        }

        return $rawBody;
    }

    /**
     * @return string
     */
    protected function getRequestMethod()
    {
        return strtoupper($this->request->getMethod());
    }

    /**
     * @param string $responseBody
     * @param int    $statusCode
     *
     * @return void
     */
    protected function setResponseBody($responseBody, $statusCode)
    {
        $this->response->setStatusCode($statusCode);
        $this->response->setJson($responseBody);
    }
}
