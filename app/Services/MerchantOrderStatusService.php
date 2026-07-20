<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\MerchantOrder;
use DomainException;
use Illuminate\Support\Facades\DB;

class MerchantOrderStatusService
{
    private const TRANSITIONS = [
        MerchantOrder::STATUS_PENDING => [
            MerchantOrder::STATUS_ACCEPTED,
            MerchantOrder::STATUS_REFUSED,
        ],
        MerchantOrder::STATUS_ACCEPTED => [
            MerchantOrder::STATUS_PREPARING,
        ],
        MerchantOrder::STATUS_PREPARING => [
            MerchantOrder::STATUS_READY,
        ],
    ];

    public function transition(Merchant $merchant, int $orderId, string $targetStatus): MerchantOrder
    {
        return DB::transaction(function () use ($merchant, $orderId, $targetStatus) {
            $order = $merchant->orders()
                ->whereKey($orderId)
                ->lockForUpdate()
                ->firstOrFail();

            $allowedTargets = self::TRANSITIONS[$order->status] ?? [];

            if (!in_array($targetStatus, $allowedTargets, true)) {
                throw new DomainException($this->messageForInvalidTransition($order->status, $targetStatus));
            }

            $updates = [
                'status' => $targetStatus,
            ];

            if ($targetStatus === MerchantOrder::STATUS_ACCEPTED) {
                $updates['accepted_at'] = now();
            }

            if ($targetStatus === MerchantOrder::STATUS_REFUSED) {
                $updates['refused_at'] = now();
            }

            if ($targetStatus === MerchantOrder::STATUS_READY) {
                $updates['ready_at'] = now();
            }

            $order->update($updates);

            return $order->fresh(['merchant', 'user', 'items']);
        });
    }

    private function messageForInvalidTransition(string $currentStatus, string $targetStatus): string
    {
        return "Transition impossible : la commande est {$currentStatus} et ne peut pas passer à {$targetStatus}.";
    }
}
