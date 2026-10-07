<x-app-layout>

    <div class="sb-page sb-inventory-page">
        <div class="sb-wrap">

            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">INVENTORY · CATEGORIES</span>

                    <h1>Add a <em>category.</em></h1>

                    <p>Use categories to group similar gown styles for staff and customers.</p>
                </div>

                <a class="sb-inventory-secondary" href="{{ route('owner.categories.index') }}">
                    Back to categories
                </a>
            </div>

            @if($errors->any())
                <div class="sb-form-errors">{{ $errors->first() }}</div>
            @endif

            <section class="sb-panel sb-inventory-form-card">

                <form method="POST" action="{{ route('owner.categories.store') }}" enctype="multipart/form-data">

                    @csrf

                    <h2>Category details</h2>

                    <p>Examples: Bridal, Evening, Prom, or Formal.</p>

                    <label>Category name
                        <input name="name" value="{{ old('name') }}" maxlength="255" placeholder="Evening gowns"
                            required>
                    </label>

                    <label>Description
                        <textarea name="description" rows="4"
                            placeholder="What kinds of gowns belong in this collection?">{{ old('description') }}</textarea>
                    </label>

                    <label>Category image
                        <input type="file" name="image" accept="image/png,image/jpeg,image/webp"
                            onchange="const f=this.files[0],p=document.getElementById('category-preview'); if(f){p.src=URL.createObjectURL(f); p.hidden=false;}">
                        <img id="category-preview" class="sb-image-preview" alt="Image preview" hidden>
                        <small class="sb-field-hint">JPG, PNG or WEBP, up to 2 MB.</small>
                    </label>

                    <div class="sb-inventory-form-actions">
                        <a class="sb-inventory-secondary" href="{{ route('owner.categories.index') }}">Cancel</a>

                        <button class="sb-inventory-primary" type="submit">Create category</button>
                    </div>

                </form>

            </section>

        </div>
    </div>

</x-app-layout>