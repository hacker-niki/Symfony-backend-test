<?php
declare(strict_types=1);

namespace App\Service\Interface;

interface PriceCalculatorServiceInterface
{
    public function calculate(int $productId, string $taxNumber, ?string $couponCode): float;
}