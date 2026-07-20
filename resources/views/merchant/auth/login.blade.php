@extends('merchant.layout')

@section('title', 'Connexion client — Véloxi')

@section('content')
<section style="max-width:520px;margin:32px auto;" class="card">
    <div style="padding:24px;">
        <h1>Connexion client</h1>
        <p class="muted">Connectez-vous avec votre compte Véloxi pour finaliser la commande.</p>
        <form method="POST" action="{{ route('merchant.login.store') }}">
            @csrf
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div class="field">
                <label>Mot de passe</label>
                <input type="password" name="password" required>
            </div>
            <button class="btn btn-primary" type="submit">Se connecter</button>
        </form>
        <p class="muted" style="margin-top:18px;">Pas encore de compte ? <a href="{{ route('merchant.register') }}"><strong>Créer un compte</strong></a></p>
    </div>
</section>
@endsection
