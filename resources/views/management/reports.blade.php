<x-app-layout>

    <div class="sb-page">

        <div class="sb-wrap">

            {{-- PAGE HEADER --}}
            <div class="sb-heading">

                <div>
                    <span class="sb-kicker">ANALYTICS</span>

                    <h1>
                        Business <em>reports.</em>
                    </h1>

                    <p>
                        Reservation, rental, inventory, and payment totals.
                    </p>
                </div>

                <div class="sb-report-toolbar">
                    <form class="sb-report-period" method="GET" action="{{ route('owner.reports') }}"
                        x-data="{ period: '{{ $period['key'] }}' }">
                        <label for="report-period">Period</label>
                        <select id="report-period" name="period" x-model="period">
                            <option value="today">Today</option>
                            <option value="this_week">This week</option>
                            <option value="this_month">This month</option>
                            <option value="this_year">This year</option>
                            <option value="custom">Custom range</option>
                        </select>
                        <div class="sb-report-custom-range" x-show="period === 'custom'">
                            <label for="report-start-date">From</label>
                            <input id="report-start-date" type="date" name="start_date"
                                value="{{ $period['start_date'] }}" x-bind:required="period === 'custom'">
                            <label for="report-end-date">To</label>
                            <input id="report-end-date" type="date" name="end_date" value="{{ $period['end_date'] }}"
                                x-bind:required="period === 'custom'">
                        </div>
                        <button class="sb-report-filter-button" type="submit">Apply</button>
                    </form>

                    <div class="sb-report-actions">
                        <a class="sb-report-export-link"
                            href="{{ route('owner.reports.pdf', request()->only(['period', 'start_date', 'end_date'])) }}">
                            Download PDF
                        </a>
                    </div>
                </div>

            </div>


            {{-- SUMMARY CARDS --}}
            <div class="sb-report-stats">

                {{-- Reservations --}}
                <div class="sb-report-stat">
                    <div class="sb-report-stat-top">
                        <span>Reservations</span>
                        <div class="sb-report-icon"><svg viewBox="0 0 24 24" aria-hidden="true">
                                <use href="#sb-i-calendar" />
                            </svg></div>
                    </div>
                    <strong>{{ $reservationCount }}</strong>
                    <small>{{ $period['label'] }}</small>
                </div>

                {{-- Active rentals (not affected by the period) --}}
                <div class="sb-report-stat">
                    <div class="sb-report-stat-top">
                        <span>Active rentals</span>
                        <div class="sb-report-icon"><svg viewBox="0 0 24 24" aria-hidden="true">
                                <use href="#sb-i-dress" />
                            </svg></div>
                    </div>
                    <strong>{{ $rentalCount }}</strong>
                    <small>Right now · released or overdue</small>
                </div>

                {{-- Payments --}}
                <div class="sb-report-stat">
                    <div class="sb-report-stat-top">
                        <span>Verified payments</span>
                        <div class="sb-report-icon"><svg viewBox="0 0 24 24" aria-hidden="true">
                                <use href="#sb-i-card" />
                            </svg></div>
                    </div>
                    <strong>₱{{ number_format($paymentTotal, 0) }}</strong>
                    <small>Verified in {{ strtolower($period['label']) }}</small>
                </div>

                {{-- Inventory (not affected by the period) --}}
                <div class="sb-report-stat">
                    <div class="sb-report-stat-top">
                        <span>Available gowns</span>
                        <div class="sb-report-icon"><svg viewBox="0 0 24 24" aria-hidden="true">
                                <use href="#sb-i-tag" />
                            </svg></div>
                    </div>
                    <strong>{{ $availableCount }}<span class="sb-stat-total"> / {{ $gownCount }}</span></strong>
                    <small>Right now · available / total gowns</small>
                </div>

            </div>


            {{-- REPORT CONTENT --}}
            <div class="sb-report-grid">

                {{-- MONTHLY RESERVATIONS --}}
                <section class="sb-report-panel sb-monthly-panel">

                    <div class="sb-report-panel-head">

                        <div>
                            <span class="sb-panel-kicker">RESERVATIONS</span>
                            <h2>Monthly reservations</h2>
                            <p>
                                {{ \Illuminate\Support\Carbon::parse($period['start_date'])->format('M j, Y') }} –
                                {{ \Illuminate\Support\Carbon::parse($period['end_date'])->format('M j, Y') }}
                            </p>
                        </div>

                        <div class="sb-chart-label">{{ $reservationCount }} total</div>

                    </div>


                    @if($reservationCount > 0)
                        <div class="sb-chart">
                            @php $maxValue = max(collect($monthly)->max('value') ?? 0, 1); @endphp
                            @foreach($monthly as $month)
                                @php
                                    $isZero = $month['value'] === 0;
                                    $barHeight = $isZero ? 3 : max(14, ($month['value'] / $maxValue) * 135);
                                @endphp
                                <div class="sb-chart-column" title="{{ $month['label'] }}: {{ $month['value'] }}">
                                    @unless($isZero)
                                        <div class="sb-chart-value">{{ $month['value'] }}</div>
                                    @endunless
                                    <div class="sb-chart-bar {{ $isZero ? 'is-zero' : '' }}"
                                        style="height: {{ $barHeight }}px;"></div>
                                    <small>{{ $month['label'] }}</small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="sb-report-empty sb-chart-empty">
                            <h3>No reservation data yet</h3>
                            <p>Reservation activity will appear here once bookings are recorded.</p>
                        </div>
                    @endif

                </section>


                {{-- MOST RESERVED GOWNS --}}
                <section class="sb-report-panel sb-popular-panel">

                    <div class="sb-report-panel-head">
                        <div>
                            <span class="sb-panel-kicker">INVENTORY PERFORMANCE</span>
                            <h2>Most reserved gowns</h2>
                            <p>By reservation count</p>
                        </div>
                    </div>


                    <div class="sb-rank-list">

                        @forelse($popular as $index => $gown)

                            <div class="sb-rank">

                                <div class="sb-rank-number {{ $index === 0 ? 'is-top' : '' }}">
                                    {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                                </div>

                                <div class="sb-rank-thumb">
                                    @if($gown->image)
                                        <img src="{{ $gown->image_url }}" alt="{{ $gown->name }}"
                                            loading="lazy">
                                    @else
                                        <span aria-hidden="true">✿</span>
                                    @endif
                                </div>

                                <div class="sb-rank-info">
                                    <strong>{{ $gown->name }}</strong>
                                    <small>{{ $gown->category->name ?? 'Collection' }}</small>

                                    <div class="sb-rank-bar-track"
                                        aria-label="{{ $gown->reservation_items_count }} reservations">
                                        <span
                                            style="width: {{ max(6, ($gown->reservation_items_count / max($popularMax, 1)) * 100) }}%;"></span>
                                    </div>
                                </div>

                                <div class="sb-rank-count">
                                    <strong>{{ $gown->reservation_items_count }}</strong>
                                    <small>{{ $gown->reservation_items_count === 1 ? 'reservation' : 'reservations' }}</small>
                                </div>

                            </div>

                        @empty

                            <div class="sb-report-empty">
                                <div class="sb-empty-icon"><svg viewBox="0 0 24 24" aria-hidden="true">
                                        <use href="#sb-i-dress" />
                                    </svg></div>
                                <h3>No reservation data yet</h3>
                                <p>Gown popularity will appear here after the first reservation is recorded.</p>
                            </div>

                        @endforelse

                    </div>

                </section>

            </div>

        </div>

    </div>


    {{-- REPORT PAGE STYLES (cream / maroon / gold, same as the rest of the system) --}}
    <style>
        .sb-page {
            --r-cream: #f8f0d6;
            --r-card: #fbf5e3;
            --r-maroon: #6b0020;
            --r-maroon-dark: #4f0018;
            --r-gold: #a67c2e;
            --r-line: #d9c089;
            --r-muted: #7a6a60;
            min-height: 100vh;
            background: var(--r-cream);
            color: #4a3b35;
            font-family: 'DM Sans', sans-serif;
        }

        .sb-wrap {
            max-width: 1180px;
            margin: 0 auto;
            padding: 45px 48px 70px;
        }

        /* HEADER */
        .sb-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 30px;
            margin-bottom: 32px;
        }

        .sb-heading h1 {
            margin: 0;
            color: var(--r-maroon);
            font-family: 'Playfair Display', serif;
            font-size: 44px;
            line-height: 1.05;
            font-weight: 600;
            letter-spacing: -1px;
        }

        .sb-heading h1 em {
            color: var(--r-gold);
            font-style: italic;
            font-weight: 400;
        }

        .sb-heading p {
            margin: 11px 0 0;
            color: var(--r-muted);
            font-size: 15px;
            line-height: 1.5;
        }

        .sb-report-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: flex-end;
            gap: 10px;
        }

        .sb-report-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 9px;
        }

        .sb-report-period {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            min-height: 44px;
            padding: 6px 8px 6px 14px;
            border: 1px solid var(--r-line);
            border-radius: 999px;
            background: var(--r-card);
        }

        .sb-report-period>label,
        .sb-report-custom-range label {
            color: var(--r-gold);
            font: 700 11px 'DM Sans', sans-serif;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .sb-report-period select,
        .sb-report-period input {
            min-height: 32px;
            padding: 5px 10px;
            border: 1px solid var(--r-line);
            border-radius: 999px;
            background: #fff;
            color: #4a3b35;
            font: 12px 'DM Sans', sans-serif;
        }

        /* Period select shows a dropdown arrow so it reads as a dropdown */
        .sb-report-period select {
            appearance: none;
            -webkit-appearance: none;
            padding-right: 30px;
            cursor: pointer;
            background-color: #fff;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23a67c2e' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 11px center;
        }

        .sb-report-custom-range {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }

        .sb-report-filter-button {
            min-height: 32px;
            padding: 0 16px;
            border: 1px solid var(--r-gold);
            border-radius: 999px;
            background: transparent;
            color: var(--r-maroon);
            font: 600 12px 'DM Sans', sans-serif;
            cursor: pointer;
            transition: background .15s ease;
        }

        .sb-report-filter-button:hover {
            background: rgba(166, 124, 46, .12);
        }

        .sb-report-export-link,
        .sb-report-export-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 22px;
            border: 1px solid var(--r-maroon);
            border-radius: 999px;
            background: var(--r-maroon);
            color: #fff;
            font: 600 13px 'DM Sans', sans-serif;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(107, 0, 32, .2);
            transition: background .15s ease;
        }

        .sb-report-export-link:hover,
        .sb-report-export-button:hover {
            background: var(--r-maroon-dark);
            border-color: var(--r-maroon-dark);
        }

        .sb-report-period select:focus,
        .sb-report-period input:focus,
        .sb-report-filter-button:focus-visible,
        .sb-report-export-link:focus-visible {
            outline: 2px solid var(--r-gold);
            outline-offset: 2px;
        }

        /* STAT CARDS: four identical cards with a gold accent strip */
        .sb-report-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 23px;
        }

        .sb-report-stat {
            position: relative;
            min-height: 125px;
            padding: 19px 20px 19px 24px;
            background: var(--r-card);
            border: 1px solid var(--r-line);
            border-radius: 20px;
            box-shadow: 0 10px 26px rgba(107, 0, 32, .08);
            overflow: hidden;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .sb-report-stat::before {
            content: "";
            position: absolute;
            left: 0;
            top: 18px;
            bottom: 18px;
            width: 4px;
            border-radius: 0 4px 4px 0;
            background: #b8902f;
        }

        .sb-report-stat:nth-child(2)::before {
            background: #6b0020;
        }

        .sb-report-stat:nth-child(3)::before {
            background: #2f7d4f;
        }

        .sb-report-stat:nth-child(4)::before {
            background: #c9821a;
        }

        .sb-report-stat:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(107, 0, 32, .12);
        }

        .sb-report-stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .sb-report-stat-top>span {
            color: var(--r-muted);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .sb-report-icon {
            width: 36px;
            height: 36px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: rgba(166, 124, 46, .16);
            color: var(--r-gold);
        }

        .sb-report-icon svg,
        .sb-empty-icon svg {
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .sb-report-stat>strong {
            display: block;
            color: var(--r-maroon);
            font-family: 'DM Sans', sans-serif;
            font-variant-numeric: lining-nums tabular-nums;
            font-size: 30px;
            font-weight: 700;
            line-height: 1.1;
        }

        .sb-stat-total {
            font-size: 18px;
            font-weight: 500;
            color: var(--r-muted);
        }

        .sb-report-stat>small {
            display: block;
            margin-top: 9px;
            color: var(--r-muted);
            font-size: 12px;
            line-height: 1.4;
        }

        /* REPORT GRID: panels size to their content, so a short list is not padded out */
        .sb-report-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 16px;
            align-items: start;
        }

        .sb-report-panel {
            background: var(--r-card);
            border: 1px solid var(--r-line);
            border-radius: 20px;
            box-shadow: 0 10px 26px rgba(107, 0, 32, .08);
            overflow: hidden;
        }

        .sb-report-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 22px 23px 0;
        }

        .sb-panel-kicker {
            display: block;
            margin-bottom: 5px;
            color: var(--r-gold);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
        }

        .sb-report-panel-head h2 {
            margin: 0;
            color: var(--r-maroon);
            font-family: 'Playfair Display', serif;
            font-size: 21px;
            font-weight: 600;
            line-height: 1.3;
        }

        .sb-report-panel-head p {
            margin: 4px 0 0;
            color: var(--r-muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .sb-chart-label {
            padding: 6px 12px;
            border: 1px solid var(--r-line);
            border-radius: 999px;
            background: transparent;
            color: var(--r-maroon);
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        /* CHART: bars are labelled with their value, empty months stay almost invisible */
        .sb-chart {
            height: 225px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 9px;
            padding: 20px 22px 17px;
        }

        .sb-chart-column {
            height: 175px;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            min-width: 0;
        }

        html body .sb-chart-value {
            min-height: 0;
            margin-bottom: 5px;
            padding: 0;
            background: transparent !important;
            color: var(--r-maroon) !important;
            font-size: 12px;
            font-weight: 700;
        }

        .sb-chart-bar {
            width: 17px;
            min-height: 3px;
            border-radius: 5px 5px 2px 2px;
            background: linear-gradient(180deg, #c9a961, #a67c2e) !important;
            transition: height .2s ease, filter .2s ease;
        }

        .sb-chart-bar.is-zero {
            width: 8px;
            height: 3px !important;
            border-radius: 2px;
            background: #eadcb2 !important;
        }

        .sb-chart-column:hover .sb-chart-bar:not(.is-zero) {
            filter: brightness(1.08);
        }

        .sb-chart-column small {
            margin-top: 7px;
            color: var(--r-muted);
            font-size: 11px;
        }

        .sb-chart-empty {
            min-height: 225px;
            margin: 14px 22px 22px;
            border-radius: 12px;
        }

        /* POPULAR GOWNS */
        .sb-rank-list {
            margin-top: 17px;
        }

        .sb-rank {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 14px 23px;
            border-top: 1px solid rgba(217, 192, 137, .5);
            transition: background .15s ease;
        }

        .sb-rank:hover {
            background: rgba(166, 124, 46, .08);
        }

        .sb-rank-number {
            width: 31px;
            height: 31px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: rgba(166, 124, 46, .16);
            color: var(--r-maroon);
            font: 600 13px 'DM Sans', sans-serif;
            font-variant-numeric: lining-nums;
        }

        .sb-rank-number.is-top {
            background: var(--r-maroon);
            color: #f0d79a;
        }

        .sb-rank-thumb {
            flex: 0 0 44px;
            width: 44px;
            height: 58px;
            display: grid;
            place-items: center;
            overflow: hidden;
            border: 1px solid var(--r-line);
            border-radius: 10px;
            background: #f3e6c4;
            color: var(--r-gold);
        }

        .sb-rank-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: top center;
        }

        .sb-rank-info {
            min-width: 0;
            flex: 1;
        }

        .sb-rank-bar-track {
            width: 100%;
            height: 6px;
            overflow: hidden;
            margin-top: 9px;
            border-radius: 99px;
            background: rgba(166, 124, 46, .18);
        }

        .sb-rank-bar-track span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #c9a961, #a67c2e);
        }

        .sb-rank-info strong {
            display: block;
            overflow: hidden;
            color: var(--r-maroon);
            font-size: 14px;
            font-weight: 600;
            line-height: 1.4;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sb-rank-info small {
            display: block;
            margin-top: 3px;
            color: var(--r-muted);
            font-size: 11px;
            line-height: 1.4;
        }

        .sb-rank-count {
            text-align: right;
            white-space: nowrap;
            line-height: 1.2;
        }

        .sb-rank-count strong {
            display: block;
            margin-bottom: 2px;
            color: var(--r-maroon);
            font-size: 18px;
            font-weight: 700;
            font-variant-numeric: lining-nums;
        }

        .sb-rank-count small {
            color: var(--r-muted);
            font-size: 11px;
        }

        /* EMPTY STATE */
        .sb-report-empty {
            min-height: 210px;
            padding: 25px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .sb-empty-icon {
            width: 45px;
            height: 45px;
            display: grid;
            place-items: center;
            margin-bottom: 12px;
            border-radius: 50%;
            background: var(--r-maroon);
            color: #f0d79a;
        }

        .sb-report-empty h3 {
            margin: 0 0 5px;
            color: var(--r-maroon);
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 600;
        }

        .sb-report-empty p {
            max-width: 260px;
            margin: 0;
            color: var(--r-muted);
            font-size: 12px;
            line-height: 1.6;
        }

        /* RESPONSIVE */
        @media (max-width: 1050px) {
            .sb-report-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .sb-report-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 700px) {
            .sb-wrap {
                padding: 30px 20px 50px;
            }

            .sb-heading {
                flex-direction: column;
            }

            .sb-report-toolbar {
                width: 100%;
                justify-content: flex-start;
            }

            .sb-report-period {
                width: 100%;
                border-radius: 20px;
            }

            .sb-report-custom-range {
                width: 100%;
            }

            .sb-report-custom-range input {
                flex: 1;
                min-width: 110px;
            }

            .sb-report-stats {
                grid-template-columns: 1fr;
            }

            .sb-heading h1 {
                font-size: 36px;
            }

            .sb-heading p {
                font-size: 14px;
            }

            .sb-chart {
                padding-left: 12px;
                padding-right: 12px;
                gap: 4px;
            }

            .sb-chart-bar,
            .sb-chart-bar.is-zero {
                width: 12px;
            }

            .sb-report-actions {
                width: 100%;
            }

            .sb-report-export-link,
            .sb-report-export-button {
                flex: 1;
            }
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 12mm;
            }

            .sb-sidebar,
            .sb-sidebar-brand,
            .sb-report-toolbar {
                display: none !important;
            }

            .sb-main-column,
            .sb-main-content {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .sb-page {
                min-height: auto;
                background: #fff !important;
            }

            .sb-wrap {
                max-width: none;
                padding: 0;
            }

            .sb-report-grid {
                grid-template-columns: 1.6fr 1fr;
            }

            .sb-report-panel,
            .sb-report-stat {
                box-shadow: none;
                break-inside: avoid;
            }

            .sb-chart-bar {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</x-app-layout>