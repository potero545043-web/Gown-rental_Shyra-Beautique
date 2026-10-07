<x-app-layout>

    <div class="sb-page sb-inventory-page">
        <div class="sb-wrap">

            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">INVENTORY · CATEGORIES</span>

                    <h1>Edit <em>{{ $category->name }}.</em></h1>

                    <p>Category changes also update how assigned gowns appear in the collection.</p>
                </div>

                <a class="sb-inventory-secondary" href="{{ route('owner.categories.index') }}">
                    Back to categories
                </a>
            </div>

            @if($errors->any())
                <div class="sb-form-errors">{{ $errors->first() }}</div>
            @endif

            <section class="sb-panel sb-inventory-form-card">

                <form method="POST" action="{{ route('owner.categories.update', $category) }}"
                    enctype="multipart/form-data">

                    @csrf
                    @method('PUT')

                    <h2>Category details</h2>

                    <label>Category name
                        <input name="name" value="{{ old('name', $category->name) }}" maxlength="255" required>
                    </label>

                    <label>Description
                        <textarea name="description"
                            rows="4">{{ old('description', $category->description) }}</textarea>
                    </label>

                    <label>Category image
                        <img id="category-preview" class="sb-image-preview" alt="{{ $category->name }}"
                            @if($category->image) src="{{ asset('storage/' . $category->image) }}" @else hidden @endif>

                        <input type="file" name="image" accept="image/png,image/jpeg,image/webp"
                            onchange="const f=this.files[0],p=document.getElementById('category-preview'); if(f){p.src=URL.createObjectURL(f); p.hidden=false;}">

                        <small class="sb-field-hint">
                            {{ $category->image ? 'Choose a file only if you want to replace the current image.' : 'JPG, PNG or WEBP, up to 2 MB.' }}
                        </small>
                    </label>

                    <label class="sb-inventory-check">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))>
                        Available for new gown assignments
                    </label>

                    <div class="sb-inventory-form-actions">
                        <a class="sb-inventory-secondary" href="{{ route('owner.categories.index') }}">Cancel</a>

                        <button class="sb-inventory-primary" type="submit">Save category</button>
                    </div>

                </form>

            </section>

        </div>
    </div>

</x-app-layout>