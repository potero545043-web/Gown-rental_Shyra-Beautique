<x-app-layout>
    <div class="sb-page sb-inventory-page"><div class="sb-wrap">
        <div class="sb-heading"><div><span class="sb-kicker">INVENTORY · GOWNS</span><h1>{{ $formTitle }}<em>.</em></h1><p>Add the rental details, category, and optional pieces supplied with this look.</p></div><a class="sb-inventory-secondary" href="{{ route('owner.gowns.index', !empty($gown?->archived_at) ? ['archived' => 1] : []) }}">{{ !empty($gown?->archived_at) ? 'Back to archive' : 'Back to collection' }}</a></div>
        @if($errors->any())<div class="sb-form-errors">{{ $errors->first() }}</div>@endif
        <section class="sb-panel sb-inventory-form-card">
            <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data">
                @csrf
                @if($isEditing) @method('PUT') @endif
                @include('gowns._fields')
            </form>
        </section>
        @if($isEditing)
            <div class="sb-gown-archive-actions">
                @if($gown->archived_at)
                    <p>This gown is archived and hidden from active inventory and customer browsing.</p>
                    <form method="POST" action="{{ route('owner.gowns.restore', $gown) }}">
                        @csrf
                        @method('PATCH')
                        <button class="sb-inventory-primary" type="submit">Restore gown</button>
                    </form>
                @elseif(!in_array($gown->status, ['reserved', 'rented'], true))
                    <p>Archiving hides this gown from active inventory. You can restore it anytime from View archive.</p>
                    <form method="POST" action="{{ route('owner.gowns.archive', $gown) }}"
                        data-confirm-title="Archive" data-confirm-accent="this gown?"
                        data-confirm-message="This gown will move to your archive. You can restore it at any time."
                        data-confirm-yes="Archive gown" data-confirm-no="Keep gown">
                        @csrf
                        <button class="sb-inventory-secondary" type="submit">Archive gown</button>
                    </form>
                @else
                    <p>A reserved or rented gown cannot be archived.</p>
                @endif
            </div>
        @endif
    </div></div>
</x-app-layout>
