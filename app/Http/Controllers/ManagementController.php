<?php

namespace App\Http\Controllers;

use App\Models\{AuditLog, CleaningRecord, Customer, DamageReport, Employee, Gown, GownRelease, GownReturn, MaintenanceRecord, Payment, Penalty, Reservation, SystemSetting, User};
use App\Models\GownPurchase;
use App\Services\ReservationCart;
use App\Services\VercelBlobStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ManagementController extends Controller
{
    private function reservationCart(): ReservationCart
    {
        return ReservationCart::forRole(request()->user()->role);
    }

    private function pageSize(Request $request, string $parameter = 'per_page'): int
    {
        $size = $request->integer($parameter, 12);

        return in_array($size, [12, 24, 48], true) ? $size : 12;
    }

    /** Adds a gown to the current booking cart and returns to the collection. */
    public function addToCart(Gown $gown)
    {
        abort_unless($gown->status === 'available', 422, $gown->name . ' is not currently available for a new reservation.');
        abort_if($gown->archived_at !== null, 422, 'This gown is no longer in the collection.');

        $this->reservationCart()->add($gown->id);

        return back()->with('success', $gown->name . ' was added to the in-store cart. Add more gowns, then open My Cart to reserve them together.');
    }

    /** Shows the in-store cart so several gowns can be reserved together. */
    public function cart()
    {
        return view('management.cart', [
            'gowns' => $this->reservationCart()->gowns(),
            'rentalTotal' => $this->reservationCart()->total(),
            'lateFeePerDay' => (float) SystemSetting::where('setting_key', 'late_fee_per_day')->value('setting_value'),
        ]);
    }

    /** Removes a gown from the current booking cart. */
    public function removeFromCart(Gown $gown)
    {
        $this->reservationCart()->remove($gown->id);

        return redirect()->route(request()->user()->role . '.cart')
            ->with('success', $gown->name . ' was removed from the in-store cart.');
    }

    public function clearCart()
    {
        $this->reservationCart()->clear();

        return redirect()->route(request()->user()->role . '.catalog')->with('success', 'The in-store cart was cleared.');
    }

    /**
     * Starts the walk-in booking form. With no gown in the URL it is seeded from
     * the staff cart so several gowns flow straight into one reservation.
     */
    public function employeeReservationForm(?Gown $gown = null)
    {
        $cartGowns = $this->reservationCart()->gowns();

        if ($gown) {
            abort_unless($gown->status === 'available', 422, 'Only gowns currently marked available can start a new reservation.');
            $cartGowns = $cartGowns->prepend($gown)->unique('id')->values();
        }

        abort_if($cartGowns->isEmpty(), 422, 'Add at least one gown to the in-store cart before starting a reservation.');

        return view('management.reservation-create', [
            'customers' => Customer::where('status', 'active')->orderBy('full_name')->get(),
            'gown' => $cartGowns->first(),
            'cartGowns' => $cartGowns,
            // Other available gowns so staff can add several pieces to one walk-in booking.
            'addableGowns' => Gown::with('category')
                ->whereNull('archived_at')
                ->where('status', 'available')
                ->whereNotIn('id', $cartGowns->pluck('id'))
                ->orderBy('name')
                ->get(),
            'lateFeePerDay' => (float) SystemSetting::where('setting_key', 'late_fee_per_day')->value('setting_value'),
        ]);
    }

    public function rentals(Request $request)
    {
        return view('management.rentals', [
            'rentals' => Reservation::with(['customer', 'items.gown', 'gownRelease', 'gownReturn'])
                ->whereIn('status', ['confirmed', 'ready_for_pickup', 'released', 'overdue', 'returned', 'completed'])
                ->orderBy('pickup_date')
                ->paginate($this->pageSize($request, 'rentals_per_page'), ['*'], 'rentals_page')
                ->withQueryString(),
            'cleanings' => CleaningRecord::with('gown')
                ->where('status', 'pending')
                ->latest()
                ->paginate($this->pageSize($request, 'cleanings_per_page'), ['*'], 'cleanings_page')
                ->withQueryString(),
            'pickupsToday' => Reservation::whereIn('status', ['confirmed', 'ready_for_pickup'])->whereDate('pickup_date', today())->count(),
            'activeRentals' => Reservation::whereIn('status', ['released', 'overdue'])->count(),
            'returnsDue' => Reservation::whereIn('status', ['released', 'overdue'])->whereDate('return_date', '<=', today())->count(),
            'cleaningCount' => CleaningRecord::where('status', 'pending')->count(),
            'base' => request()->user()->role,
        ]);
    }

    public function storeEmployeeReservation(Request $request)
    {
        $data = $request->validate([
            'existing_customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['required_without:existing_customer_id', 'nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'contact_number' => ['required_without:existing_customer_id', 'nullable', 'string', 'max:40'],
            'gown_ids' => ['nullable', 'array'],
            'gown_ids.*' => ['integer', 'distinct', 'exists:gowns,id'],
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date' => ['required', 'date', 'after_or_equal:pickup_date'],
            'bust' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'waist' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'hips' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'length' => ['nullable', 'numeric', 'min:0', 'max:400'],
            'government_id' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'id_safe_slot' => ['required', 'string', 'max:80'],
            'agreement_accepted' => ['accepted'],
            'payment_amount' => ['required', 'numeric', 'gt:0'],
            'payment_method' => ['required', Rule::in(['cash'])],
        ]);
        if (Carbon::parse($data['return_date'])->gt(Carbon::parse($data['pickup_date'])->addDays(2))) {
            throw ValidationException::withMessages(['return_date' => 'A gown may be rented for up to 3 days from its pickup date.']);
        }
        $lateFeePerDay = (float) SystemSetting::where('setting_key', 'late_fee_per_day')->value('setting_value');
        if ($lateFeePerDay <= 0) {
            throw ValidationException::withMessages(['return_date' => 'Set the shop late fee per day in settings before accepting reservations.']);
        }

        $customer = !empty($data['existing_customer_id'])
            ? Customer::findOrFail($data['existing_customer_id'])
            : Customer::create([
                'customer_code' => 'CUS-' . strtoupper(Str::random(8)),
                'full_name' => $data['customer_name'],
                'contact_number' => $data['contact_number'],
                'email' => $data['customer_email'] ?? null,
                'status' => 'active',
            ]);
        abort_unless($customer->status === 'active', 422, 'This customer account cannot make new reservations.');
        $idPath = app(VercelBlobStorage::class)->store($request->file('government_id'), 'government-ids', 'private');
        $measurements = collect(['Bust' => $data['bust'] ?? null, 'Waist' => $data['waist'] ?? null, 'Hips' => $data['hips'] ?? null, 'Length' => $data['length'] ?? null])
            ->filter(fn($value) => $value !== null)->map(fn($value, $key) => $key . ': ' . $value . ' cm')->values()->join('; ');

        // Fall back to the staff cart when the form did not carry explicit ids,
        // so the Shopee-style "reserve everything in the cart" flow always works.
        $gownIds = array_values(array_filter((array) ($data['gown_ids'] ?? [])));
        if ($gownIds === []) {
            $gownIds = $this->reservationCart()->ids();
        }
        if ($gownIds === []) {
            throw ValidationException::withMessages(['gown_ids' => 'Add at least one gown before saving this reservation.']);
        }

        $reservation = DB::transaction(function () use ($request, $data, $gownIds, $customer, $idPath, $measurements, $lateFeePerDay) {
            $gowns = Gown::whereIn('id', $gownIds)->lockForUpdate()->get();
            if ($gowns->count() !== count(array_unique($gownIds))) {
                throw ValidationException::withMessages(['gown_ids' => 'One of the selected gowns could not be found.']);
            }
            foreach ($gowns as $gown) {
                abort_unless($gown->status === 'available', 422, $gown->name . ' is not currently available for new reservations.');
                $overlap = Reservation::with('gownReturn')->whereNotIn('status', ['cancelled', 'rejected'])
                    ->whereHas('items', fn($items) => $items->where('gown_id', $gown->id))->get()
                    ->contains(function ($existing) use ($data) {
                        $end = $existing->gownReturn?->actual_return_date ?? $existing->return_date;
                        return Carbon::parse($existing->pickup_date)->startOfDay()->lte(Carbon::parse($data['return_date'])->startOfDay())
                            && Carbon::parse($end)->startOfDay()->gte(Carbon::parse($data['pickup_date'])->startOfDay());
                    });
                if ($overlap)
                    throw ValidationException::withMessages(['gown_ids' => $gown->name . ' is already booked or in its cleaning period for those dates.']);
            }

            $total = round($gowns->sum(fn($gown) => (float) $gown->rental_price), 2);
            if ((float) $data['payment_amount'] > $total)
                throw ValidationException::withMessages(['payment_amount' => 'Payment cannot exceed the rental fee.']);
            if ((float) $data['payment_amount'] < $total && (float) $data['payment_amount'] <= 500)
                throw ValidationException::withMessages(['payment_amount' => 'A down payment must be greater than ₱500.00.']);
            $booking = Reservation::create([
                'reservation_code' => 'SB-' . now()->format('ymd') . '-' . strtoupper(Str::random(5)),
                'customer_id' => $customer->id,
                'created_by' => $request->user()->id,
                'pickup_date' => $data['pickup_date'],
                'return_date' => $data['return_date'],
                'rental_total' => $total,
                'security_deposit_total' => 0,
                'late_fee_per_day' => $lateFeePerDay,
                'grand_total' => $total,
                'amount_paid' => $data['payment_amount'],
                'balance' => $total - (float) $data['payment_amount'],
                'status' => 'confirmed',
                'measurements' => $measurements ?: null,
                'government_id_photo_path' => $idPath,
                'physical_id_photo_path' => $idPath,
                'id_safe_slot' => $data['id_safe_slot'],
                'agreement_accepted_at' => now(),
                'collateral_status' => 'held',
            ]);
            $booking->items()->createMany(
                $gowns->map(fn($gown) => [
                    'gown_id' => $gown->id,
                    'rental_price' => (float) $gown->rental_price,
                    'security_deposit' => 0,
                    'quantity' => 1,
                ])->all()
            );
            Payment::create([
                'reservation_id' => $booking->id,
                'customer_id' => $customer->id,
                'payment_reference' => 'PAY-' . now()->format('ymd') . '-' . strtoupper(Str::random(6)),
                'payment_type' => (float) $data['payment_amount'] < $total ? 'downpayment' : 'rental_balance',
                'payment_method' => $data['payment_method'],
                'amount' => $data['payment_amount'],
                'status' => 'verified',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'remarks' => (float) $data['payment_amount'] < $total ? 'Non-refundable in-store down payment.' : 'Full in-store rental payment.',
            ]);
            return $booking;
        });

        $this->reservationCart()->clear();

        return redirect()->route($request->user()->role . '.reservations')->with('success', 'In-store reservation ' . $reservation->reservation_code . ' created and payment recorded.');
    }

    public function releaseGown(Request $request, Reservation $reservation)
    {
        abort_unless(in_array($reservation->status, ['confirmed', 'ready_for_pickup'], true), 422, 'Only confirmed reservations can be released.');
        abort_if($reservation->pickup_date->isFuture(), 422, 'This rental cannot be released before its pickup date.');
        $data = $request->validate([
            'condition_before' => ['required', Rule::in(['excellent', 'good', 'fair', 'damaged'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'physical_id_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'id_safe_slot' => ['required', 'string', 'max:80'],
        ]);
        if (!$reservation->physical_id_photo_path && !$request->hasFile('physical_id_photo')) {
            throw ValidationException::withMessages(['physical_id_photo' => 'Capture a photo of the physical ID before storing it.']);
        }
        $physicalIdPath = $request->hasFile('physical_id_photo')
            ? app(VercelBlobStorage::class)->store($request->file('physical_id_photo'), 'government-ids', 'private')
            : $reservation->physical_id_photo_path;
        DB::transaction(function () use ($request, $reservation, $data, $physicalIdPath) {
            GownRelease::updateOrCreate(['reservation_id' => $reservation->id], [
                'condition_before' => $data['condition_before'],
                'notes' => $data['notes'] ?? null,
                'processed_by' => $request->user()->id,
                'release_date' => today(),
                'release_time' => now()->format('H:i:s'),
            ]);
            $reservation->update(['status' => 'released', 'physical_id_photo_path' => $physicalIdPath, 'id_safe_slot' => $data['id_safe_slot'], 'collateral_status' => 'held']);
            foreach ($reservation->items()->with('gown')->get() as $item)
                $item->gown?->update(['status' => 'rented']);
        });
        return back()->with('success', 'Gown handoff recorded. Rental is now active.');
    }

    public function returnGown(Request $request, Reservation $reservation)
    {
        abort_unless(in_array($reservation->status, ['released', 'overdue'], true), 422, 'Only active rentals can be returned.');
        $data = $request->validate([
            'condition_after' => ['required', Rule::in(['excellent', 'good', 'fair', 'damaged'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'repair_cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'purchased_gown_ids' => ['sometimes', 'array'],
            'purchased_gown_ids.*' => ['integer', 'distinct'],
            'purchase_amounts' => ['sometimes', 'array'],
            'purchase_amounts.*' => ['nullable', 'numeric', 'min:0.01', 'max:1000000'],
        ]);
        $lateDays = max(0, Carbon::parse($reservation->return_date)->startOfDay()->diffInDays(today(), false));
        $purchasedGownIds = collect($data['purchased_gown_ids'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();
        $reservationGownIds = $reservation->items()->pluck('gown_id')->map(fn($id) => (int) $id);
        if ($purchasedGownIds->diff($reservationGownIds)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'purchased_gown_ids' => 'Only gowns in this reservation can be marked as purchased.',
            ]);
        }

        foreach ($purchasedGownIds as $gownId) {
            if (empty($data['purchase_amounts'][$gownId])) {
                throw ValidationException::withMessages([
                    'purchase_amounts.' . $gownId => 'Enter the agreed sale amount for each purchased gown.',
                ]);
            }
        }

        DB::transaction(function () use ($request, $reservation, $data, $lateDays, $purchasedGownIds) {
            $rentalReturn = GownReturn::updateOrCreate(['reservation_id' => $reservation->id], [
                'processed_by' => $request->user()->id,
                'actual_return_date' => today(),
                'actual_return_time' => now()->format('H:i:s'),
                'condition_after' => $data['condition_after'],
                'late_days' => $lateDays,
                'notes' => $data['notes'] ?? null,
            ]);
            $extraCharges = 0;
            foreach ($reservation->items()->with('gown')->get() as $item) {
                $gown = $item->gown;
                if (!$gown)
                    continue;
                $customerPurchased = $purchasedGownIds->contains((int) $gown->id);
                if ($data['condition_after'] === 'damaged') {
                    $gown->update([
                        'condition' => 'damaged',
                        'status' => $customerPurchased ? 'retired' : 'damaged',
                    ]);
                    $repairCost = (float) ($data['repair_cost'] ?? 0);
                    DamageReport::create(['gown_return_id' => $rentalReturn->id, 'gown_id' => $gown->id, 'damage_type' => 'Rental return damage', 'description' => $data['notes'] ?? 'Damage recorded during return inspection.', 'repair_cost' => $repairCost, 'final_repair_cost' => $repairCost, 'discovered_at' => today(), 'severity' => 'moderate']);
                    if ($repairCost > 0 && !$customerPurchased) {
                        Penalty::create(['reservation_id' => $reservation->id, 'gown_return_id' => $rentalReturn->id, 'penalty_type' => 'damage_fee', 'amount' => $repairCost, 'status' => 'pending']);
                        $extraCharges += $repairCost;
                    }
                } else {
                    $gown->update([
                        'condition' => $data['condition_after'],
                        'status' => $customerPurchased ? 'retired' : 'for_cleaning',
                    ]);
                    if (!$customerPurchased) {
                        CleaningRecord::create(['gown_id' => $gown->id, 'processed_by' => $request->user()->id, 'cleaning_date' => today(), 'cleaning_type' => 'Post-rental cleaning', 'status' => 'pending', 'notes' => 'Created after reservation ' . $reservation->reservation_code]);
                    }
                }

                if ($customerPurchased) {
                    $purchaseAmount = (float) $data['purchase_amounts'][$gown->id];
                    GownPurchase::create([
                        'reservation_id' => $reservation->id,
                        'gown_return_id' => $rentalReturn->id,
                        'gown_id' => $gown->id,
                        'processed_by' => $request->user()->id,
                        'amount' => $purchaseAmount,
                        'purchased_at' => now(),
                    ]);
                    Penalty::create([
                        'reservation_id' => $reservation->id,
                        'gown_return_id' => $rentalReturn->id,
                        'penalty_type' => 'gown_purchase',
                        'amount' => $purchaseAmount,
                        'status' => 'pending',
                    ]);
                    $extraCharges += $purchaseAmount;
                }
            }
            $lateFee = (float) $reservation->late_fee_per_day;
            if ($lateFee <= 0)
                $lateFee = (float) SystemSetting::where('setting_key', 'late_fee_per_day')->value('setting_value');
            if ($lateDays > 0 && $lateFee > 0) {
                $lateCharge = $lateDays * $lateFee;
                Penalty::create(['reservation_id' => $reservation->id, 'gown_return_id' => $rentalReturn->id, 'penalty_type' => 'late_return', 'amount' => $lateCharge, 'status' => 'pending']);
                $extraCharges += $lateCharge;
            }
            $reservation->update(['status' => 'returned', 'grand_total' => (float) $reservation->grand_total + $extraCharges, 'balance' => (float) $reservation->balance + $extraCharges]);
        });
        return redirect()->route($request->user()->role . '.rentals')->with('success', 'Return inspection recorded. Any cleaning or charges are now listed on the account.');
    }

    public function releaseIdCollateral(Request $request, Reservation $reservation)
    {
        abort_unless(in_array($reservation->status, ['returned', 'completed'], true), 422, 'The gown must be returned before its ID can be released.');
        abort_if((float) $reservation->balance > 0, 422, 'Clear all rental, damage, and late charges before releasing the ID.');
        abort_unless($reservation->collateral_status === 'held', 422, 'There is no held ID collateral to release.');
        $reservation->update(['collateral_status' => 'released']);
        return back()->with('success', 'Original ID collateral released to the customer.');
    }

    public function completeCleaning(Request $request, CleaningRecord $cleaning)
    {
        abort_unless($cleaning->status === 'pending', 422, 'This cleaning record is already closed.');
        $cleaning->update(['status' => 'completed', 'processed_by' => $request->user()->id, 'cleaning_date' => today()]);
        if ($cleaning->gown && $cleaning->gown->condition !== 'damaged' && $cleaning->gown->status === 'for_cleaning') {
            $cleaning->gown->update(['status' => 'available']);
        }
        return back()->with('success', 'Cleaning completed; the gown is available again.');
    }

    public function maintenance(Request $request)
    {
        $perPage = in_array($request->integer('per_page', 12), [12, 24, 48], true)
            ? $request->integer('per_page', 12)
            : 12;

        return view('management.maintenance', [
            'maintenanceRecords' => MaintenanceRecord::with(['gown', 'processedBy'])
                ->latest()
                ->paginate($perPage)
                ->withQueryString(),
            'gowns' => Gown::whereNull('archived_at')->whereNotIn('status', ['retired', 'rented'])->orderBy('name')->get(),
            'base' => request()->user()->role,
        ]);
    }

    public function createMaintenance(Request $request)
    {
        $data = $request->validate([
            'gown_id' => ['required', 'exists:gowns,id'],
            'maintenance_type' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:2000'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'maintenance_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $data) {
            $gown = Gown::whereNull('archived_at')->whereKey($data['gown_id'])->lockForUpdate()->firstOrFail();
            abort_if(in_array($gown->status, ['retired', 'rented'], true), 422, 'This gown cannot be sent to maintenance right now.');
            $activeRental = Reservation::whereIn('status', ['pending', 'awaiting_payment', 'confirmed', 'ready_for_pickup', 'released', 'overdue'])
                ->whereHas('items', fn($items) => $items->where('gown_id', $gown->id))
                ->exists();
            abort_if($activeRental, 422, 'This gown is linked to an active reservation and cannot be sent to maintenance.');

            MaintenanceRecord::create($data + [
                'processed_by' => $request->user()->id,
                'cost' => $data['cost'] ?? 0,
                'status' => 'pending',
            ]);
            $gown->update(['status' => 'under_maintenance']);
        });

        return back()->with('success', 'Maintenance record added and gown removed from the available collection.');
    }

    public function completeMaintenance(Request $request, MaintenanceRecord $maintenance)
    {
        $data = $request->validate([
            'condition' => ['required', Rule::in(['excellent', 'good', 'fair', 'damaged'])],
            'completion_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $maintenance, $data) {
            $record = MaintenanceRecord::whereKey($maintenance->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'pending', 422, 'This maintenance record is already completed.');
            $record->update([
                'status' => 'completed',
                'processed_by' => $request->user()->id,
                'notes' => trim(($record->notes ? $record->notes . "\n" : '') . ($data['completion_notes'] ?? '')) ?: null,
            ]);

            $gown = Gown::whereKey($record->gown_id)->lockForUpdate()->first();
            if ($gown) {
                $gown->condition = $data['condition'];
                $hasPendingMaintenance = MaintenanceRecord::where('gown_id', $gown->id)->where('status', 'pending')->exists();
                if (!$hasPendingMaintenance) {
                    $gown->status = $data['condition'] === 'damaged' ? 'damaged' : 'available';
                }
                $gown->save();
            }
        });

        return back()->with('success', 'Maintenance closed and the gown condition updated.');
    }

    public function reservations(Request $request)
    {
        $query = Reservation::with(['customer', 'items.gown', 'payments', 'cancelledBy'])->latest();
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'pending') {
                $query->whereIn('status', ['pending', 'awaiting_payment']);
            } else {
                $query->where('status', $status);
            }
        }
        if ($request->filled('q'))
            $query->where(function ($q) use ($request) {
                $term = $request->string('q');
                $q->where('reservation_code', 'like', "%$term%")->orWhereHas('customer', fn($c) => $c->where('full_name', 'like', "%$term%"));
            });
        return view('management.reservations', [
            'reservations' => $query->paginate($this->pageSize($request))->withQueryString(),
            'base' => $request->user()->role,
        ]);
    }

    public function reservationDetails(Reservation $reservation)
    {
        $reservation->load([
            'customer',
            'items.gown.category',
            'payments',
            'gownRelease',
            'gownReturn',
            'penalties',
        ]);

        return view('management.reservation-show', [
            'reservation' => $reservation,
            'base' => request()->user()->role,
        ]);
    }

    public function updateReservation(Request $request, Reservation $reservation)
    {
        $allowedTransitions = match ($reservation->status) {
            'pending', 'awaiting_payment' => ['confirmed', 'cancelled', 'rejected'],
            'confirmed' => ['ready_for_pickup', 'cancelled', 'rejected'],
            'ready_for_pickup' => ['cancelled'],
            'returned' => ['completed'],
            default => [],
        };
        $data = $request->validate([
            'status' => ['required', Rule::in(array_merge([$reservation->status], $allowedTransitions))],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
            'pickup_date' => ['sometimes', 'required', 'date'],
            'return_date' => ['sometimes', 'required', 'date', 'after_or_equal:pickup_date'],
        ]);
        if (isset($data['pickup_date'], $data['return_date'])) {
            $pickupDate = Carbon::parse($data['pickup_date'])->startOfDay();
            $returnDate = Carbon::parse($data['return_date'])->startOfDay();

            if ($returnDate->gt($pickupDate->copy()->addDays(2))) {
                throw ValidationException::withMessages([
                    'return_date' => 'A gown may be rented for up to 3 days from its pickup date.',
                ]);
            }

            $datesChanged = $pickupDate->toDateString() !== $reservation->pickup_date->toDateString()
                || $returnDate->toDateString() !== $reservation->return_date->toDateString();

            if ($datesChanged && $pickupDate->lt(today()->startOfDay())) {
                throw ValidationException::withMessages([
                    'pickup_date' => 'Updated reservation dates must be today or later.',
                ]);
            }

            if ($datesChanged) {
                $gownIds = $reservation->items()->pluck('gown_id');
                $conflict = Reservation::with('gownReturn')
                    ->where('id', '!=', $reservation->id)
                    ->whereNotIn('status', ['cancelled', 'rejected'])
                    ->whereHas('items', fn($items) => $items->whereIn('gown_id', $gownIds))
                    ->get()
                    ->contains(function (Reservation $existing) use ($pickupDate, $returnDate) {
                        $occupiedThrough = $existing->gownReturn?->actual_return_date ?? $existing->return_date;

                        return Carbon::parse($existing->pickup_date)->startOfDay()->lte($returnDate)
                            && Carbon::parse($occupiedThrough)->startOfDay()->gte($pickupDate);
                    });

                if ($conflict) {
                    throw ValidationException::withMessages([
                        'pickup_date' => 'One or more gowns in this reservation are already booked for those dates.',
                    ]);
                }
            }
        }
        if ($data['status'] === 'completed') {
            abort_unless($reservation->status === 'returned', 422, 'Only returned rentals can be completed.');
            abort_if((float) $reservation->balance > 0, 422, 'Clear the outstanding balance before completing this rental.');
        }
        $previousStatus = $reservation->status;
        $reservation->update($data);
        if ($data['status'] === 'confirmed' && $previousStatus !== 'confirmed') {
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'reservation.approved',
                'description' => "Reservation {$reservation->reservation_code}: Approved by " . $request->user()->name . '.',
                'ip_address' => $request->ip(),
            ]);
        }
        return back()->with('success', 'Reservation status updated.');
    }

    public function customers(Request $request)
    {
        $query = Customer::withCount('reservations')->with('reservations')->latest();
        if (request()->filled('q')) {
            $term = request('q');
            $query->where(fn($builder) => $builder->where('full_name', 'like', "%$term%")
                ->orWhere('email', 'like', "%$term%")
                ->orWhere('contact_number', 'like', "%$term%"));
        }
        return view('management.customers', [
            'customers' => $query->paginate($this->pageSize($request))->withQueryString(),
        ]);
    }

    public function updateCustomer(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($customer, $data) {
            $customer->update(['full_name' => $data['full_name']]);

            if ($customer->user_id) {
                $customer->user()->update(['name' => $data['full_name']]);
            }
        });

        return back()->with('success', 'Customer name updated. The new name is now shown on all linked reservation and payment history.');
    }

    public function customerDetails(Request $request, Customer $customer)
    {
        $reservations = $customer->reservations()
            ->with([
                'items.gown',
                'gownPurchases.gown',
                'payments' => fn($query) => $query->latest(),
                'penalties' => fn($query) => $query->latest(),
                'gownReturn',
                'cancelledBy',
            ])
            ->latest()
            ->paginate($this->pageSize($request))
            ->withQueryString();
        $lateFeePerDay = (float) SystemSetting::where('setting_key', 'late_fee_per_day')->value('setting_value');

        foreach ($reservations as $reservation) {
            $actualLateDays = (int) ($reservation->gownReturn?->late_days ?? 0);
            $currentLateDays = 0;
            if (!$reservation->gownReturn && in_array($reservation->status, ['released', 'overdue'], true) && $reservation->return_date?->isBefore(today())) {
                $currentLateDays = Carbon::parse($reservation->return_date)->startOfDay()->diffInDays(today(), false);
            }
            $reservation->display_late_days = max($actualLateDays, $currentLateDays);
            $agreedLateFee = (float) $reservation->late_fee_per_day ?: $lateFeePerDay;
            $reservation->projected_late_fee = $currentLateDays * $agreedLateFee;
            $reservation->retained_down_payment = (float) $reservation->payments
                ->where('payment_type', 'downpayment')
                ->where('status', 'verified')
                ->sum('amount');
            $reservation->cancellation_history = $reservation->status === 'cancelled'
                ? AuditLog::with('user')->where('description', 'like', '%' . $reservation->reservation_code . '%')->oldest()->get()
                : collect();
        }

        return view('management.customer-show', [
            'customer' => $customer,
            'reservations' => $reservations,
            'reservationCount' => $customer->reservations()->count(),
            'verifiedPaid' => Payment::whereHas('reservation', fn($query) => $query->where('customer_id', $customer->id))
                ->where('status', 'verified')
                ->sum('amount'),
            'outstandingBalance' => $customer->reservations()->sum('balance'),
            'lateFeePerDay' => $lateFeePerDay,
            'base' => request()->user()->role,
        ]);
    }

    public function payments(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['pending', 'verified', 'rejected', 'refunded'])],
            'method' => ['nullable', Rule::in(['cash', 'gcash'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = Payment::with(['reservation', 'customer'])->latest();
        if (!empty($filters['q'])) {
            $term = $filters['q'];
            $query->where(function ($search) use ($term) {
                $search->where('payment_reference', 'like', '%' . $term . '%')
                    ->orWhereHas('customer', fn($customer) => $customer->where('full_name', 'like', '%' . $term . '%'))
                    ->orWhereHas('reservation', fn($reservation) => $reservation->where('reservation_code', 'like', '%' . $term . '%'));
            });
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['method'])) {
            $query->where('payment_method', $filters['method']);
        }
        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        $totalAmount = (float) (clone $query)->sum('amount');
        $verifiedQuery = (clone $query)->whereIn('status', ['verified', 'paid']);
        $verifiedAmount = (float) (clone $verifiedQuery)->sum('amount');
        $verifiedCount = $verifiedQuery->count();
        $reviewQuery = (clone $query)->where('status', 'pending');
        $reviewAmount = (float) (clone $reviewQuery)->sum('amount');
        $reviewCount = $reviewQuery->count();

        return view('management.payments', [
            'payments' => $query->paginate($this->pageSize($request))->withQueryString(),
            'totalAmount' => $totalAmount,
            'totalCount' => (clone $query)->count(),
            'verifiedAmount' => $verifiedAmount,
            'verifiedCount' => $verifiedCount,
            'reviewAmount' => $reviewAmount,
            'reviewCount' => $reviewCount,
            'openReservations' => Reservation::with('customer')
                ->where('balance', '>', 0)
                ->whereNotIn('status', ['cancelled', 'rejected', 'completed'])
                ->latest()->get(),
            'filters' => $filters,
            'base' => request()->user()->role,
        ]);
    }

    public function verifyPayment(Request $request, Payment $payment)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['verified', 'rejected'])], 'remarks' => ['nullable', 'string', 'max:500']]);
        DB::transaction(function () use ($request, $payment, $data) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'pending', 422, 'This payment has already been reviewed.');
            if ($data['status'] === 'verified') {
                $reservation = Reservation::whereKey($locked->reservation_id)->lockForUpdate()->firstOrFail();
                abort_if(in_array($reservation->status, ['cancelled', 'rejected', 'completed'], true), 422, 'Payments cannot be approved for a closed reservation.');
                if ((float) $locked->amount > (float) $reservation->balance) {
                    throw ValidationException::withMessages(['payment' => 'This payment exceeds the reservation balance. Review the account before approving it.']);
                }
                $paid = (float) $reservation->amount_paid + (float) $locked->amount;
                $reservation->update(['amount_paid' => $paid, 'balance' => max(0, (float) $reservation->grand_total - $paid)]);
            }
            $locked->update([
                'status' => $data['status'],
                'verified_by' => $data['status'] === 'verified' ? $request->user()->id : null,
                'verified_at' => $data['status'] === 'verified' ? now() : null,
                'remarks' => $data['remarks'] ?? $locked->remarks,
            ]);
        });
        return back()->with('success', 'Payment review saved.');
    }

    public function paymentProof(Request $request, Payment $payment)
    {
        if ($request->user()->role === 'customer') {
            abort_unless($payment->customer?->user_id === $request->user()->id, 404);
        } else {
            abort_unless(in_array($request->user()->role, ['owner', 'employee'], true), 403);
        }
        return app(VercelBlobStorage::class)->privateFileResponse($payment->proof_of_payment);
    }

    public function collateralPhoto(Request $request, Reservation $reservation, string $type)
    {
        abort_unless(in_array($request->user()->role, ['owner', 'employee'], true), 403);
        abort_unless(in_array($type, ['digital', 'physical'], true), 404);
        $path = $type === 'physical' ? $reservation->physical_id_photo_path : $reservation->government_id_photo_path;
        return app(VercelBlobStorage::class)->privateFileResponse($path);
    }

    public function recordPayment(Request $request, Reservation $reservation)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', $request->input('payment_type') === 'downpayment' ? 'gt:500' : 'gt:0', 'lte:' . (float) $reservation->balance], 'payment_method' => ['required', Rule::in(['cash'])], 'payment_type' => ['required', Rule::in(['downpayment', 'rental_balance', 'other'])], 'remarks' => ['nullable', 'string', 'max:500']]);
        $reservation->loadMissing('customer');
        $payment = DB::transaction(function () use ($data, $reservation, $request) {
            $lockedReservation = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            abort_if(in_array($lockedReservation->status, ['cancelled', 'rejected', 'completed'], true), 422, 'Payments cannot be recorded for a closed reservation.');
            if ((float) $data['amount'] > (float) $lockedReservation->balance) {
                throw ValidationException::withMessages(['amount' => 'The payment is greater than the current outstanding balance. Refresh the page and try again.']);
            }
            $payment = Payment::create($data + [
                'reservation_id' => $lockedReservation->id,
                'customer_id' => $lockedReservation->customer_id,
                'payment_reference' => 'PAY-' . now()->format('ymd') . '-' . strtoupper(Str::random(6)),
                'status' => 'verified',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
            ]);
            $amountPaid = (float) $lockedReservation->amount_paid + (float) $payment->amount;
            $lockedReservation->update(['amount_paid' => $amountPaid, 'balance' => max(0, (float) $lockedReservation->grand_total - $amountPaid)]);
            return $payment;
        });
        if ($payment->payment_type === 'downpayment')
            $payment->update(['remarks' => trim(($payment->remarks ? $payment->remarks . ' ' : '') . 'Non-refundable down payment.')]);
        return back()->with('success', 'Payment ' . $payment->payment_reference . ' recorded.');
    }

    public function employees(Request $request)
    {
        return view('management.employees', [
            'employees' => Employee::with('user')->latest()->paginate($this->pageSize($request))->withQueryString(),
        ]);
    }

    public function createEmployeeForm()
    {
        return view('management.employee-create');
    }

    public function employeeDetails(Request $request, Employee $employee)
    {
        $userId = $employee->user_id;
        $activities = collect()
            ->concat(GownRelease::with('reservation')->where('processed_by', $userId)->get()->map(fn($row) => [
                'date' => $row->created_at,
                'action' => 'Gown released',
                'description' => 'Reservation ' . ($row->reservation?->reservation_code ?? '—') . ' · ' . ucfirst($row->condition_before) . ' condition at handoff',
            ]))
            ->concat(GownReturn::with('reservation')->where('processed_by', $userId)->get()->map(fn($row) => [
                'date' => $row->created_at,
                'action' => 'Gown returned',
                'description' => 'Reservation ' . ($row->reservation?->reservation_code ?? '—') . ' · ' . $row->late_days . ' late day(s)',
            ]))
            ->concat(CleaningRecord::with('gown')->where('processed_by', $userId)->get()->map(fn($row) => [
                'date' => $row->updated_at ?? $row->created_at,
                'action' => 'Cleaning updated',
                'description' => ($row->gown?->name ?? 'Gown') . ' · ' . $row->cleaning_type . ' · ' . ucfirst($row->status),
            ]))
            ->concat(MaintenanceRecord::with('gown')->where('processed_by', $userId)->get()->map(fn($row) => [
                'date' => $row->updated_at ?? $row->created_at,
                'action' => 'Maintenance updated',
                'description' => ($row->gown?->name ?? 'Gown') . ' · ' . $row->maintenance_type . ' · ' . ucfirst($row->status),
            ]))
            ->concat(Payment::with('reservation')->where('verified_by', $userId)->get()->map(fn($row) => [
                'date' => $row->verified_at ?? $row->updated_at,
                'action' => 'Payment recorded or verified',
                'description' => $row->payment_reference . ' · Reservation ' . ($row->reservation?->reservation_code ?? '—') . ' · ₱' . number_format((float) $row->amount, 2),
            ]))
            ->sortByDesc('date')->values();

        $pageSize = $this->pageSize($request);
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $activities = new LengthAwarePaginator(
            $activities->forPage($currentPage, $pageSize),
            $activities->count(),
            $pageSize,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()],
        );

        return view('management.employee-show', compact('employee', 'activities'));
    }

    public function createEmployee(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'unique:users,email'], 'password' => ['required', 'string', 'min:8'], 'contact_number' => ['nullable', 'string', 'max:40'], 'position' => ['required', 'string', 'max:100']]);
        DB::transaction(function () use ($data) {
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password']), 'role' => 'employee']);
            Employee::create(['user_id' => $user->id, 'employee_code' => 'EMP-' . strtoupper(Str::random(8)), 'full_name' => $data['name'], 'contact_number' => $data['contact_number'] ?? null, 'position' => $data['position'], 'status' => 'active']);
        });
        return redirect()->route('owner.employees')->with('success', 'Employee account created. Share the login details securely.');
    }

    public function toggleEmployee(Employee $employee)
    {
        $employee->update(['status' => $employee->status === 'active' ? 'inactive' : 'active']);
        return back()->with('success', 'Employee access updated.');
    }

    public function updateEmployee(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($employee->user_id)],
            'contact_number' => ['nullable', 'string', 'max:40'],
            'position' => ['required', 'string', 'max:100'],
        ]);
        DB::transaction(function () use ($data, $employee) {
            $employee->user()->update(['name' => $data['name'], 'email' => $data['email']]);
            $employee->update(['full_name' => $data['name'], 'contact_number' => $data['contact_number'] ?? null, 'position' => $data['position']]);
        });
        return back()->with('success', 'Employee information updated.');
    }

    public function reports(Request $request)
    {
        $period = $this->resolveReportPeriod($request);
        $dateRange = [$period['start_date'], $period['end_date']];
        $reservationCount = Reservation::whereBetween(DB::raw('DATE(created_at)'), $dateRange)->count();
        $monthlyCounts = Reservation::whereBetween(DB::raw('DATE(created_at)'), $dateRange)->get(['created_at'])
            ->groupBy(fn($reservation) => $reservation->created_at->format('Y-m'))
            ->map(fn($rows) => $rows->count());
        $monthly = $this->reportMonthBuckets($period, $monthlyCounts);
        $popular = Gown::with('category')
            ->whereHas('reservationItems.reservation', fn($query) => $query->whereBetween(DB::raw('DATE(created_at)'), $dateRange))
            ->withCount([
                'reservationItems as reservation_items_count' => fn($query) => $query->whereHas(
                    'reservation',
                    fn($reservationQuery) => $reservationQuery->whereBetween(DB::raw('DATE(created_at)'), $dateRange)
                ),
            ])->orderByDesc('reservation_items_count')->take(10)->get();

        return view('management.reports', [
            'period' => $period,
            'reservationCount' => $reservationCount,
            'rentalCount' => Reservation::whereIn('status', ['released', 'overdue'])->count(),
            'paymentTotal' => Payment::where('status', 'verified')->whereBetween(DB::raw('DATE(verified_at)'), $dateRange)->sum('amount'),
            'gownCount' => Gown::count(),
            'availableCount' => Gown::where('status', 'available')->count(),
            'popular' => $popular,
            'popularMax' => (int) ($popular->max('reservation_items_count') ?? 0),
            'monthly' => $monthly,
        ]);
    }

    public function exportReports(Request $request)
    {
        $period = $this->resolveReportPeriod($request);
        $dateRange = [$period['start_date'], $period['end_date']];
        $reservationCount = Reservation::whereBetween(DB::raw('DATE(created_at)'), $dateRange)->count();
        $rentalCount = Reservation::whereIn('status', ['released', 'overdue'])->count();
        $paymentTotal = Payment::where('status', 'verified')->whereBetween(DB::raw('DATE(verified_at)'), $dateRange)->sum('amount');
        $gownCount = Gown::count();
        $availableCount = Gown::where('status', 'available')->count();
        $monthlyCounts = Reservation::whereBetween(DB::raw('DATE(created_at)'), $dateRange)->get(['created_at'])
            ->groupBy(fn($reservation) => $reservation->created_at->format('Y-m'))
            ->map(fn($rows) => $rows->count());
        $monthly = $this->reportMonthBuckets($period, $monthlyCounts);
        $popular = Gown::with('category')->withCount([
            'reservationItems as reservation_items_count' => fn($query) => $query->whereHas(
                'reservation',
                fn($reservationQuery) => $reservationQuery->whereBetween(DB::raw('DATE(created_at)'), $dateRange)
            ),
        ])->having('reservation_items_count', '>', 0)
            ->orderByDesc('reservation_items_count')->take(5)->get();

        return response()->streamDownload(function () use ($period, $reservationCount, $rentalCount, $paymentTotal, $gownCount, $availableCount, $monthly, $popular) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            $writeRow = static function (array $row) use ($stream): void {
                $row = array_map(static function ($value) {
                    $value = (string) $value;
                    return preg_match('/^[\x00-\x20]*[=+\-@]/', $value) ? "'" . $value : $value;
                }, $row);
                fputcsv($stream, $row);
            };

            $writeRow(['Business report', 'Shyra Beautique']);
            $writeRow(['Generated at', now()->format('Y-m-d H:i:s')]);
            $writeRow(['Period', $period['label'], $period['start_date'], $period['end_date']]);
            $writeRow([]);
            $writeRow(['Summary', 'Metric', 'Value']);
            $writeRow(['Summary', 'Total reservations', $reservationCount]);
            $writeRow(['Summary', 'Active rentals', $rentalCount]);
            $writeRow(['Summary', 'Verified payments (PHP)', number_format((float) $paymentTotal, 2, '.', '')]);
            $writeRow(['Summary', 'Available gowns', $availableCount]);
            $writeRow(['Summary', 'Total gowns', $gownCount]);
            $writeRow([]);
            $writeRow(['Monthly reservations', $period['start_date'], $period['end_date']]);
            $writeRow(['Month', 'Reservations']);
            foreach ($monthly as $month) {
                $writeRow([$month['label'], $month['value']]);
            }
            $writeRow([]);
            $writeRow(['Most reserved gowns', 'Category', 'Reservations']);
            foreach ($popular as $gown) {
                $writeRow([$gown->name, $gown->category->name ?? 'Collection', $gown->reservation_items_count]);
            }

            fclose($stream);
        }, 'shyra-business-report-' . $period['start_date'] . '-to-' . $period['end_date'] . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportReportsPdf(Request $request)
    {
        $period = $this->resolveReportPeriod($request);
        $dateRange = [$period['start_date'], $period['end_date']];
        $reservations = Reservation::with(['customer', 'items.gown', 'payments'])
            ->whereBetween(DB::raw('DATE(created_at)'), $dateRange)
            ->oldest()
            ->get();
        $monthlyCounts = $reservations->groupBy(fn($reservation) => $reservation->created_at->format('Y-m'))
            ->map(fn($rows) => $rows->count());
        $monthly = $this->reportMonthBuckets($period, $monthlyCounts);
        $popular = Gown::with('category')
            ->whereHas('reservationItems.reservation', fn($query) => $query->whereBetween(DB::raw('DATE(created_at)'), $dateRange))
            ->withCount([
                'reservationItems as reservation_items_count' => fn($query) => $query->whereHas(
                    'reservation',
                    fn($reservationQuery) => $reservationQuery->whereBetween(DB::raw('DATE(created_at)'), $dateRange)
                ),
            ])->orderByDesc('reservation_items_count')->take(10)->get();
        $payments = Payment::with(['customer', 'reservation'])
            ->whereBetween(DB::raw('DATE(created_at)'), $dateRange)
            ->oldest()
            ->get();
        $gownsByStatus = Gown::select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')->pluck('total', 'status');
        $gownCount = (int) $gownsByStatus->sum();
        $availableCount = (int) $gownsByStatus->get('available', 0);
        $monthsInPeriod = max(count($monthly), 1);
        $reservationStatusCounts = $reservations->countBy('status');
        $paymentStatusTotals = collect(['verified', 'pending', 'rejected', 'refunded'])
            ->mapWithKeys(fn($status) => [$status => (float) $payments->where('status', $status)->sum('amount')]);
        $shopName = SystemSetting::where('setting_key', 'shop_name')->value('setting_value') ?: 'Shyra Beautique';
        $contactEmail = SystemSetting::where('setting_key', 'contact_email')->value('setting_value');
        // The real brand logo. This previously pointed at shyra-logo.jpg, which was
        // an unrelated fashion photo rather than a logo.
        $logoPath = public_path('images/Logo.png');

        // dompdf needs the GD extension to size and rasterise an <img>. If GD is
        // unavailable the whole PDF would throw "The PHP GD extension is
        // required", so only pass the logo when GD can actually handle it.
        $logoDataUri = null;
        if (is_file($logoPath) && extension_loaded('gd')) {
            $logoDataUri = 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath));
        }

        $pdf = Pdf::loadView('management.reports-pdf', [
            'period' => $period,
            'generatedAt' => now(),
            'shopName' => $shopName,
            'contactEmail' => $contactEmail,
            // NOTE: previously pointed at public/images/shyra-logo.jpg, which is not a
            // logo at all (it is an unrelated fashion photo), so it was being stamped
            // onto the report cover. The cover now renders the real brand logo instead.
            'logoDataUri' => $logoDataUri,
            'reservations' => $reservations,
            'reservationCount' => $reservations->count(),
            'completedCount' => $reservationStatusCounts->get('completed', 0),
            'upcomingCount' => $reservationStatusCounts->only(['pending', 'awaiting_payment', 'confirmed', 'ready_for_pickup'])->sum(),
            'cancelledCount' => $reservationStatusCounts->get('cancelled', 0),
            'averageReservationsPerMonth' => $reservations->count() / $monthsInPeriod,
            'monthly' => $monthly,
            'popular' => $popular,
            'popularMax' => (int) ($popular->max('reservation_items_count') ?? 0),
            'gownCount' => $gownCount,
            'availableCount' => $availableCount,
            'rentedCount' => (int) $gownsByStatus->get('rented', 0),
            'reservedCount' => (int) $gownsByStatus->get('reserved', 0),
            'maintenanceCount' => (int) $gownsByStatus->get('under_maintenance', 0) + (int) $gownsByStatus->get('for_cleaning', 0),
            'availabilityRate' => $gownCount > 0 ? ($availableCount / $gownCount) * 100 : 0,
            'paymentTotal' => (float) $paymentStatusTotals->get('verified', 0),
            'paymentCount' => $payments->count(),
            'paymentStatusTotals' => $paymentStatusTotals,
            'payments' => $payments,
            'activeRentalCount' => $reservations->whereIn('status', ['released', 'overdue'])->count(),
            'releasedRentalCount' => $reservations->where('status', 'released')->count(),
            'overdueRentalCount' => $reservations->where('status', 'overdue')->count(),
        ])->setPaper('a4', 'portrait')->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isRemoteEnabled' => false,
                    'isHtml5ParserEnabled' => true,
                ]);

        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $dompdf->getCanvas()->page_text(36, 815, $shopName . ' · Confidential', $font, 8, [0.38, 0.28, 0.3]);
        $dompdf->getCanvas()->page_text(492, 815, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 8, [0.38, 0.28, 0.3]);

        return $pdf->download('shyra-business-report-' . $period['start_date'] . '-to-' . $period['end_date'] . '.pdf');
    }

    private function resolveReportPeriod(Request $request): array
    {
        $filters = $request->validate([
            'period' => ['nullable', Rule::in(['today', 'this_week', 'this_month', 'this_year', 'custom'])],
            'start_date' => ['nullable', 'date', 'required_if:period,custom'],
            'end_date' => ['nullable', 'date', 'required_if:period,custom', 'after_or_equal:start_date'],
        ]);
        $key = $filters['period'] ?? 'this_year';
        $now = Carbon::now();

        [$start, $end] = match ($key) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'custom' => [Carbon::parse($filters['start_date'])->startOfDay(), Carbon::parse($filters['end_date'])->endOfDay()],
            default => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
        };

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();
        $label = match ($key) {
            'today' => 'Today',
            'this_week' => 'This week',
            'this_month' => 'This month',
            'this_year' => 'This year',
            default => Carbon::parse($startDate)->format('M j, Y') . ' to ' . Carbon::parse($endDate)->format('M j, Y'),
        };

        return [
            'key' => $key,
            'label' => $label,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    private function reportMonthBuckets(array $period, $monthlyCounts): array
    {
        $start = Carbon::parse($period['start_date'])->startOfMonth();
        $end = Carbon::parse($period['end_date'])->startOfMonth();
        $showYear = $start->year !== $end->year;
        $months = [];

        while ($start->lte($end)) {
            $key = $start->format('Y-m');
            $months[$key] = [
                'label' => $start->format($showYear ? 'M y' : 'M'),
                'value' => (int) $monthlyCounts->get($key, 0),
            ];
            $start->addMonth();
        }

        return $months;
    }

    public function settings()
    {
        $settings = SystemSetting::pluck('setting_value', 'setting_key');
        return view('management.settings', compact('settings'));
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate(['shop_name' => ['required', 'string', 'max:120'], 'contact_email' => ['nullable', 'email', 'max:255'], 'contact_number' => ['nullable', 'string', 'max:40'], 'late_fee_per_day' => ['required', 'numeric', 'min:0.01', 'max:100000']]);
        foreach ($data as $key => $value)
            SystemSetting::updateOrCreate(['setting_key' => $key], ['setting_value' => (string) $value]);
        return back()->with('success', 'Boutique settings saved.');
    }
}
