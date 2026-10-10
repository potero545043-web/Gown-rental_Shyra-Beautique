<?php

use App\Models\Category;
use App\Models\DamageReport;
use App\Models\Gown;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $category = Category::create(['name' => 'Damage test gowns']);
    $this->gown = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-DAMAGE-TEST-001',
        'name' => 'Damage test gown',
        'rental_price' => 1800,
        'security_deposit' => 0,
        'status' => 'available',
    ]);
});

it('shows the damage reporting action and form to the owner', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $this->actingAs($owner)
        ->get(route('owner.damages'))
        ->assertOk()
        ->assertSee('Report damage')
        ->assertSee('Damage description')
        ->assertSee('Date discovered')
        ->assertSee('Photo evidence')
        ->assertSee('Estimated repair cost')
        ->assertDontSee('Estimated repairs');
});

it('records damage details, private photo evidence, and an unavailable gown status', function () {
    $owner = User::factory()->create(['role' => 'owner']);

    $response = $this->actingAs($owner)->post(route('owner.damages.store'), [
        'gown_id' => $this->gown->id,
        'description' => 'Torn seam near the left sleeve.',
        'severity' => 'moderate',
        'discovered_at' => today()->toDateString(),
        'estimated_repair_cost' => '1250.00',
        'mark_unavailable' => '1',
        'photo' => UploadedFile::fake()->image('damage.jpg'),
    ]);

    $response->assertRedirect(route('owner.damages'));

    $damage = DamageReport::firstOrFail();
    expect($damage->gown_id)->toBe($this->gown->id)
        ->and($damage->description)->toBe('Torn seam near the left sleeve.')
        ->and($damage->severity)->toBe('moderate')
        ->and($damage->estimated_repair_cost)->toBe('1250.00')
        ->and($damage->final_repair_cost)->toBeNull()
        ->and($damage->photos)->toStartWith('damage-evidence/');

    expect($this->gown->fresh()->status)->toBe('damaged')
        ->and($this->gown->fresh()->condition)->toBe('damaged');

    Storage::disk('local')->assertExists($damage->photos);
    $this->get(route('owner.damages.photo', $damage))->assertOk();
});

it('links a damage report to a relevant rental and rejects unrelated rentals', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $customer = \App\Models\Customer::create([
        'customer_code' => 'CUS-DAMAGE-TEST-001',
        'full_name' => 'Damage test customer',
        'contact_number' => '09123456789',
        'status' => 'active',
    ]);
    $reservation = Reservation::create([
        'reservation_code' => 'RES-DAMAGE-TEST-001',
        'customer_id' => $customer->id,
        'pickup_date' => today(),
        'return_date' => today(),
        'rental_total' => 1800,
        'grand_total' => 1800,
        'balance' => 0,
        'status' => 'returned',
    ]);
    $reservation->items()->create([
        'gown_id' => $this->gown->id,
        'rental_price' => 1800,
        'security_deposit' => 0,
    ]);

    $this->actingAs($owner)->post(route('owner.damages.store'), [
        'gown_id' => $this->gown->id,
        'reservation_id' => $reservation->id,
        'description' => 'Loose beading.',
        'severity' => 'minor',
        'discovered_at' => today()->toDateString(),
    ])->assertRedirect(route('owner.damages'));

    expect(DamageReport::firstOrFail()->reservation_id)->toBe($reservation->id);

    $otherGown = Gown::create([
        'category_id' => $this->gown->category_id,
        'gown_code' => 'GWN-DAMAGE-TEST-002',
        'name' => 'Unrelated gown',
        'rental_price' => 1900,
        'security_deposit' => 0,
        'status' => 'available',
    ]);

    $this->post(route('owner.damages.store'), [
        'gown_id' => $otherGown->id,
        'reservation_id' => $reservation->id,
        'description' => 'Unrelated damage.',
        'severity' => 'minor',
        'discovered_at' => today()->toDateString(),
    ])->assertSessionHasErrors('reservation_id');
});

it('allows the owner to record the final repair cost later', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $damage = DamageReport::create([
        'gown_id' => $this->gown->id,
        'damage_type' => 'Manual damage report',
        'description' => 'Missing bead.',
        'severity' => 'minor',
        'discovered_at' => today(),
        'estimated_repair_cost' => 500,
    ]);

    $this->actingAs($owner)
        ->patch(route('owner.damages.final-cost', $damage), ['final_repair_cost' => '425.50'])
        ->assertRedirect();

    expect($damage->fresh()->final_repair_cost)->toBe('425.50')
        ->and($damage->fresh()->repair_cost)->toBe('425.50');
});

it('prevents employees from creating damage reports or viewing private evidence', function () {
    $employee = User::factory()->create(['role' => 'employee']);

    $this->actingAs($employee)
        ->post(route('owner.damages.store'), [
            'gown_id' => $this->gown->id,
            'description' => 'Unauthorized report.',
            'severity' => 'minor',
            'discovered_at' => today()->toDateString(),
        ])
        ->assertForbidden();

    $damage = DamageReport::create([
        'gown_id' => $this->gown->id,
        'damage_type' => 'Test',
        'description' => 'Private photo test.',
        'severity' => 'minor',
        'photos' => 'missing-private-file.jpg',
    ]);

    $this->get(route('owner.damages.photo', $damage))->assertForbidden();
});
