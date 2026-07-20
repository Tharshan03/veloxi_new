<?php

namespace App\Services;

use App\Models\MerchantProduct;
use DomainException;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Collection;

class MerchantCart
{
    public const FULFILLMENT_PICKUP = 'pickup';
    public const FULFILLMENT_DELIVERY = 'delivery';

    private const SESSION_KEY = 'merchant_cart';

    private SessionManager $session;

    public function __construct(SessionManager $session)
    {
        $this->session = $session;
    }

    public function add(MerchantProduct $product, int $quantity = 1): void
    {
        $quantity = max(1, $quantity);
        $cart = $this->payload();

        if (!empty($cart['merchant_id']) && (int) $cart['merchant_id'] !== (int) $product->merchant_id) {
            throw new DomainException('Votre panier contient déjà des produits d’un autre restaurant.');
        }

        $cart['merchant_id'] = (int) $product->merchant_id;

        $item = $cart['items'][$product->id] ?? [
            'merchant_id' => (int) $product->merchant_id,
            'product_id' => (int) $product->id,
            'name' => $product->name,
            'unit_price' => (float) $product->price,
            'quantity' => 0,
            'line_total' => 0,
        ];

        $item['quantity'] += $quantity;
        $item['unit_price'] = (float) $product->price;
        $item['line_total'] = round($item['unit_price'] * $item['quantity'], 2);

        $cart['items'][$product->id] = $item;

        $this->put($cart);
    }

    public function update(int $productId, int $quantity): void
    {
        $cart = $this->payload();

        if (!isset($cart['items'][$productId])) {
            return;
        }

        if ($quantity <= 0) {
            $this->remove($productId);
            return;
        }

        $cart['items'][$productId]['quantity'] = $quantity;
        $cart['items'][$productId]['line_total'] = round(
            $cart['items'][$productId]['unit_price'] * $quantity,
            2
        );

        $this->put($cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->payload();
        unset($cart['items'][$productId]);

        if (empty($cart['items'])) {
            $this->clear();
            return;
        }

        $this->put($cart);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    public function payload(): array
    {
        return $this->session->get(self::SESSION_KEY, [
            'merchant_id' => null,
            'fulfillment_type' => self::FULFILLMENT_DELIVERY,
            'selected_address_id' => null,
            'subtotal' => 0,
            'delivery_fee' => null,
            'delivery_distance_km' => null,
            'total' => 0,
            'items' => [],
        ]);
    }

    public function items(): Collection
    {
        return collect($this->payload()['items'] ?? []);
    }

    public function merchantId(): ?int
    {
        $merchantId = $this->payload()['merchant_id'] ?? null;

        return $merchantId ? (int) $merchantId : null;
    }

    public function fulfillmentType(): string
    {
        $type = $this->payload()['fulfillment_type'] ?? self::FULFILLMENT_DELIVERY;

        return in_array($type, [self::FULFILLMENT_PICKUP, self::FULFILLMENT_DELIVERY], true)
            ? $type
            : self::FULFILLMENT_DELIVERY;
    }

    public function setFulfillmentType(string $type): void
    {
        if (!in_array($type, [self::FULFILLMENT_PICKUP, self::FULFILLMENT_DELIVERY], true)) {
            return;
        }

        $cart = $this->payload();
        $cart['fulfillment_type'] = $type;

        if ($type === self::FULFILLMENT_PICKUP) {
            $cart['selected_address_id'] = null;
            $cart['delivery_fee'] = 0;
            $cart['delivery_distance_km'] = null;
        }

        $this->put($cart);
    }

    public function selectedAddressId(): ?int
    {
        $addressId = $this->payload()['selected_address_id'] ?? null;

        return $addressId ? (int) $addressId : null;
    }

    public function setSelectedAddressId(?int $addressId): void
    {
        $cart = $this->payload();
        $cart['selected_address_id'] = $addressId;
        $this->put($cart);
    }

    public function storeQuote(array $quote): void
    {
        $cart = $this->payload();
        $cart['subtotal'] = $quote['subtotal'] ?? 0;
        $cart['delivery_fee'] = $quote['delivery_fee'] ?? null;
        $cart['delivery_distance_km'] = $quote['delivery_distance_km'] ?? null;
        $cart['total'] = $quote['total'] ?? $cart['subtotal'];
        $this->put($cart);
    }

    public function subtotal(): float
    {
        return round($this->items()->sum('line_total'), 2);
    }

    public function count(): int
    {
        return (int) $this->items()->sum('quantity');
    }

    public function isEmpty(): bool
    {
        return $this->items()->isEmpty();
    }

    private function put(array $cart): void
    {
        $this->session->put(self::SESSION_KEY, $cart);
    }
}
