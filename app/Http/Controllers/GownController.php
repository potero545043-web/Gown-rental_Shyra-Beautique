<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Accessory;
use App\Models\Gown;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class GownController extends Controller
{
    public function index(Request $request)
    {
        $query = Gown::with(['category', 'accessories'])->withCount([
            'reservationItems as bookings_count' => fn($items) => $items->whereHas(
                'reservation',
                fn($reservations) => $reservations->whereNotIn('status', ['cancelled', 'rejected'])
            ),
        ])->latest();
        if ($request->filled('q')) {
            $term = $request->string('q');
            $query->where(fn($builder) => $builder->where('name', 'like', "%$term%")
                ->orWhere('gown_code', 'like', "%$term%")
                ->orWhere('color', 'like', "%$term%"));
        }
        if ($request->filled('category'))
            $query->where('category_id', $request->integer('category'));
        if ($request->filled('status'))
            $query->where('status', $request->string('status'));
        if ($request->filled('condition'))
            $query->where('condition', $request->string('condition'));
        $gowns = $query->paginate(15)->withQueryString();
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $accessories = Accessory::where('status', 'available')->orderBy('name')->get();
        $inventoryStats = [
            'total' => Gown::count(),
            'available' => Gown::where('status', 'available')->count(),
            'rented' => Gown::where('status', 'rented')->count(),
            'maintenance' => Gown::whereIn('status', ['for_cleaning', 'under_maintenance', 'damaged'])->count(),
        ];

        return view('gowns.index', compact('gowns', 'categories', 'accessories', 'inventoryStats'));
    }


    public function create()
    {
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        $accessories = Accessory::where('status', 'available')->orderBy('name')->get();
        return view('gowns.create', compact('categories', 'accessories'));
    }


    private function generateGownCode(): string
    {
        $lastGown = Gown::orderByDesc('id')->first();

        if (!$lastGown) {
            return 'GWN-0001';
        }

        $lastNumber = (int) str_replace(
            'GWN-',
            '',
            $lastGown->gown_code
        );

        $nextNumber = $lastNumber + 1;

        return 'GWN-' . str_pad(
            $nextNumber,
            4,
            '0',
            STR_PAD_LEFT
        );
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => [
                'required',
                'exists:categories,id'
            ],

            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'size' => [
                'required',
                'string',
                'max:50'
            ],

            'color' => [
                'required',
                'string',
                'max:100'
            ],

            'style' => [
                'nullable',
                'string',
                'max:255'
            ],

            'rental_price' => [
                'required',
                'numeric',
                'min:0'
            ],

            'security_deposit' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'purchase_price' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'measurements' => [
                'nullable',
                'string'
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120'
            ],

            'condition' => [
                'required',
                Rule::in([
                    'excellent',
                    'good',
                    'fair',
                    'damaged'
                ])
            ],

            'status' => [
                'required',
                Rule::in([
                    'available',
                    'reserved',
                    'rented',
                    'for_cleaning',
                    'under_maintenance',
                    'damaged',
                    'unavailable',
                    'retired'
                ])
            ],

            'date_purchased' => [
                'nullable',
                'date'
            ],

            'accessories' => ['nullable', 'array'],
            'accessories.*' => ['integer', 'exists:accessories,id'],
        ]);


        $validated['gown_code'] =
            $this->generateGownCode();
        $accessoryIds = $validated['accessories'] ?? [];
        unset($validated['accessories']);


        if ($request->hasFile('image')) {

            $validated['image'] =
                $request
                    ->file('image')
                    ->store('gowns', 'public');
        }


        $validated['security_deposit'] = 0;
        $gown = Gown::create($validated);
        $gown->accessories()->sync(collect($accessoryIds)->mapWithKeys(fn($id) => [$id => ['quantity' => 1]])->all());


        return redirect()
            ->route('owner.gowns.index')
            ->with(
                'success',
                'Gown added successfully.'
            );
    }


    public function show(Gown $gown)
    {
        $gown->load([
            'category',
            'accessories',
            'reservationItems',
        ]);

        return view(
            'gowns.show',
            compact('gown')
        );
    }


    public function edit(Gown $gown)
    {
        $categories = Category::where(fn($q) => $q->where('is_active', true)->orWhere('id', $gown->category_id))
            ->get();

        $accessories = Accessory::where('status', 'available')
            ->orWhereHas('gowns', fn($query) => $query->where('gowns.id', $gown->id))
            ->orderBy('name')->get();
        return view(
            'gowns.edit',
            compact(
                'gown',
                'categories',
                'accessories'
            )
        );
    }


    public function update(
        Request $request,
        Gown $gown
    ) {
        $validated = $request->validate([
            'category_id' => [
                'required',
                'exists:categories,id'
            ],

            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'size' => [
                'required',
                'string',
                'max:50'
            ],

            'color' => [
                'required',
                'string',
                'max:100'
            ],

            'style' => [
                'nullable',
                'string',
                'max:255'
            ],

            'rental_price' => [
                'required',
                'numeric',
                'min:0'
            ],

            'security_deposit' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'purchase_price' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'description' => [
                'nullable',
                'string'
            ],

            'measurements' => [
                'nullable',
                'string'
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120'
            ],

            'condition' => [
                'required',
                Rule::in([
                    'excellent',
                    'good',
                    'fair',
                    'damaged'
                ])
            ],

            'status' => [
                'required',
                Rule::in([
                    'available',
                    'reserved',
                    'rented',
                    'for_cleaning',
                    'under_maintenance',
                    'damaged',
                    'unavailable',
                    'retired'
                ])
            ],

            'date_purchased' => [
                'nullable',
                'date'
            ],

            'accessories' => ['nullable', 'array'],
            'accessories.*' => ['integer', 'exists:accessories,id'],
        ]);
        $accessoryIds = $validated['accessories'] ?? [];
        unset($validated['accessories']);


        if ($request->hasFile('image')) {

            if ($gown->image) {

                Storage::disk('public')
                    ->delete($gown->image);
            }


            $validated['image'] =
                $request
                    ->file('image')
                    ->store('gowns', 'public');
        }


        $validated['security_deposit'] = 0;
        $gown->update($validated);
        $gown->accessories()->sync(collect($accessoryIds)->mapWithKeys(fn($id) => [$id => ['quantity' => 1]])->all());


        return redirect()
            ->route('owner.gowns.index')
            ->with(
                'success',
                'Gown updated successfully.'
            );
    }


    public function destroy(Gown $gown)
    {
        $gown->update([
            'status' => 'unavailable',
        ]);


        return redirect()
            ->route('owner.gowns.index')
            ->with(
                'success',
                'Gown marked unavailable successfully.'
            );
    }
}
