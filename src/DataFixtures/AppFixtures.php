<?php

namespace App\DataFixtures;

use App\Entity\Coupon;
use App\Entity\Product;
use App\Entity\Tax;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public const string COUPON_TYPE_PERCENT = 'percent';
    public const string COUPON_TYPE_FIXED = 'fixed';

    public function load(ObjectManager $manager): void
    {
        $productsData = [
            ['name' => 'Iphone', 'price' => '100.00'],
            ['name' => 'Наушники', 'price' => '20.00'],
            ['name' => 'Чехол', 'price' => '10.00'],
        ];

        foreach ($productsData as $productInfo) {
            $product = new Product();
            $product->setName($productInfo['name']);
            $product->setPrice($productInfo['price']);
            $manager->persist($product);
        }

        $taxesData = [
            ['countryCode' => 'DE', 'rate' => '19.00'],
            ['countryCode' => 'IT', 'rate' => '22.00'],
            ['countryCode' => 'FR', 'rate' => '20.00'],
            ['countryCode' => 'GR', 'rate' => '24.00'],
        ];

        foreach ($taxesData as $taxInfo) {
            $tax = new Tax();
            $tax->setCountryCode($taxInfo['countryCode']);
            $tax->setRate($taxInfo['rate']);
            $manager->persist($tax);
        }

        $couponsData = [
            ['code' => 'D15', 'type' => self::COUPON_TYPE_PERCENT, 'value' => '15.00'],
            ['code' => 'P6', 'type' => self::COUPON_TYPE_PERCENT, 'value' => '6.00'],
            ['code' => 'F10', 'type' => self::COUPON_TYPE_FIXED, 'value' => '10.00'],
        ];

        foreach ($couponsData as $couponInfo) {
            $coupon = new Coupon();
            $coupon->setCode($couponInfo['code']);
            $coupon->setType($couponInfo['type']);
            $coupon->setValue($couponInfo['value']);
            $manager->persist($coupon);
        }

        $manager->flush();
    }
}