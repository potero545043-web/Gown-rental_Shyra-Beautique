<?php

use App\Models\Category;
use App\Models\Employee;
use App\Models\Gown;
use App\Models\Reservation;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ReservationCart;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->customerUser = User::factory()->create(['role' => 'customer']);
    $this->employeeUser = User::factory()->create(['role' => 'employee']);
    $this->ownerUser = User::factory()->create(['role' => 'owner']);
    Employee::create([
        'user_id' => $this->employeeUser->id,
        'employee_code' => 'EMP-CART-001',
        'full_name' => $this->employeeUser->name,
        'position' => 'Rental Staff',
        'status' => 'active',
    ]);

    $category = Category::create(['name' => 'Formal gowns']);
    $this->gownA = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-CART-A',
        'name' => 'Cart gown A',
        'rental_price' => 2000,
        'security_deposit' => 0,
        'status' => 'available',
    ]);
    $this->gownB = Gown::create([
        'category_id' => $category->id,
        'gown_code' => 'GWN-CART-B',
        'name' => 'Cart gown B',
        'rental_price' => 1500,
        'security_deposit' => 0,
        'status' => 'available',
    ]);

    SystemSetting::create(['setting_key' => 'late_fee_per_day', 'setting_value' => '150']);
});

it('keeps customer and staff carts in separate session keys', function () {
    expect(ReservationCart::forRole('customer')->ids())->toBe([])
        ->and(ReservationCart::forRole('employee')->ids())->toBe([])
        ->and(ReservationCart::forRole('owner')->ids())->toBe([])
        ->and(ReservationCart::keyFor('customer'))->not->toBe(ReservationCart::keyFor('employee'))
        ->and(ReservationCart::keyFor('owner'))->not->toBe(ReservationCart::keyFor('employee'));
});

it('adds a gown to the customer cart from the catalog and shows it on the cart page', function () {
    // Adding to the cart keeps the customer on the page they were on (catalog).
    $this->actingAs($this->customerUser)
        ->from(route('customer.catalog'))
        ->post(route('customer.cart.add', $this->gownA))
        ->assertRedirect(route('customer.catalog'));

    expect(ReservationCart::forRole('customer')->ids())->toBe([$this->gownA->id]);

    $this->actingAs($this->customerUser)
        ->get(route('customer.cart'))
        ->assertOk()
        ->assertSee('Cart gown A')
        ->assertSee($this->gownA->gown_code)
        ->assertSee('Reserve 1 gown', false);
});

it('does not add the same customer gown twice', function () {
    $this->actingAs($this->customerUser)->post(route('customer.cart.add', $this->gownA));
    $this->actingAs($this->customerUser)->post(route('customer.cart.add', $this->gownA));

    expect(ReservationCart::forRole('customer')->ids())->toBe([$this->gownA->id]);
});

it('removes and clears customer cart gowns', function () {
    $this->actingAs($this->customerUser)->post(route('customer.cart.add', $this->gownA));
    $this->actingAs($this->customerUser)->post(route('customer.cart.add', $this->gownB));
    expect(ReservationCart::forRole('customer')->ids())->toBe([$this->gownA->id, $this->gownB->id]);

    $this->actingAs($this->customerUser)
        ->delete(route('customer.cart.remove', $this->gownA))
        ->assertRedirect(route('customer.cart'));
    expect(ReservationCart::forRole('customer')->ids())->toBe([$this->gownB->id]);

    $this->actingAs($this->customerUser)
        ->delete(route('customer.cart.clear'))
        ->assertRedirect(route('customer.catalog'));
    expect(ReservationCart::forRole('customer')->ids())->toBe([]);
});

it('adds a gown to the staff cart and shows it on the in-store cart page', function () {
    // Staff stay on the collection after adding, and open the cart themselves.
    $this->actingAs($this->employeeUser)
        ->from(route('employee.catalog'))
        ->post(route('employee.cart.add', $this->gownA))
        ->assertRedirect(route('employee.catalog'));

    expect(ReservationCart::forRole('employee')->ids())->toBe([$this->gownA->id]);

    $this->actingAs($this->employeeUser)
        ->get(route('employee.cart'))
        ->assertOk()
        ->assertSee('Cart gown A')
        ->assertSee('Reserve 1 gown', false);
});

it('reserves all staff cart gowns as one in-store reservation', function () {
    Storage::fake('public');
    $this->actingAs($this->employeeUser)->withSession(['staff_reservation_cart' => [$this->gownA->id, $this->gownB->id]]);

    // The wizard seeded from the cart shows both gowns.
    $this->actingAs($this->employeeUser)
        ->withSession(['staff_reservation_cart' => [$this->gownA->id, $this->gownB->id]])
        ->get(route('employee.reserve'))
        ->assertOk()
        ->assertSee('Cart gown A')
        ->assertSee('Cart gown B');

    $this->actingAs($this->employeeUser)
        ->withSession(['staff_reservation_cart' => [$this->gownA->id, $this->gownB->id]])
        ->post(route('employee.reservations.store'), [
            'customer_name' => 'Walk-in Guest',
            'contact_number' => '09171234567',
            'pickup_date' => today()->addDays(2)->toDateString(),
            'return_date' => today()->addDays(3)->toDateString(),
            'payment_amount' => '3500.00',
            'payment_method' => 'cash',
            'agreement_accepted' => '1',
            'id_safe_slot' => 'Safe A · Slot 01',
            'government_id' => \Illuminate\Http\UploadedFile::fake()->image('id.jpg'),
        ]);

    $reservation = Reservation::with('items')->firstOrFail();
    expect($reservation->items)->toHaveCount(2)
        ->and((float) $reservation->rental_total)->toBe(3500.0)
        ->and($reservation->items->pluck('gown_id')->all())
        ->toContain($this->gownA->id, $this->gownB->id);

    // The cart is emptied after the booking is saved.
    expect(ReservationCart::forRole('employee')->ids())->toBe([]);
});

it('lets owners add gowns to a separate cart and open the bulk reservation flow', function () {
    $catalogHtml = $this->actingAs($this->ownerUser)
        ->get(route('owner.catalog'))
        ->assertOk()
        ->assertSee('My Cart')
        ->assertSee(route('owner.cart.add', $this->gownA))
        ->getContent();

    expect(substr_count($catalogHtml, '>My Cart</span>'))->toBe(1);

    $this->actingAs($this->ownerUser)
        ->get(route('owner.catalog.show', $this->gownA))
        ->assertOk()
        ->assertSee(route('owner.cart.add', $this->gownA));

    $this->actingAs($this->ownerUser)
        ->from(route('owner.catalog'))
        ->post(route('owner.cart.add', $this->gownA))
        ->assertRedirect(route('owner.catalog'));

    expect(ReservationCart::forRole('owner')->ids())->toBe([$this->gownA->id])
        ->and(ReservationCart::forRole('employee')->ids())->toBe([]);

    $this->actingAs($this->ownerUser)
        ->get(route('owner.catalog'))
        ->assertOk()
        ->assertSee('sb-worktop-cart-count', false)
        ->assertSee('href="' . route('owner.cart') . '"', false);

    $this->actingAs($this->ownerUser)
        ->get(route('owner.cart'))
        ->assertOk()
        ->assertSee('Cart gown A')
        ->assertSee(route('owner.reserve'))
        ->assertSee('Reserve 1 gown', false);

    $this->actingAs($this->ownerUser)
        ->get(route('owner.reserve'))
        ->assertOk()
        ->assertSee('Cart gown A');
});
