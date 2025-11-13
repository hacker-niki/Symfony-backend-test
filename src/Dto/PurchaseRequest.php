<?php
declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

#[OA\Schema(title: "Purchase Request", description: "Payload to finalize the purchase of a product")]
class PurchaseRequest
{
    #[Assert\NotBlank(message: 'Product ID should not be empty.')]
    #[Assert\Positive(message: 'Product ID must be a positive number.')]
    #[OA\Property(description: "The unique ID of the product.", example: 1)]
    public int $product;

    #[Assert\NotBlank(message: 'Tax number should not be empty.')]
    #[Assert\Regex(
        pattern: '/^(DE\d{9}|IT\d{11}|GR\d{9}|FR[A-Z]{2}\d{9})$/',
        message: 'The tax number format is invalid.'
    )]
    #[OA\Property(description: "Tax number of the customer, determines the tax rate.", example: "IT12345678900")]
    public string $taxNumber;

    #[OA\Property(description: "An optional discount coupon code.", example: "D15", nullable: true)]
    public ?string $couponCode = null;

    #[Assert\NotBlank(message: 'Payment processor should not be empty.')]
    #[Assert\Choice(
        choices: ['paypal', 'stripe'],
        message: 'Choose a valid payment processor: paypal or stripe.'
    )]
    #[OA\Property(description: "The payment gateway to use.", enum: ['paypal', 'stripe'], example: "paypal")]
    public string $paymentProcessor;
}
