<?php

namespace App\Tests;

use App\Service\PaymentService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiControllerTest extends WebTestCase
{
    /**
     * Тестирует успешное выполнение запроса на расчет цены.
     */
    public function testCalculatePriceSuccess(): void
    {
        $client = static::createClient();

        $requestData = [
            'product' => 1,
            'taxNumber' => 'DE123456789',
            'couponCode' => 'D15'
        ];

        $client->request(
            'POST',
            '/calculate-price',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $this->assertJson($response->getContent());
        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('price', $responseData);
        $this->assertIsNumeric($responseData['price']);
    }

    /**
     * Тестирует случай, когда продукт не найден при расчете цены.
     */
    public function testCalculatePriceFailsWithInvalidProduct(): void
    {
        $client = static::createClient();

        $requestData = [
            'product' => 999,
            'taxNumber' => 'DE123456789',
        ];

        $client->request(
            'POST',
            '/calculate-price',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Product not found', $responseData['error']);
    }

    /**
     * Тестирует успешное выполнение покупки.
     */
    public function testPurchaseSuccess(): void
    {
        $client = static::createClient();

        $requestData = [
            'product' => 1,
            'taxNumber' => 'IT12345678900',
            'couponCode' => 'D15',
            'paymentProcessor' => 'paypal'
        ];

        $client->request(
            'POST',
            '/purchase',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Purchase successful.', $responseData['message']);
    }

    /**
     * Тестирует ошибку покупки, когда платежный процессор выбрасывает исключение.
     */
    public function testPurchaseFailsWhenPaymentProcessorThrowsException(): void
    {
        $client = static::createClient();

        $paymentServiceMock = $this->createMock(PaymentService::class);

        $paymentServiceMock->method('pay')
            ->willThrowException(new Exception('Payment gateway is down'));

        static::getContainer()->set(PaymentService::class, $paymentServiceMock);

        $requestData = [
            'product' => 1,
            'taxNumber' => 'GR123456789',
            'paymentProcessor' => 'stripe'
        ];

        $client->request(
            'POST',
            '/purchase',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = $client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('Payment gateway is down', $responseData['error']);
    }
}
