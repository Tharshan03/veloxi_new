@extends('merchant.layout')

@section('title', 'Mes commandes — Véloxi')

@section('content')
<section class="vr-container vr-page-card vr-card">
    <h1>Mes commandes</h1>

    @if($orders->isEmpty())
        <p class="vr-muted">Aucune commande pour le moment.</p>
    @else
        <div class="vr-order-list">
            @foreach($orders as $order)
                <a class="vr-order-row" href="{{ route('merchant.account.orders.show', $order) }}">
                    <span>#{{ $order->id }}</span>
                    <strong>{{ $order->merchant?->name }}</strong>
                    <span>{{ $order->created_at?->format('d/m/Y H:i') }}</span>
                    <span>{{ $order->fulfillment_type === 'pickup' ? 'À emporter' : 'Livraison' }}</span>
                    <span>{{ $order->status }}</span>
                    <strong>{{ number_format($order->total ?: $order->total_amount, 2, ',', ' ') }} €</strong>
                </a>
            @endforeach
        </div>

        {{ $orders->links() }}
    @endif
</section>
@endsection
