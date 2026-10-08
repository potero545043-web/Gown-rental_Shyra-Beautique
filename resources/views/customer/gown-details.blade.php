<x-app-layout>
<div class="sb-page"><div class="sb-wrap">
    <div class="sb-heading"><div><span class="sb-kicker">{{ strtoupper($gown->category->name ?? 'THE COLLECTION') }}</span><h1>{{ $gown->name }}</h1><p>{{ $gown->gown_code }} · {{ $gown->color ?? 'Occasionwear' }}</p></div><a class="sb-outline-btn" href="{{ route(auth()->user()->role.'.catalog') }}">Back to collection</a></div>
    <div class="sb-detail-grid">
        <div class="sb-detail-photo" style="background-image:url('{{ $gown->image_url }}')"><span>{{ ucfirst(str_replace('_',' ',$gown->status)) }}</span></div>
        <section class="sb-panel sb-detail-info"><span class="sb-kicker">GOWN DETAILS</span><h2>{{ $gown->name }}</h2><div class="sb-detail-price">₱{{ number_format($gown->rental_price,2) }} <small>/ rental</small></div><p>{{ $gown->description ?: 'Contact the boutique for fitting and rental questions.' }}</p>
            <div class="sb-detail-facts"><div><small>SIZE</small><b>{{ $gown->size ?: 'Ask our team' }}</b></div><div><small>COLOR</small><b>{{ $gown->color ?: 'See photos' }}</b></div><div><small>CONDITION</small><b>{{ ucfirst($gown->condition) }}</b></div></div>
            @if(auth()->user()->role === 'customer' && in_array($gown->status, ['available','reserved','rented'], true))
                <a class="sb-btn sb-detail-cta" href="{{ route('customer.reserve',$gown) }}">Choose rental dates <span>→</span></a>
            @elseif(in_array(auth()->user()->role, ['owner','employee'], true) && $gown->status === 'available')
                <a class="sb-btn sb-detail-cta" href="{{ route(auth()->user()->role.'.catalog.reserve',$gown) }}">Start reservation <span>→</span></a>
            @elseif(auth()->user()->role === 'employee')
                <p class="sb-detail-staff">Internal catalog view · {{ $gown->status === 'available' ? 'Available in collection' : 'Check upcoming reservation dates' }}</p>
            @else
                <p class="sb-detail-staff">This piece is currently unavailable for new reservations.</p>
            @endif
        </section>
    </div>
</div></div>
</x-app-layout>
