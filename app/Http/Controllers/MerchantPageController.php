<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\UserAddress;
use App\Services\MerchantCart;
use App\Services\MerchantOrderService;

class MerchantPageController extends Controller
{
    public function show(Merchant $merchant, MerchantCart $cart, MerchantOrderService $orderService)
    {
        abort_if((int) $merchant->status !== 1, 404);

        session(['merchant_home_slug' => $merchant->slug]);

        $merchant->load([
            'categories' => function ($query) {
                $query->where('status', 1)
                    ->orderBy('position')
                    ->orderBy('name')
                    ->with(['products' => function ($productQuery) {
                        $productQuery->where('status', 1)
                            ->orderBy('sort_order')
                            ->orderBy('name');
                    }]);
            },
            'products' => function ($query) {
                $query->whereNull('category_id')
                    ->where('status', 1)
                    ->orderBy('sort_order')
                    ->orderBy('name');
            },
        ]);

        $selectedAddress = null;

        if (auth()->check() && $cart->fulfillmentType() === MerchantCart::FULFILLMENT_DELIVERY) {
            if ($cart->selectedAddressId()) {
                $selectedAddress = UserAddress::query()
                    ->where('user_id', auth()->id())
                    ->where('id', $cart->selectedAddressId())
                    ->first();
            }

            if (!$selectedAddress) {
                $selectedAddress = UserAddress::query()
                    ->where('user_id', auth()->id())
                    ->orderByRaw('CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN 0 ELSE 1 END')
                    ->latest()
                    ->first();

                if ($selectedAddress) {
                    $cart->setSelectedAddressId((int) $selectedAddress->id);
                }
            }
        }

        $quote = $orderService->quote($merchant, $cart, $selectedAddress);
        $cart->storeQuote($quote);

        return view('merchant.restaurant', [
            'merchant' => $merchant,
            'cartCount' => $cart->count(),
            'quote' => $quote,
            'selectedAddress' => $selectedAddress,
        ]);
    }
}
