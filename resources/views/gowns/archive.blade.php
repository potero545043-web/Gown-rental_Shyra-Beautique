<x-app-layout>

    <div class="sb-page sb-inventory-page">
        <div class="sb-wrap">

            {{-- PAGE HEADER --}}
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">INVENTORY · ARCHIVE</span>
                    <h1>Archived <em>gowns.</em></h1>
                    <p>Gowns removed from active inventory. Restore any piece to make it rentable again.</p>
                </div>
                <a class="sb-inventory-secondary" href="{{ route('owner.gowns.index') }}">Back to collection</a>
            </div>

            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="sb-form-errors" role="alert">{{ $errors->first() }}</div>
            @endif

            <section class="sb-panel">
                <div class="sb-inventory-content">

                    <div class="sb-inventory-toolbar">
                        <div>
                            <span class="sb-kicker">ARCHIVED GOWNS</span>
                            <h2>Archive</h2>
                            <p>{{ $gowns->total() }} {{ \Illuminate\Support\Str::plural('gown', $gowns->total()) }}
                                archived</p>
                        </div>
                    </div>

                    {{-- FILTERS --}}
                    <form method="GET" class="sb-inventory-filter" data-live-filter>
                        <input type="hidden" name="per_page" value="{{ $gowns->perPage() }}">
                        <div class="sb-inventory-search">
                            <input type="search" name="q" value="{{ request('q') }}"
                                placeholder="Search archived gowns, code, or color" aria-label="Search archived gowns"
                                data-live-search>
                        </div>

                        <select name="category" aria-label="Filter by category" data-live-apply>
                            <option value="">All categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(request('category') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>

                    </form>

                    @if($gowns->isEmpty())
                        <div class="sb-inventory-empty">
                            <div class="sb-empty-icon">🗃</div>
                            <b>No archived gowns</b>
                            <p>Gowns you archive from the collection will appear here.</p>
                        </div>
                    @else
                        <div class="sb-inventory-table-wrap">
                            <table class="sb-inventory-table sb-gown-table">
                                <thead>
                                    <tr>
                                        <th>Gown</th>
                                        <th>Category</th>
                                        <th>Size</th>
                                        <th>Rental price</th>
                                        <th>Archived</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($gowns as $gown)
                                        <tr>
                                            <td data-label="Gown">
                                                <div class="sb-inventory-name">
                                                    <div class="sb-inventory-thumb">
                                                        @if($gown->image)
                                                            <img src="{{ $gown->image_url }}" alt="{{ $gown->name }}"
                                                                loading="lazy">
                                                        @else
                                                            <span aria-hidden="true">✿</span>
                                                        @endif
                                                    </div>
                                                    <span>
                                                        <b>{{ $gown->name }}</b>
                                                        <small>{{ $gown->gown_code }} ·
                                                            {{ ucfirst($gown->color ?: 'Color not set') }}</small>
                                                    </span>
                                                </div>
                                            </td>
                                            <td data-label="Category">{{ $gown->category->name ?? 'Uncategorized' }}</td>
                                            <td data-label="Size">{{ $gown->size ?: '—' }}</td>
                                            <td data-label="Rental price"><strong
                                                    class="sb-rental-price">₱{{ number_format($gown->rental_price, 2) }}</strong>
                                            </td>
                                            <td data-label="Archived">
                                                <span
                                                    class="sb-inventory-status is-disabled">{{ $gown->archived_at?->toFormattedDateString() ?? '—' }}</span>
                                            </td>
                                            <td data-label="Actions">
                                                <div class="sb-inventory-actions">
                                                    <a href="{{ route('owner.gowns.show', $gown) }}"
                                                        class="sb-action-view">View</a>
                                                    <form method="POST" action="{{ route('owner.gowns.restore', $gown) }}"
                                                        data-confirm-title="Restore"
                                                        data-confirm-accent="this gown?"
                                                        data-confirm-message="Are you sure you want to restore this gown? It will return to active inventory."
                                                        data-confirm-yes="Yes, restore"
                                                        data-confirm-no="Keep archived">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="sb-action-restore" type="submit">Restore</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @include('components.table-pagination', ['paginator' => $gowns, 'itemLabel' => 'gowns'])
                    @endif

                </div>
            </section>

        </div>
    </div>

</x-app-layout>