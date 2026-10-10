<x-app-layout>

    <div class="sb-page sb-inventory-page sb-care-log-page">
        <div class="sb-wrap">

            {{-- PAGE HEADER --}}
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">INVENTORY · CARE LOG</span>

                    <h1>Damage <em>reports.</em></h1>

                    <p>Track damage, repair estimates, and gown condition in one place.</p>
                </div>

                @if($base === 'owner')
                    <button class="sb-inventory-primary sb-inventory-filter-add" type="button"
                        onclick="document.getElementById('damage-create-dialog').showModal()">
                        <span aria-hidden="true">＋</span>
                        Report damage
                    </button>
                @endif
            </div>


            {{-- MESSAGES --}}
            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="sb-form-errors">{{ $errors->first() }}</div>
            @endif


            {{-- STAT CARDS --}}
            <div class="sb-stat-grid sb-inventory-ledger-stats">
                <article class="sb-stat">
                    <span>Damage reports</span>
                    <b>{{ $totalCount }}</b>
                    <small>Logged across all rentals</small>
                    <i><svg viewBox="0 0 24 24" aria-hidden="true">
                            <use href="#sb-i-spark" />
                        </svg></i>
                </article>
                <article class="sb-stat">
                    <span>Final repair cost</span>
                    <b>&#8369;{{ number_format($totalFinalCost, 2) }}</b>
                    <small>Confirmed repair fees</small>
                    <i><svg viewBox="0 0 24 24" aria-hidden="true">
                            <use href="#sb-i-card" />
                        </svg></i>
                </article>
            </div>


            {{-- DAMAGE PANEL --}}
            <section class="sb-panel">

                <div class="sb-inventory-content">

                    {{-- PANEL HEADER --}}
                    <div class="sb-inventory-toolbar">

                        <div>
                            <span class="sb-kicker">DAMAGE LOG</span>

                            <h2>Recorded damage</h2>

                            <p>
                                {{ $totalCount }}
                                {{ \Illuminate\Support\Str::plural('report', $totalCount) }}
                            </p>
                        </div>

                        <form class="sb-filterbar sb-ledger-filter" method="GET" data-live-filter>
                            <input type="hidden" name="per_page" value="{{ $damages->perPage() }}">
                            <input type="search" name="q" value="{{ request('q') }}"
                                placeholder="Search gown or reservation" aria-label="Search damage reports">
                            <select name="severity" aria-label="Filter by damage severity">
                                <option value="">All severities</option>
                                @foreach(['minor', 'moderate', 'major', 'severe'] as $severity)
                                    <option value="{{ $severity }}" @selected(request('severity') === $severity)>
                                        {{ ucfirst($severity) }}
                                    </option>
                                @endforeach
                            </select>
                            <button class="sb-btn" type="submit">Filter</button>
                            @if(request()->filled('q') || request()->filled('severity'))
                                <a class="sb-filter-clear" href="{{ route($base . '.damages') }}">Clear</a>
                            @endif
                        </form>

                    </div>


                    {{-- TABLE --}}
                    <div class="sb-inventory-table-wrap">

                        <table class="sb-inventory-table sb-damage-table">

                            <thead>
                                <tr>
                                    <th>Gown</th>
                                    <th>Damage</th>
                                    <th>Reservation</th>
                                    <th>Estimated cost</th>
                                    <th>Final cost</th>
                                    <th>Severity</th>
                                    <th>Discovered</th>
                                    <th>Evidence</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($damages as $damage)

                                    @php
                                        $severityClass = match ($damage->severity) {
                                            'minor' => 'is-available',
                                            'moderate' => 'is-reserved',
                                            'major', 'severe' => 'is-damaged',
                                            default => 'is-disabled',
                                        };
                                    @endphp

                                    <tr>

                                        {{-- GOWN --}}
                                        <td data-label="Gown">
                                            <div class="sb-inventory-name">
                                                <div class="sb-inventory-thumb">
                                                    @if($damage->gown && $damage->gown->image)
                                                        <img src="{{ $damage->gown->image_url }}"
                                                            alt="{{ $damage->gown->name }}" loading="lazy">
                                                    @else
                                                        <span aria-hidden="true">✿</span>
                                                    @endif
                                                </div>
                                                <span>
                                                    <b>{{ $damage->gown->name ?? 'Removed gown' }}</b>
                                                    <small>{{ $damage->gown->gown_code ?? '' }}</small>
                                                </span>
                                            </div>
                                        </td>


                                        {{-- DAMAGE --}}
                                        <td data-label="Damage" class="sb-category-desc">
                                            <b class="sb-work-type">{{ $damage->damage_type }}</b>
                                            <span>{{ $damage->description ?: 'No description recorded' }}</span>
                                        </td>


                                        {{-- RESERVATION --}}
                                        <td data-label="Reservation">
                                            @if($damage->reservation ?? $damage->gownReturn?->reservation)
                                                <strong>{{ ($damage->reservation ?? $damage->gownReturn?->reservation)->reservation_code }}</strong>
                                            @else
                                                <span class="sb-cell-sub">—</span>
                                            @endif
                                        </td>


                                        {{-- ESTIMATED REPAIR COST --}}
                                        <td data-label="Estimated cost">
                                            <strong class="sb-rental-price">
                                                @if($damage->estimated_repair_cost !== null)
                                                    &#8369;{{ number_format($damage->estimated_repair_cost, 2) }}
                                                @else
                                                    <span class="sb-cell-sub">Not estimated</span>
                                                @endif
                                            </strong>
                                        </td>

                                        {{-- FINAL REPAIR COST --}}
                                        <td data-label="Final cost">
                                            @php
                                                $finalCost = $damage->final_repair_cost
                                                    ?? ($damage->repair_cost > 0 ? $damage->repair_cost : null);
                                            @endphp
                                            @if($finalCost !== null)
                                                <strong class="sb-rental-price">&#8369;{{ number_format($finalCost, 2) }}</strong>
                                            @else
                                                <details class="sb-damage-final-cost">
                                                    <summary>Record final cost</summary>
                                                    <form method="POST"
                                                        action="{{ route('owner.damages.final-cost', $damage) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <label>
                                                            Final cost (PHP)
                                                            <input type="number" name="final_repair_cost" min="0"
                                                                max="1000000" step="0.01" required>
                                                        </label>
                                                        <button class="sb-inventory-primary" type="submit">Save cost</button>
                                                    </form>
                                                </details>
                                            @endif
                                        </td>


                                        {{-- SEVERITY --}}
                                        <td data-label="Severity">
                                            <span class="sb-inventory-status sb-gown-status {{ $severityClass }}">
                                                {{ ucfirst($damage->severity) }}
                                            </span>
                                        </td>


                                        {{-- DATE DISCOVERED --}}
                                        <td data-label="Discovered">
                                            {{ $damage->discovered_at?->format('M d, Y') ?? $damage->created_at?->format('M d, Y') }}
                                        </td>

                                        {{-- PRIVATE PHOTO EVIDENCE --}}
                                        <td data-label="Evidence">
                                            @if($damage->photos)
                                                <a class="sb-damage-evidence" href="{{ route('owner.damages.photo', $damage) }}"
                                                    target="_blank" rel="noopener">
                                                    <img src="{{ route('owner.damages.photo', $damage) }}"
                                                        alt="Damage evidence for {{ $damage->gown?->name ?? 'gown' }}"
                                                        loading="lazy">
                                                    <span>View photo</span>
                                                </a>
                                            @else
                                                <span class="sb-cell-sub">None</span>
                                            @endif
                                        </td>


                                        {{-- ACTIONS --}}
                                        <td data-label="Actions">
                                            @if($base === 'owner')
                                                <div class="sb-inventory-actions">
                                                    <form method="POST" action="{{ route('owner.damages.destroy', $damage) }}"
                                                        onsubmit="return confirm('Remove this damage report?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="sb-retire" type="submit">Remove</button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="sb-cell-sub">View only</span>
                                            @endif
                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="9">
                                            <div class="sb-inventory-empty">
                                                <b>No damage recorded</b>
                                                <p>Damage flagged during a gown return will appear here for review.</p>
                                            </div>
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- PAGINATION --}}
                    @include('components.table-pagination', ['paginator' => $damages, 'itemLabel' => 'reports'])

                </div>

            </section>

            @if($base === 'owner')
                <dialog class="sb-side-drawer" id="damage-create-dialog"
                    aria-labelledby="damage-create-title"
                    onclick="if (event.target === this) this.close()">
                    <div class="sb-side-drawer-head">
                        <div>
                            <span class="sb-kicker">INVENTORY · CARE LOG</span>
                            <h2 id="damage-create-title">Report gown damage</h2>
                            <p>Record what happened and whether the gown should be taken out of circulation.</p>
                        </div>
                        <button class="sb-side-drawer-close" type="button" aria-label="Close form"
                            onclick="document.getElementById('damage-create-dialog').close()">&times;</button>
                    </div>

                    <form method="POST" action="{{ route('owner.damages.store') }}"
                        class="sb-side-drawer-form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="_inventory_drawer" value="damage">

                        @if($errors->any() && old('_inventory_drawer') === 'damage')
                            <div class="sb-form-errors">{{ $errors->first() }}</div>
                        @endif

                        <label>Gown
                            <select name="gown_id" required>
                                <option value="">Select a gown</option>
                                @foreach($gowns as $gown)
                                    <option value="{{ $gown->id }}" @selected(old('gown_id') == $gown->id)>
                                        {{ $gown->gown_code }} · {{ $gown->name }} ({{ str_replace('_', ' ', $gown->status) }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('gown_id')" />
                        </label>

                        <label>Rental reference <span class="sb-cell-sub">(optional)</span>
                            <select name="reservation_id">
                                <option value="">No rental linked</option>
                                @foreach($reservations as $reservation)
                                    <option value="{{ $reservation->id }}" @selected(old('reservation_id') == $reservation->id)>
                                        {{ $reservation->reservation_code }} · {{ $reservation->customer?->full_name ?? 'Customer' }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('reservation_id')" />
                        </label>

                        <label>Damage description
                            <textarea name="description" rows="4" maxlength="5000" required
                                placeholder="Describe the damage and where it is located">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" />
                        </label>

                        <div class="sb-damage-form-row">
                            <label>Severity
                                <select name="severity" required>
                                    @foreach(['minor', 'moderate', 'major'] as $severity)
                                        <option value="{{ $severity }}" @selected(old('severity', 'minor') === $severity)>
                                            {{ ucfirst($severity) }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('severity')" />
                            </label>

                            <label>Date discovered
                                <input type="date" name="discovered_at"
                                    value="{{ old('discovered_at', today()->format('Y-m-d')) }}"
                                    max="{{ today()->format('Y-m-d') }}" required>
                                <x-input-error :messages="$errors->get('discovered_at')" />
                            </label>
                        </div>

                        <label>Estimated repair cost (PHP)
                            <input type="number" name="estimated_repair_cost" min="0" max="1000000"
                                step="0.01" value="{{ old('estimated_repair_cost') }}"
                                placeholder="Leave blank if not estimated yet">
                            <x-input-error :messages="$errors->get('estimated_repair_cost')" />
                            <small>Record the confirmed final cost from the damage log after repair.</small>
                        </label>

                        <label>Photo evidence <span class="sb-cell-sub">(optional, JPG/PNG/WebP up to 5 MB)</span>
                            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                            <x-input-error :messages="$errors->get('photo')" />
                        </label>

                        <label class="sb-damage-unavailable">
                            <input type="checkbox" name="mark_unavailable" value="1"
                                @checked(old('mark_unavailable'))>
                            <span>
                                <b>Mark gown unavailable</b>
                                <small>Set its condition and status to damaged while it needs repair.</small>
                            </span>
                        </label>

                        <div class="sb-side-drawer-actions">
                            <button class="sb-side-drawer-cancel" type="button"
                                onclick="document.getElementById('damage-create-dialog').close()">Cancel</button>
                            <button class="sb-inventory-primary" type="submit">Save damage report</button>
                        </div>
                    </form>
                </dialog>

                @if($errors->any() && old('_inventory_drawer') === 'damage')
                    <script>
                        document.getElementById('damage-create-dialog').showModal();
                    </script>
                @endif
            @endif

        </div>
    </div>

</x-app-layout>