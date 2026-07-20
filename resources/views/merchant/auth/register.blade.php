@extends('merchant.layout')

@section('title', 'Créer un compte client — Véloxi')

@section('content')
<section style="max-width:560px;margin:32px auto;" class="card">
    <div style="padding:24px;">
        <h1>Créer un compte Véloxi</h1>
        <p class="muted">Un compte unique pour commander sur les sites commerçants Véloxi.</p>
        <form method="POST" action="{{ route('merchant.register.store') }}">
            @csrf
            <div class="field">
                <label>Nom</label>
                <input type="text" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div class="field">
                <label>Téléphone</label>
                <input type="text" name="contact_number" value="{{ old('contact_number') }}">
            </div>
            <div class="field">
                <label>Mot de passe</label>
                <input type="password" name="password" required>
            </div>
            <div class="field">
                <label>Confirmation du mot de passe</label>
                <input type="password" name="password_confirmation" required>
            </div>
            <button class="btn btn-primary" type="submit">Créer mon compte</button>
        </form>
        <p class="muted" style="margin-top:18px;">Déjà un compte ? <a href="{{ route('merchant.login') }}"><strong>Se connecter</strong></a></p>
    </div>
</section>
@endsection
