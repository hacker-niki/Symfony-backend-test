<?php
declare(strict_types=1);

namespace App\Service;

use App\Enum\CouponType;
use App\Repository\Interface\CouponRepositoryInterface;
use App\Repository\Interface\ProductRepositoryInterface;
use App\Repository\Interface\TaxRepositoryInterface;
use App\Service\Interface\PriceCalculatorServiceInterface;
use InvalidArgumentException;

final class PriceCalculatorService implements PriceCalculatorServiceInterface
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CouponRepositoryInterface  $couponRepository,
        private readonly TaxRepositoryInterface     $taxRepository
    )
    {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function calculate(int $productId, string $taxNumber, ?string $couponCode): float
    {
        $product = $this->productRepository->find($productId);
        if (!$product) {
            throw new InvalidArgumentException('Product not found.');
        }

        $price = (float)$product->getPrice();

        if ($couponCode) {
            $coupon = $this->couponRepository->findOneBy(['code' => $couponCode]);
            if (!$coupon) {
                throw new InvalidArgumentException('Invalid coupon code.');
            }

            $price = $this->applyCoupon($price, $coupon->getType(), (float)$coupon->getValue());
        }

        $price = max(0, $price);

        $countryCode = substr($taxNumber, 0, 2);
        $tax = $this->taxRepository->findOneBy(['countryCode' => $countryCode]);

        if (!$tax) {
            throw new InvalidArgumentException('Tax rate for this country is not configured.');
        }

        $finalPrice = $price * (1 + ((float)$tax->getRate() / 100));

        return round($finalPrice, 2);
    }

    /**
     * @param CouponType $type Тип купона
     */
    private function applyCoupon(float $price, CouponType $type, float $value): float
    {
        return match ($type) {
            CouponType::Fixed => $price - $value,
            CouponType::Percent => $price * (1 - $value / 100),
        };
    }
}
