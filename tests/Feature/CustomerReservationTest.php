<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\AuditLog;
use App\Models\Gown;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\SystemSetting;
use App\Models\User;

beforeEach(function () {
    $this->customerUser = User::factory()->create(['role' => 'customer']);
    $category = Category::create(['name' => 'Formal gowns']);
    $this->gown = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-TEST-001',
        'name' => 'Test gown',
        'rental_price' => 2500,
        'security_deposit' => 1000,
        'status' => 'available',
    ]);
    SystemSetting::create([
        'setting_key' => 'late_fee_per_day',
        'setting_value' => '150',
    ]);
    $this->reservationData = [
        'contact_number' => '09171234567',
        'pickup_date' => today()->addDays(2)->toDateString(),
        'return_date' => today()->addDays(3)->toDateString(),
        'payment_method' => 'cash',
        'payment_option' => 'downpayment',
        'payment_amount' => '600.00',
        'agreement_accepted' => '1',
    ];
});

it('rejects incomplete Philippine mobile numbers', function () {
    $this->actingAs($this->customerUser)
        ->withSession(['reservation_cart' => [$this->gown->id]])
        ->from(route('customer.reserve'))
        ->post(route('customer.reserve.store'), array_replace($this->reservationData, [
            'contact_number' => '066',
        ]))
        ->assertSessionHasErrors('contact_number');

    $this->actingAs($this->customerUser)
        ->withSession(['reservation_cart' => [$this->gown->id]])
        ->from(route('customer.reserve'))
        ->post(route('customer.reserve.store'), array_replace($this->reservationData, [
            'contact_number' => '+639171234567',
        ]))
        ->assertSessionHasErrors('contact_number');

    expect(Reservation::count())->toBe(0);
});

it('stores a cash down payment without charging a cash deposit', function () {
    $response = $this->actingAs($this->customerUser)
        ->withSession(['reservation_cart' => [$this->gown->id]])
        ->post(route('customer.reserve.store'), $this->reservationData);

    $reservation = Reservation::with(['items', 'payments'])->firstOrFail();

    $response->assertRedirect(route('customer.reservations.show', $reservation));
    expect($reservation->customer->contact_number)->toBe('09171234567')
        ->and($reservation->agreement_accepted_ip)->toBe('127.0.0.1')
        ->and($reservation->event_date)->toBeNull()
        ->and((float) $reservation->security_deposit_total)->toBe(0.0)
        ->and((float) $reservation->grand_total)->toBe(2500.0)
        ->and((float) $reservation->amount_paid)->toBe(0.0)
        ->and((float) $reservation->balance)->toBe(2500.0)
        ->and($reservation->government_id_photo_path)->toBeNull()
        ->and($reservation->payments)->toHaveCount(1)
        ->and($reservation->payments->first()->payment_method)->toBe('cash')
        ->and($reservation->payments->first()->status)->toBe('pending')
        ->and((float) $reservation->payments->first()->amount)->toBe(600.0)
        ->and($reservation->payment_reference_number)->toBeNull();
});

it('shows customer reservations in a clickable table and opens reservation details for staff', function () {
    $this->actingAs($this->customerUser)
        ->withSession(['reservation_cart' => [$this->gown->id]])
        ->post(route('customer.reserve.store'), $this->reservationData);

    $reservation = Reservation::with('customer')->firstOrFail();

    $this->actingAs($this->customerUser)
        ->get(route('customer.reservations'))
        ->assertOk()
        ->assertSee('sb-customer-reservations-table', false)
        ->assertSee('data-href="' . route('customer.reservations.show', $reservation) . '"', false)
        ->assertSee($reservation->reservation_code)
        ->assertDontSee('>More</summary>', false)
        ->assertDontSee('data-customer-payment-form', false);

    $owner = User::factory()->create(['role' => 'owner']);
    $this->actingAs($owner)
        ->get(route('owner.reservations'))
        ->assertOk()
        ->assertSee('data-href="' . route('owner.reservations.show', $reservation) . '"', false);

    $this->actingAs($this->customerUser)
        ->get(route('customer.reservations.show', $reservation))
        ->assertOk()
        ->assertSee('data-customer-payment-form', false)
        ->assertSee('>Back</a>', false)
        ->assertDontSee('View Reservation')
        ->assertSee('Cancel Reservation');

    $this->actingAs($owner)
        ->get(route('owner.reservations.show', $reservation))
        ->assertOk()
        ->assertSee('details.')
        ->assertSee($reservation->reservation_code)
        ->assertSee($this->gown->name)
        ->assertSee('class="sb-reservation-gown-photo"', false)
        ->assertSee('alt="' . $this->gown->name . '"', false)
        ->assertSee('src="' . asset('images/image.webp') . '"', false)
        ->assertSee('Payment summary');
});

it('books several different gowns in one reservation with a combined total', function () {
    $secondGown = Gown::create([
        'category_id' => $this->gown->category_id,
        'gown_code' => 'GWN-TEST-002',
        'name' => 'Second test gown',
        'rental_price' => 1500,
        'security_deposit' => 0,
        'status' => 'available',
    ]);

    $this->actingAs($this->customerUser)
        ->withSession(['reservation_cart' => [$this->gown->id, $secondGown->id]])
        ->post(route('customer.reserve.store'), array_replace($this->reservationData, [
            'payment_option' => 'downpayment',
            'payment_amount' => '1000.00',
        ]));

    $reservation = Reservation::with('items')->firstOrFail();
    expect($reservation->items)->toHaveCount(2)
        ->and((float) $reservation->rental_total)->toBe(4000.0)
        ->and((float) $reservation->grand_total)->toBe(4000.0)
        ->and($reservation->items->pluck('gown_id')->all())->toContain($this->gown->id, $secondGown->id);
});

it('rejects a reservation whose cart gowns overlap on the shared dates', function () {
    $existing = Reservation::create([
        'reservation_code' => 'GR-TEST-EXIST',
        'customer_id' => Customer::create([
            'customer_code' => 'CUS-TEST-EXIST',
            'full_name' => 'Existing customer',
            'contact_number' => '09170000000',
            'status' => 'active',
        ])->id,
        'pickup_date' => today()->addDays(2)->toDateString(),
        'return_date' => today()->addDays(3)->toDateString(),
        'rental_total' => 2500,
        'grand_total' => 2500,
        'balance' => 2500,
        'status' => 'confirmed',
    ]);
    $existing->items()->create(['gown_id' => $this->gown->id, 'rental_price' => 2500, 'quantity' => 1]);

    $this->actingAs($this->customerUser)
        ->withSession(['reservation_cart' => [$this->gown->id]])
        ->from(route('customer.reserve'))
        ->post(route('customer.reserve.store'), $this->reservationData)
        ->assertSessionHasErrors('pickup_date');

    expect(Reservation::count())->toBe(1);
});

it('requires a full payment option to match the rental fee', function () {
    $this->actingAs($this->customerUser)
        ->withSession(['reservation_cart' => [$this->gown->id]])
        ->from(route('customer.reserve'))
        ->post(route('customer.reserve.store'), array_replace($this->reservationData, [
            'payment_option' => 'full',
            'payment_amount' => '500.00',
        ]))
        ->assertSessionHasErrors('payment_amount');

    expect(Reservation::count())->toBe(0)
        ->and(Payment::count())->toBe(0);
});

it('rejects down payments of ₱500 or less and does not accept GCash', function () {
    $this->actingAs($this->customerUser)
        ->withSession(['reservation_cart' => [$this->gown->id]])
        ->from(route('customer.reserve'))
        ->post(route('customer.reserve.store'), array_replace($this->reservationData, [
            'payment_amount' => '500.00',
        ]))
        ->assertSessionHasErrors('payment_amount');

    $this->actingAs($this->customerUser)
        ->withSession(['reservation_cart' => [$this->gown->id]])
        ->from(route('customer.reserve'))
        ->post(route('customer.reserve.store'), array_replace($this->reservationData, [
            'payment_method' => 'gcash',
        ]))
        ->assertSessionHasErrors('payment_method');

    expect(Reservation::count())->toBe(0)
        ->and(Payment::count())->toBe(0);
});

it('renders only the required reservation fields from the paper', function () {
    $this->actingAs($this->customerUser)
        ->withSession(['reservation_cart' => [$this->gown->id]])
        ->get(route('customer.reserve'))
        ->assertOk()
        ->assertSee('Test gown')
        ->assertSee('Return date')
        ->assertSee('Full payment')
        ->assertSee('Down payment')
        ->assertSee('Bring one valid government-issued ID')
        ->assertSee('Requirements')
        ->assertSee('Pending approval after', false)
        ->assertSee('data-step-error', false)
        ->assertDontSee('Event date')
        ->assertDontSee('Occasion')
        ->assertDontSee('Height (cm)')
        ->assertDontSee('Gown length')
        ->assertDontSee('GCash')
        ->assertDontSee('Security deposit')
        ->assertDontSee('Bank transfer');
});

it('allows at most three calendar rental days in availability checks', function () {
    $this->actingAs($this->customerUser)
        ->getJson(route('customer.reserve.availability', $this->gown) . '?' . http_build_query([
            'pickup_date' => today()->addDays(2)->toDateString(),
            'return_date' => today()->addDays(4)->toDateString(),
        ]))
        ->assertOk()
        ->assertJsonPath('available', true)
        ->assertJsonPath('rental_days', 3);

    $this->actingAs($this->customerUser)
        ->getJson(route('customer.reserve.availability', $this->gown) . '?' . http_build_query([
            'pickup_date' => today()->addDays(2)->toDateString(),
            'return_date' => today()->addDays(5)->toDateString(),
        ]))
        ->assertOk()
        ->assertJsonPath('available', false);
});

it('confirms cancellation and retains the reservation and verified down payment', function () {
    $this->actingAs($this->customerUser)
        ->withSession(['reservation_cart' => [$this->gown->id]])
        ->post(route('customer.reserve.store'), $this->reservationData);

    $reservation = Reservation::with('payments')->firstOrFail();
    $payment = $reservation->payments->first();
    $payment->update(['status' => 'verified', 'verified_at' => now()]);
    $reservation->update(['amount_paid' => 600, 'balance' => 1900]);

    $this->actingAs($this->customerUser)
        ->get(route('customer.reservations'))
        ->assertOk()
        ->assertSee('sb-customer-reservations-table', false)
        ->assertDontSee('data-cancel-action=', false);

    $this->actingAs($this->customerUser)
        ->get(route('customer.reservations.show', $reservation))
        ->assertOk()
        ->assertSee('Cancel reservation?')
        ->assertSee('Keep Reservation')
        ->assertSee('Cancel Reservation')
        ->assertSee('600.00', false);

    $this->actingAs($this->customerUser)
        ->patch(route('customer.reservations.cancel', $reservation))
        ->assertRedirect(route('customer.reservations.show', $reservation));

    $reservation->refresh();
    expect($reservation->status)->toBe('cancelled')
        ->and(Reservation::count())->toBe(1)
        ->and(Payment::count())->toBe(1)
        ->and((float) $reservation->amount_paid)->toBe(600.0);

    $this->actingAs($this->customerUser)
        ->get(route('customer.reservations.show', $reservation))
        ->assertOk()
        ->assertSee('Reservation Cancelled')
        ->assertSee('Your reservation for Test gown has been cancelled.')
        ->assertSee('₱600.00')
        ->assertSee('₱0.00')
        ->assertSee('retained according to the cancellation policy')
        ->assertSee('Back to My Reservations')
        ->assertDontSee('Reservation lifecycle');

    $owner = User::factory()->create(['role' => 'owner']);
    $this->actingAs($owner)
        ->get(route('owner.customers.show', $reservation->customer))
        ->assertOk()
        ->assertSee('Cancelled by')
        ->assertSee($this->customerUser->name)
        ->assertSee('Down payment retained')
        ->assertSee('₱600.00')
        ->assertSee('Refund')
        ->assertSee('View Reservation History');

    expect(AuditLog::where('description', 'like', '%' . $reservation->reservation_code . '%')->count())->toBe(2);
});

it('shows owner actions in a floating manage panel and hides payments for cancelled bookings', function () {
    $this->actingAs($this->customerUser)
        ->withSession(['reservation_cart' => [$this->gown->id]])
        ->post(route('customer.reserve.store'), $this->reservationData);

    $reservation = Reservation::firstOrFail();
    $reservation->update(['status' => 'cancelled']);
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->get(route('owner.reservations'))
        ->assertOk()
        ->assertSee('Manage')
        ->assertSee('sb-row-actions-panel', false)
        ->assertDontSee('Record cash payment');
});

it('renders owner payment review actions without an undefined route base', function () {
    $this->actingAs($this->customerUser)
        ->withSession(['reservation_cart' => [$this->gown->id]])
        ->post(route('customer.reserve.store'), $this->reservationData);

    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->get(route('owner.payments'))
        ->assertOk()
        ->assertSee('Verify')
        ->assertSee('Reject');
});

it('renders full customer history and employee edit forms inside their panels', function () {
    $this->actingAs($this->customerUser)
        ->withSession(['reservation_cart' => [$this->gown->id]])
        ->post(route('customer.reserve.store'), $this->reservationData);

    $reservation = Reservation::firstOrFail();
    $owner = User::factory()->create(['role' => 'owner']);
    $this->actingAs($owner)
        ->get(route('owner.customers.show', $reservation->customer))
        ->assertOk()
        ->assertSee($reservation->reservation_code)
        ->assertSee($this->gown->name)
        ->assertSee('Payments')
        ->assertSee('Penalties and balance');

    $employeeUser = User::factory()->create(['role' => 'employee']);
    $employee = Employee::create([
        'user_id' => $employeeUser->id,
        'employee_code' => 'EMP-TEST-001',
        'full_name' => $employeeUser->name,
        'position' => 'Rental Staff',
        'status' => 'active',
    ]);

    $this->actingAs($owner)
        ->get(route('owner.employees'))
        ->assertOk()
        ->assertSee($employee->employee_code)
        ->assertSee('sb-employee-edit-panel', false)
        ->assertSee('Save details');
});

it('downloads a generated business report as a PDF', function () {
    $this->actingAs($this->customerUser)
        ->withSession(['reservation_cart' => [$this->gown->id]])
        ->post(route('customer.reserve.store'), $this->reservationData);

    $owner = User::factory()->create(['role' => 'owner']);

    $response = $this->actingAs($owner)
        ->get(route('owner.reports.pdf', ['period' => 'this_year']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect(substr($response->getContent(), 0, 5))->toBe('%PDF-');
});