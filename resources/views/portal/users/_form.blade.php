{{-- resources/views/portal/users/_form.blade.php --}}
@php
    $isEdit  = !is_null($user);
    $action  = $isEdit ? route('portal.users.update', $user) : route('portal.users.store');
    $method  = $isEdit ? 'PUT' : 'POST';
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @if($isEdit) @method($method) @endif

    <div class="card">
        <div class="card-header"><span class="card-title">Account Information</span></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:1.1rem;">

            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" value="{{ old('name', $user?->name) }}"
                       class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
                       placeholder="John Doe">
                @error('name') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label class="form-label">Username *</label>
                    <input type="text" name="username" value="{{ old('username', $user?->username) }}"
                           class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}"
                           placeholder="johndoe">
                    @error('username') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" value="{{ old('email', $user?->email) }}"
                           class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                           placeholder="john@vos.org">
                    @error('email') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label class="form-label">Password {{ $isEdit ? '(leave blank to keep current)' : '*' }}</label>
                    <input type="password" name="password"
                           class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                           placeholder="{{ $isEdit ? '••••••••' : 'Min 8 chars, mixed case + number' }}">
                    @error('password') <div class="form-error">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Voice</label>
                    <select name="voice" class="form-control">
                        <option value="">— Not assigned —</option>
                        @foreach(['Sopran','Alto','Tenor','Bass'] as $v)
                            <option value="{{ $v }}" {{ old('voice', $user?->voice) === $v ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

        </div>
    </div>

    <div class="card" style="margin-top:1rem;">
        <div class="card-header"><span class="card-title">Roles & Permissions</span></div>
        <div class="card-body">

            <div class="form-group">
                <label class="form-label">Assign Roles * <span style="text-transform:none;letter-spacing:0;font-weight:400;color:var(--text-muted);">(select one or more)</span></label>
                <div style="display:flex;flex-direction:column;gap:.5rem;">
                    @foreach($roles as $role)
                    <label style="display:flex;align-items:flex-start;gap:.65rem;padding:.65rem .9rem;border:1px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;transition:border-color .15s;" class="role-check-label">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" style="margin-top:2px;accent-color:var(--accent);"
                               {{ in_array($role->id, old('roles', $isEdit ? $user->roles->pluck('id')->toArray() : [])) ? 'checked' : '' }}>
                        <div>
                            <div style="font-weight:600;font-size:.875rem;">{{ $role->display_name }}</div>
                            <div style="font-size:.75rem;color:var(--text-muted);">{{ $role->description }}</div>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('roles') <div class="form-error">{{ $message }}</div> @enderror
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:.5rem;">
                <label style="display:flex;align-items:center;gap:.65rem;padding:.65rem .9rem;border:1px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" style="accent-color:var(--accent);"
                           {{ old('is_active', $isEdit ? $user->is_active : true) ? 'checked' : '' }}>
                    <div>
                        <div style="font-weight:600;font-size:.875rem;">Active Account</div>
                        <div style="font-size:.75rem;color:var(--text-muted);">User can log in</div>
                    </div>
                </label>

                <label style="display:flex;align-items:center;gap:.65rem;padding:.65rem .9rem;border:1px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;">
                    <input type="checkbox" name="can_upload_documents" value="1" style="accent-color:var(--accent);"
                           {{ old('can_upload_documents', $isEdit ? $user->can_upload_documents : false) ? 'checked' : '' }}>
                    <div>
                        <div style="font-weight:600;font-size:.875rem;">Can Upload Documents</div>
                        <div style="font-size:.75rem;color:var(--text-muted);">Approved to upload PDFs</div>
                    </div>
                </label>
            </div>

        </div>
    </div>

    <div style="display:flex;gap:.75rem;margin-top:1.5rem;justify-content:flex-end;">
        <a href="{{ route('portal.users.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Save Changes' : 'Create User' }} →</button>
    </div>
</form>

<style>
.role-check-label:has(input:checked) {
    border-color: rgba(200,169,110,.4);
    background: rgba(200,169,110,.05);
}
</style>
