@extends('merchant.layout')

@section('title', 'Panier — Véloxi')

@php
    $fulfillmentType = $cart->fulfillmentType();
    $summary = $quote ?? [
        'subtotal' => $cart->subtotal(),
        'delivery_fee' => null,
        'delivery_distance_km' => null,
        'total' => $cart->subtotal(),
        'delivery_pending' => $fulfillmentType === 'delivery',
    ];
    $deliveryNeedsAddress = $fulfillmentType === 'delivery' && !$selectedAddress;
@endphp

@section('content')
<section class="vr-container vr-page-shell">
    <div class="vr-card vr-page-card">
        <h1>Votre panier</h1>

        @if($cart->isEmpty())
            <div class="vr-empty-cart">
                <div style="font-size:42px;" aria-hidden="true">🛍️</div>
                <p>Votre panier est vide.</p>
            </div>
        @else
            <form method="POST" action="{{ route('merchant.cart.fulfillment') }}" class="vr-fulfillment-selector vr-fulfillment-wide">
                @csrf
                @method('PATCH')
                <label class="vr-choice-card {{ $fulfillmentType === 'pickup' ? 'is-selected' : '' }}">
                    <input type="radio" name="fulfillment_type" value="pickup" onchange="this.form.submit()" {{ $fulfillmentType === 'pickup' ? 'checked' : '' }}>
                    <span>
                        <strong>À emporter</strong>
                        <small>Retrait chez {{ $merchant?->name }}</small>
                    </span>
                </label>
                <label class="vr-choice-card {{ $fulfillmentType === 'delivery' ? 'is-selected' : '' }}">
                    <input type="radio" name="fulfillment_type" value="delivery" onchange="this.form.submit()" {{ $fulfillmentType === 'delivery' ? 'checked' : '' }}>
                    <span>
                        <strong>Livraison avec Véloxi</strong>
                        <small>Frais calculés côté serveur selon la distance</small>
                    </span>
                </label>
            </form>

            @foreach($cart->items() as $item)
                <div class="vr-cart-line">
                    <div>
                        <strong>{{ $item['name'] }}</strong>
                        <div class="vr-muted">{{ number_format($item['unit_price'], 2, ',', ' ') }} € / unité</div>
                    </div>
                    <form method="POST" action="{{ route('merchant.cart.update', $item['product_id']) }}" class="vr-qty-control" data-qty-control>
                        @csrf
                        @method('PATCH')
                        <button type="button" class="vr-qty-btn" data-qty-minus aria-label="Diminuer la quantité">−</button>
                        <input class="vr-qty-input" type="number" min="0" max="99" name="quantity" value="{{ $item['quantity'] }}" aria-label="Quantité">
                        <button type="button" class="vr-qty-btn" data-qty-plus aria-label="Augmenter la quantité">+</button>
                    </form>
                    <form method="POST" action="{{ route('merchant.cart.remove', $item['product_id']) }}">
                        @csrf
                        @method('DELETE')
                        <button class="vr-btn vr-btn-light vr-btn-small" type="submit">Supprimer</button>
                    </form>
                </div>
            @endforeach
        @endif
    </div>

    <aside class="vr-card vr-summary-card">
        <h2>Résumé</h2>

        @if($merchant)
            <p class="vr-muted">{{ $merchant->name }}</p>
        @endif

        <div class="vr-summary-line">
            <span>Mode</span>
            <strong>{{ $fulfillmentType === 'pickup' ? 'À emporter' : 'Livraison' }}</strong>
        </div>

        @if($fulfillmentType === 'pickup')
            <div class="vr-info-note">
                <strong>Retrait au restaurant</strong>
                <span>{{ $merchant?->address ?: 'Adresse du restaurant non renseignée' }}</span>
            </div>
        @elseif(!$selectedAddress)
            <div class="vr-warning-note">
                @auth
                    <p>Ajoutez une adresse avec coordonnées pour calculer les frais de livraison.</p>
                    <div class="vr-note-actions">
                        <a href="{{ route('merchant.checkout.show') }}">Ajouter une adresse</a>
                    </div>
                @else
                    <p>Connectez-vous ou créez un compte pour choisir une adresse et calculer les frais de livraison.</p>
                    <div class="vr-note-actions">
                        <a href="{{ route('merchant.login') }}">Se connecter</a>
                        <a href="{{ route('merchant.register') }}">Créer un compte</a>
                    </div>
                @endauth
            </div>
        @else
            <div class="vr-info-note">
                <strong>Adresse sélectionnée</strong>
                <span>{{ $selectedAddress->address }}</span>
            </div>
        @endif

        <div class="vr-summary-line">
            <span>Sous-total</span>
            <strong>{{ number_format($summary['subtotal'], 2, ',', ' ') }} €</strong>
        </div>
        <div class="vr-summary-line">
            <span>Livraison</span>
            @if($fulfillmentType === 'pickup')
                <strong>0,00 €</strong>
            @elseif($summary['delivery_pending'] ?? false)
                <strong>
                    à calculer
                    @guest
                        <a class="vr-inline-action" href="{{ route('merchant.login') }}">Se connecter pour calculer</a>
                    @endguest
                </strong>
            @elseif(($summary['eligible'] ?? true) === false)
                <strong>indisponible</strong>
            @else
                <strong>{{ number_format($summary['delivery_fee'] ?? 0, 2, ',', ' ') }} €</strong>
            @endif
        </div>

        @if(!empty($summary['delivery_distance_km']))
            <div class="vr-summary-line">
                <span>Distance</span>
                <strong>{{ number_format($summary['delivery_distance_km'], 2, ',', ' ') }} km</strong>
            </div>
        @endif

        @if(!empty($summary['message']))
            <div class="vr-warning-note">{{ $summary['message'] }}</div>
        @endif

        <div class="vr-total-line">
            <span>Total</span>
            <strong>{{ number_format($summary['total'], 2, ',', ' ') }} €</strong>
        </div>

        @if(!$cart->isEmpty())
            @if($deliveryNeedsAddress)
                @auth
                    <a class="vr-btn vr-btn-primary" href="{{ route('merchant.checkout.show') }}">Choisir une adresse</a>
                @else
                    <a class="vr-btn vr-btn-primary" href="{{ route('merchant.login') }}">Choisir une adresse</a>
                @endauth
            @else
                <a class="vr-btn vr-btn-primary" href="{{ route('merchant.checkout.show') }}">Commander</a>
            @endif
            <form method="POST" action="{{ route('merchant.cart.clear') }}" style="margin-top:12px;">
                @csrf
                @method('DELETE')
                <button class="vr-btn vr-btn-light" type="submit">Vider le panier</button>
            </form>
        @endif
    </aside>
</section>
@endsection
