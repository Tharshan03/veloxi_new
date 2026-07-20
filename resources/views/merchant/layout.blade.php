<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Véloxi Restaurant')</title>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/merchant-restaurant.css') }}?v={{ filemtime(public_path('css/merchant-restaurant.css')) }}">
    @stack('styles')
</head>
@php
    $layoutCart = session('merchant_cart', ['items' => []]);
    $layoutCartItems = collect($layoutCart['items'] ?? []);
    $layoutCartCount = (int) $layoutCartItems->sum('quantity');
    $layoutCartSubtotal = round((float) $layoutCartItems->sum('line_total'), 2);
    $layoutCartTotal = round((float) ($layoutCart['total'] ?? $layoutCartSubtotal), 2);
    $layoutMerchant = $merchant ?? null;

    if (!$layoutMerchant && !empty($layoutCart['merchant_id'])) {
        $layoutMerchant = \App\Models\Merchant::query()
            ->where('status', 1)
            ->find($layoutCart['merchant_id']);
    }

    if (!$layoutMerchant && session('merchant_home_slug')) {
        $layoutMerchant = \App\Models\Merchant::query()
            ->where('status', 1)
            ->where('slug', session('merchant_home_slug'))
            ->first();
    }

    $layoutHomeUrl = $layoutMerchant
        ? route('merchant.public.show', $layoutMerchant)
        : url('/');
@endphp
<body>
    <header class="vr-header">
        <div class="vr-container vr-nav">
            <a href="{{ $layoutHomeUrl }}" class="vr-logo" aria-label="Accueil Véloxi">
                <img src="{{ asset('images/logo.png') }}" alt="Véloxi">
                <span class="vr-logo-text">Vélo<span>xi</span></span>
            </a>

            <nav class="vr-menu" data-merchant-menu aria-label="Navigation restaurant">
                <a href="{{ $layoutHomeUrl }}">Accueil</a>
                <a href="#menu" data-scroll-target="#menu" class="is-active">Menu</a>
                <a href="#about" data-scroll-target="#about">À propos</a>
                <a href="#reviews" data-scroll-target="#reviews">Avis</a>
                <a href="#contact" data-scroll-target="#contact">Contact</a>
            </nav>

            <div class="vr-actions">
                <a class="vr-btn vr-btn-cart" href="{{ route('merchant.cart.show') }}" aria-label="Voir le panier" data-cart-link>
                    🛍️ Panier (<span data-cart-count>{{ $layoutCartCount }}</span>)
                    <span class="vr-badge-count {{ $layoutCartCount > 0 ? '' : 'is-hidden' }}" data-cart-badge>{{ $layoutCartCount }}</span>
                </a>

                @auth
                    <a class="vr-btn vr-btn-light vr-account-link" href="{{ route('merchant.account.orders.index') }}">Mon compte</a>
                @else
                    <a class="vr-btn vr-btn-light vr-account-link" href="{{ route('merchant.login') }}">Connexion</a>
                @endauth

                <button class="vr-burger" type="button" data-merchant-burger aria-label="Ouvrir le menu" aria-expanded="false">
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    @if (session('success'))
        <div class="vr-alert vr-alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="vr-alert">{{ $errors->first() }}</div>
    @endif

    @yield('content')

    <section class="vr-container vr-merchant-cta" id="about">
        <div class="vr-merchant-icon" aria-hidden="true">🏪</div>
        <div>
            <h3>Vous êtes un commerçant ?</h3>
            <p>Créez votre site de commande avec Véloxi et commencez à recevoir des commandes en ligne.</p>
        </div>
        <a href="#" class="vr-btn vr-btn-light">En savoir plus</a>
    </section>

    <footer class="vr-footer" id="contact">
        <div class="vr-container">
            <div class="vr-footer-grid">
                <div>
                    <a href="{{ $layoutHomeUrl }}" class="vr-footer-logo">
                        <img src="{{ asset('images/logo.png') }}" alt="Véloxi">
                    </a>
                    <p>Véloxi connecte les commerçants, les clients et les livreurs locaux pour des livraisons rapides et fiables.</p>
                    <div class="vr-socials" aria-label="Réseaux sociaux">
                        <span>f</span><span>◎</span><span>♪</span>
                    </div>
                </div>
                <div>
                    <h4>Liens utiles</h4>
                    <a href="{{ $layoutHomeUrl }}">Accueil</a>
                    <a href="#menu" data-scroll-target="#menu">Menu</a>
                    <a href="#about" data-scroll-target="#about">À propos</a>
                    <a href="#contact" data-scroll-target="#contact">Contact</a>
                    <a href="#">Devenir livreur</a>
                </div>
                <div>
                    <h4>Infos</h4>
                    <a href="#">FAQ</a>
                    <a href="#">CGU</a>
                    <a href="{{ route('privacypolicy') }}">Politique de confidentialité</a>
                    <a href="#">Mentions légales</a>
                </div>
                <div>
                    <h4>Téléchargez l’application</h4>
                    <a class="vr-store-badge" href="#" aria-label="Disponible sur Google Play">
                        ▶ <span><small>Disponible sur</small>Google Play</span>
                    </a>
                    <a class="vr-store-badge" href="#" aria-label="Télécharger dans l'App Store">
                         <span><small>Télécharger dans</small>l’App Store</span>
                    </a>
                </div>
            </div>
            <div class="vr-copyright">© 2024 Véloxi — Tous droits réservés.</div>
        </div>
    </footer>

    <a href="{{ route('merchant.cart.show') }}" class="vr-btn vr-btn-primary vr-mobile-cart {{ $layoutCartCount > 0 ? '' : 'is-hidden' }}" data-mobile-cart>
        <span>Panier · <span data-mobile-cart-count>{{ $layoutCartCount }}</span> article(s)</span>
        <strong data-mobile-cart-total>{{ number_format($layoutCartTotal, 2, ',', ' ') }} €</strong>
    </a>

    <script src="{{ asset('js/merchant-restaurant.js') }}?v={{ filemtime(public_path('js/merchant-restaurant.js')) }}" defer></script>
    @stack('scripts')
</body>
</html>
