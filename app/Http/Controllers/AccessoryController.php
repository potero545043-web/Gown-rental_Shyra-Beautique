<?php

namespace App\Http\Controllers;

use App\Models\Accessory;
use App\Services\VercelBlobStorage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccessoryController extends Controller
{
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'quantity' => ['required', 'integer', 'min:0'],
            'replacement_cost' => ['nullable', 'numeric', 'min:0'],
            'status' => [
                'required',
                Rule::in(['available', 'unavailable', 'damaged', 'retired']),
            ],
        ];
    }

    public function index(Request $request)
    {
        $perPage = in_array($request->integer('per_page', 12), [12, 24, 48], true)
            ? $request->integer('per_page', 12)
            : 12;
        $accessories = Accessory::withCount('gowns')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('accessories.index', compact('accessories'));
    }

    public function create()
    {
        return view('accessories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        if ($request->hasFile('image')) {
            $validated['image'] = app(VercelBlobStorage::class)
                ->store($request->file('image'), 'accessories', 'public');
        }

        Accessory::create($validated);

        return redirect()
            ->route('owner.accessories.index')
            ->with('success', 'Accessory added successfully.');
    }

    public function show(Accessory $accessory)
    {
        $accessory->load([
            'gowns.category',
        ]);

        return view('accessories.show', compact('accessory'));
    }

    public function edit(Accessory $accessory)
    {
        return view('accessories.edit', compact('accessory'));
    }

    public function update(Request $request, Accessory $accessory)
    {
        $validated = $request->validate($this->rules());

        $previousImage = $accessory->image;
        if ($request->hasFile('image')) {
            $validated['image'] = app(VercelBlobStorage::class)
                ->store($request->file('image'), 'accessories', 'public');
        } else {
            unset($validated['image']);
        }

        $accessory->update($validated);
        if ($request->hasFile('image') && $previousImage) {
            app(VercelBlobStorage::class)->delete($previousImage, 'public');
        }

        return redirect()
            ->route('owner.accessories.index')
            ->with('success', 'Accessory updated successfully.');
    }

    public function destroy(Accessory $accessory)
    {
        $accessory->update([
            'status' => 'unavailable',
        ]);

        return redirect()
            ->route('owner.accessories.index')
            ->with('success', 'Accessory marked unavailable successfully.');
    }
}
