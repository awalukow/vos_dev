{{-- resources/views/portal/users/_form.blade.php --}}
@php
    $isEdit  = !is_null($user);
    $action  = $isEdit ? route('portal.users.update', $user) : route('portal.users.store');
    $method  = $isEdit ? 'PUT' : 'POST';
@endphp

{{-- Reset password flash — shown after Reset Password button is clicked --}}
@if(session('reset_password'))
<div style="background:rgba(200,169,110,.1);border:1px solid rgba(200,169,110,.3);border-radius:10px;padding:1.1rem 1.25rem;margin-bottom:1.25rem;display:flex;align-items:flex-start;gap:.75rem;">
    <span style="font-size:1.2rem;">🔑</span>
    <div>
        <div style="font-weight:700;font-size:.9rem;color:var(--accent);margin-bottom:.3rem;">Password Reset Successfully</div>
        <div style="font-size:.85rem;color:var(--text-muted);margin-bottom:.5rem;">New temporary password for <strong style="color:var(--text);">{{ $user->name }}</strong>:</div>
        <div style="font-family:monospace;font-size:1.1rem;font-weight:700;background:var(--surface2);padding:.5rem 1rem;border-radius:6px;border:1px solid var(--border2);letter-spacing:.08em;color:var(--text);display:inline-block;">
            {{ session('reset_password') }}
        </div>
        <div style="font-size:.75rem;color:var(--text-muted);margin-top:.5rem;">⚠ Copy and share this password now. It won't be shown again. The user will be prompted to change it on next login.</div>
    </div>
</div>
@endif

<form method="POST" action="{{ $action }}">
    @csrf
    @if($isEdit) @method($method) @endif

    <div class="card">
        <div class="card-header">
            <span class="card-title">Account Information</span>
            @if($isEdit && $user->is_password_flushed)
                <span style="font-size:.75rem;padding:.2rem .65rem;border-radius:20px;background:rgba(245,158,11,.12);color:#fbbf24;border:1px solid rgba(245,158,11,.25);">
                    ⏳ Awaiting password change
                </span>
            @endif
        </div>
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
                    <label class="form-label">
                        Password {{ $isEdit ? '(leave blank to keep current)' : '*' }}
                    </label>
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

    {{-- Action buttons --}}
    <div style="display:flex;gap:.75rem;margin-top:1.5rem;justify-content:space-between;align-items:center;flex-wrap:wrap;">

        {{-- Reset Password (edit only) --}}
        @if($isEdit)
        <form method="POST" action="{{ route('portal.users.reset-password', $user) }}"
              onsubmit="return confirm('Reset password for {{ $user->name }}? They will be required to set a new one on next login.')">
            @csrf
            <button type="submit" class="btn btn-secondary" style="border-color:rgba(245,158,11,.35);color:#fbbf24;">
                🔑 Reset Password
            </button>
        </form>
        @else
        {{-- On create: generate random password option --}}
        <label style="display:flex;align-items:center;gap:.5rem;font-size:.82rem;color:var(--text-muted);cursor:pointer;">
            <input type="checkbox" name="generate_password" value="1" id="genPwCheck"
                   style="accent-color:var(--accent);"
                   onchange="togglePasswordRequired(this)">
            Send temporary password <span style="color:var(--accent);">(user must change on login)</span>
        </label>
        @endif

        <div style="display:flex;gap:.75rem;">
            <a href="{{ route('portal.users.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Save Changes' : 'Create User' }} →</button>
        </div>
    </div>
</form>

<style>
.role-check-label:has(input:checked) {
    border-color: rgba(200,169,110,.4);
    background: rgba(200,169,110,.05);
}
</style>

<script>
function togglePasswordRequired(cb) {
    const pwField = document.querySelector('input[name="password"]');
    if (cb.checked) {
        pwField.placeholder = 'Enter temporary password for the user';
    } else {
        pwField.placeholder = 'Min 8 chars, mixed case + number';
    }
}
</script>
