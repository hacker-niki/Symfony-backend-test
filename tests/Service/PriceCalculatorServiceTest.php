<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Coupon;
use App\Entity\Product;
use App\Entity\Tax;
use App\Enum\CouponType;
use App\Repository\Interface\CouponRepositoryInterface;
use App\Repository\Interface\ProductRepositoryInterface;
use App\Repository\Interface\TaxRepositoryInterface;
use App\Service\PriceCalculatorService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PriceCalculatorService::class)]
final class PriceCalculatorServiceTest extends TestCase
{
    private ProductRepositoryInterface $productRepository;
    private CouponRepositoryInterface $couponRepository;
    private TaxRepositoryInterface $taxRepository;
    private PriceCalculatorService $service;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->couponRepository = $this->createMock(CouponRepositoryInterface::class);
        $this->taxRepository = $this->createMock(TaxRepositoryInterface::class);

        $this->service = new PriceCalculatorService(
            $this->productRepository,
            $this->couponRepository,
            $this->taxRepository
        );
    }

    public function testCalculateReturnsDiscountedPriceWithTax(): void
    {
        $product = (new Product())->setPrice('100');
        $coupon = (new Coupon())
            ->setType(CouponType::Percent)
            ->setValue('15');
        $tax = (new Tax())
            ->setCountryCode('DE')
            ->setRate('19');

        $this->productRepository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $this->couponRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'D15'])
            ->willReturn($coupon);

        $this->taxRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['countryCode' => 'DE'])
            ->willReturn($tax);

        $result = $this->service->calculate(1, 'DE123456789', 'D15');

        self::assertSame(101.15, $result);
    }

    public function testCalculateForcesPriceFloorBeforeTax(): void
    {
        $product = (new Product())->setPrice('5');
        $coupon = (new Coupon())
            ->setType(CouponType::Fixed)
            ->setValue('10');
        $tax = (new Tax())
            ->setCountryCode('DE')
            ->setRate('25');

        $this->productRepository->method('find')->willReturn($product);
        $this->couponRepository->method('findOneBy')->willReturn($coupon);
        $this->taxRepository->method('findOneBy')->willReturn($tax);

        $result = $this->service->calculate(1, 'DE123456789', 'FIXED');

        self::assertSame(0.0, $result);
    }

    public function testCalculateThrowsWhenProductMissing(): void
    {
        $this->productRepository->method('find')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Product not found.');

        $this->service->calculate(42, 'DE123456789', null);
    }

    public function testCalculateThrowsWhenCouponUnknown(): void
    {
        $product = (new Product())->setPrice('100');
        $this->productRepository->method('find')->willReturn($product);
        $this->couponRepository->method('findOneBy')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid coupon code.');

        $this->service->calculate(1, 'DE123456789', 'UNKNOWN');
    }

    public function testCalculateThrowsWhenTaxMissing(): void
    {
        $product = (new Product())->setPrice('50');
        $this->productRepository->method('find')->willReturn($product);
        $this->taxRepository->method('findOneBy')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tax rate for this country is not configured.');

        $this->service->calculate(1, 'US123456789', null);
    }
}
