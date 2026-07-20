@extends('merchant.layout')

@section('title', $merchant->name.' — Véloxi')

@php
    $cartPayload = session('merchant_cart', ['items' => []]);
    $cartItems = collect($cartPayload['items'] ?? []);
    $cartProductImages = \App\Models\MerchantProduct::query()
        ->whereIn('id', $cartItems->pluck('product_id')->filter()->all())
        ->pluck('image', 'id');
    $cartCount = (int) $cartItems->sum('quantity');
    $fulfillmentType = $cartPayload['fulfillment_type'] ?? 'delivery';
    $summary = $quote ?? [
        'subtotal' => round((float) $cartItems->sum('line_total'), 2),
        'delivery_fee' => null,
        'total' => round((float) $cartItems->sum('line_total'), 2),
        'delivery_pending' => $fulfillmentType === 'delivery',
    ];
    $firstCategory = $merchant->categories->first(function ($category) {
        return $category->products->isNotEmpty();
    });
    $heroStyle = $merchant->cover_image
        ? "background-image: linear-gradient(90deg, rgba(0,0,0,.86) 0%, rgba(0,0,0,.66) 44%, rgba(0,0,0,.18) 100%), url('".asset($merchant->cover_image)."');"
        : null;
    $categoryIcons = ['🥙', '🍔', '🍟', '🥤', '🍰', '🌯'];
@endphp

@section('content')
<section class="vr-hero {{ $merchant->cover_image ? 'has-cover' : '' }}" style="{{ $heroStyle }}">
    <div class="vr-container vr-hero-inner">
        <div class="vr-status-pill">
            <span class="vr-status-dot {{ $merchant->is_open === false ? 'closed' : '' }}"></span>
            <span>{{ $merchant->is_open === false ? 'FERMÉ' : 'OUVERT' }} · Jusqu’à 23h00</span>
        </div>

        <h1>{{ $merchant->name }}</h1>
        <p class="vr-hero-subtitle">
            {{ $merchant->description ?: 'Des produits frais, savoureux et livrés rapidement chez vous avec Véloxi.' }}
        </p>

        <div class="vr-rating-row" id="reviews">
            <span><span class="vr-star">★</span> 4,7/5 (128 avis)</span>
            <span aria-hidden="true">|</span>
            <span>⏱️ 15–25 min</span>
        </div>

        <div class="vr-hero-actions">
            <a href="#menu" data-scroll-target="#menu" class="vr-btn vr-btn-primary">
                Commander maintenant <span aria-hidden="true">→</span>
            </a>
            <a href="#menu" data-scroll-target="#menu" class="vr-btn vr-btn-dark">
                Voir le menu <span aria-hidden="true">↓</span>
            </a>
        </div>

        <div class="vr-delivery-note">
            <span aria-hidden="true">🚴</span>
            <span>Livraison rapide avec Véloxi</span>
        </div>
    </div>

    <div class="vr-speed-badge" aria-label="Livraison rapide 10 à 25 minutes">
        <div>
            <div class="vr-speed-icon">⏱</div>
            <div>LIVRAISON</div>
            <strong>RAPIDE</strong>
            <small>10–25 MIN</small>
        </div>
    </div>
</section>

<section class="vr-container vr-info-strip" aria-label="Informations restaurant">
    <div class="vr-card vr-info-card">
        <div class="vr-info-item">
            <div class="vr-info-icon">⌖</div>
            <div>
                <div class="vr-info-title">Adresse</div>
                <div class="vr-info-value">{{ $merchant->address ?: 'Adresse bientôt disponible' }}</div>
            </div>
        </div>
        <div class="vr-info-item">
            <div class="vr-info-icon">☎</div>
            <div>
                <div class="vr-info-title">Téléphone</div>
                <div class="vr-info-value">{{ $merchant->phone ?: 'Non renseigné' }}</div>
            </div>
        </div>
        <div class="vr-info-item">
            <div class="vr-info-icon">◷</div>
            <div>
                <div class="vr-info-title">Horaires</div>
                <div class="vr-info-value">Tous les jours<br>11h00 – 23h00</div>
            </div>
        </div>
        <div class="vr-info-item">
            <div class="vr-info-icon">🛵</div>
            <div>
                <div class="vr-info-title">Livraison</div>
                <div class="vr-info-value">15–25 min<br>avec Véloxi</div>
            </div>
        </div>
    </div>
</section>

<section class="vr-container vr-main-grid" id="menu">
    <div class="vr-card vr-menu-panel">
        <div class="vr-section-head">
            <h2 class="vr-section-title">Notre menu</h2>
            <label class="vr-search" aria-label="Rechercher un produit">
                <input type="search" data-product-search placeholder="Rechercher un produit...">
                <span aria-hidden="true">⌕</span>
            </label>
        </div>

        <div class="vr-category-tabs" aria-label="Catégories">
            @foreach($merchant->categories as $index => $category)
                @if($category->products->isNotEmpty())
                    <button type="button"
                            class="vr-category-tab {{ optional($firstCategory)->id === $category->id ? 'is-active' : '' }}"
                            data-category-target="#category-{{ $category->id }}">
                        <span aria-hidden="true">{{ $categoryIcons[$index % count($categoryIcons)] }}</span>
                        {{ $category->name }}
                    </button>
                @endif
            @endforeach
        </div>

        <form method="POST" action="{{ route('merchant.cart.fulfillment') }}" class="vr-fulfillment-selector">
            @csrf
            @method('PATCH')
            <label class="vr-choice-card {{ $fulfillmentType === 'pickup' ? 'is-selected' : '' }}">
                <input type="radio" name="fulfillment_type" value="pickup" onchange="this.form.submit()" {{ $fulfillmentType === 'pickup' ? 'checked' : '' }}>
                <span>
                    <strong>À emporter</strong>
                    <small>Retrait au restaurant · frais 0 €</small>
                </span>
            </label>
            <label class="vr-choice-card {{ $fulfillmentType === 'delivery' ? 'is-selected' : '' }}">
                <input type="radio" name="fulfillment_type" value="delivery" onchange="this.form.submit()" {{ $fulfillmentType === 'delivery' ? 'checked' : '' }}>
                <span>
                    <strong>Livraison avec Véloxi</strong>
                    <small>
                        @if(!empty($selectedAddress))
                            {{ $selectedAddress->address }}
                        @else
                            Adresse requise au checkout
                        @endif
                    </small>
                </span>
            </label>
        </form>

        @foreach($merchant->categories as $categoryIndex => $category)
            @if($category->products->isNotEmpty())
                <div class="vr-category-section" id="category-{{ $category->id }}">
                    <h3 class="vr-category-heading">{{ $category->name }}</h3>
                    <div class="vr-products-grid">
                        @foreach($category->products as $product)
                            <article class="vr-card vr-product-card" data-product-card="{{ $product->name }} {{ $product->description }} {{ $category->name }}">
                                <div class="vr-product-image">
                                    @if($product->image)
                                        <img src="{{ asset($product->image) }}" alt="{{ $product->name }}">
                                    @else
                                        <span aria-hidden="true">{{ $categoryIcons[$categoryIndex % count($categoryIcons)] }}</span>
                                    @endif
                                </div>
                                <div class="vr-product-body">
                                    <h3 class="vr-product-name">{{ $product->name }}</h3>
                                    <p class="vr-product-desc">{{ $product->description ?: 'Produit disponible à la commande.' }}</p>
                                    <div class="vr-product-bottom">
                                        <span class="vr-price">{{ number_format($product->price, 2, ',', ' ') }} €</span>
                                        <form method="POST" action="{{ route('merchant.cart.add', $product) }}">
                                            @csrf
                                            <input type="hidden" name="quantity" value="1">
                                            <button class="vr-btn vr-btn-primary vr-add-btn" type="submit">
                                                <span aria-hidden="true">＋</span> Ajouter
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach

        @if($merchant->products->isNotEmpty())
            <div class="vr-category-section" id="category-other">
                <div class="vr-products-grid">
                    @foreach($merchant->products as $product)
                        <article class="vr-card vr-product-card" data-product-card="{{ $product->name }} {{ $product->description }}">
                            <div class="vr-product-image">
                                @if($product->image)
                                    <img src="{{ asset($product->image) }}" alt="{{ $product->name }}">
                                @else
                                    <span aria-hidden="true">🥙</span>
                                @endif
                            </div>
                            <div class="vr-product-body">
                                <h3 class="vr-product-name">{{ $product->name }}</h3>
                                <p class="vr-product-desc">{{ $product->description ?: 'Produit disponible à la commande.' }}</p>
                                <div class="vr-product-bottom">
                                    <span class="vr-price">{{ number_format($product->price, 2, ',', ' ') }} €</span>
                                    <form method="POST" action="{{ route('merchant.cart.add', $product) }}">
                                        @csrf
                                        <button class="vr-btn vr-btn-primary vr-add-btn" type="submit">＋ Ajouter</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endif

        <button class="vr-load-more" type="button" data-scroll-target="#menu">Voir plus de produits ↓</button>
    </div>

    <aside class="vr-card vr-sidebar" aria-label="Votre commande" data-order-sidebar>
        <h2>Votre commande</h2>

        <div data-order-sidebar-content>
        @if($cartItems->isEmpty())
            <div class="vr-empty-cart" data-empty-cart>
                <div style="font-size:38px;" aria-hidden="true">🛍️</div>
                <p>Votre panier est vide.</p>
            </div>
        @else
            @foreach($cartItems as $item)
                <div class="vr-mini-item">
                    <div class="vr-mini-thumb" aria-hidden="true">
                        @if(!empty($cartProductImages[$item['product_id']]))
                            <img src="{{ asset($cartProductImages[$item['product_id']]) }}" alt="">
                        @else
                            🥙
                        @endif
                    </div>
                    <div>
                        <div class="vr-mini-name">{{ $item['name'] }}</div>
                        <div class="vr-mini-qty">x{{ $item['quantity'] }}</div>
                    </div>
                    <div class="vr-mini-price">{{ number_format($item['line_total'], 2, ',', ' ') }} €</div>
                </div>
            @endforeach

            <hr style="border:0;border-top:1px solid var(--veloxi-border);margin:18px 0;">
            <div class="vr-summary-line">
                <span>Sous-total</span>
                <strong>{{ number_format($summary['subtotal'], 2, ',', ' ') }} €</strong>
            </div>
            <div class="vr-summary-line">
                <span>Mode</span>
                <strong>{{ $fulfillmentType === 'pickup' ? 'À emporter' : 'Livraison' }}</strong>
            </div>
            @if($fulfillmentType === 'delivery' && !empty($selectedAddress))
                <div class="vr-info-note vr-mini-address">
                    <strong>Adresse</strong>
                    <span>{{ $selectedAddress->address }}</span>
                </div>
            @endif
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
            <div class="vr-total-line">
                <span>Total</span>
                <strong>{{ number_format($summary['total'], 2, ',', ' ') }} €</strong>
            </div>
            <a href="{{ route('merchant.cart.show') }}" class="vr-btn vr-btn-primary">Voir le panier <span aria-hidden="true">→</span></a>
        @endif
        </div>

        <div class="vr-fast-box">
            <div class="vr-fast-icon" aria-hidden="true">🛵</div>
            <div>
                <div class="vr-fast-title">Livraison rapide</div>
                <div class="vr-muted">Votre commande sera livrée en 15–25 min avec Véloxi.</div>
            </div>
        </div>
    </aside>
</section>
@endsection
