<?php

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DamageReport;
use App\Models\Employee;
use App\Models\Gown;
use App\Models\GownPurchase;
use App\Models\Penalty;
use App\Models\Reservation;
use App\Models\User;

it('keeps linked reservation names current when customers update their profile', function () {
    $user = User::factory()->create(['role' => 'customer', 'name' => 'Former Customer']);
    $customer = Customer::create([
        'user_id' => $user->id,
        'customer_code' => 'CUS-NAME-001',
        'full_name' => $user->name,
        'contact_number' => '09123456789',
        'status' => 'active',
    ]);

    foreach (['cancelled', 'completed'] as $index => $status) {
        Reservation::create([
            'reservation_code' => 'SB-NAME-' . $index,
            'customer_id' => $customer->id,
            'pickup_date' => today(),
            'return_date' => today(),
            'rental_total' => 1000,
            'grand_total' => 1000,
            'balance' => 0,
            'status' => $status,
        ]);
    }

    $this->actingAs($user)->patch('/profile', [
        'name' => 'Updated Customer',
        'email' => $user->email,
    ])->assertSessionHasNoErrors();

    expect($customer->fresh()->full_name)->toBe('Updated Customer')
        ->and(Reservation::with('customer')->get()->pluck('customer.full_name')->unique()->all())
        ->toBe(['Updated Customer']);
});

it('keeps employee directory names current when employees update their profile', function () {
    $user = User::factory()->create(['role' => 'employee', 'name' => 'Former Employee']);
    $employee = Employee::create([
        'user_id' => $user->id,
        'employee_code' => 'EMP-NAME-001',
        'full_name' => $user->name,
        'status' => 'active',
    ]);

    $this->actingAs($user)->patch('/profile', [
        'name' => 'Updated Employee',
        'email' => $user->email,
    ])->assertSessionHasNoErrors();

    expect($employee->fresh()->full_name)->toBe('Updated Employee')
        ->and($user->fresh()->name)->toBe('Updated Employee');
});

it('updates a customers name on every linked transaction from management', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $customer = Customer::create([
        'customer_code' => 'CUS-NAME-002',
        'full_name' => 'Test Customer',
        'contact_number' => '09123456789',
        'status' => 'active',
    ]);
    $reservation = Reservation::create([
        'reservation_code' => 'SB-NAME-OLD',
        'customer_id' => $customer->id,
        'pickup_date' => today(),
        'return_date' => today(),
        'rental_total' => 1000,
        'grand_total' => 1000,
        'balance' => 0,
        'status' => 'cancelled',
    ]);

    $this->actingAs($owner)
        ->patch(route('owner.customers.update', $customer), ['full_name' => 'Corrected Customer'])
        ->assertSessionHasNoErrors();

    $this->actingAs($owner)
        ->get(route('owner.customers.show', $customer))
        ->assertOk()
        ->assertSee('Corrected Customer')
        ->assertSee($reservation->reservation_code);

    expect($reservation->fresh()->customer->full_name)->toBe('Corrected Customer');
});

it('renders historical approval actors using their current employee name', function () {
    $user = User::factory()->create(['role' => 'employee', 'name' => 'Old Staff Name']);
    $audit = AuditLog::create([
        'user_id' => $user->id,
        'action' => 'reservation.approved',
        'description' => 'Reservation SB-HISTORY-01: Approved by Old Staff Name.',
    ]);

    $user->update(['name' => 'Current Staff Name']);

    expect($audit->fresh()->display_description)
        ->toBe('Reservation SB-HISTORY-01: Approved by Current Staff Name.');
});

it('records customer gown purchases and removes sold gowns from rental inventory', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $customerUser = User::factory()->create(['role' => 'customer', 'name' => 'Purchase Customer']);
    $customer = Customer::create([
        'user_id' => $customerUser->id,
        'customer_code' => 'CUS-SALE-001',
        'full_name' => 'Purchase Customer',
        'contact_number' => '09123456789',
        'status' => 'active',
    ]);
    $category = Category::create(['name' => 'Sale gowns']);
    $gown = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-SALE-001',
        'name' => 'Purchased gown',
        'rental_price' => 2000,
        'security_deposit' => 0,
        'status' => 'rented',
    ]);
    $reservation = Reservation::create([
        'reservation_code' => 'SB-SALE-001',
        'customer_id' => $customer->id,
        'pickup_date' => today(),
        'return_date' => today(),
        'rental_total' => 2000,
        'grand_total' => 2000,
        'amount_paid' => 0,
        'balance' => 2000,
        'status' => 'released',
    ]);
    $reservation->items()->create([
        'gown_id' => $gown->id,
        'rental_price' => 2000,
        'security_deposit' => 0,
        'quantity' => 1,
    ]);

    $this->actingAs($owner)
        ->post(route('owner.rentals.return', $reservation), [
            'condition_after' => 'damaged',
            'repair_cost' => 500,
            'purchased_gown_ids' => [$gown->id],
            'purchase_amounts' => [$gown->id => 3500],
            'notes' => 'Customer purchased the damaged gown.',
        ])
        ->assertSessionHasNoErrors();

    expect($gown->fresh()->status)->toBe('retired')
        ->and($gown->fresh()->condition)->toBe('damaged')
        ->and(GownPurchase::where('reservation_id', $reservation->id)->value('amount'))->toBe('3500.00')
        ->and((float) $reservation->fresh()->grand_total)->toBe(5500.0)
        ->and((float) $reservation->fresh()->balance)->toBe(5500.0)
        ->and(Penalty::where('reservation_id', $reservation->id)->pluck('penalty_type')->all())
        ->toBe(['gown_purchase']);

    $this->actingAs($customerUser)
        ->get(route('customer.reservations.show', $reservation))
        ->assertOk()
        ->assertSee('₱5,500.00')
        ->assertDontSee('₱9,000.00');
});

it('requires an agreed sale amount before accepting a customer gown purchase', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $customer = Customer::create([
        'customer_code' => 'CUS-SALE-002',
        'full_name' => 'Purchase Customer Two',
        'contact_number' => '09123456789',
        'status' => 'active',
    ]);
    $category = Category::create(['name' => 'Sale gowns']);
    $gown = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-SALE-002',
        'name' => 'Second gown',
        'rental_price' => 1500,
        'security_deposit' => 0,
        'status' => 'rented',
    ]);
    $reservation = Reservation::create([
        'reservation_code' => 'SB-SALE-002',
        'customer_id' => $customer->id,
        'pickup_date' => today(),
        'return_date' => today(),
        'rental_total' => 1500,
        'grand_total' => 1500,
        'balance' => 1500,
        'status' => 'released',
    ]);
    $reservation->items()->create([
        'gown_id' => $gown->id,
        'rental_price' => 1500,
        'security_deposit' => 0,
        'quantity' => 1,
    ]);

    $this->actingAs($owner)
        ->from(route('owner.rentals'))
        ->post(route('owner.rentals.return', $reservation), [
            'condition_after' => 'good',
            'purchased_gown_ids' => [$gown->id],
        ])
        ->assertSessionHasErrors('purchase_amounts.' . $gown->id);

    expect($reservation->fresh()->status)->toBe('released')
        ->and($gown->fresh()->status)->toBe('rented')
        ->and(GownPurchase::where('reservation_id', $reservation->id)->exists())->toBeFalse();
});

it('keeps damaged gowns out of rental inventory and records their repair charge', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $customer = Customer::create([
        'customer_code' => 'CUS-DAMAGE-001',
        'full_name' => 'Damage Customer',
        'contact_number' => '09123456789',
        'status' => 'active',
    ]);
    $category = Category::create(['name' => 'Damaged gowns']);
    $gown = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-DAMAGE-001',
        'name' => 'Damaged gown',
        'rental_price' => 1800,
        'security_deposit' => 0,
        'status' => 'rented',
    ]);
    $reservation = Reservation::create([
        'reservation_code' => 'SB-DAMAGE-001',
        'customer_id' => $customer->id,
        'pickup_date' => today(),
        'return_date' => today(),
        'rental_total' => 1800,
        'grand_total' => 1800,
        'balance' => 1800,
        'status' => 'released',
    ]);
    $reservation->items()->create([
        'gown_id' => $gown->id,
        'rental_price' => 1800,
        'security_deposit' => 0,
        'quantity' => 1,
    ]);

    $this->actingAs($owner)
        ->post(route('owner.rentals.return', $reservation), [
            'condition_after' => 'damaged',
            'repair_cost' => 650,
        ])
        ->assertSessionHasNoErrors();

    expect($gown->fresh()->status)->toBe('damaged')
        ->and(DamageReport::where('gown_id', $gown->id)->exists())->toBeTrue()
        ->and(Penalty::where('reservation_id', $reservation->id)->value('penalty_type'))->toBe('damage_fee')
        ->and((float) $reservation->fresh()->balance)->toBe(2450.0)
        ->and(GownPurchase::where('reservation_id', $reservation->id)->exists())->toBeFalse();
});

it('places archive under management and exposes landing links in the mobile menu', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $ownerHtml = $this->actingAs($owner)->get(route('owner.dashboard'))->assertOk()->getContent();
    preg_match('/<nav class="sb-side-links" data-nav-section="management">(.*?)<\\/nav>/s', $ownerHtml, $managementSection);
    preg_match('/<div class="sb-inventory-subnav">(.*?)<\\/div>/s', $ownerHtml, $inventorySection);

    expect($managementSection[1] ?? '')->toContain('>Archive</span>')
        ->and($inventorySection[1] ?? '')->not->toContain('Archive');

    auth()->logout();
    $this->get('/')
        ->assertOk()
        ->assertSee('aria-label="Toggle site navigation"', false)
        ->assertSee('id="sb-home-mobile-menu"', false)
        ->assertSee('href="#about"', false)
        ->assertSee('href="#contact"', false);
});
