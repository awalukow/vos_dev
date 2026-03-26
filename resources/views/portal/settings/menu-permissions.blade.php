@extends('portal.layouts.app')
@section('title', 'Menu Permissions')
@section('page-title', 'Settings — Menu Permissions')

@push('styles')
<style>
.perm-table th, .perm-table td { text-align: center; }
.perm-table th:first-child, .perm-table td:first-child { text-align: left; }
.perm-table input[type=checkbox] {
    width: 18px; height: 18px; accent-color: var(--accent); cursor: pointer;
}
.menu-parent-row td { background: rgba(255,255,255,.025); }
.menu-parent-row td:first-child { font-weight: 600; font-size: .9rem; }
.menu-child-row td:first-child { padding-left: 2.5rem; color: var(--text-muted); }
.role-header { font-size: .75rem; }
.check-all-btn {
    display: block; font-size: .68rem; color: var(--text-muted); cursor: pointer;
    margin-top: .25rem; text-decoration: underline; text-underline-offset: 2px;
}
.check-all-btn:hover { color: var(--accent); }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1>Menu Permissions</h1>
        <p>Control which roles can access which menus in the portal.</p>
    </div>
</div>

<form method="POST" action="{{ route('portal.settings.menu-permissions.update') }}">
@csrf

<div class="card">
    <div class="card-header">
        <span class="card-title">Role × Menu Matrix</span>
        <button type="submit" class="btn btn-primary">Save Permissions</button>
    </div>
    <div class="table-wrap">
        <table class="perm-table">
            <thead>
                <tr>
                    <th>Menu Item</th>
                    @foreach($roles as $role)
                    <th class="role-header">
                        {{ $role->display_name }}
                        <span class="check-all-btn" onclick="toggleAll({{ $role->id }}, true)">All</span>
                        <span class="check-all-btn" onclick="toggleAll({{ $role->id }}, false)">None</span>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($menus as $menu)
                {{-- Parent row --}}
                <tr class="menu-parent-row">
                    <td>
                        <div style="display:flex;align-items:center;gap:.5rem;">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" style="opacity:.5;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                            </svg>
                            {{ $menu->label }}
                        </div>
                    </td>
                    @foreach($roles as $role)
                    <td>
                        <input type="checkbox"
                               name="permissions[{{ $role->id }}][{{ $menu->id }}]"
                               value="1"
                               class="role-{{ $role->id }}"
                               {{ isset($permissions[$role->id][$menu->id]) ? 'checked' : '' }}>
                    </td>
                    @endforeach
                </tr>
                {{-- Child rows --}}
                @foreach($menu->children as $child)
                <tr class="menu-child-row">
                    <td>↳ {{ $child->label }}</td>
                    @foreach($roles as $role)
                    <td>
                        <input type="checkbox"
                               name="permissions[{{ $role->id }}][{{ $child->id }}]"
                               value="1"
                               class="role-{{ $role->id }}"
                               {{ isset($permissions[$role->id][$child->id]) ? 'checked' : '' }}>
                    </td>
                    @endforeach
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="padding:1rem 1.5rem;border-top:1px solid var(--border);display:flex;justify-content:flex-end;">
        <button type="submit" class="btn btn-primary">Save Permissions</button>
    </div>
</div>

</form>
@endsection

@push('scripts')
<script>
function toggleAll(roleId, checked) {
    document.querySelectorAll(`.role-${roleId}`).forEach(cb => cb.checked = checked);
}
</script>
@endpush
