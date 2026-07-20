<?php

namespace App\Http\Controllers;

use App\Models\MerchantOrder;

class MerchantAccountOrderController extends Controller
{
    public function index()
    {
        $orders = MerchantOrder::query()
            ->with('merchant')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('merchant.account.orders.index', [
            'orders' => $orders,
        ]);
    }

    public function show(MerchantOrder $order)
    {
        abort_if((int) $order->user_id !== (int) auth()->id(), 403);

        $order->load(['merchant', 'items', 'deliveryAddress']);

        return view('merchant.account.orders.show', [
            'order' => $order,
        ]);
    }
}
