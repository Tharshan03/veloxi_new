<?php

namespace App\Http\Controllers;

use App\Http\Requests\MerchantCheckoutRequest;
use App\Models\Merchant;
use App\Models\MerchantOrder;
use App\Models\UserAddress;
use App\Services\MerchantCart;
use App\Services\MerchantOrderService;
use DomainException;

class MerchantCheckoutController extends Controller
{
    public function show(MerchantCart $cart, MerchantOrderService $orderService)
    {
        if (!auth()->check()) {
            return redirect()->guest(route('merchant.login'));
        }

        if ($cart->isEmpty()) {
            return redirect()->route('merchant.cart.show')->withErrors('Votre panier est vide.');
        }

        $merchant = Merchant::find($cart->merchantId());
        $addresses = UserAddress::query()
            ->where('user_id', auth()->id())
            ->orderByRaw('CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN 0 ELSE 1 END')
            ->latest()
            ->get();
        $selectedAddress = $cart->selectedAddressId()
            ? $addresses->firstWhere('id', $cart->selectedAddressId())
            : $addresses->first();

        if ($selectedAddress && !$cart->selectedAddressId()) {
            $cart->setSelectedAddressId((int) $selectedAddress->id);
        }

        $quote = $merchant ? $orderService->quote($merchant, $cart, $selectedAddress) : null;

        if ($quote) {
            $cart->storeQuote($quote);
        }

        return view('merchant.checkout', [
            'cart' => $cart,
            'merchant' => $merchant,
            'user' => auth()->user(),
            'addresses' => $addresses,
            'selectedAddress' => $selectedAddress,
            'quote' => $quote,
        ]);
    }

    public function store(MerchantCheckoutRequest $request, MerchantCart $cart, MerchantOrderService $orderService)
    {
        if (!auth()->check()) {
            return redirect()->guest(route('merchant.login'));
        }

        if ($cart->isEmpty()) {
            return redirect()->route('merchant.cart.show')->withErrors('Votre panier est vide.');
        }

        if ($request->filled('selected_address_id')) {
            $cart->setSelectedAddressId((int) $request->selected_address_id);
        }

        if (
            $cart->fulfillmentType() === MerchantCart::FULFILLMENT_DELIVERY
            && !$request->filled('selected_address_id')
            && !$request->filled('new_delivery_address')
            && !$cart->selectedAddressId()
        ) {
            return back()->withErrors('Adresse requise')->withInput();
        }

        try {
            $order = $orderService->createFromCart(auth()->user(), $cart, $request->validated());
        } catch (DomainException $exception) {
            return back()->withErrors($exception->getMessage())->withInput();
        }

        $cart->clear();

        return redirect()->route('merchant.order.confirmation', $order);
    }

    public function confirmation(MerchantOrder $order)
    {
        if (!auth()->check()) {
            return redirect()->guest(route('merchant.login'));
        }

        abort_if((int) $order->user_id !== (int) auth()->id(), 403);

        $order->load(['merchant', 'items']);

        return view('merchant.confirmation', [
            'order' => $order,
        ]);
    }
}
