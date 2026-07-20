<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantOrder;
use App\Models\MerchantProduct;
use App\Models\User;
use App\Models\UserAddress;
use DomainException;
use Illuminate\Support\Facades\DB;

class MerchantOrderService
{
    private DeliveryEligibilityService $eligibilityService;
    private DeliveryPricingService $pricingService;

    public function __construct(
        DeliveryEligibilityService $eligibilityService,
        DeliveryPricingService $pricingService
    ) {
        $this->eligibilityService = $eligibilityService;
        $this->pricingService = $pricingService;
    }

    public function quote(Merchant $merchant, MerchantCart $cart, ?UserAddress $address = null): array
    {
        $subtotal = $this->recalculateSubtotal($cart);
        $fulfillmentType = $cart->fulfillmentType();
        $deliveryFee = 0;
        $deliveryDistanceKm = null;
        $deliveryPending = false;
        $eligibility = null;

        if ((int) $merchant->status !== 1) {
            return [
                'subtotal' => $subtotal,
                'delivery_fee' => null,
                'delivery_distance_km' => null,
                'total' => $subtotal,
                'delivery_pending' => false,
                'eligible' => false,
                'message' => 'Restaurant indisponible.',
            ];
        }

        if ($fulfillmentType === MerchantCart::FULFILLMENT_PICKUP) {
            if (!$merchant->accepts_pickup) {
                return [
                    'subtotal' => $subtotal,
                    'delivery_fee' => 0,
                    'delivery_distance_km' => null,
                    'total' => $subtotal,
                    'delivery_pending' => false,
                    'eligible' => false,
                    'message' => 'Ce restaurant ne propose pas le retrait.',
                ];
            }

            if ((float) $merchant->minimum_order_amount > 0 && $subtotal < (float) $merchant->minimum_order_amount) {
                return [
                    'subtotal' => $subtotal,
                    'delivery_fee' => 0,
                    'delivery_distance_km' => null,
                    'total' => $subtotal,
                    'delivery_pending' => false,
                    'eligible' => false,
                    'message' => 'Montant minimum non atteint',
                ];
            }
        }

        if ($fulfillmentType === MerchantCart::FULFILLMENT_DELIVERY) {
            if ($address === null) {
                $deliveryPending = true;
            } else {
                $eligibility = $this->eligibilityService->check($merchant, $address, $subtotal);

                if (!($eligibility['eligible'] ?? false)) {
                    return [
                        'subtotal' => $subtotal,
                        'delivery_fee' => null,
                        'delivery_distance_km' => $eligibility['distance_km'] ?? null,
                        'total' => $subtotal,
                        'delivery_pending' => false,
                        'eligible' => false,
                        'message' => $eligibility['message'] ?? 'Livraison indisponible',
                    ];
                }

                $deliveryDistanceKm = (float) $eligibility['distance_km'];
                $deliveryFee = $this->pricingService->quote($deliveryDistanceKm)['delivery_fee'];
            }
        }

        return [
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryPending ? null : round($deliveryFee, 2),
            'delivery_distance_km' => $deliveryDistanceKm,
            'total' => round($subtotal + $deliveryFee, 2),
            'delivery_pending' => $deliveryPending,
            'eligible' => true,
            'message' => null,
            'eligibility' => $eligibility,
        ];
    }

    public function createFromCart(User $user, MerchantCart $cart, array $data): MerchantOrder
    {
        if ($cart->isEmpty()) {
            throw new DomainException('Votre panier est vide.');
        }

        $merchant = Merchant::findOrFail($cart->merchantId());
        $address = $this->resolveAddress($user, $cart, $data);
        $quote = $this->quote($merchant, $cart, $address);

        if (!($quote['eligible'] ?? false)) {
            throw new DomainException($quote['message'] ?: 'Livraison indisponible.');
        }

        if ($cart->fulfillmentType() === MerchantCart::FULFILLMENT_DELIVERY && $address === null) {
            throw new DomainException('Adresse requise');
        }

        $cartItems = $cart->items();
        $productIds = $cartItems->pluck('product_id')->map(fn ($id) => (int) $id)->all();

        $products = MerchantProduct::query()
            ->whereIn('id', $productIds)
            ->where('status', 1)
            ->get()
            ->keyBy('id');

        if ($products->count() !== count($productIds)) {
            throw new DomainException('Un produit du panier n’est plus disponible.');
        }

        $merchantIds = $products->pluck('merchant_id')->unique();
        if ($merchantIds->count() !== 1 || (int) $merchantIds->first() !== (int) $cart->merchantId()) {
            throw new DomainException('Votre panier contient des produits incompatibles.');
        }

        return DB::transaction(function () use ($user, $merchant, $cart, $cartItems, $products, $data, $address, $quote) {
            $deliveryAddress = $address?->address;
            $deliveryLatitude = $address?->latitude;
            $deliveryLongitude = $address?->longitude;

            $order = MerchantOrder::create([
                'user_id' => $user->id,
                'merchant_id' => $merchant->id,
                'status' => MerchantOrder::STATUS_PENDING,
                'fulfillment_type' => $cart->fulfillmentType(),
                'subtotal_amount' => $quote['subtotal'],
                'subtotal' => $quote['subtotal'],
                'delivery_fee' => $quote['delivery_fee'] ?? 0,
                'delivery_distance_km' => $quote['delivery_distance_km'],
                'total_amount' => $quote['total'],
                'total' => $quote['total'],
                'delivery_address_id' => $address?->id,
                'delivery_address' => $deliveryAddress,
                'delivery_address_line2' => $data['delivery_address_line2'] ?? null,
                'delivery_city' => $data['delivery_city'] ?? null,
                'delivery_postal_code' => $data['delivery_postal_code'] ?? null,
                'delivery_instructions' => $data['delivery_instructions'] ?? null,
                'delivery_latitude' => $deliveryLatitude,
                'delivery_longitude' => $deliveryLongitude,
                'pickup_time' => $data['pickup_time'] ?? null,
                'customer_name' => $data['customer_name'] ?? $user->name,
                'customer_phone' => $data['customer_phone'] ?? $user->contact_number,
                'notes' => $data['delivery_instructions'] ?? null,
            ]);

            foreach ($cartItems as $item) {
                $product = $products->get($item['product_id']);
                $quantity = (int) $item['quantity'];
                $unitPrice = (float) $product->price;

                $order->items()->create([
                    'merchant_product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => round($unitPrice * $quantity, 2),
                ]);
            }

            return $order;
        });
    }

    public function resolveAddress(User $user, MerchantCart $cart, array $data): ?UserAddress
    {
        if ($cart->fulfillmentType() !== MerchantCart::FULFILLMENT_DELIVERY) {
            return null;
        }

        $selectedAddressId = $data['selected_address_id'] ?? $cart->selectedAddressId();

        if ($selectedAddressId) {
            return UserAddress::query()
                ->where('user_id', $user->id)
                ->where('id', $selectedAddressId)
                ->first();
        }

        if (!empty($data['new_delivery_address'])) {
            return UserAddress::create([
                'user_id' => $user->id,
                'address' => $data['new_delivery_address'],
                'latitude' => $data['new_delivery_latitude'] ?? null,
                'longitude' => $data['new_delivery_longitude'] ?? null,
                'contact_number' => $data['customer_phone'] ?? $user->contact_number,
                'address_type' => 'delivery',
            ]);
        }

        return null;
    }

    private function recalculateSubtotal(MerchantCart $cart): float
    {
        if ($cart->isEmpty()) {
            return 0;
        }

        $items = $cart->items();
        $products = MerchantProduct::query()
            ->whereIn('id', $items->pluck('product_id')->all())
            ->where('status', 1)
            ->get()
            ->keyBy('id');

        $subtotal = 0;

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);

            if (!$product) {
                continue;
            }

            $subtotal += (float) $product->price * (int) $item['quantity'];
        }

        return round($subtotal, 2);
    }
}
