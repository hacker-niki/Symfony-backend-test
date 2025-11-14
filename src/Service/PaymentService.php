<?php
declare(strict_types=1);

namespace App\Service;

use App\Repository\CouponRepository;
use App\Repository\ProductRepository;
use App\Repository\TaxRepository;
use App\Service\Interface\PaymentServiceInterface;
use Exception;
use InvalidArgumentException;
use Systemeio\TestForCandidates\PaymentProcessor\PaypalPaymentProcessor;
use Systemeio\TestForCandidates\PaymentProcessor\StripePaymentProcessor;

final class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private readonly PaypalPaymentProcessor $paypalProcessor,
        private readonly StripePaymentProcessor $stripeProcessor
    ) {
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function pay(float $amount, string $processorName): void
    {
        try {
            match ($processorName) {
                'paypal' => $this->paypalProcessor->pay($this->convertToCents($amount)),
                'stripe' => $this->stripeProcessor->processPayment($this->convertToCents($amount)),
                default => throw new InvalidArgumentException('Unsupported payment processor specified.'),
            };
        } catch (InvalidArgumentException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new Exception('Payment failed: ' . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    private function convertToCents(float $amount): int
    {
        return (int)($amount * 100);
    }
}
