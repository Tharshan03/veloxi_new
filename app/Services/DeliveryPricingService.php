<?php

namespace App\Services;

class DeliveryPricingService
{
    public function quote(float $distanceKm): array
    {
        $baseDistanceKm = (float) config('veloxi.delivery.base_distance_km', 4);
        $baseDeliveryPrice = (float) config('veloxi.delivery.base_delivery_price', 3.89);
        $extraPricePerKm = (float) config('veloxi.delivery.extra_price_per_km', 0.50);

        $extraDistanceKm = max(0, $distanceKm - $baseDistanceKm);
        $extraAmount = $extraDistanceKm * $extraPricePerKm;
        $deliveryFee = round($baseDeliveryPrice + $extraAmount, 2);

        return [
            'distance_km' => round($distanceKm, 2),
            'base_distance_km' => $baseDistanceKm,
            'base_delivery_price' => $baseDeliveryPrice,
            'extra_distance_km' => round($extraDistanceKm, 2),
            'extra_price_per_km' => $extraPricePerKm,
            'delivery_fee' => $deliveryFee,
        ];
    }
}
