<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\UserAddress;

class AddressDistanceService
{
    public function distanceBetweenMerchantAndAddress(Merchant $merchant, UserAddress $address): ?float
    {
        if (
            $merchant->latitude === null ||
            $merchant->longitude === null ||
            $address->latitude === null ||
            $address->longitude === null
        ) {
            return null;
        }

        return $this->haversine(
            (float) $merchant->latitude,
            (float) $merchant->longitude,
            (float) $address->latitude,
            (float) $address->longitude
        );
    }

    public function haversine(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $earthRadiusKm = 6371;
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($fromLatitude))
            * cos(deg2rad($toLatitude))
            * sin($longitudeDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 2);
    }
}
