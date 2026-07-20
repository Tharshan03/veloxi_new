@extends('merchant.dashboard.layout')

@section('title', 'Commande #'.$order->id.' — '.$merchant->name)

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
    $timeline = ['pending', 'accepted', 'preparing', 'ready', 'delivery_requested', 'delivered'];
    $activeIndex = array_search($order->status, $timeline, true);
    $subtotal = $order->subtotal ?: $order->subtotal_amount;
    $total = $order->total ?: $order->total_amount;
@endphp

@section('content')
<main class="md-container md-page">
    <section class="md-page-head">
        <div>
            <a class="md-back" href="{{ route('merchant.orders.index') }}">← Retour aux commandes</a>
            <h1>Commande #{{ $order->id }}</h1>
            <p>{{ $merchant->name }} · {{ $order->created_at?->format('d/m/Y H:i') }}</p>
        </div>
        <a href="{{ route('merchant.orders.show', $order) }}" class="md-btn md-btn-light">Actualiser</a>
    </section>

    <section class="md-detail-grid">
        <div class="md-card md-detail-main">
            <div class="md-detail-head">
                <div>
                    <span class="md-status md-status-{{ $order->status }}">{{ $statusLabels[$order->status] ?? $order->status }}</span>
                    <h2>Informations commande</h2>
                </div>
                <div class="md-actions-row">
                    @if($order->status === 'pending')
                        <form method="POST" action="{{ route('merchant.orders.accept', $order) }}" onsubmit="return confirm('Accepter cette commande ?')">
                            @csrf
                            <button class="md-btn md-btn-primary" type="submit">Accepter</button>
                        </form>
                        <form method="POST" action="{{ route('merchant.orders.refuse', $order) }}" onsubmit="return confirm('Refuser cette commande ?')">
                            @csrf
                            <button class="md-btn md-btn-danger" type="submit">Refuser</button>
                        </form>
                    @elseif($order->status === 'accepted')
                        <form method="POST" action="{{ route('merchant.orders.preparing', $order) }}">
                            @csrf
                            <button class="md-btn md-btn-primary" type="submit">Passer en préparation</button>
                        </form>
                    @elseif($order->status === 'preparing')
                        <form method="POST" action="{{ route('merchant.orders.ready', $order) }}">
                            @csrf
                            <button class="md-btn md-btn-primary" type="submit">Marquer prête</button>
                        </form>
                    @elseif($order->status === 'ready')
                        <button class="md-btn md-btn-disabled" disabled title="Disponible au prochain sprint">Créer course Véloxi</button>
                    @endif
                </div>
            </div>

            <div class="md-info-grid">
                <div>
                    <span>Client</span>
                    <strong>{{ $order->customer_name ?: $order->user?->name }}</strong>
                </div>
                <div>
                    <span>Téléphone</span>
                    <strong>{{ $order->customer_phone ?: $order->user?->contact_number ?: 'Non renseigné' }}</strong>
                </div>
                <div>
                    <span>Email</span>
                    <strong>{{ $order->user?->email ?: 'Non renseigné' }}</strong>
                </div>
                <div>
                    <span>Mode</span>
                    <strong>{{ $order->fulfillment_type === 'pickup' ? 'À emporter' : 'Livraison' }}</strong>
                </div>
            </div>

            @if($order->fulfillment_type === 'delivery')
                <div class="md-note">
                    <strong>Adresse de livraison</strong>
                    <span>{{ $order->delivery_address ?: 'Adresse non renseignée' }}</span>
                    @if($order->delivery_address_line2)<span>{{ $order->delivery_address_line2 }}</span>@endif
                    @if($order->delivery_postal_code || $order->delivery_city)
                        <span>{{ trim(($order->delivery_postal_code ?: '').' '.($order->delivery_city ?: '')) }}</span>
                    @endif
                </div>
            @else
                <div class="md-note">
                    <strong>Retrait restaurant</strong>
                    <span>{{ $merchant->address ?: 'Adresse restaurant non renseignée' }}</span>
                    @if($order->pickup_time)<span>Heure souhaitée : {{ $order->pickup_time->format('d/m/Y H:i') }}</span>@endif
                </div>
            @endif

            @if($order->delivery_instructions)
                <div class="md-note">
                    <strong>Instructions client</strong>
                    <span>{{ $order->delivery_instructions }}</span>
                </div>
            @endif

            <h2>Produits</h2>
            <div class="md-items">
                @foreach($order->items as $item)
                    <div class="md-item-row">
                        <div>
                            <strong>{{ $item->product_name }}</strong>
                            <span>{{ $item->quantity }} × {{ number_format($item->unit_price, 2, ',', ' ') }} €</span>
                        </div>
                        <strong>{{ number_format($item->total_price, 2, ',', ' ') }} €</strong>
                    </div>
                @endforeach
            </div>
        </div>

        <aside class="md-card md-detail-side">
            <h2>Timeline</h2>
            <div class="md-timeline">
                @foreach($timeline as $index => $status)
                    <div class="md-step {{ $activeIndex !== false && $index <= $activeIndex ? 'is-done' : '' }} {{ in_array($status, ['delivery_requested', 'delivered'], true) ? 'is-future' : '' }}">
                        <span></span>
                        <strong>{{ $statusLabels[$status] ?? $status }}</strong>
                    </div>
                @endforeach
            </div>

            <h2>Total</h2>
            <div class="md-summary-line">
                <span>Sous-total</span>
                <strong>{{ number_format($subtotal, 2, ',', ' ') }} €</strong>
            </div>
            <div class="md-summary-line">
                <span>Livraison</span>
                <strong>{{ number_format($order->delivery_fee, 2, ',', ' ') }} €</strong>
            </div>
            <div class="md-summary-total">
                <span>Total</span>
                <strong>{{ number_format($total, 2, ',', ' ') }} €</strong>
            </div>
        </aside>
    </section>
</main>
@endsection
