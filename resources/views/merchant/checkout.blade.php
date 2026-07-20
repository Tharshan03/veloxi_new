@extends('merchant.layout')

@section('title', 'Checkout — Véloxi')

@php
    $fulfillmentType = $cart->fulfillmentType();
    $summary = $quote ?? [
        'subtotal' => $cart->subtotal(),
        'delivery_fee' => null,
        'delivery_distance_km' => null,
        'total' => $cart->subtotal(),
        'delivery_pending' => $fulfillmentType === 'delivery',
        'eligible' => true,
    ];
    $deliveryBlocked = $fulfillmentType === 'delivery'
        && (
            !$selectedAddress
            || ($summary['delivery_pending'] ?? false)
            || (($summary['eligible'] ?? true) === false)
        );
@endphp

@section('content')
<section class="vr-container vr-page-shell">
    <div class="vr-card vr-page-card">
        <h1>Finaliser la commande</h1>

        <form method="POST" action="{{ route('merchant.checkout.store') }}" class="vr-form">
            @csrf

            <div class="vr-form-grid">
                <label class="vr-field">
                    <span>Nom</span>
                    <input type="text" name="customer_name" value="{{ old('customer_name', $user->name) }}" required>
                </label>
                <label class="vr-field">
                    <span>Email</span>
                    <input type="email" name="customer_email" value="{{ old('customer_email', $user->email) }}" required>
                </label>
                <label class="vr-field">
                    <span>Téléphone</span>
                    <input type="text" name="customer_phone" value="{{ old('customer_phone', $user->contact_number) }}">
                </label>
            </div>

            @if($fulfillmentType === 'pickup')
                <div class="vr-info-note">
                    <strong>Retrait au restaurant</strong>
                    <span>{{ $merchant?->address ?: 'Adresse du restaurant non renseignée' }}</span>
                </div>
                <label class="vr-field">
                    <span>Heure de retrait souhaitée optionnelle</span>
                    <input type="datetime-local" name="pickup_time" value="{{ old('pickup_time') }}">
                </label>
            @else
                <h2>Adresse de livraison</h2>

                @if($addresses->isEmpty())
                    <div class="vr-warning-note">
                        Aucune adresse enregistrée. Ajoutez une adresse avec latitude/longitude pour calculer la livraison.
                    </div>
                @else
                    <div class="vr-address-list">
                        @foreach($addresses as $address)
                            <label class="vr-choice-card {{ optional($selectedAddress)->id === $address->id ? 'is-selected' : '' }}">
                                <input type="radio" name="selected_address_id" value="{{ $address->id }}" {{ optional($selectedAddress)->id === $address->id ? 'checked' : '' }}>
                                <span>
                                    <strong>{{ $address->address_type ?: 'Adresse' }}</strong>
                                    <small>{{ $address->address }}</small>
                                    @if($address->latitude && $address->longitude)
                                        <small>{{ $address->latitude }}, {{ $address->longitude }}</small>
                                    @else
                                        <small>Coordonnées manquantes</small>
                                    @endif
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="vr-new-address">
                    <h3>Ajouter une adresse</h3>
                    <label class="vr-field">
                        <span>Adresse</span>
                        <input type="text" name="new_delivery_address" value="{{ old('new_delivery_address') }}" placeholder="Ex. 10 rue de Paris, 93150 Le Blanc-Mesnil">
                    </label>
                    <div class="vr-form-grid">
                        <label class="vr-field">
                            <span>Latitude</span>
                            <input type="text" name="new_delivery_latitude" value="{{ old('new_delivery_latitude') }}" placeholder="48.93">
                        </label>
                        <label class="vr-field">
                            <span>Longitude</span>
                            <input type="text" name="new_delivery_longitude" value="{{ old('new_delivery_longitude') }}" placeholder="2.46">
                        </label>
                    </div>
                </div>

                <label class="vr-field">
                    <span>Complément d’adresse</span>
                    <input type="text" name="delivery_address_line2" value="{{ old('delivery_address_line2') }}">
                </label>
                <div class="vr-form-grid">
                    <label class="vr-field">
                        <span>Ville</span>
                        <input type="text" name="delivery_city" value="{{ old('delivery_city') }}">
                    </label>
                    <label class="vr-field">
                        <span>Code postal</span>
                        <input type="text" name="delivery_postal_code" value="{{ old('delivery_postal_code') }}">
                    </label>
                </div>
                <label class="vr-field">
                    <span>Instructions de livraison</span>
                    <textarea name="delivery_instructions">{{ old('delivery_instructions') }}</textarea>
                </label>
            @endif

            @if(!empty($summary['message']))
                <div class="vr-warning-note">{{ $summary['message'] }}</div>
            @endif

            <button class="vr-btn vr-btn-primary" type="submit" {{ $deliveryBlocked ? 'disabled' : '' }}>
                {{ $deliveryBlocked ? 'Choisir une adresse' : 'Valider la commande' }}
            </button>
        </form>
    </div>

    <aside class="vr-card vr-summary-card">
        <h2>Résumé</h2>
        @if($merchant)<p class="vr-muted">{{ $merchant->name }}</p>@endif

        @foreach($cart->items() as $item)
            <div class="vr-summary-line">
                <span>{{ $item['quantity'] }} × {{ $item['name'] }}</span>
                <strong>{{ number_format($item['line_total'], 2, ',', ' ') }} €</strong>
            </div>
        @endforeach

        <hr style="border:0;border-top:1px solid var(--veloxi-border);margin:16px 0;">
        <div class="vr-summary-line">
            <span>Mode</span>
            <strong>{{ $fulfillmentType === 'pickup' ? 'À emporter' : 'Livraison' }}</strong>
        </div>
        <div class="vr-summary-line">
            <span>Sous-total</span>
            <strong>{{ number_format($summary['subtotal'], 2, ',', ' ') }} €</strong>
        </div>
        <div class="vr-summary-line">
            <span>Livraison</span>
            @if($fulfillmentType === 'pickup')
                <strong>0,00 €</strong>
            @elseif($summary['delivery_pending'] ?? false)
                <strong>à calculer</strong>
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
        <div class="vr-total-line">
            <span>Total</span>
            <strong>{{ number_format($summary['total'], 2, ',', ' ') }} €</strong>
        </div>
    </aside>
</section>
@endsection
