<?php

declare(strict_types=1);

namespace Axytos\KaufAufRechnung\Shopware\Tests\Unit\ErrorReporting;

use Axytos\ECommerce\Clients\ErrorReporting\ErrorReportingClientInterface;
use Axytos\KaufAufRechnung\Shopware\ErrorReporting\ErrorHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @internal
 */
class ErrorHandlerTest extends TestCase
{
    /** @var ErrorReportingClientInterface&MockObject */
    private $errorReportingClient;

    /** @var KernelInterface&MockObject */
    private $kernel;

    /**
     * @var ErrorHandler
     */
    private $sut;

    public function setUp(): void
    {
        $this->errorReportingClient = $this->createMock(ErrorReportingClientInterface::class);
        $this->kernel = $this->createMock(KernelInterface::class);

        $this->sut = new ErrorHandler(
            $this->errorReportingClient,
            $this->kernel
        );
    }

    public function test_handle_reports_error(): void
    {
        $error = new \Exception();

        $this->errorReportingClient
            ->expects($this->once())
            ->method('reportError')
            ->with($error)
        ;

        $this->sut->handle($error);
    }

    public function test_handle_does_not_rethrow_error_if_debug_mode_is_disabled(): void
    {
        $this->expectNotToPerformAssertions();

        $error = new \Exception();

        $this->kernel->method('isDebug')->willReturn(false);

        $this->sut->handle($error);
    }

    public function test_handle_does_rethrow_error_if_debug_mode_is_enabled(): void
    {
        $error = new \Exception();

        $this->expectExceptionObject($error);

        $this->kernel->method('isDebug')->willReturn(true);

        $this->sut->handle($error);
    }
}
