<x-app-layout>

    <div class="sb-page sb-inventory-page">
        <div class="sb-wrap">

            {{-- PAGE HEADER --}}
            <div class="sb-heading">
                <div>
                    <span class="sb-kicker">TEAM ACCESS</span>

                    <h1>Your <em>team.</em></h1>

                    <p>Create staff accounts and manage employee access to operations.</p>
                </div>
            </div>


            {{-- MESSAGES --}}
            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif

            @if($errors->any() && !old('_employee_drawer'))
                <div class="sb-form-errors">{{ $errors->first() }}</div>
            @endif


            {{-- STAFF PANEL --}}
            <section class="sb-panel">

                <div class="sb-inventory-content">

                    {{-- PANEL HEADER: title left, Add button right --}}
                    <div class="sb-inventory-toolbar">

                        <div>
                            <span class="sb-kicker">STAFF DIRECTORY</span>

                            <h2>All employees</h2>

                            <p>
                                {{ $employees->total() }}
                                employee {{ \Illuminate\Support\Str::plural('account', $employees->total()) }}
                            </p>
                        </div>

                        <button class="sb-inventory-primary sb-inventory-filter-add" type="button"
                            onclick="document.getElementById('employee-create-dialog').showModal()">
                            <span aria-hidden="true">＋</span>
                            Add employee
                        </button>

                    </div>


                    {{-- TABLE --}}
                    <div class="sb-inventory-table-wrap">

                        <table class="sb-inventory-table sb-employee-table">

                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Contact</th>
                                    <th>Position</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($employees as $employee)

                                    @php
                                        $initials = \Illuminate\Support\Str::upper(
                                            \Illuminate\Support\Str::of($employee->full_name)
                                                ->explode(' ')
                                                ->filter()
                                                ->map(fn($w) => mb_substr($w, 0, 1))
                                                ->take(2)
                                                ->implode('')
                                        );
                                    @endphp

                                    <tr>

                                        {{-- EMPLOYEE --}}
                                        <td data-label="Employee">
                                            <div class="sb-inventory-name">

                                                <div class="sb-employee-avatar" aria-hidden="true">
                                                    {{ $initials }}
                                                </div>

                                                <span>
                                                    <b>{{ $employee->full_name }}</b>
                                                    <small>{{ $employee->employee_code }}</small>
                                                </span>

                                            </div>
                                        </td>


                                        {{-- CONTACT --}}
                                        <td data-label="Contact" class="sb-employee-contact">
                                            <span>{{ $employee->user->email }}</span>
                                            <small>{{ $employee->contact_number ?: 'No phone number' }}</small>
                                        </td>


                                        {{-- POSITION --}}
                                        <td data-label="Position">
                                            {{ $employee->position }}
                                        </td>


                                        {{-- STATUS --}}
                                        <td data-label="Status">
                                            <span
                                                class="sb-inventory-status sb-gown-status {{ $employee->status === 'active' ? 'is-available' : 'is-disabled' }}">
                                                {{ ucfirst($employee->status) }}
                                            </span>
                                        </td>


                                        {{-- ACTIONS --}}
                                        <td data-label="Actions">

                                            <div class="sb-inventory-actions">

                                                <button type="button" class="sb-action-edit"
                                                    onclick="document.getElementById('employee-edit-{{ $employee->id }}').showModal()">
                                                    Edit
                                                </button>

                                                <form method="POST"
                                                    action="{{ route('owner.employees.toggle', $employee) }}">

                                                    @csrf
                                                    @method('PATCH')

                                                    @if($employee->status === 'active')
                                                        <button class="sb-retire" type="submit">Deactivate</button>
                                                    @else
                                                        <button class="sb-action-edit" type="submit">Activate</button>
                                                    @endif

                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="5">
                                            <div class="sb-inventory-empty">
                                                <b>No employees yet</b>
                                                <p>Add your first staff account to give your team access to rental
                                                    operations.</p>
                                            </div>
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    {{-- PAGINATION --}}
                    <div class="sb-pagination">
                        <span class="sb-pagination-count">
                            Showing {{ $employees->firstItem() ?? 0 }}–{{ $employees->lastItem() ?? 0 }}
                            of {{ $employees->total() }}
                        </span>

                        @if($employees->hasPages())
                            {{ $employees->links() }}
                        @endif
                    </div>

                </div>

            </section>


            {{-- EDIT EMPLOYEE DRAWERS (one per employee, outside the table so nothing gets clipped) --}}
            @foreach($employees as $employee)

                <dialog class="sb-side-drawer" id="employee-edit-{{ $employee->id }}"
                    aria-labelledby="employee-edit-title-{{ $employee->id }}"
                    onclick="if (event.target === this) this.close()">

                    <div class="sb-side-drawer-head">

                        <div>
                            <span class="sb-kicker">TEAM ACCESS</span>

                            <h2 id="employee-edit-title-{{ $employee->id }}">Edit employee</h2>

                            <p>{{ $employee->employee_code }} · update contact details and position.</p>
                        </div>

                        <button class="sb-side-drawer-close" type="button" aria-label="Close form"
                            onclick="document.getElementById('employee-edit-{{ $employee->id }}').close()">
                            &times;
                        </button>

                    </div>

                    <form method="POST" action="{{ route('owner.employees.update', $employee) }}"
                        class="sb-side-drawer-form">

                        @csrf
                        @method('PUT')

                        <label>Full name
                            <input name="name" value="{{ $employee->full_name }}" required>
                        </label>

                        <label>Email address
                            <input type="email" name="email" value="{{ $employee->user->email }}" required>
                        </label>

                        <label>Contact number
                            <input name="contact_number" value="{{ $employee->contact_number }}">
                        </label>

                        <label>Position
                            <input name="position" value="{{ $employee->position }}" required>
                        </label>

                        <div class="sb-side-drawer-actions">
                            <button class="sb-side-drawer-cancel" type="button"
                                onclick="document.getElementById('employee-edit-{{ $employee->id }}').close()">
                                Cancel
                            </button>

                            <button class="sb-inventory-primary" type="submit">Save details</button>
                        </div>

                    </form>

                </dialog>

            @endforeach


            {{-- ADD EMPLOYEE DRAWER --}}
            <dialog class="sb-side-drawer" id="employee-create-dialog" aria-labelledby="employee-create-title"
                onclick="if (event.target === this) this.close()">

                <div class="sb-side-drawer-head">

                    <div>
                        <span class="sb-kicker">TEAM ACCESS</span>

                        <h2 id="employee-create-title">New employee account</h2>

                        <p>A new employee account receives operational access only.</p>
                    </div>

                    <button class="sb-side-drawer-close" type="button" aria-label="Close form"
                        onclick="document.getElementById('employee-create-dialog').close()">
                        &times;
                    </button>

                </div>


                <form method="POST" action="{{ route('owner.employees.store') }}" class="sb-side-drawer-form">

                    @csrf

                    <input type="hidden" name="_employee_drawer" value="1">

                    @if($errors->any() && old('_employee_drawer'))
                        <div class="sb-form-errors">{{ $errors->first() }}</div>
                    @endif

                    <label>Full name
                        <input name="name" required autofocus autocomplete="name" value="{{ old('name') }}">
                        <x-input-error :messages="$errors->get('name')" />
                    </label>

                    <label>Email address
                        <input type="email" name="email" required autocomplete="email" value="{{ old('email') }}">
                        <x-input-error :messages="$errors->get('email')" />
                    </label>

                    <label>Password
                        <span class="sb-employee-password" x-data="{ showPassword: false }">
                            <input id="employee-password" x-bind:type="showPassword ? 'text' : 'password'"
                                name="password" minlength="8" required autocomplete="new-password">
                            <button type="button" x-on:click.prevent="showPassword = !showPassword"
                                x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'"
                                x-bind:aria-pressed="showPassword">
                                <svg x-show="!showPassword" aria-hidden="true" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg x-show="showPassword" aria-hidden="true" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path
                                        d="m3 3 18 18M10.6 6.2A10.8 10.8 0 0 1 12 6c6.5 0 10 6 10 6a16 16 0 0 1-3.1 3.8M6.2 6.3C3.5 8 2 12 2 12s3.5 6 10 6c1.3 0 2.5-.3 3.5-.7" />
                                    <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                                </svg>
                            </button>
                        </span>
                        <small class="sb-side-drawer-help">Minimum 8 characters.</small>
                        <x-input-error :messages="$errors->get('password')" />
                    </label>

                    <label>Contact number
                        <input type="tel" inputmode="tel" name="contact_number" autocomplete="tel"
                            placeholder="09XX XXX XXXX" value="{{ old('contact_number') }}">
                        <x-input-error :messages="$errors->get('contact_number')" />
                    </label>

                    <label>Position
                        <input name="position" placeholder="e.g. Rental Staff"
                            value="{{ old('position', 'Rental Staff') }}" required>
                        <small class="sb-side-drawer-help">Position title only; account permissions stay
                            employee-level.</small>
                        <x-input-error :messages="$errors->get('position')" />
                    </label>

                    <div class="sb-side-drawer-actions">
                        <button class="sb-side-drawer-cancel" type="button"
                            onclick="document.getElementById('employee-create-dialog').close()">
                            Cancel
                        </button>

                        <button class="sb-inventory-primary" type="submit">Create employee</button>
                    </div>

                </form>

            </dialog>


            {{-- REOPEN DRAWER AFTER VALIDATION ERROR --}}
            @if(old('_employee_drawer'))
                <script>
                    document.getElementById('employee-create-dialog').showModal();
                </script>
            @endif

        </div>
    </div>

</x-app-layout>