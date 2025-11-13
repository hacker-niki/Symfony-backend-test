<?php
declare(strict_types=1);

namespace App\Service;

use App\Enum\CouponType;
use App\Repository\CouponRepository;
use App\Repository\ProductRepository;
use App\Repository\TaxRepository;
use InvalidArgumentException;

class PriceCalculatorService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CouponRepository  $couponRepository,
        private readonly TaxRepository     $taxRepository
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
