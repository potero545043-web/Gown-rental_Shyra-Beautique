<x-app-layout>

    <div class="sb-page">

        <div class="sb-wrap">
            <div class="sb-heading">

                <div>

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
                            href="{{ route('owner.reports.pdf', request()->only(['period', 'start_date', 'end_date'])) }}">Download
                            PDF</a>
                    </div>
                </div>

            </div>


            {{-- =========================================
            SUMMARY CARDS
            ========================================== --}}
            <div class="sb-report-stats">

                {{-- Reservations --}}
                <div class="sb-report-stat">

                    <div class="sb-report-stat-top">
                        <span>Reservations</span>

                        <div class="sb-report-icon">
                            ◷
                        </div>
                    </div>

                    <strong>
                        {{ $reservationCount }}
                    </strong>

                    <small>
                        {{ $period['label'] }}
                    </small>

                </div>


                {{-- Active Rentals --}}
                <div class="sb-report-stat">

                    <div class="sb-report-stat-top">
                        <span>Active rentals</span>

                        <div class="sb-report-icon">
                            ♡
                        </div>
                    </div>

                    <strong>
                        {{ $rentalCount }}
                    </strong>

                    <small>
                        Released or overdue
                    </small>

                </div>


                {{-- Payments --}}
                <div class="sb-report-stat sb-report-stat-gold">

                    <div class="sb-report-stat-top">
                        <span>Verified payments</span>

                        <div class="sb-report-icon">
                            ₱
                        </div>
                    </div>

                    <strong>
                        ₱{{ number_format($paymentTotal, 0) }}
                    </strong>

                    <small>
                        Verified in {{ strtolower($period['label']) }}
                    </small>

                </div>


                {{-- Inventory --}}
                <div class="sb-report-stat">

                    <div class="sb-report-stat-top">
                        <span>Available gowns</span>

                        <div class="sb-report-icon">
                            ♧
                        </div>
                    </div>

                    <strong>
                        {{ $availableCount }}
                        <span class="sb-stat-total">
                            / {{ $gownCount }}
                        </span>
                    </strong>

                    <small>
                        Available / total gowns
                    </small>

                </div>

            </div>


            {{-- =========================================
            REPORT CONTENT
            ========================================== --}}
            <div class="sb-report-grid">


                {{-- =====================================
                MONTHLY RESERVATIONS
                ====================================== --}}
                <section class="sb-report-panel sb-monthly-panel">

                    <div class="sb-report-panel-head">

                        <div>

                            <span class="sb-panel-kicker">
                                RESERVATIONS
                            </span>

                            <h2>
                                Monthly reservations
                            </h2>

                            <p>{{ $period['start_date'] }} to {{ $period['end_date'] }}</p>

                        </div>

                        <div class="sb-chart-label">
                            {{ $reservationCount }} total
                        </div>

                    </div>


                    @if($reservationCount > 0)
                        <div class="sb-chart">
                            @php $maxValue = max(collect($monthly)->max('value') ?? 0, 1); @endphp
                            @foreach($monthly as $month)
                                @php $barHeight = max(12, ($month['value'] / $maxValue) * 135); @endphp
                                <div class="sb-chart-column">
                                    <div class="sb-chart-value">{{ $month['value'] }}</div>
                                    <div class="sb-chart-bar" style="height: {{ $barHeight }}px;"></div>
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

                            <span class="sb-panel-kicker">
                                INVENTORY PERFORMANCE
                            </span>

                            <h2>
                                Most reserved gowns
                            </h2>

                            <p>
                                By reservation count
                            </p>

                        </div>

                    </div>


                    @forelse($popular as $index => $gown)

                        <div class="sb-rank">

                            <div class="sb-rank-number">
                                {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}
                            </div>

                            <div class="sb-rank-info">

                                <strong>
                                    {{ $gown->name }}
                                </strong>

                                <small>
                                    {{ $gown->category->name ?? 'Collection' }}
                                </small>

                                <div class="sb-rank-bar-track"
                                    aria-label="{{ $gown->reservation_items_count }} reservations">
                                    <span
                                        style="width: {{ max(6, ($gown->reservation_items_count / max($popularMax, 1)) * 100) }}%;"></span>
                                </div>

                            </div>

                            <div class="sb-rank-count">
                                <strong>
                                    {{ $gown->reservation_items_count }}
                                </strong>

                                <small>
                                    reservations
                                </small>
                            </div>

                        </div>

                    @empty

                        <div class="sb-report-empty">

                            <div class="sb-empty-icon">
                                ♡
                            </div>

                            <h3>
                                No reservation data yet
                            </h3>

                            <p>
                                Gown popularity will appear here after
                                the first reservation is recorded.
                            </p>

                        </div>

                    @endforelse

                </section>

            </div>

        </div>

    </div>


    {{-- REPORT PAGE STYLES --}}

    <style>
        /* PAGE */

        .sb-page {
            min-height: 100vh;
            background: #fcf8f3;
            color: #4b3938;
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

        .sb-report-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 9px;
        }

        .sb-report-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-start;
            justify-content: flex-end;
            gap: 10px;
        }

        .sb-report-period {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 7px;
            padding: 7px 9px;
            border: 1px solid #eaded2;
            border-radius: 9px;
            background: #fffdf9;
        }

        .sb-report-period>label,
        .sb-report-custom-range label {
            color: #806b61;
            font: 600 11px 'DM Sans', sans-serif;
        }

        .sb-report-period select,
        .sb-report-period input {
            min-height: 32px;
            padding: 5px 8px;
            border: 1px solid #e7d9cc;
            border-radius: 6px;
            background: #fff;
            color: #4a3037;
            font: 12px 'DM Sans', sans-serif;
        }

        .sb-report-custom-range {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
        }

        .sb-report-filter-button {
            min-height: 32px;
            padding: 0 11px;
            border: 0;
            border-radius: 6px;
            background: #f3e8e2;
            color: #641d35;
            font: 600 11px 'DM Sans', sans-serif;
            cursor: pointer;
        }

        .sb-report-export-link,
        .sb-report-export-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 15px;
            border: 1px solid #641d35;
            border-radius: 8px;
            background: #641d35;
            color: #fff;
            font: 600 12px 'DM Sans', sans-serif;
            text-decoration: none;
            cursor: pointer;
        }

        .sb-report-export-link:hover,
        .sb-report-export-button:hover {
            background: #51162a;
            border-color: #51162a;
        }

        .sb-report-kicker {
            display: block;
            margin-bottom: 14px;
            color: #c79b59;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 2px;
        }

        .sb-heading h1 {
            margin: 0;
            color: #641d35;
            font-family: 'Playfair Display', serif;
            font-size: 44px;
            line-height: 1.05;
            font-weight: 600;
            letter-spacing: -1px;
        }

        .sb-heading h1 em {
            color: #b56d78;
            font-style: italic;
            font-weight: 400;
        }

        .sb-heading p {
            margin: 11px 0 0;
            color: #98776d;
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            line-height: 1.5;
        }


        /* DASHBOARD BUTTON */

        .sb-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 43px;
            padding: 0 20px;
            border-radius: 9px;
            background: #641d35;
            border: 1px solid #641d35;
            color: #fff;
            text-decoration: none;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(100, 29, 53, 0.13);
            transition: .2s ease;
        }

        .sb-btn:hover {
            background: #51162a;
            border-color: #51162a;
        }


        /* STAT CARDS */

        .sb-report-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 23px;
        }

        .sb-report-stat {
            position: relative;
            min-height: 125px;
            padding: 19px 20px;
            background: #fffdf9;
            border: 1px solid #eaded2;
            border-radius: 13px;
            box-shadow: 0 7px 22px rgba(83, 52, 42, .035);
        }

        .sb-report-stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .sb-report-stat-top>span {
            color: #66706b;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 500;
        }

        .sb-report-icon {
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: #fff0e8;
            color: #b76652;
            font-size: 16px;
        }

        .sb-report-stat-gold .sb-report-icon {
            background: #f6ecd9;
            color: #a57b35;
        }

        .sb-report-stat>strong {
            display: block;
            color: #39423c;
            font-family: 'Playfair Display', serif;
            font-size: 27px;
            font-weight: 600;
            line-height: 1.1;
        }

        .sb-stat-total {
            font-size: 20px;
            color: #746761;
        }

        .sb-report-stat>small {
            display: block;
            margin-top: 9px;
            color: #999087;
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }


        /* REPORT GRID */

        .sb-report-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 16px;
        }

        .sb-report-panel {
            background: #fffdf9;
            border: 1px solid #eaded2;
            border-radius: 14px;
            box-shadow: 0 7px 24px rgba(83, 52, 42, .04);
            overflow: hidden;
        }


        /* PANEL HEADER */

        .sb-report-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 22px 23px 0;
        }

        .sb-panel-kicker {
            display: block;
            margin-bottom: 5px;
            color: #b38b7d;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1.5px;
        }

        .sb-report-panel-head h2 {
            margin: 0;
            color: #64263a;
            font-family: 'Playfair Display', serif;
            font-size: 21px;
            font-weight: 600;
            line-height: 1.3;
        }

        .sb-report-panel-head p {
            margin: 4px 0 0;
            color: #977b71;
            font-family: 'DM Sans', sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }

        .sb-chart-label {
            padding: 6px 10px;
            border-radius: 20px;
            background: #f5eee7;
            color: #8b7167;
            font-family: 'DM Sans', sans-serif;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
        }


        /* CHART */

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

        .sb-chart-value {
            min-height: 15px;
            margin-bottom: 5px;
            color: #7f8880;
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            font-weight: 500;
        }

        .sb-chart-bar {
            width: 17px;
            min-height: 5px;
            border-radius: 5px 5px 2px 2px;
            background: #8d9c94;
            transition: height .2s ease;
        }

        .sb-chart-bar-empty {
            background: #d8dcd8;
        }

        .sb-chart-empty {
            min-height: 225px;
        }

        .sb-chart-column small {
            margin-top: 7px;
            color: #96928d;
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
        }


        /* POPULAR GOWNS */

        .sb-popular-panel {
            min-height: 300px;
        }

        .sb-rank {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 15px 23px;
            border-top: 1px solid #f0e7df;
        }

        .sb-rank:first-of-type {
            margin-top: 17px;
        }

        .sb-rank-number {
            width: 31px;
            height: 31px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: #f5e8df;
            color: #8d5a65;
            font-family: 'Playfair Display', serif;
            font-size: 13px;
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
            background: #f2eae3;
        }

        .sb-rank-bar-track span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: #9b6570;
        }

        .sb-rank-info strong {
            display: block;
            overflow: hidden;
            color: #604048;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.4;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sb-rank-info small {
            display: block;
            margin-top: 3px;
            color: #a18b83;
            font-family: 'DM Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
        }

        .sb-rank-count {
            text-align: right;
            white-space: nowrap;
        }

        .sb-rank-count strong {
            display: block;
            color: #64263a;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px;
            font-weight: 600;
        }

        .sb-rank-count small {
            color: #a18b83;
            font-family: 'DM Sans', sans-serif;
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
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            border-radius: 50%;
            background: #f5e8df;
            color: #8d5a65;
            font-size: 18px;
        }

        .sb-report-empty h3 {
            margin: 0 0 5px;
            color: #64263a;
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-weight: 600;
        }

        .sb-report-empty p {
            max-width: 260px;
            margin: 0;
            color: #98827a;
            font-family: 'DM Sans', sans-serif;
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

            .sb-chart-bar {
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