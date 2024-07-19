<?php

namespace Axytos\KaufAufRechnung\Shopware\StoreApi\Controller;

use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Core\Abstractions\Model\Actions\ActionExecutorInterface;
use Axytos\KaufAufRechnung\Core\Abstractions\Model\Actions\ActionResultInterface;
use Axytos\KaufAufRechnung\Core\Model\Actions\Results\FatalErrorResult;
use Axytos\KaufAufRechnung\Core\Model\Actions\Results\InvalidDataResult;
use Axytos\KaufAufRechnung\Core\Model\Actions\Results\InvalidMethodResult;
use Axytos\KaufAufRechnung\Core\Model\Actions\Results\PluginNotConfiguredResult;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Logging\LoggerAdapterInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use Symfony\Component\HttpFoundation\Request;

/**
 * URL of this controll: http://localhost/store-api/AxytosKaufAufRechnung/v1/action/execute
 *
 * For controller development see:
 * - https://developer.shopware.com/docs/v6.5/guides/plugins/plugins/framework/store-api/add-store-api-route.html
 *
 * @package Axytos\KaufAufRechnung\Shopware\StoreApi\Controller
 *
 * @RouteScope(scopes={"store-api"}, defaults={"auth_required"=false})
 */
#[Route(defaults: ['_routeScope' => ['store-api'], 'auth_required' => false])]
class ActionCallbackController
{
    /**
     * @var \Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler
     */
    private $errorHandler;
    /**
     * @var \Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator
     */
    private $pluginConfigurationValidator;
    /**
     * @var \Axytos\KaufAufRechnung\Core\Abstractions\Model\Actions\ActionExecutorInterface
     */
    private $actionExecutor;
    /**
     * @var \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Logging\LoggerAdapterInterface
     */
    private $logger;


    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    private $request;
    /**
     * @var \Symfony\Component\HttpFoundation\JsonResponse
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

            if ($this->isNotPostRequest()) {
                $this->setResult(new InvalidMethodResult($this->request->getMethod()));
                return $this->response;
            }

            if ($this->pluginConfigurationValidator->isInvalid()) {
                $this->setResult(new PluginNotConfiguredResult());
                return $this->response;
            }

            $this->processAction();
            return $this->response;
        } catch (\Throwable $th) {
            $this->setResult(new FatalErrorResult());
            $this->errorHandler->handle($th);
        } catch (\Exception $th) { // @phpstan-ignore-line | php5.6 compatibility
            $this->setResult(new FatalErrorResult());
            $this->errorHandler->handle($th);
        }
        return $this->response;
    }

    /**
     * @return void
     */
    private function processAction()
    {
        $rawBody = $this->getRequestBody();

        if ($rawBody === '') {
            $this->logger->error('Process Action Request: HTTP request body empty');
            $this->setResult(new InvalidDataResult('HTTP request body empty'));
            return;
        }

        $decodedBody = json_decode($rawBody, true);
        if (!is_array($decodedBody)) {
            $this->logger->error('Process Action Request: HTTP request body is not a json object');
            $this->setResult(new InvalidDataResult('HTTP request body is not a json object'));
            return;
        }

        $loggableRequestBody = $decodedBody;
        if (array_key_exists('clientSecret', $loggableRequestBody)) {
            $loggableRequestBody['clientSecret'] = '****';
        }
        $encodedLoggableRequestBody = json_encode($loggableRequestBody);
        $this->logger->info("Process Action Request: request body '$encodedLoggableRequestBody'");

        $clientSecret = array_key_exists('clientSecret', $decodedBody) ? $decodedBody['clientSecret'] : null;
        if (!is_string($clientSecret)) {
            $this->logger->error("Process Action Request: Required string property 'clientSecret' is missing");
            $this->setResult(new InvalidDataResult('Required string property', 'clientSecret'));
            return;
        }

        $action = array_key_exists('action', $decodedBody) ?  $decodedBody['action'] : null;
        if (!is_string($action)) {
            $this->logger->error("Process Action Request: Required string property 'action' is missing");
            $this->setResult(new InvalidDataResult('Required string property', 'action'));
            return;
        }

        $params = array_key_exists('params', $decodedBody) ? $decodedBody['params'] : null;
        if (!is_null($params) && !is_array($params)) {
            $this->logger->error("Process Action Request: Optional object property 'params' ist not an array");
            $this->setResult(new InvalidDataResult('Optional object property', 'params'));
            return;
        }

        $result = $this->actionExecutor->executeAction($clientSecret, $action, $params);
        $this->setResult($result);
    }

    /**
     * @return string
     */
    private function getRequestBody()
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
    private function getRequestMethod()
    {
        return strtoupper($this->request->getMethod());
    }

    /**
     * @return bool
     */
    private function isNotPostRequest()
    {
        return $this->getRequestMethod() !== 'POST';
    }

    /**
     * @param ActionResultInterface $actionResult
     * @return void
     */
    private function setResult($actionResult)
    {
        $this->response->setStatusCode($actionResult->getHttpStatusCode());
        $this->response->setJson(strval(json_encode($actionResult)));
    }
}
