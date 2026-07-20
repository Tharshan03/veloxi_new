@extends('merchant.layout')

@section('title', 'Commande confirmée — Véloxi')

@section('content')
<section style="max-width:760px;margin:32px auto;" class="card">
    <div style="padding:28px;">
        <h1>Commande reçue</h1>
        <p class="muted">Votre commande #{{ $order->id }} a été envoyée à {{ $order->merchant->name }}.</p>
        <p>Statut : <strong>{{ $order->status }}</strong></p>
        <p>Mode : <strong>{{ $order->fulfillment_type === 'pickup' ? 'À emporter' : 'Livraison avec Véloxi' }}</strong></p>
        @if($order->fulfillment_type === 'pickup')
            <p class="muted">Retrait : {{ $order->merchant->address }}</p>
        @else
            <p class="muted">Adresse : {{ $order->delivery_address }}</p>
        @endif
        <h2>Résumé</h2>
        @foreach($order->items as $item)
            <div style="display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid var(--line);padding:10px 0;">
                <span>{{ $item->quantity }} × {{ $item->product_name }}</span>
                <strong>{{ number_format($item->total_price, 2, ',', ' ') }} €</strong>
            </div>
        @endforeach
        <div style="margin-top:16px;">
            <div style="display:flex;justify-content:space-between;"><span>Sous-total</span><strong>{{ number_format($order->subtotal ?: $order->subtotal_amount, 2, ',', ' ') }} €</strong></div>
            <div style="display:flex;justify-content:space-between;"><span>Livraison</span><strong>{{ number_format($order->delivery_fee, 2, ',', ' ') }} €</strong></div>
            @if($order->delivery_distance_km)
                <div style="display:flex;justify-content:space-between;"><span>Distance</span><strong>{{ number_format($order->delivery_distance_km, 2, ',', ' ') }} km</strong></div>
            @endif
        </div>
        <h2 style="text-align:right;">Total {{ number_format($order->total ?: $order->total_amount, 2, ',', ' ') }} €</h2>
        <p class="muted">Le suivi sur site et l’invitation app Véloxi seront ajoutés dans un sprint ultérieur.</p>
        <a class="btn btn-primary" href="{{ route('merchant.public.show', $order->merchant) }}">Retour au restaurant</a>
    </div>
</section>
@endsection
