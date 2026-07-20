@extends('merchant.dashboard.layout')

@section('title', 'Aucun commerce associé — Véloxi')

@section('content')
<main class="md-container md-page">
    <section class="md-card md-empty-state">
        <h1>Aucun commerce associé</h1>
        <p>Votre compte possède le rôle commerçant, mais aucun restaurant actif ne lui est associé.</p>
        <p>Contactez l’équipe Véloxi pour rattacher votre compte à un commerce.</p>
    </section>
</main>
@endsection
