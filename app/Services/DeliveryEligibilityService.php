<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\UserAddress;

class DeliveryEligibilityService
{
    private AddressDistanceService $distanceService;

    public function __construct(AddressDistanceService $distanceService)
    {
        $this->distanceService = $distanceService;
    }

    public function check(Merchant $merchant, ?UserAddress $address, float $subtotal = 0): array
    {
        if ((int) $merchant->status !== 1) {
            return $this->response(false, 'merchant_inactive', 'Restaurant indisponible.');
        }

        if (!$merchant->accepts_delivery) {
            return $this->response(false, 'delivery_unavailable', 'Ce restaurant ne propose pas la livraison.');
        }

        if ($address === null) {
            return $this->response(false, 'address_required', 'Adresse requise');
        }

        $distanceKm = $this->distanceService->distanceBetweenMerchantAndAddress($merchant, $address);

        if ($distanceKm === null) {
            return $this->response(false, 'distance_unavailable', 'Impossible de calculer la distance');
        }

        $maxDistanceKm = (float) ($merchant->max_delivery_distance_km ?: config('veloxi.delivery.default_max_delivery_distance_km', 5));

        if ($distanceKm > $maxDistanceKm) {
            return $this->response(false, 'outside_delivery_zone', 'Adresse hors zone de livraison', [
                'distance_km' => $distanceKm,
                'max_distance_km' => $maxDistanceKm,
            ]);
        }

        $minimumOrderAmount = (float) $merchant->minimum_order_amount;

        if ($minimumOrderAmount > 0 && $subtotal < $minimumOrderAmount) {
            return $this->response(false, 'minimum_order_not_reached', 'Montant minimum non atteint', [
                'minimum_order_amount' => $minimumOrderAmount,
            ]);
        }

        return $this->response(true, 'eligible', null, [
            'distance_km' => $distanceKm,
            'max_distance_km' => $maxDistanceKm,
        ]);
    }

    private function response(bool $eligible, string $code, ?string $message, array $data = []): array
    {
        return array_merge([
            'eligible' => $eligible,
            'code' => $code,
            'message' => $message,
        ], $data);
    }
}
