<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\MerchantProduct;
use App\Models\UserAddress;
use App\Services\MerchantCart;
use App\Services\MerchantOrderService;
use DomainException;
use Illuminate\Http\Request;

class MerchantCartController extends Controller
{
    public function show(MerchantCart $cart, MerchantOrderService $orderService)
    {
        $merchant = $cart->merchantId() ? Merchant::find($cart->merchantId()) : null;
        $selectedAddress = $this->selectedAddress($cart);
        $quote = $merchant ? $orderService->quote($merchant, $cart, $selectedAddress) : null;

        if ($quote) {
            $cart->storeQuote($quote);
        }

        return view('merchant.cart', [
            'cart' => $cart,
            'merchant' => $merchant,
            'quote' => $quote,
            'selectedAddress' => $selectedAddress,
        ]);
    }

    public function add(Request $request, MerchantProduct $product, MerchantCart $cart)
    {
        abort_if((int) $product->status !== 1, 404);

        $request->validate([
            'quantity' => 'nullable|integer|min:1|max:99',
        ]);

        try {
            $cart->add($product, (int) $request->input('quantity', 1));
        } catch (DomainException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return redirect()->route('merchant.cart.show')->withErrors($exception->getMessage());
        }

        if ($request->expectsJson()) {
            $subtotal = $cart->subtotal();
            $payloadTotal = (float) ($cart->payload()['total'] ?? 0);
            $items = $cart->items()->map(function ($item) {
                return [
                    'product_id' => (int) $item['product_id'],
                    'name' => $item['name'],
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (float) $item['unit_price'],
                    'line_total' => (float) $item['line_total'],
                ];
            })->values();

            return response()->json([
                'message' => null,
                'cart' => [
                    'count' => $cart->count(),
                    'subtotal' => $subtotal,
                    'total' => $payloadTotal > 0 ? $payloadTotal : $subtotal,
                    'items' => $items,
                ],
            ]);
        }

        return redirect()
            ->to(route('merchant.public.show', $product->merchant) . '#menu');
    }

    public function update(Request $request, MerchantProduct $product, MerchantCart $cart)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0|max:99',
        ]);

        $cart->update($product->id, (int) $request->quantity);

        return redirect()->route('merchant.cart.show')->with('success', 'Panier mis à jour.');
    }

    public function remove(MerchantProduct $product, MerchantCart $cart)
    {
        $cart->remove($product->id);

        return redirect()->route('merchant.cart.show')->with('success', 'Produit supprimé.');
    }

    public function clear(MerchantCart $cart)
    {
        $cart->clear();

        return redirect()->route('merchant.cart.show')->with('success', 'Panier vidé.');
    }

    public function fulfillment(Request $request, MerchantCart $cart)
    {
        $data = $request->validate([
            'fulfillment_type' => 'required|in:pickup,delivery',
            'selected_address_id' => 'nullable|integer|exists:user_addresses,id',
        ]);

        $cart->setFulfillmentType($data['fulfillment_type']);

        if ($data['fulfillment_type'] === MerchantCart::FULFILLMENT_DELIVERY) {
            $addressId = $data['selected_address_id'] ?? null;

            if ($addressId && auth()->check()) {
                $belongsToUser = UserAddress::where('user_id', auth()->id())
                    ->where('id', $addressId)
                    ->exists();

                if ($belongsToUser) {
                    $cart->setSelectedAddressId((int) $addressId);
                }
            } elseif (auth()->check()) {
                $address = $this->defaultAddressForCurrentUser();

                if ($address) {
                    $cart->setSelectedAddressId((int) $address->id);
                }
            }
        }

        return back();
    }

    private function selectedAddress(MerchantCart $cart): ?UserAddress
    {
        if (!auth()->check()) {
            return null;
        }

        if ($cart->selectedAddressId()) {
            $selectedAddress = UserAddress::query()
                ->where('user_id', auth()->id())
                ->where('id', $cart->selectedAddressId())
                ->first();

            if ($selectedAddress) {
                return $selectedAddress;
            }
        }

        if ($cart->fulfillmentType() !== MerchantCart::FULFILLMENT_DELIVERY) {
            return null;
        }

        $address = $this->defaultAddressForCurrentUser();

        if ($address) {
            $cart->setSelectedAddressId((int) $address->id);
        }

        return $address;
    }

    private function defaultAddressForCurrentUser(): ?UserAddress
    {
        return UserAddress::query()
            ->where('user_id', auth()->id())
            ->orderByRaw('CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN 0 ELSE 1 END')
            ->latest()
            ->first();
    }
}
