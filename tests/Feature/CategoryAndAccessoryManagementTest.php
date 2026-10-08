<?php

use App\Models\Accessory;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->owner = User::factory()->create(['role' => 'owner']);
});

it('lets the owner open the category form without a category image field', function () {
    $this->actingAs($this->owner)
        ->get(route('owner.categories.index'))
        ->assertOk()
        ->assertSee('Add category')
        ->assertSee("document.getElementById('category-create-dialog').showModal()", false)
        ->assertDontSee('Category image');
});

it('creates and updates categories without requiring an image', function () {
    $this->actingAs($this->owner)
        ->post(route('owner.categories.store'), [
            'name' => 'Evening gowns',
            'description' => 'Formal floor-length gowns',
        ])
        ->assertRedirect(route('owner.categories.index'));

    $category = Category::where('name', 'Evening gowns')->firstOrFail();
    expect($category->description)->toBe('Formal floor-length gowns');

    $this->actingAs($this->owner)
        ->get(route('owner.categories.edit', $category))
        ->assertOk()
        ->assertDontSee('Category image');

    $this->put(route('owner.categories.update', $category), [
        'name' => 'Formal gowns',
        'description' => 'Updated description',
        'is_active' => '1',
    ])->assertRedirect(route('owner.categories.index'));

    expect($category->fresh()->name)->toBe('Formal gowns');
});

it('removes the category-level add gown shortcut', function () {
    $category = Category::create(['name' => 'Bridal']);

    $this->actingAs($this->owner)
        ->get(route('owner.categories.show', $category))
        ->assertOk()
        ->assertDontSee('Add a gown')
        ->assertDontSee(route('owner.gowns.create'), false);
});

it('shows the uploaded accessory image in inventory and item details', function () {
    Storage::fake('public');
    $image = UploadedFile::fake()->image('tiara.jpg');

    $this->actingAs($this->owner)
        ->get(route('owner.accessories.create'))
        ->assertOk()
        ->assertSee('id="accessory-preview"', false)
        ->assertSee("p.style.display='block'", false);

    $this->actingAs($this->owner)
        ->post(route('owner.accessories.store'), [
            'name' => 'Crystal tiara',
            'description' => 'Silver accessory',
            'image' => $image,
            'quantity' => 2,
            'replacement_cost' => 500,
            'status' => 'available',
        ])
        ->assertRedirect(route('owner.accessories.index'));

    $accessory = Accessory::where('name', 'Crystal tiara')->firstOrFail();
    Storage::disk('public')->assertExists($accessory->image);
    $imageUrl = asset('storage/' . $accessory->image);

    $this->actingAs($this->owner)
        ->get(route('owner.accessories.index'))
        ->assertOk()
        ->assertSee('id="accessory-create-preview"', false)
        ->assertSee("p.style.display='block'", false)
        ->assertSee($imageUrl, false)
        ->assertSee('alt="Crystal tiara"', false);

    $this->get(route('owner.accessories.show', $accessory))
        ->assertOk()
        ->assertSee($imageUrl, false)
        ->assertSee('alt="Crystal tiara"', false);
});
