<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 25mm 17mm 23mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #382d30;
            font: 9pt "DejaVu Sans", sans-serif;
            line-height: 1.45;
        }

        .running-header {
            position: fixed;
            top: -17mm;
            left: 0;
            right: 0;
            padding-bottom: 4mm;
            border-bottom: 1px solid #d8c5bf;
            color: #6f5157;
            font-size: 8pt;
        }

        .running-header table,
        .running-header td {
            border: 0;
            padding: 0;
        }

        .running-header td:last-child {
            text-align: right;
            color: #8a7777;
        }

        .kicker {
            color: #a46c59;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 1.4pt;
            text-transform: uppercase;
        }

        h1,
        h2,
        h3,
        p {
            margin-top: 0;
        }

        h1 {
            margin-bottom: 3mm;
            color: #5c1a2b;
            font: bold 28pt "DejaVu Serif", serif;
        }

        h2 {
            margin: 0 0 3mm;
            color: #5c1a2b;
            font: bold 17pt "DejaVu Serif", serif;
        }

        h3 {
            margin: 0 0 2mm;
            color: #6d3440;
            font: bold 11pt "DejaVu Serif", serif;
        }

        .subhead {
            color: #796d6b;
            font-size: 10pt;
        }

        .rule {
            height: 0;
            margin: 8mm 0;
            border: 0;
            border-top: 1px solid #c8a080;
        }

        .cover {
            min-height: 235mm;
            text-align: center;
        }

        .brand-logo {
            display: block;
            width: 34mm;
            height: 34mm;
            margin: 8mm auto 5mm;
            object-fit: contain;
        }

        .brand-name {
            margin-bottom: 2mm;
            color: #5c1a2b;
            font: bold 15pt "DejaVu Serif", serif;
        }

        .brand-tagline {
            color: #8b706c;
            font-size: 8pt;
            letter-spacing: 1.1pt;
            text-transform: uppercase;
        }

        .cover-title {
            margin-top: 13mm;
            color: #5c1a2b;
            font: bold 29pt "DejaVu Serif", serif;
        }

        .cover-period {
            margin: 2mm 0 0;
            color: #a85d69;
            font: bold 15pt "DejaVu Serif", serif;
        }

        .cover-range {
            margin-top: 3mm;
            color: #756769;
            font-size: 10pt;
        }

        .executive {
            margin: 10mm 0 5mm;
            text-align: left;
        }

        .executive-copy {
            margin: 2mm 0 7mm;
            color: #665b5d;
            font-size: 9pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            padding: 2.5mm 2mm;
            background: #f6efeb;
            color: #815b56;
            font-size: 7.5pt;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            padding: 2.5mm 2mm;
            border-bottom: 1px solid #eadfda;
            vertical-align: top;
        }

        .metric-grid td {
            width: 50%;
            padding: 4mm 5mm;
            border: 1px solid #eadfda;
        }

        .metric-label {
            display: block;
            color: #806e6e;
            font-size: 8pt;
            text-transform: uppercase;
        }

        .metric-value {
            display: block;
            margin-top: 1mm;
            color: #5c1a2b;
            font: bold 19pt "DejaVu Serif", serif;
        }

        .prepared {
            margin-top: 9mm;
            text-align: left;
            color: #74696b;
            font-size: 8pt;
        }

        .confidential {
            margin-top: 14mm;
            color: #a46c59;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 2pt;
            text-align: center;
        }

        .page-break {
            page-break-after: always;
        }

        .section-head {
            margin-bottom: 6mm;
            padding-bottom: 3mm;
            border-bottom: 2px solid #7f2c40;
        }

        .section-head p {
            margin: 0;
            color: #796d6b;
        }

        .summary-strip {
            margin: 4mm 0 7mm;
        }

        .summary-strip td {
            width: 25%;
            padding: 3mm;
            border-right: 1px solid #e8ded9;
        }

        .summary-strip td:last-child {
            border-right: 0;
        }

        .summary-strip strong {
            display: block;
            margin-top: 1mm;
            color: #5c1a2b;
            font: bold 14pt "DejaVu Serif", serif;
        }

        .chart-table td {
            padding: 1.5mm 2mm;
        }

        .chart-bar-track {
            height: 4mm;
            background: #f2e9e5;
        }

        .chart-bar {
            height: 4mm;
            min-width: 1mm;
            background: #7d283d;
        }

        .chart-count {
            width: 16mm;
            text-align: right;
            font-weight: bold;
        }

        .compact td {
            padding-top: 2mm;
            padding-bottom: 2mm;
        }

        .rank {
            width: 10mm;
            color: #a46c59;
            font-weight: bold;
        }

        .right {
            text-align: right;
        }

        .amount {
            white-space: nowrap;
            text-align: right;
        }

        .status {
            color: #6e2839;
            font-weight: bold;
        }

        .small {
            color: #796d6b;
            font-size: 8pt;
        }

        .utilization {
            margin: 5mm 0;
            padding: 3mm;
            border-left: 3px solid #a46c59;
            background: #faf5f2;
        }

        .note {
            margin: 4mm 0;
            color: #76696a;
            font-size: 8pt;
        }

        .details-table {
            font-size: 7.5pt;
        }

        .details-table th {
            font-size: 7pt;
        }

        .details-table td {
            padding: 2mm 1.5mm;
            overflow-wrap: anywhere;
        }

        .empty {
            padding: 7mm 2mm;
            color: #8b7e7d;
            text-align: center;
        }

        .block {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>
    <div class="running-header">
        <table>
            <tr>
                <td><strong>{{ $shopName }}</strong></td>
                <td>BUSINESS REPORT · {{ strtoupper($period['label']) }}</td>
            </tr>
        </table>
    </div>

    <section class="cover">
        @if($logoDataUri)<img class="brand-logo" src="{{ $logoDataUri }}" alt="{{ $shopName }} logo">@endif
        <div class="brand-name">{{ $shopName }}</div>
        <div class="brand-tagline">Gown Reservation &amp; Rental Management</div>
        <div class="cover-title">BUSINESS REPORT</div>
        <div class="cover-period">{{ $period['label'] }}</div>
        <div class="cover-range">{{ \Carbon\Carbon::parse($period['start_date'])->format('F j, Y') }} –
            {{ \Carbon\Carbon::parse($period['end_date'])->format('F j, Y') }}
        </div>
        <hr class="rule">

        <div class="executive">
            <span class="kicker">Executive summary</span>
            <p class="executive-copy">This report summarizes reservation activity, gown inventory, rental operations,
                and recorded payments for the selected reporting period.</p>
            <table class="metric-grid">
                <tr>
                    <td><span class="metric-label">Reservations</span><strong
                            class="metric-value">{{ number_format($reservationCount) }}</strong></td>
                    <td><span class="metric-label">Active rentals</span><strong
                            class="metric-value">{{ number_format($activeRentalCount) }}</strong></td>
                </tr>
                <tr>
                    <td><span class="metric-label">Verified payments</span><strong
                            class="metric-value">₱{{ number_format($paymentTotal, 2) }}</strong></td>
                    <td><span class="metric-label">Available gowns</span><strong
                            class="metric-value">{{ $availableCount }} / {{ $gownCount }}</strong></td>
                </tr>
            </table>
            <div class="prepared">
                <strong>Prepared for:</strong> Business Owner<br>
                @if($contactEmail)<strong>Contact:</strong> {{ $contactEmail }}<br>@endif
                <strong>Generated:</strong> {{ $generatedAt->format('F j, Y, g:i A') }}
            </div>
        </div>
        <div class="confidential">CONFIDENTIAL · FOR BUSINESS USE</div>
    </section>

    <div class="page-break"></div>
    <section>
        <div class="section-head"><span class="kicker">01 · Reservations</span>
            <h2>Reservation performance</h2>
            <p>Reporting period: {{ $period['start_date'] }} to {{ $period['end_date'] }}</p>
        </div>
        <table class="summary-strip">
            <tr>
                <td><span class="metric-label">Total</span><strong>{{ number_format($reservationCount) }}</strong></td>
                <td><span class="metric-label">Average /
                        month</span><strong>{{ number_format($averageReservationsPerMonth, 2) }}</strong></td>
                <td><span class="metric-label">Upcoming</span><strong>{{ number_format($upcomingCount) }}</strong></td>
                <td><span class="metric-label">Cancelled</span><strong>{{ number_format($cancelledCount) }}</strong>
                </td>
            </tr>
        </table>
        <h3>Monthly reservations</h3>
        <table class="chart-table">
            <thead>
                <tr>
                    <th>Month</th>
                    <th>Reservation activity</th>
                    <th class="right">Count</th>
                </tr>
            </thead>
            <tbody>
                @php $monthlyMax = max(collect($monthly)->max('value') ?? 0, 1); @endphp
                @foreach($monthly as $month)
                    <tr>
                        <td>{{ $month['label'] }}</td>
                        <td>
                            <div class="chart-bar-track">
                                <div class="chart-bar"
                                    style="width: {{ $month['value'] > 0 ? max(2, ($month['value'] / $monthlyMax) * 100) : 0 }}%">
                                </div>
                            </div>
                        </td>
                        <td class="chart-count">{{ $month['value'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <h3 style="margin-top:7mm">Reservation summary</h3>
        <table class="compact">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th class="right">Result</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total reservations</td>
                    <td class="right">{{ $reservationCount }}</td>
                </tr>
                <tr>
                    <td>Completed reservations</td>
                    <td class="right">{{ $completedCount }}</td>
                </tr>
                <tr>
                    <td>Upcoming reservations</td>
                    <td class="right">{{ $upcomingCount }}</td>
                </tr>
                <tr>
                    <td>Cancelled reservations</td>
                    <td class="right">{{ $cancelledCount }}</td>
                </tr>
            </tbody>
        </table>
    </section>

    <div class="page-break"></div>
    <section>
        <div class="section-head"><span class="kicker">02 · Inventory</span>
            <h2>Inventory performance</h2>
            <p>Gown utilization and reservation activity</p>
        </div>
        <h3>Most reserved gowns</h3>
        <table class="compact">
            <thead>
                <tr>
                    <th class="rank">Rank</th>
                    <th>Gown</th>
                    <th>Category</th>
                    <th class="right">Reservations</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($popular as $index => $gown)
                    <tr>
                        <td class="rank">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $gown->name }}<br><span class="small">{{ $gown->gown_code }}</span></td>
                        <td>{{ $gown->category->name ?? 'Collection' }}</td>
                        <td class="right">{{ $gown->reservation_items_count }}</td>
                        <td class="status">{{ ucfirst(str_replace('_', ' ', $gown->status)) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="5">No reservation activity recorded during this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <h3 style="margin-top:8mm">Inventory overview</h3>
        <table class="compact">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th class="right">Result</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total gowns</td>
                    <td class="right">{{ $gownCount }}</td>
                </tr>
                <tr>
                    <td>Available</td>
                    <td class="right">{{ $availableCount }}</td>
                </tr>
                <tr>
                    <td>Currently rented</td>
                    <td class="right">{{ $rentedCount }}</td>
                </tr>
                <tr>
                    <td>Reserved</td>
                    <td class="right">{{ $reservedCount }}</td>
                </tr>
                <tr>
                    <td>Cleaning / maintenance</td>
                    <td class="right">{{ $maintenanceCount }}</td>
                </tr>
                <tr>
                    <td>Availability rate</td>
                    <td class="right">{{ number_format($availabilityRate, 1) }}%</td>
                </tr>
            </tbody>
        </table>
        <div class="utilization">Available inventory <strong>{{ $availableCount }} of {{ $gownCount }}</strong>
            <div class="chart-bar-track" style="margin-top:2mm">
                <div class="chart-bar" style="width: {{ $availabilityRate }}%"></div>
            </div>
        </div>
    </section>

    <div class="page-break"></div>
    <section>
        <div class="section-head"><span class="kicker">03 · Finance and operations</span>
            <h2>Payments &amp; rental activity</h2>
            <p>Recorded payment activity and rental status during the reporting period</p>
        </div>
        <table class="summary-strip">
            <tr>
                <td><span class="metric-label">Verified
                        payments</span><strong>₱{{ number_format($paymentTotal, 2) }}</strong></td>
                <td><span class="metric-label">Transactions</span><strong>{{ number_format($paymentCount) }}</strong>
                </td>
                <td><span class="metric-label">Active
                        rentals</span><strong>{{ number_format($activeRentalCount) }}</strong></td>
                <td><span class="metric-label">Overdue</span><strong>{{ number_format($overdueRentalCount) }}</strong>
                </td>
            </tr>
        </table>
        <h3>Payment status</h3>
        <table class="compact">
            <thead>
                <tr>
                    <th>Status</th>
                    <th class="right">Transactions</th>
                    <th class="amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach(['verified' => 'Verified', 'pending' => 'Pending', 'rejected' => 'Rejected', 'refunded' => 'Refunded'] as $status => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="right">{{ $payments->where('status', $status)->count() }}</td>
                        <td class="amount">₱{{ number_format($paymentStatusTotals->get($status, 0), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <h3 style="margin-top:8mm">Rental activity</h3>
        <table class="compact">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Active rentals (released / overdue)</td>
                    <td class="right">{{ $activeRentalCount }}</td>
                </tr>
                <tr>
                    <td>Released rentals</td>
                    <td class="right">{{ $releasedRentalCount }}</td>
                </tr>
                <tr>
                    <td>Overdue returns</td>
                    <td class="right">{{ $overdueRentalCount }}</td>
                </tr>
            </tbody>
        </table>
        <p class="note">Only verified payments are included in the verified payment total. Pending transactions remain
            subject to staff review.</p>
    </section>

    <div class="page-break"></div>
    <section>
        <div class="section-head"><span class="kicker">04 · Appendix</span>
            <h2>Detailed records</h2>
            <p>Reservations and payment transactions created during the reporting period</p>
        </div>
        <h3>Reservation details</h3>
        <table class="details-table">
            <thead>
                <tr>
                    <th>Reservation</th>
                    <th>Customer</th>
                    <th>Gown</th>
                    <th>Rental dates</th>
                    <th class="amount">Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reservations as $reservation)
                    <tr>
                        <td>{{ $reservation->reservation_code }}</td>
                        <td>{{ $reservation->customer?->full_name ?? 'Guest' }}</td>
                        <td>{{ $reservation->items->map(fn($item) => $item->gown?->name)->filter()->join(', ') ?: 'Gown' }}
                        </td>
                        <td>{{ $reservation->pickup_date?->format('M d, Y') }} –
                            {{ $reservation->return_date?->format('M d, Y') }}
                        </td>
                        <td class="amount">₱{{ number_format($reservation->grand_total, 2) }}</td>
                        <td class="status">{{ ucfirst(str_replace('_', ' ', $reservation->status)) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="6">No reservations were created during this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <h3 style="margin-top:8mm">Payment transactions</h3>
        <table class="details-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Reservation</th>
                    <th>Type</th>
                    <th>Method</th>
                    <th class="amount">Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr>
                        <td>{{ $payment->created_at?->format('M d, Y') }}</td>
                        <td>{{ $payment->customer?->full_name ?? 'Customer' }}</td>
                        <td>{{ $payment->reservation?->reservation_code ?? '—' }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_type)) }}</td>
                        <td>{{ ucfirst($payment->payment_method) }}</td>
                        <td class="amount">₱{{ number_format($payment->amount, 2) }}</td>
                        <td class="status">{{ ucfirst($payment->status) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="7">No payment transactions were recorded during this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</body>

</html>