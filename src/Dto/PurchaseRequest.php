<?php
declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PurchaseRequest
{
    #[Assert\NotBlank(message: 'Product ID should not be empty.')]
    #[Assert\Positive(message: 'Product ID must be a positive number.')]
    public int $product;

    #[Assert\NotBlank(message: 'Tax number should not be empty.')]
    #[Assert\Regex(
        pattern: '/^(DE\d{9}|IT\d{11}|GR\d{9}|FR[A-Z]{2}\d{9})$/',
        message: 'The tax number format is invalid.'
    )]
    public string $taxNumber;

    public ?string $couponCode = null;

    #[Assert\NotBlank(message: 'Payment processor should not be empty.')]
    #[Assert\Choice(
        choices: ['paypal', 'stripe'],
        message: 'Choose a valid payment processor: paypal or stripe.'
    )]
    public string $paymentProcessor;
}