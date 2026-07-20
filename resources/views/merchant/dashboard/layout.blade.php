<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard commerçant — Véloxi')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/merchant-dashboard.css') }}?v={{ file_exists(public_path('css/merchant-dashboard.css')) ? filemtime(public_path('css/merchant-dashboard.css')) : time() }}">
</head>
<body>
    <header class="md-header">
        <div class="md-container md-nav">
            <a href="{{ route('merchant.orders.index') }}" class="md-brand">
                <img src="{{ asset('images/logo.png') }}" alt="Véloxi">
                <span>Espace commerçant</span>
            </a>
            <nav class="md-actions">
                <a href="{{ route('merchant.orders.index') }}">Commandes</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Déconnexion</button>
                </form>
            </nav>
        </div>
    </header>

    @if(session('success'))
        <div class="md-container md-alert md-alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="md-container md-alert">{{ $errors->first() }}</div>
    @endif

    @yield('content')
</body>
</html>
