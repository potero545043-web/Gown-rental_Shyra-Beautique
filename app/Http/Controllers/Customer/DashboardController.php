<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Gown, Customer, Payment, Reservation, SystemSetting};
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    /** Longest rental window measured from the pickup date. */
    public const MAX_RENTAL_DAYS = 3;

    /** Bump when the rental terms change so acceptances stay auditable. */
    public const AGREEMENT_VERSION = 'v1.0';

    public function index()
    {
        $customer = Customer::where('user_id', auth()->id())->first();
        $rentals = $customer ? Reservation::with('items.gown')
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'awaiting_payment', 'confirmed', 'ready_for_pickup', 'released', 'overdue'])
            ->where(fn($query) => $query->whereDate('return_date', '>=', today())
                ->orWhereIn('status', ['released', 'overdue']))
            ->orderBy('return_date')
            ->orderBy('pickup_date')
            ->take(5)
            ->get() : collect();
        $gownOptions = Gown::where('status', '!=', 'retired')->get(['size', 'style', 'color']);

        return view('customer.dashboard', [
            'featured' => Gown::with('category')->whereIn('status', ['available', 'reserved', 'rented'])->latest()->take(4)->get(),
            'rentals' => $rentals,
            'filterOptions' => [
                'sizes' => $gownOptions->pluck('size')->filter()->unique()->sort()->values(),
                'styles' => $gownOptions->pluck('style')->filter()->unique()->sort()->values(),
                'colors' => $gownOptions->pluck('color')->filter()->unique()->sort()->values(),
            ],
        ]);
    }

    public function catalog()
    {
        $gowns = Gown::with('category')->where('status', '!=', 'retired')->latest()->get();
        return view('customer.catalog', compact('gowns'));
    }

    public function details(Gown $gown)
    {
        abort_if($gown->status === 'retired', 404);
        $gown->load('category', 'accessories');
        return view('customer.gown-details', compact('gown'));
    }

    public function reserve(Gown $gown)
    {
        abort_unless(in_array($gown->status, ['available', 'reserved', 'rented'], true), 404);
        $gown->load('category');
        $lateFeePerDay = (float) SystemSetting::where('setting_key', 'late_fee_per_day')->value('setting_value');
        return view('customer.reserve', [
            'gown' => $gown,
            'lateFeePerDay' => $lateFeePerDay,
            'maxRentalDays' => self::MAX_RENTAL_DAYS,
            'agreementVersion' => self::AGREEMENT_VERSION,
        ]);
    }

    /**
     * Live availability check backing step 1, so the customer cannot continue
     * on dates the gown is already spoken for (cleaning buffer included).
     */
    public function checkAvailability(Request $request, Gown $gown)
    {
        abort_unless(in_array($gown->status, ['available', 'reserved', 'rented'], true), 404);

        $data = $request->validate([
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after_or_equal:pickup_date'],
        ]);

        $pickup = Carbon::parse($data['pickup_date'])->startOfDay();
        $return = Carbon::parse($data['return_date'])->startOfDay();
        $days = $pickup->diffInDays($return);
        $conflicts = $this->conflictsFor($gown, $pickup, $return);

        $reason = null;
        if ($days + 1 > self::MAX_RENTAL_DAYS) {
            $reason = 'A gown may be rented for up to ' . self::MAX_RENTAL_DAYS . ' days from its pickup date.';
        } elseif ($conflicts) {
            $reason = 'This gown is already booked ' . $conflicts[0]['from']->toFormattedDateString()
                . ' \u2013 ' . $conflicts[0]['through']->toFormattedDateString()
                . '. Please pick different dates.';
        }

        return response()->json([
            'available' => $reason === null,
            'reason' => $reason,
            'summary' => $reason === null ? 'Available for your dates \u00b7 Pickup ' . $pickup->toFormattedDateString()
                . ' \u00b7 Return ' . $return->toFormattedDateString() . ' \u00b7 Subject to staff approval' : null,
            'rental_days' => $days + 1,
            'conflicts' => array_map(fn($block) => [
                'from' => $block['from']->toDateString(),
                'through' => $block['through']->toDateString(),
                'status' => $block['status'],
            ], $conflicts),
        ]);
    }

    public function storeReservation(Request $request, Gown $gown)
    {
        $contactNumber = preg_replace('/\D/', '', (string) $request->input('contact_number', ''));
        $request->merge(['contact_number' => $contactNumber]);

        $data = $request->validate([
            'contact_number' => ['required', 'digits:11', 'regex:/^09\d{9}$/'],
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after_or_equal:pickup_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'bust' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'waist' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'hips' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'agreement_accepted' => ['accepted'],
            'payment_method' => ['required', Rule::in(['cash'])],
            'payment_option' => ['required', Rule::in(['full', 'downpayment'])],
            'payment_amount' => ['required', 'numeric', 'gt:0'],
        ], [
            'contact_number.digits' => 'Format: 09XXXXXXXXX (11 digits).',
            'contact_number.regex' => 'Format: 09XXXXXXXXX (11 digits).',
        ]);
        if (Carbon::parse($data['return_date'])->gt(Carbon::parse($data['pickup_date'])->addDays(self::MAX_RENTAL_DAYS - 1))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'return_date' => 'A gown may be rented for up to ' . self::MAX_RENTAL_DAYS . ' days from its pickup date.',
            ]);
        }
        $lateFeePerDay = (float) SystemSetting::where('setting_key', 'late_fee_per_day')->value('setting_value');
        if ($lateFeePerDay <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages(['return_date' => 'The shop must configure a late fee per day before accepting reservations.']);
        }
        $user = $request->user();
        $measurements = collect([
            'Bust' => $data['bust'] ?? null,
            'Waist' => $data['waist'] ?? null,
            'Hips' => $data['hips'] ?? null,
        ])->filter(fn($value) => $value !== null)
            ->map(fn($value, $key) => $key . ': ' . $value . ' cm')->values()->join('; ');
        $customer = Customer::updateOrCreate(['user_id' => $user->id], [
            'customer_code' => Customer::where('user_id', $user->id)->value('customer_code') ?? 'CUS-' . strtoupper(Str::random(8)),
            'full_name' => $user->name,
            'contact_number' => $data['contact_number'],
            'email' => $user->email,
            'status' => 'active',
        ]);

        try {
            $reservation = DB::transaction(function () use ($request, $gown, $customer, $user, $data, $measurements, $lateFeePerDay) {
                $lockedGown = Gown::whereKey($gown->id)->lockForUpdate()->firstOrFail();
                abort_unless(in_array($lockedGown->status, ['available', 'reserved', 'rented'], true), 422, 'This gown cannot be reserved at this time.');
                $conflicts = $this->conflictsFor($lockedGown, Carbon::parse($data['pickup_date']), Carbon::parse($data['return_date']));
                if ($conflicts) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['pickup_date' => 'This gown is already reserved for some or all of those dates. Please choose another date range.']);
                }

                $rentalFee = (float) $lockedGown->rental_price;
                $paymentAmount = (float) $data['payment_amount'];
                if ($paymentAmount > $rentalFee) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['payment_amount' => 'Payment cannot be greater than the rental fee.']);
                }
                if ($data['payment_option'] === 'downpayment' && $paymentAmount <= 500) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['payment_amount' => 'A down payment must be greater than ₱500.00 and less than the rental fee.']);
                }
                if ($data['payment_option'] === 'full' && round($paymentAmount, 2) !== round($rentalFee, 2)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['payment_amount' => 'Full payment must equal the rental fee. Choose down payment to enter a smaller amount.']);
                }
                if ($data['payment_option'] === 'downpayment' && $paymentAmount >= $rentalFee) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['payment_amount' => 'A down payment must be greater than zero and less than the full rental fee.']);
                }

                $booking = Reservation::create([
                    'reservation_code' => 'GR-' . now()->format('Y') . '-' . strtoupper(Str::random(6)),
                    'customer_id' => $customer->id,
                    'created_by' => $user->id,
                    'pickup_date' => $data['pickup_date'],
                    'return_date' => $data['return_date'],
                    'rental_total' => $rentalFee,
                    'security_deposit_total' => 0,
                    'late_fee_per_day' => $lateFeePerDay,
                    'grand_total' => $rentalFee,
                    'amount_paid' => 0,
                    'balance' => $rentalFee,
                    'status' => 'pending',
                    'customer_notes' => $data['notes'] ?? null,
                    'measurements' => $measurements ?: null,
                    'agreement_version' => self::AGREEMENT_VERSION,
                    'agreement_accepted_at' => now(),
                    'agreement_accepted_ip' => $request->ip(),
                    'payment_reference_number' => null,
                    'collateral_status' => 'not_received',
                ]);
                $booking->items()->create([
                    'gown_id' => $lockedGown->id,
                    'rental_price' => $rentalFee,
                    'security_deposit' => 0,
                    'quantity' => 1,
                ]);
                Payment::create([
                    'reservation_id' => $booking->id,
                    'customer_id' => $customer->id,
                    'payment_reference' => 'SBP-' . now()->format('ymd') . '-' . strtoupper(Str::random(6)),
                    'payment_type' => $data['payment_option'] === 'full' ? 'rental_balance' : 'downpayment',
                    'payment_method' => $data['payment_method'],
                    'amount' => $paymentAmount,
                    'status' => 'pending',
                    'remarks' => 'Cash payment awaiting staff confirmation.',
                ]);
                return $booking;
            });
        } catch (QueryException $exception) {
            report($exception);
            return back()->withInput()->withErrors([
                'reservation' => 'We could not submit your reservation (RES-500). Your request was not saved. Please try again or contact the boutique.',
            ]);
        }
        return redirect()->route('customer.reservations.show', $reservation)->with('success', 'Reservation ' . $reservation->reservation_code . ' submitted. Staff will review your agreement and payment.');
    }

    public function reservations()
    {
        $customer = Customer::where('user_id', auth()->id())->first();
        $reservations = $customer ? Reservation::with(['items.gown', 'payments', 'gownReturn', 'gownRelease', 'penalties'])
            ->where('customer_id', $customer->id)
            ->latest()
            ->get() : collect();

        return view('customer.reservations', [
            'reservations' => $reservations,
            'lifecycles' => $reservations->mapWithKeys(fn($reservation) => [$reservation->id => $this->lifecycleFor($reservation)]),
        ]);
    }

    public function showReservation(Reservation $reservation)
    {
        $customer = Customer::where('user_id', auth()->id())->firstOrFail();
        abort_unless($reservation->customer_id === $customer->id, 404);
        $reservation->load(['items.gown', 'payments', 'gownReturn', 'gownRelease', 'penalties', 'cancelledBy']);
        $cancellationHistory = $reservation->status === 'cancelled'
            ? AuditLog::where('description', 'like', '%' . $reservation->reservation_code . '%')->oldest()->get()
            : collect();

        return view('customer.reservation-show', [
            'reservation' => $reservation,
            'lifecycle' => $this->lifecycleFor($reservation),
            'cancellationHistory' => $cancellationHistory,
        ]);
    }

    public function cancelReservation(Request $request, Reservation $reservation)
    {
        $customer = Customer::where('user_id', $request->user()->id)->firstOrFail();
        abort_unless($reservation->customer_id === $customer->id, 404);

        DB::transaction(function () use ($reservation, $request) {
            $locked = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, ['pending', 'awaiting_payment'], true), 422, 'Only requests that staff have not confirmed can be cancelled here. Please contact the boutique for help with a confirmed booking.');
            $verifiedDownPayments = $locked->payments()
                ->where('payment_type', 'downpayment')
                ->where('status', 'verified')
                ->get();
            $retainedAmount = (float) $verifiedDownPayments->sum('amount');
            $now = now();

            $locked->update([
                'status' => 'cancelled',
                'cancelled_by' => $request->user()->id,
                'cancelled_at' => $now,
                'cancellation_reason' => 'Customer cancelled',
                'cancellation_refund_amount' => 0,
            ]);

            foreach ($verifiedDownPayments as $payment) {
                $payment->update([
                    'remarks' => trim(($payment->remarks ? $payment->remarks . ' ' : '')
                        . 'Retained after customer cancellation; non-refundable. Refund: ₱0.00.'),
                ]);
            }

            $locked->payments()
                ->where('payment_type', 'downpayment')
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'remarks' => 'Reservation cancelled before this cash payment was verified; amount is not counted as paid.',
                ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'reservation.cancelled',
                'description' => "Reservation {$locked->reservation_code}: Customer cancelled. Verified down payment retained: ₱" . number_format($retainedAmount, 2) . '. Refund: ₱0.00.',
                'ip_address' => $request->ip(),
            ]);

            if ($retainedAmount > 0) {
                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'payment.retained',
                    'description' => "Reservation {$locked->reservation_code}: Down payment ₱" . number_format($retainedAmount, 2) . ' retained after customer cancellation; original payment amount preserved.',
                    'ip_address' => $request->ip(),
                ]);
            }
        });

        return redirect()->route('customer.reservations.show', $reservation)
            ->with('success', 'Reservation cancelled. Any verified down payment is retained under the cancellation policy.');
    }

    public function submitPayment(Request $request, Reservation $reservation)
    {
        $customer = Customer::where('user_id', $request->user()->id)->firstOrFail();
        abort_unless($reservation->customer_id === $customer->id, 404);
        abort_if(in_array($reservation->status, ['cancelled', 'rejected', 'completed'], true), 422, 'Payments are closed for this reservation.');
        $pendingAmount = (float) Payment::where('reservation_id', $reservation->id)->where('status', 'pending')->sum('amount');
        $payableNow = max(0, (float) $reservation->balance - $pendingAmount);
        abort_if($payableNow <= 0, 422, 'Your outstanding balance is already covered by payments waiting for review.');
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'lte:' . $payableNow],
            'payment_type' => ['required', Rule::in(['downpayment', 'rental_balance', 'penalty', 'damage_fee'])],
            'amount' => ['required', 'numeric', $request->input('payment_type') === 'downpayment' ? 'gt:500' : 'gt:0', 'lte:' . $payableNow],
        ]);
        Payment::create([
            'reservation_id' => $reservation->id,
            'customer_id' => $customer->id,
            'payment_reference' => 'SBP-' . now()->format('ymd') . '-' . strtoupper(Str::random(6)),
            'payment_type' => $data['payment_type'],
            'payment_method' => 'cash',
            'amount' => $data['amount'],
            'status' => 'pending',
            'remarks' => 'Cash payment awaiting staff confirmation.',
        ]);
        return back()->with('success', 'Payment submitted. The boutique will verify it shortly.');
    }

    /**
     * Date blocks through the return date, so a gown can never be double-booked.
     *
     * @return array<int, array{from: \Illuminate\Support\Carbon, through: \Illuminate\Support\Carbon, status: string}>
     */
    private function conflictsFor(Gown $gown, Carbon $pickup, Carbon $return): array
    {
        return Reservation::with('gownReturn')
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->whereHas('items', fn($items) => $items->where('gown_id', $gown->id))
            ->get()
            ->filter(function ($existing) use ($pickup, $return) {
                $occupiedFrom = Carbon::parse($existing->pickup_date)->startOfDay();
                $returnDate = $existing->gownReturn?->actual_return_date ?? $existing->return_date;
                $occupiedThrough = Carbon::parse($returnDate)->startOfDay();

                return $occupiedFrom->lte($return) && $occupiedThrough->gte($pickup);
            })
            ->map(fn($existing) => [
                'from' => Carbon::parse($existing->pickup_date)->startOfDay(),
                'through' => Carbon::parse($existing->gownReturn?->actual_return_date ?? $existing->return_date)->startOfDay(),
                'status' => $existing->status,
            ])
            ->values()
            ->all();
    }

    /**
     * Builds the customer-visible lifecycle of a reservation so a booking can
     * show real progress instead of a single status string.
     *
     * @return array<int, array{key: string, label: string, state: string, meta: ?string}>
     */
    private function lifecycleFor(Reservation $reservation): array
    {
        $release = $reservation->gownRelease;
        $returned = $reservation->gownReturn;
        $paid = (float) $reservation->amount_paid;
        $pending = (float) $reservation->payments->where('status', 'pending')->sum('amount');
        $closed = in_array($reservation->status, ['cancelled', 'rejected'], true);

        return [
            [
                'key' => 'requested',
                'label' => 'Reservation submitted',
                'state' => 'done',
                'meta' => $reservation->created_at?->toFormattedDateString(),
            ],
            [
                'key' => 'agreement',
                'label' => 'Rental agreement',
                'state' => $reservation->agreement_accepted_at ? 'done' : 'upcoming',
                'meta' => $reservation->agreement_accepted_at
                    ? 'Accepted · ' . $reservation->agreement_accepted_at->toFormattedDateString()
                    : 'Awaiting acceptance',
            ],
            [
                'key' => 'payment',
                'label' => 'Payment',
                'state' => $closed ? 'upcoming' : ($pending > 0 ? 'current' : ($paid > 0 ? 'done' : 'upcoming')),
                'meta' => $pending > 0
                    ? '₱' . number_format($pending, 2) . ' under review'
                    : '₱' . number_format($paid, 2) . ' paid of ₱' . number_format($reservation->grand_total, 2),
            ],
            [
                'key' => 'pickup',
                'label' => 'Pickup',
                'state' => $release ? 'done' : 'upcoming',
                'meta' => $release?->release_date
                    ? 'Collected ' . $release->release_date->toFormattedDateString()
                    : 'Scheduled ' . ($reservation->pickup_date?->toFormattedDateString() ?? '—'),
            ],
            [
                'key' => 'return',
                'label' => 'Return',
                'state' => $returned?->actual_return_date ? 'done' : ($release ? 'current' : 'upcoming'),
                'meta' => $returned?->actual_return_date
                    ? 'Returned ' . $returned->actual_return_date->toFormattedDateString()
                    : 'Due ' . ($reservation->return_date?->toFormattedDateString() ?? '—'),
            ],
            [
                'key' => 'inspection',
                'label' => 'Inspection',
                'state' => !$returned ? 'upcoming' : ($reservation->status === 'completed' ? 'done' : 'current'),
                'meta' => $returned
                    ? ($returned->condition_after ? 'Condition: ' . $returned->condition_after : 'Awaiting inspection')
                    : 'Happens after return',
            ],
            [
                'key' => 'id',
                'label' => 'Government ID',
                'state' => match ($reservation->collateral_status) {
                    'held' => 'current',
                    'released' => 'done',
                    default => 'upcoming',
                },
                'meta' => match ($reservation->collateral_status) {
                    'held' => 'Held securely until the gown is returned',
                    'released' => 'Returned to the customer',
                    default => 'Bring a valid government-issued ID at pickup',
                },
            ],
            [
                'key' => 'completed',
                'label' => 'Completed',
                'state' => $reservation->status === 'completed' ? 'done' : 'upcoming',
                'meta' => $reservation->status === 'completed' ? 'Gown returned and account settled' : 'After the gown is returned and inspected',
            ],
        ];
    }
}
