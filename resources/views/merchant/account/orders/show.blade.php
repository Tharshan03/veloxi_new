@extends('merchant.layout')

@section('title', 'Commande #'.$order->id.' — Véloxi')

@section('content')
<section class="vr-container vr-page-shell">
    <div class="vr-card vr-page-card">
        <h1>Commande #{{ $order->id }}</h1>
        <p class="vr-muted">{{ $order->merchant?->name }} · {{ $order->created_at?->format('d/m/Y H:i') }}</p>
        <p>Statut : <strong>{{ $order->status }}</strong></p>
        <p>Mode : <strong>{{ $order->fulfillment_type === 'pickup' ? 'À emporter' : 'Livraison avec Véloxi' }}</strong></p>

        @if($order->fulfillment_type === 'pickup')
            <div class="vr-info-note">
                <strong>Retrait</strong>
                <span>{{ $order->merchant?->address }}</span>
                @if($order->pickup_time)
                    <span>Heure souhaitée : {{ $order->pickup_time->format('d/m/Y H:i') }}</span>
                @endif
            </div>
        @else
            <div class="vr-info-note">
                <strong>Livraison</strong>
                <span>{{ $order->delivery_address }}</span>
                @if($order->delivery_distance_km)
                    <span>{{ number_format($order->delivery_distance_km, 2, ',', ' ') }} km</span>
                @endif
            </div>
        @endif

        <h2>Produits</h2>
        @foreach($order->items as $item)
            <div class="vr-cart-line">
                <div>
                    <strong>{{ $item->product_name }}</strong>
                    <div class="vr-muted">{{ $item->quantity }} × {{ number_format($item->unit_price, 2, ',', ' ') }} €</div>
                </div>
                <strong>{{ number_format($item->total_price, 2, ',', ' ') }} €</strong>
            </div>
        @endforeach
    </div>

    <aside class="vr-card vr-summary-card">
        <h2>Total</h2>
        <div class="vr-summary-line">
            <span>Sous-total</span>
            <strong>{{ number_format($order->subtotal ?: $order->subtotal_amount, 2, ',', ' ') }} €</strong>
        </div>
        <div class="vr-summary-line">
            <span>Livraison</span>
            <strong>{{ number_format($order->delivery_fee, 2, ',', ' ') }} €</strong>
        </div>
        <div class="vr-total-line">
            <span>Total</span>
            <strong>{{ number_format($order->total ?: $order->total_amount, 2, ',', ' ') }} €</strong>
        </div>
        <a class="vr-btn vr-btn-light" href="{{ route('merchant.account.orders.index') }}">Retour aux commandes</a>
    </aside>
</section>
@endsection
