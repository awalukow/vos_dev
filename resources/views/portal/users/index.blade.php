@extends('portal.layouts.app')
@section('title', 'Users')
@section('page-title', 'User Management')

@section('content')

<div class="page-header">
    <div>
        <h1>Users</h1>
        <p>Manage portal user accounts and their roles.</p>
    </div>
    <a href="{{ route('portal.users.create') }}" class="btn btn-primary">+ Add User</a>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title">All Users</span>
        <form method="GET" style="display:flex;gap:.5rem;">
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control" placeholder="Search name, email, username…"
                   style="width:240px;padding:.4rem .8rem;font-size:.82rem;">
            <button type="submit" class="btn btn-secondary btn-sm">Search</button>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Username</th>
                    <th>Roles</th>
                    <th>Voice</th>
                    <th>Can Upload Docs</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:.65rem;">
                            <div style="width:34px;height:34px;border-radius:50%;background:var(--accent-glow);border:1.5px solid var(--accent);display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:700;font-size:.82rem;color:var(--accent);flex-shrink:0;">
                                {{ strtoupper(substr($u->name, 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight:600;">{{ $u->name }}</div>
                                <div style="font-size:.75rem;color:var(--text-muted);">{{ $u->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="font-family:monospace;font-size:.85rem;color:var(--text-muted);">@{{ $u->username }}</td>
                    <td>
                        <div style="display:flex;flex-wrap:wrap;gap:.3rem;">
                            @foreach($u->roles->sortByDesc('level') as $role)
                                <span class="badge badge-role">{{ $role->display_name }}</span>
                            @endforeach
                        </div>
                    </td>
                    <td>
                        @if($u->voice)
                            @php
                                $voiceColors = ['Sopran'=>'rgba(236,72,153,.12);color:#f472b6','Alto'=>'rgba(168,85,247,.12);color:#c084fc','Tenor'=>'rgba(59,130,246,.12);color:#60a5fa','Bass'=>'rgba(34,197,94,.12);color:#4ade80'];
                            @endphp
                            <span style="padding:.2rem .55rem;border-radius:20px;font-size:.72rem;font-weight:700;background:{{ $voiceColors[$u->voice] ?? 'var(--surface2)' }}">
                                {{ $u->voice }}
                            </span>
                        @else
                            <span style="color:var(--text-muted);font-size:.78rem;">—</span>
                        @endif
                    </td>
                    <td>
                        @if($u->can_upload_documents)
                            <span class="badge badge-signed">Yes</span>
                        @else
                            <span class="badge badge-draft">No</span>
                        @endif
                    </td>
                    <td>
                        @if($u->is_active)
                            <span class="badge badge-completed">Active</span>
                        @else
                            <span class="badge badge-rejected">Inactive</span>
                        @endif
                    </td>
                    <td style="color:var(--text-muted);font-size:.8rem;">{{ $u->created_at->format('d M Y') }}</td>
                    <td>
                        <div style="display:flex;gap:.4rem;">
                            <a href="{{ route('portal.users.edit', $u) }}" class="btn btn-secondary btn-sm">Edit</a>
                            @if($u->id !== Auth::guard('portal')->id())
                            <form method="POST" action="{{ route('portal.users.destroy', $u) }}"
                                  onsubmit="return confirm('Remove {{ $u->name }}? This action cannot be undone.')">
                                @csrf @method('DELETE')
                                <button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align:center;color:var(--text-muted);padding:3rem;">No users found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div style="padding:1rem 1.5rem;border-top:1px solid var(--border);">
        {{ $users->links('portal.components.pagination') }}
    </div>
    @endif
</div>

@endsection
