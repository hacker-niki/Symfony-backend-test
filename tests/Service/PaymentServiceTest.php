<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\PaymentService;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Systemeio\TestForCandidates\PaymentProcessor\PaypalPaymentProcessor;
use Systemeio\TestForCandidates\PaymentProcessor\StripePaymentProcessor;

#[CoversClass(PaymentService::class)]
final class PaymentServiceTest extends TestCase
{
    private PaypalPaymentProcessor $paypalProcessor;
    private StripePaymentProcessor $stripeProcessor;
    private PaymentService $service;

    protected function setUp(): void
    {
        $this->paypalProcessor = $this->createMock(PaypalPaymentProcessor::class);
        $this->stripeProcessor = $this->createMock(StripePaymentProcessor::class);

        $this->service = new PaymentService(
            $this->paypalProcessor,
            $this->stripeProcessor
        );
    }

    public function testPayDelegatesToPaypalProcessor(): void
    {
        $this->paypalProcessor->expects(self::once())
            ->method('pay')
            ->with(1075);
        $this->stripeProcessor->expects(self::never())
            ->method('processPayment');

        $this->service->pay(10.75, 'paypal');
    }

    public function testPayDelegatesToStripeProcessor(): void
    {
        $this->stripeProcessor->expects(self::once())
            ->method('processPayment')
            ->with(7250);
        $this->paypalProcessor->expects(self::never())
            ->method('pay');

        $this->service->pay(72.50, 'stripe');
    }

    public function testPayThrowsForUnsupportedProcessor(): void
    {
        $this->paypalProcessor->expects(self::never())->method('pay');
        $this->stripeProcessor->expects(self::never())->method('processPayment');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported payment processor specified.');

        $this->service->pay(10.0, 'crypto');
    }

    public function testPayWrapsProcessorExceptions(): void
    {
        $this->stripeProcessor->method('processPayment')
            ->willThrowException(new Exception('Gateway down'));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Payment failed: Gateway down');

        $this->service->pay(15.25, 'stripe');
    }
}
