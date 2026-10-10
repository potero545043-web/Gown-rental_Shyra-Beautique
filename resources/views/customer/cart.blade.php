<x-app-layout>
    @php $gownCount = $gowns->count(); @endphp
    <div class="sb-page sb-cart-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">MY CART</span>
                    <h1>Your <em>cart.</em></h1>
                    <p>Every gown you have added waits here. Reserve them all at once with a single agreement and
                        payment.</p>
                </div>
                <a class="sb-outline-btn" href="{{ route('customer.catalog') }}">Continue browsing</a>
            </div>

            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif

            @if($gowns->isEmpty())
                <div class="sb-panel sb-empty sb-cart-empty">
                    <span class="sb-cart-empty-icon">
                        <svg>
                            <use href="#sb-i-cart" />
                        </svg>
                    </span>
                    <h2>Your cart is empty</h2>
                    <p>Browse the collection and use the cart button on a gown to add it here. You can add as many as
                        you need for one event.</p>
                    <a class="sb-btn" href="{{ route('customer.catalog') }}">Browse the collection <span
                            aria-hidden="true">&rarr;</span></a>
                </div>
            @else
                <div class="sb-cart-layout">
                    <div class="sb-cart-items">
                        <div class="sb-cart-items-head">
                            <h2>{{ $gownCount }} gown{{ $gownCount === 1 ? '' : 's' }} in your cart</h2>
                            <form method="POST" action="{{ route('customer.cart.clear') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="sb-cart-clear">Clear cart</button>
                            </form>
                        </div>

                        @foreach($gowns as $gown)
                            <article class="sb-cart-row">
                                <a class="sb-cart-row-photo" style="background-image:url('{{ $gown->image_url }}')"
                                    href="{{ route('customer.gowns.show', $gown) }}" aria-label="{{ $gown->name }}"></a>
                                <div class="sb-cart-row-info">
                                    <span class="sb-kicker">{{ strtoupper($gown->category->name ?? 'THE COLLECTION') }}</span>
                                    <h3>{{ $gown->name }}</h3>
                                    <small>{{ $gown->gown_code }} · Size {{ $gown->size ?? 'Various' }} ·
                                        {{ $gown->color ?? 'See photos' }}</small>
                                    <span
                                        class="sb-cart-row-availability is-{{ $gown->status }}">{{ ucfirst(str_replace('_', ' ', $gown->status)) }}</span>
                                </div>
                                <div class="sb-cart-row-price">
                                    <b>₱{{ number_format((float) $gown->rental_price, 2) }}</b>
                                    <small>/ rental</small>
                                </div>
                                <form method="POST" action="{{ route('customer.cart.remove', $gown) }}"
                                    class="sb-cart-row-remove">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" aria-label="Remove {{ $gown->name }}" title="Remove">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </form>
                            </article>
                        @endforeach
                    </div>

                    <aside class="sb-cart-summary">
                        <h2>Reservation summary</h2>
                        <div class="sb-cart-summary-rows">
                            <div><span>Gowns</span><b>{{ $gownCount }}</b></div>
                            <div><span>Rental period</span><b>Up to {{ $maxRentalDays }} days</b></div>
                            <div><span>Daily late fee</span><b>₱{{ number_format($lateFeePerDay, 2) }}</b></div>
                        </div>
                        <div class="sb-cart-summary-total">
                            <span>Rental total</span><b>₱{{ number_format($rentalTotal, 2) }}</b>
                        </div>
                        <a class="sb-btn sb-cart-checkout" href="{{ route('customer.reserve') }}">
                            Reserve {{ $gownCount }} gown{{ $gownCount === 1 ? '' : 's' }} <span
                                aria-hidden="true">&rarr;</span>
                        </a>
                        <p class="sb-cart-summary-note">You will choose the pickup and return dates next. Availability
                            is checked for every gown before the request is submitted.</p>
                    </aside>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>