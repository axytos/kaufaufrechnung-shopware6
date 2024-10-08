<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\Administration;

use Axytos\ECommerce\Clients\CredentialValidation\CredentialValidationClientInterface;
use Axytos\KaufAufRechnung\Shopware\Administration\Controller\Api\CredentialValidationController;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class CredentialValidationControllerTest extends TestCase
{
    /** @var CredentialValidationClientInterface&MockObject */
    private $credentialValidationClient;

    /** @var ErrorHandler&MockObject */
    private $errorHandler;

    /**
     * @var CredentialValidationController
     */
    private $sut;

    public function setUp(): void
    {
        $this->credentialValidationClient = $this->createMock(CredentialValidationClientInterface::class);
        $this->errorHandler = $this->createMock(ErrorHandler::class);

        $this->sut = new CredentialValidationController(
            $this->credentialValidationClient,
            $this->errorHandler
        );
    }

    public function test_validate_credentials_returns_true_if_api_key_is_valid(): void
    {
        $this->credentialValidationClient
            ->method('validateApiKey')
            ->willReturn(true)
        ;

        $response = $this->sut->validateCredentials();

        /** @var string */
        $json = $response->getContent();
        /** @var mixed[] */
        $content = json_decode($json, true);

        $this->assertTrue($content['success']);
    }

    public function test_validate_credentials_returns_false_if_api_key_is_valid(): void
    {
        $this->credentialValidationClient
            ->method('validateApiKey')
            ->willReturn(false)
        ;

        $response = $this->sut->validateCredentials();

        /** @var string */
        $json = $response->getContent();
        /** @var mixed[] */
        $content = json_decode($json, true);

        $this->assertFalse($content['success']);
    }

    public function test_validate_credentials_returns_false_if_api_key_validation_fails(): void
    {
        $this->credentialValidationClient
            ->method('validateApiKey')
            ->willThrowException(new \Exception())
        ;

        $response = $this->sut->validateCredentials();

        /** @var string */
        $json = $response->getContent();
        /** @var mixed[] */
        $content = json_decode($json, true);

        $this->assertFalse($content['success']);
    }

    public function test_validate_credentials_reports_error_if_api_key_validation_fails(): void
    {
        $exception = new \Exception();

        $this->credentialValidationClient
            ->method('validateApiKey')
            ->willThrowException($exception)
        ;

        $this->errorHandler
            ->expects($this->once())
            ->method('handle')
            ->with($exception)
        ;

        $this->sut->validateCredentials();
    }
}
