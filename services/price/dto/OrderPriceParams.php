<?php

namespace app\services\price\dto;

use InvalidArgumentException;

class OrderPriceParams
{
    public int $orderId;
    public float $productPrice;
    public int $productQuantity;
    public array $productDimensions = [
        'width' => 0.0,
        'height' => 0.0,
        'depth' => 0.0,
        'weight' => 0.0,
    ];
    public int $packagingQuantity;
    public int $typeDeliveryId;
    public int $typePackagingId;
    public float $productInspectionPrice;
    public float $fulfillmentPrice;
    public string $calculationType;

    public const CALCULATION_TYPES = ['packaging', 'product'];

    public function __construct(array $data)
    {
        $this->orderId = $data['orderId'] ?? 0;
        $this->productPrice = (float) ($data['productPrice'] ?? 0.0);
        $this->productQuantity = (int) ($data['productQuantity'] ?? 0);
        $this->productDimensions = [
            'width' => (float) ($data['productDimensions']['width'] ?? 0.0),
            'height' => (float) ($data['productDimensions']['height'] ?? 0.0),
            'depth' => (float) ($data['productDimensions']['depth'] ?? 0.0),
            'weight' => (float) ($data['productDimensions']['weight'] ?? 0.0),
        ];
        $this->packagingQuantity = (int) ($data['packagingQuantity'] ?? 0);
        $this->typeDeliveryId = (int) ($data['typeDeliveryId'] ?? 0);
        $this->typePackagingId = (int) ($data['typePackagingId'] ?? 0);
        $this->productInspectionPrice = (float) ($data['productInspectionPrice'] ?? 0.0);
        $this->fulfillmentPrice = (float) ($data['fulfillmentPrice'] ?? 0.0);

        $calculationType = $data['calculationType'] ?? 'product';
        if (!in_array($calculationType, self::CALCULATION_TYPES)) {
            throw new InvalidArgumentException("Недопустимый тип расчёта: $calculationType");
        }
        $this->calculationType = $calculationType;
    }
}
