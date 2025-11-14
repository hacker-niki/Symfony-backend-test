<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\ApiController;
use App\Service\Interface\PaymentServiceInterface;
use App\Service\Interface\PriceCalculatorServiceInterface;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(ApiController::class)]
final class ApiControllerTest extends WebTestCase
{
    private MockObject $priceCalculatorMock;
    private MockObject $paymentServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->priceCalculatorMock = $this->createMock(PriceCalculatorServiceInterface::class);
        $this->paymentServiceMock = $this->createMock(PaymentServiceInterface::class);
    }

    #[DataProvider('calculatePriceDataProvider')]
    public function testCalculatePrice(
        array $requestData,
        callable $setupMocks,
        int $expectedStatusCode,
        array $expectedResponse
    ): void {
        $client = static::createClient();

        $setupMocks($this->priceCalculatorMock);

        static::getContainer()->set(PriceCalculatorServiceInterface::class, $this->priceCalculatorMock);

        $client->request(
            'POST',
            '/calculate-price',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame($expectedStatusCode);
        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedResponse),
            $client->getResponse()->getContent()
        );
    }

    public static function calculatePriceDataProvider(): iterable
    {
        yield 'успешный расчет с купоном' => [
            'requestData' => ['product' => 1, 'taxNumber' => 'DE123456789', 'couponCode' => 'D15'],
            'setupMocks' => function ($priceCalculatorMock) {
                $priceCalculatorMock->method('calculate')
                    ->with(1, 'DE123456789', 'D15')
                    ->willReturn(101.15);
            },
            'expectedStatusCode' => Response::HTTP_OK,
            'expectedResponse' => ['price' => 101.15],
        ];

        yield 'ошибка - сервис расчета бросает исключение' => [
            'requestData' => ['product' => 999, 'taxNumber' => 'DE123456789'],
            'setupMocks' => function ($priceCalculatorMock) {
                $priceCalculatorMock->method('calculate')
                    ->with(999, 'DE123456789', null)
                    ->willThrowException(new Exception('Product not found'));
            },
            'expectedStatusCode' => Response::HTTP_BAD_REQUEST,
            'expectedResponse' => ['error' => 'Product not found'],
        ];
    }

    #[DataProvider('purchaseDataProvider')]
    public function testPurchase(
        array $requestData,
        callable $setupMocks,
        int $expectedStatusCode,
        array $expectedResponse
    ): void {
        $client = static::createClient();

        $setupMocks($this->priceCalculatorMock, $this->paymentServiceMock, $this);

        static::getContainer()->set(PriceCalculatorServiceInterface::class, $this->priceCalculatorMock);
        static::getContainer()->set(PaymentServiceInterface::class, $this->paymentServiceMock);

        $client->request(
            'POST',
            '/purchase',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($requestData)
        );

        $this->assertResponseStatusCodeSame($expectedStatusCode);
        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedResponse),
            $client->getResponse()->getContent()
        );
    }

    public static function purchaseDataProvider(): iterable
    {
        yield 'успешная покупка' => [
            'requestData' => [
                'product' => 1, 'taxNumber' => 'IT12345678900', 'couponCode' => 'D15', 'paymentProcessor' => 'paypal'
            ],
            'setupMocks' => function ($priceCalculatorMock, $paymentServiceMock, WebTestCase $testCase) {
                $priceCalculatorMock->method('calculate')->willReturn(102.50);
                $paymentServiceMock->expects($testCase->once())
                    ->method('pay')
                    ->with(102.50, 'paypal');
            },
            'expectedStatusCode' => Response::HTTP_OK,
            'expectedResponse' => ['message' => 'Purchase successful.'],
        ];

        yield 'ошибка - сервис оплаты бросает исключение' => [
            'requestData' => [
                'product' => 1, 'taxNumber' => 'GR123456789', 'paymentProcessor' => 'stripe'
            ],
            'setupMocks' => function ($priceCalculatorMock, $paymentServiceMock, ?WebTestCase $testCase = null) {
                $priceCalculatorMock->method('calculate')->willReturn(95.20);
                $paymentServiceMock->method('pay')
                    ->with(95.20, 'stripe')
                    ->willThrowException(new Exception('Payment gateway is down'));
            },
            'expectedStatusCode' => Response::HTTP_BAD_REQUEST,
            'expectedResponse' => ['error' => 'Payment gateway is down'],
        ];

        yield 'ошибка - сервис расчета цены бросает исключение (оплата не должна вызываться)' => [
            'requestData' => [
                'product' => 999, 'taxNumber' => 'GR123456789', 'paymentProcessor' => 'stripe'
            ],
            'setupMocks' => function ($priceCalculatorMock, $paymentServiceMock, WebTestCase $testCase) {
                $priceCalculatorMock->method('calculate')
                    ->willThrowException(new Exception('Product not found'));
                $paymentServiceMock->expects($testCase->never())->method('pay');
            },
            'expectedStatusCode' => Response::HTTP_BAD_REQUEST,
            'expectedResponse' => ['error' => 'Product not found'],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->priceCalculatorMock, $this->paymentServiceMock);
    }
}
