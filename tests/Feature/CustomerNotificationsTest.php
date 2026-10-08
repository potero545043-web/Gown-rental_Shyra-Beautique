<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\Gown;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\ReservationLifecycleNotification;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->customerUser = User::factory()->create(['role' => 'customer']);
    $this->customer = Customer::create([
        'user_id' => $this->customerUser->id,
        'customer_code' => 'CUS-NOTIFY-001',
        'full_name' => $this->customerUser->name,
        'contact_number' => '09171234567',
        'email' => $this->customerUser->email,
        'status' => 'active',
    ]);

    $category = Category::create(['name' => 'Formal gowns']);
    $this->gown = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-NOTIFY-001',
        'name' => 'Blue Evening Gown',
        'rental_price' => 2500,
        'status' => 'available',
    ]);

    $this->reservation = Reservation::create([
        'reservation_code' => 'GR-NOTIFY-001',
        'customer_id' => $this->customer->id,
        'pickup_date' => today()->addDays(2),
        'return_date' => today()->addDays(3),
        'rental_total' => 2500,
        'grand_total' => 2500,
        'balance' => 2500,
        'status' => 'pending',
    ]);
    $this->reservation->items()->create([
        'gown_id' => $this->gown->id,
        'rental_price' => 2500,
    ]);
});

it('notifies a customer when their reservation is confirmed and provides an inbox', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->patch(route('owner.reservations.update', $this->reservation), ['status' => 'confirmed'])
        ->assertRedirect();

    $notification = $this->customerUser->fresh()->notifications()->firstOrFail();
    expect($notification->data['event'])->toBe('confirmed')
        ->and($notification->data['message'])->toContain('Blue Evening Gown')
        ->and($notification->data['url'])->toContain(route('customer.reservations.show', $this->reservation));

    $this->actingAs($this->customerUser)
        ->get(route('customer.notifications'))
        ->assertOk()
        ->assertSee('Reservation confirmed')
        ->assertSee('Blue Evening Gown');

    $this->post(route('customer.notifications.read', ['notification' => $notification->id]))
        ->assertRedirect();

    expect($this->customerUser->fresh()->unreadNotifications()->count())->toBe(0);
});

it('notifies a customer when an owner changes their reservation dates', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->patch(route('owner.reservations.update', $this->reservation), [
            'status' => 'pending',
            'admin_notes' => 'Please confirm the updated dates.',
            'pickup_date' => today()->addDays(4)->toDateString(),
            'return_date' => today()->addDays(5)->toDateString(),
        ])
        ->assertRedirect();

    expect($this->customerUser->fresh()->notifications()->firstOrFail()->data['event'])
        ->toBe('updated');
});

it('rejects owner date changes that conflict with another reservation', function () {
    $otherReservation = Reservation::create([
        'reservation_code' => 'GR-NOTIFY-002',
        'customer_id' => $this->customer->id,
        'pickup_date' => today()->addDays(5),
        'return_date' => today()->addDays(6),
        'rental_total' => 2500,
        'grand_total' => 2500,
        'balance' => 2500,
        'status' => 'confirmed',
    ]);
    $otherReservation->items()->create([
        'gown_id' => $this->gown->id,
        'rental_price' => 2500,
    ]);
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->from(route('owner.reservations'))
        ->patch(route('owner.reservations.update', $this->reservation), [
            'status' => 'pending',
            'pickup_date' => today()->addDays(5)->toDateString(),
            'return_date' => today()->addDays(6)->toDateString(),
        ])
        ->assertSessionHasErrors('pickup_date');

    expect($this->reservation->fresh()->pickup_date->toDateString())
        ->toBe(today()->addDays(2)->toDateString());
});

it('allows status updates without changing an existing past reservation date', function () {
    $this->reservation->update([
        'pickup_date' => today()->subDays(2)->toDateString(),
        'return_date' => today()->subDays(1)->toDateString(),
    ]);
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->patch(route('owner.reservations.update', $this->reservation), [
            'status' => 'confirmed',
            'pickup_date' => today()->subDays(2)->toDateString(),
            'return_date' => today()->subDays(1)->toDateString(),
        ])
        ->assertRedirect();

    expect($this->reservation->fresh()->status)->toBe('confirmed');
});

it('notifies customers when a reservation is ready for pickup or cancelled', function () {
    $this->reservation->update(['status' => 'ready_for_pickup']);
    $this->reservation->update(['status' => 'cancelled']);

    expect($this->customerUser->fresh()->notifications()->where('data->event', 'ready_for_pickup')->exists())
        ->toBeTrue()
        ->and($this->customerUser->fresh()->notifications()->where('data->event', 'cancelled')->exists())
        ->toBeTrue();
});

it('sends pickup reminders once for reservations due tomorrow', function () {
    $this->reservation->update([
        'status' => 'confirmed',
        'pickup_date' => Carbon::tomorrow()->toDateString(),
    ]);

    $this->artisan('app:send-reservation-reminders')
        ->expectsOutput('Sent 1 reservation reminder notification(s).')
        ->assertSuccessful();
    $this->artisan('app:send-reservation-reminders')
        ->expectsOutput('Sent 0 reservation reminder notification(s).')
        ->assertSuccessful();

    expect($this->customerUser->fresh()->notifications()->count())->toBe(2)
        ->and($this->customerUser->fresh()->notifications()->where('data->event', 'pickup_reminder')->exists())
        ->toBeTrue();
});

it('sends return and overdue reminders to the customer', function () {
    $this->reservation->update([
        'status' => 'released',
        'return_date' => Carbon::tomorrow()->toDateString(),
    ]);
    $this->artisan('app:send-reservation-reminders')->assertSuccessful();

    $this->reservation->update([
        'status' => 'overdue',
        'return_date' => Carbon::yesterday()->toDateString(),
    ]);
    $this->artisan('app:send-reservation-reminders')->assertSuccessful();

    expect($this->customerUser->fresh()->notifications()->where('data->event', 'return_reminder')->exists())
        ->toBeTrue()
        ->and($this->customerUser->fresh()->notifications()->where('data->event', 'overdue')->exists())
        ->toBeTrue();
});

it('links owner dashboard reservations to payment records', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->get(route('owner.dashboard'))
        ->assertOk()
        ->assertSee(route('owner.payments', ['q' => $this->reservation->reservation_code]), false)
        ->assertSee('View payments');
});

it('does not expose customer notifications to another account', function () {
    $this->customerUser->notify(new ReservationLifecycleNotification($this->reservation, 'confirmed'));
    $otherCustomer = User::factory()->create(['role' => 'customer']);

    $this->actingAs($otherCustomer)
        ->post(route('customer.notifications.read', ['notification' => $this->customerUser->notifications()->firstOrFail()->id]))
        ->assertNotFound();
});
