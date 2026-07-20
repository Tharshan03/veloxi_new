@extends('merchant.dashboard.layout')

@section('title', 'Commandes — '.$merchant->name)

@php
    $statusLabels = [
        'pending' => 'En attente',
        'accepted' => 'Acceptée',
        'refused' => 'Refusée',
        'preparing' => 'En préparation',
        'ready' => 'Prête',
        'delivery_requested' => 'Course demandée',
        'delivered' => 'Livrée',
        'cancelled' => 'Annulée',
    ];
    $filters = array_merge(['all'], $statuses);
@endphp

@section('content')
<main class="md-container md-page">
    <section class="md-page-head">
        <div>
            <p class="md-eyebrow">Dashboard commerçant</p>
            <h1>{{ $merchant->name }}</h1>
            <p>Gérez les commandes restaurant reçues en temps réel manuel.</p>
        </div>
        <a href="{{ route('merchant.orders.index', request()->query()) }}" class="md-btn md-btn-light">Actualiser</a>
    </section>

    <section class="md-stats">
        @foreach($counts as $status => $count)
            <article class="md-card md-stat md-status-border-{{ $status }}">
                <span>{{ $statusLabels[$status] ?? $status }}</span>
                <strong>{{ $count }}</strong>
            </article>
        @endforeach
    </section>

    <section class="md-filters" aria-label="Filtres commandes">
        @foreach($filters as $filter)
            <a class="{{ $currentStatus === $filter ? 'is-active' : '' }}"
               href="{{ route('merchant.orders.index', $filter === 'all' ? [] : ['status' => $filter]) }}">
                {{ $filter === 'all' ? 'Toutes' : ($statusLabels[$filter] ?? $filter) }}
            </a>
        @endforeach
    </section>

    <section class="md-card md-orders-card">
        @if($orders->isEmpty())
            <div class="md-empty-state">
                <h2>Aucune commande</h2>
                <p>Aucune commande ne correspond au filtre sélectionné.</p>
            </div>
        @else
            <div class="md-order-list">
                @foreach($orders as $order)
                    @php
                        $subtotal = $order->subtotal ?: $order->subtotal_amount;
                        $total = $order->total ?: $order->total_amount;
                    @endphp
                    <article class="md-order-row {{ $order->status === 'pending' ? 'is-pending' : '' }}">
                        <div>
                            <a class="md-order-id" href="{{ route('merchant.orders.show', $order) }}">#{{ $order->id }}</a>
                            <div class="md-muted">{{ $order->created_at?->format('d/m/Y H:i') }}</div>
                        </div>
                        <div>
                            <strong>{{ $order->customer_name ?: $order->user?->name }}</strong>
                            <div class="md-muted">{{ $order->customer_phone ?: $order->user?->contact_number }}</div>
                        </div>
                        <div>
                            <span class="md-pill">{{ $order->fulfillment_type === 'pickup' ? 'Pickup' : 'Delivery' }}</span>
                            @if($order->fulfillment_type === 'delivery' && $order->delivery_address)
                                <div class="md-muted md-address">{{ $order->delivery_address }}</div>
                            @endif
                        </div>
                        <div>
                            <strong>{{ $order->items->sum('quantity') }}</strong>
                            <div class="md-muted">article(s)</div>
                        </div>
                        <div class="md-money">
                            <div>Sous-total {{ number_format($subtotal, 2, ',', ' ') }} €</div>
                            <div>Livraison {{ number_format($order->delivery_fee, 2, ',', ' ') }} €</div>
                            <strong>Total {{ number_format($total, 2, ',', ' ') }} €</strong>
                        </div>
                        <div>
                            <span class="md-status md-status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                        </div>
                        <div>
                            <a class="md-btn md-btn-primary md-btn-small" href="{{ route('merchant.orders.show', $order) }}">Voir détail</a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="md-pagination">
                {{ $orders->links() }}
            </div>
        @endif
    </section>
</main>
@endsection
