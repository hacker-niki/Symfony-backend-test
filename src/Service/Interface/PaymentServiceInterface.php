<?php
declare(strict_types=1);

namespace App\Service\Interface;

interface PaymentServiceInterface
{
    public function pay(float $amount, string $processorName): void;
}