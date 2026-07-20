@extends('merchant.dashboard.layout')

@section('title', 'Connexion commerçant — Véloxi')

@section('content')
<main class="md-auth">
    <section class="md-card md-auth-card">
        <h1>Connexion commerçant</h1>
        <p>Connectez-vous avec votre compte Véloxi commerçant.</p>
        <form method="POST" action="{{ route('merchant.dashboard.login.store') }}" class="md-form">
            @csrf
            <label>
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </label>
            <label>
                <span>Mot de passe</span>
                <input type="password" name="password" required>
            </label>
            <button type="submit" class="md-btn md-btn-primary">Se connecter</button>
        </form>
    </section>
</main>
@endsection
