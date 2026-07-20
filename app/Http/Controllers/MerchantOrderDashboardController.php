<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\MerchantOrder;
use App\Services\MerchantOrderStatusService;
use DomainException;
use Illuminate\Http\Request;

class MerchantOrderDashboardController extends Controller
{
    private const FILTERABLE_STATUSES = [
        MerchantOrder::STATUS_PENDING,
        MerchantOrder::STATUS_ACCEPTED,
        MerchantOrder::STATUS_PREPARING,
        MerchantOrder::STATUS_READY,
        MerchantOrder::STATUS_REFUSED,
    ];

    public function index(Request $request)
    {
        $merchant = $this->currentMerchant($request);

        if (!$merchant) {
            return response()->view('merchant.dashboard.no-merchant', [], 403);
        }

        $status = $request->query('status', 'all');
        $status = in_array($status, self::FILTERABLE_STATUSES, true) ? $status : 'all';

        $baseQuery = $merchant->orders()->with(['user', 'items']);

        $counts = [
            MerchantOrder::STATUS_PENDING => (clone $baseQuery)->where('status', MerchantOrder::STATUS_PENDING)->count(),
            MerchantOrder::STATUS_ACCEPTED => (clone $baseQuery)->where('status', MerchantOrder::STATUS_ACCEPTED)->count(),
            MerchantOrder::STATUS_PREPARING => (clone $baseQuery)->where('status', MerchantOrder::STATUS_PREPARING)->count(),
            MerchantOrder::STATUS_READY => (clone $baseQuery)->where('status', MerchantOrder::STATUS_READY)->count(),
            MerchantOrder::STATUS_REFUSED => (clone $baseQuery)->where('status', MerchantOrder::STATUS_REFUSED)->count(),
        ];

        $orders = $baseQuery
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('merchant.dashboard.orders.index', [
            'merchant' => $merchant,
            'orders' => $orders,
            'counts' => $counts,
            'currentStatus' => $status,
            'statuses' => self::FILTERABLE_STATUSES,
        ]);
    }

    public function show(Request $request, int $order)
    {
        $merchant = $this->currentMerchant($request);

        if (!$merchant) {
            return response()->view('merchant.dashboard.no-merchant', [], 403);
        }

        $merchantOrder = $merchant->orders()
            ->with(['user', 'items.product'])
            ->findOrFail($order);

        return view('merchant.dashboard.orders.show', [
            'merchant' => $merchant,
            'order' => $merchantOrder,
        ]);
    }

    public function accept(Request $request, int $order, MerchantOrderStatusService $statusService)
    {
        return $this->transition($request, $order, MerchantOrder::STATUS_ACCEPTED, $statusService);
    }

    public function refuse(Request $request, int $order, MerchantOrderStatusService $statusService)
    {
        return $this->transition($request, $order, MerchantOrder::STATUS_REFUSED, $statusService);
    }

    public function preparing(Request $request, int $order, MerchantOrderStatusService $statusService)
    {
        return $this->transition($request, $order, MerchantOrder::STATUS_PREPARING, $statusService);
    }

    public function ready(Request $request, int $order, MerchantOrderStatusService $statusService)
    {
        return $this->transition($request, $order, MerchantOrder::STATUS_READY, $statusService);
    }

    private function transition(Request $request, int $orderId, string $targetStatus, MerchantOrderStatusService $statusService)
    {
        $merchant = $this->currentMerchant($request);

        if (!$merchant) {
            return response()->view('merchant.dashboard.no-merchant', [], 403);
        }

        try {
            $order = $statusService->transition($merchant, $orderId, $targetStatus);
        } catch (DomainException $exception) {
            return back()->withErrors($exception->getMessage());
        }

        return redirect()
            ->route('merchant.orders.show', $order)
            ->with('success', 'Statut de la commande mis à jour.');
    }

    private function currentMerchant(Request $request): ?Merchant
    {
        return $request->user()
            ->ownedMerchants()
            ->where('status', 1)
            ->oldest('id')
            ->first();
    }
}
