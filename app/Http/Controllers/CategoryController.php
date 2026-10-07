<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * Display all categories.
     */
    public function index()
    {
        $categories = Category::withCount('gowns')->latest()->get();

        return view('categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a category.
     */
    public function create()
    {
        return view('categories.create');
    }

    /**
     * Store a new category.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        Category::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image' => $request->hasFile('image')
                ? $request->file('image')->store('categories', 'public')
                : null,
            'is_active' => true,
        ]);

        return redirect()
            ->route('owner.categories.index')
            ->with('success', 'Category added successfully.');
    }

    /**
     * Display a specific category.
     */
    public function show(Category $category)
    {
        return view('categories.show', compact('category'));
    }

    /**
     * Show the form for editing a category.
     */
    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    /**
     * Update a category.
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active'),
        ];

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }

            $data['image'] = $request->file('image')->store('categories', 'public');
        }

        $category->update($data);

        return redirect()
            ->route('owner.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Deactivate a category.
     */
    public function destroy(Category $category)
    {
        $category->update([
            'is_active' => false,
        ]);

        return redirect()
            ->route('owner.categories.index')
            ->with('success', 'Category deactivated successfully.');
    }
}
