<?php

namespace App\Controller;

use App\Dto\CalculatePriceRequest;
use App\Dto\PurchaseRequest;
use App\Service\Interface\PaymentServiceInterface;
use App\Service\Interface\PriceCalculatorServiceInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class ApiController extends AbstractController
{
    public function __construct(
        private readonly PriceCalculatorServiceInterface $priceCalculator,
        private readonly PaymentServiceInterface $paymentService
    ) {
    }

    #[Route('/calculate-price', name: 'calculate_price', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Data needed to calculate the price',
        content: new OA\JsonContent(
            ref: new Model(type: CalculatePriceRequest::class)
        )
    )]
    public function calculatePrice(#[MapRequestPayload] CalculatePriceRequest $request
    ): JsonResponse {
        try {
            $price = $this->priceCalculator->calculate(
                $request->product,
                $request->taxNumber,
                $request->couponCode
            );

            return $this->json(['price' => $price]);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/purchase', name: 'purchase', methods: ['POST'])]
    #[OA\RequestBody(
        description: 'Data needed to make a purchase',
        content: new OA\JsonContent(
            ref: new Model(type: PurchaseRequest::class)
        )
    )]
    public function purchase(#[MapRequestPayload] PurchaseRequest $request
    ): JsonResponse {
        try {
            $price = $this->priceCalculator->calculate(
                $request->product,
                $request->taxNumber,
                $request->couponCode
            );

            $this->paymentService->pay($price, $request->paymentProcessor);

            return $this->json(['message' => 'Purchase successful.']);
        } catch (Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
