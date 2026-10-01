<x-app-layout>
    <div class="sb-page">
        <div class="sb-wrap">
            <div class="sb-heading">
                <div>
                    <h1>Your <em>team.</em></h1>
                    <p>Create staff accounts and manage employee access to operations.</p>
                </div>
            </div>

            @if(session('success'))
                <div class="sb-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="sb-form-errors">{{ $errors->first() }}</div>
            @endif

            <section class="sb-panel sb-team-panel">
                <div class="sb-panel-head sb-team-table-head">
                    <div>
                        <h2>Staff directory</h2>
                        <p>{{ $employees->total() }} employee accounts</p>
                    </div>
                    <button class="sb-inventory-primary" type="button"
                        onclick="document.getElementById('employee-create-dialog').showModal()">
                        Add employee <span aria-hidden="true">＋</span>
                    </button>
                </div>

                <div class="sb-table-wrap sb-team-table-wrap">
                    <table class="sb-table sb-team-table">
                        <thead>
                            <tr>
                                <th>EMPLOYEE</th>
                                <th>CONTACT</th>
                                <th>POSITION</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employees as $employee)
                                <tr>
                                    <td data-label="Employee">
                                        <strong>{{ $employee->full_name }}</strong>
                                        <small class="sb-cell-sub">{{ $employee->employee_code }}</small>
                                    </td>
                                    <td data-label="Contact">
                                        <span>{{ $employee->user->email }}</span>
                                        <small
                                            class="sb-cell-sub">{{ $employee->contact_number ?: 'No phone number' }}</small>
                                    </td>
                                    <td data-label="Position">{{ $employee->position }}</td>
                                    <td data-label="Status"><span class="sb-status">{{ ucfirst($employee->status) }}</span>
                                    </td>
                                    <td data-label="Actions">
                                        <div class="sb-team-table-actions">
                                            <details class="sb-employee-edit">
                                                <summary>Edit</summary>
                                                <div class="sb-employee-edit-panel">
                                                    <form method="POST"
                                                        action="{{ route('owner.employees.update', $employee) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <label>Name<input name="name" value="{{ $employee->full_name }}"
                                                                required></label>
                                                        <label>Email<input type="email" name="email"
                                                                value="{{ $employee->user->email }}" required></label>
                                                        <label>Phone<input name="contact_number"
                                                                value="{{ $employee->contact_number }}"></label>
                                                        <label>Position<input name="position"
                                                                value="{{ $employee->position }}" required></label>
                                                        <button class="sb-small-btn">Save details</button>
                                                    </form>
                                                </div>
                                            </details>
                                            <form method="POST" action="{{ route('owner.employees.toggle', $employee) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button
                                                    class="sb-small-btn {{ $employee->status === 'active' ? 'sb-deactivate' : '' }}">{{ $employee->status === 'active' ? 'Deactivate' : 'Activate' }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="sb-empty sb-team-empty">
                                        <strong>No employees yet</strong>
                                        <small>Add your first staff account to give your team access to rental
                                            operations.</small>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <dialog class="sb-side-drawer" id="employee-create-dialog" aria-labelledby="employee-create-title"
                onclick="if (event.target === this) this.close()">
                <div class="sb-side-drawer-head">
                    <div>
                        <span class="sb-kicker">TEAM ACCESS</span>
                        <h2 id="employee-create-title">New employee account</h2>
                        <p>A new employee account receives operational access only.</p>
                    </div>
                    <button class="sb-side-drawer-close" type="button" aria-label="Close form"
                        onclick="document.getElementById('employee-create-dialog').close()">&times;</button>
                </div>

                <form method="POST" action="{{ route('owner.employees.store') }}" class="sb-side-drawer-form">
                    @csrf
                    <input type="hidden" name="_employee_drawer" value="1">
                    @if($errors->any())
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
                            onclick="document.getElementById('employee-create-dialog').close()">Cancel</button>
                        <button class="sb-small-btn" type="submit">Create employee</button>
                    </div>
                </form>
            </dialog>

            @if(old('_employee_drawer'))
                <script>document.getElementById('employee-create-dialog').showModal();</script>
            @endif

            <div class="sb-pagination">
                {{ $employees->links() }}
            </div>
        </div>
    </div>
</x-app-layout>