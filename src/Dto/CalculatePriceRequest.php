<?php
declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(title: "Calculate Price Request", description: "Payload to calculate the final price of a product")]
class CalculatePriceRequest
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[OA\Property(description: "The unique ID of the product.", example: 1)]
    public int $product;

    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^(DE\d{9}|IT\d{11}|GR\d{9}|FR[A-Z]{2}\d{9})$/',
        message: 'Invalid tax number format.'
    )]
    #[OA\Property(description: "Tax number of the customer, determines the tax rate.", example: "DE123456789")]
    public string $taxNumber;

    #[OA\Property(description: "An optional discount coupon code.", example: "D15", nullable: true)]
    public ?string $couponCode = null;
}
