@extends('system')

@section('title', 'Employees - SubWFour')

@section('content')

<div class="emp-welcome-section">
    <div class="emp-welcome-content">
        <h2 class="emp-welcome-title">EMPLOYEES</h2>
        <p class="emp-welcome-subtitle">
            You have <strong>{{ $stats['total'] ?? 0 }}</strong> employees registered. 
            <span class="emp-highlight-active">{{ $stats['active'] ?? 0 }} active</span> · 
            <span class="emp-highlight-inactive">{{ $stats['inactive'] ?? 0 }} inactive</span>
        </p>
    </div>
    <div class="emp-welcome-illustration">
        <i class="bi bi-person-fill-gear"></i>
    </div>
</div>

<div class="emp-stat-cards">
    <div class="emp-stat-card emp-stat-total">
        <div class="emp-stat-icon"><i class="bi bi-people"></i></div>
        <div class="emp-stat-info">
            <span class="emp-stat-value">{{ $stats['total'] ?? 0 }}</span>
            <span class="emp-stat-label">Total Employees</span>
        </div>
    </div>
    <div class="emp-stat-card emp-stat-active">
        <div class="emp-stat-icon"><i class="bi bi-person-check"></i></div>
        <div class="emp-stat-info">
            <span class="emp-stat-value">{{ $stats['active'] ?? 0 }}</span>
            <span class="emp-stat-label">Active</span>
        </div>
    </div>
    <div class="emp-stat-card emp-stat-inactive">
        <div class="emp-stat-icon"><i class="bi bi-person-x"></i></div>
        <div class="emp-stat-info">
            <span class="emp-stat-value">{{ $stats['inactive'] ?? 0 }}</span>
            <span class="emp-stat-label">Inactive</span>
        </div>
    </div>
    <div class="emp-stat-card emp-stat-recent">
        <div class="emp-stat-icon"><i class="bi bi-person-plus"></i></div>
        <div class="emp-stat-info">
            <span class="emp-stat-value">{{ $stats['recent'] ?? 0 }}</span>
            <span class="emp-stat-label">New (30 days)</span>
        </div>
    </div>
</div>

{{-- Pending Password Change Requests (Admin Only) --}}
@if(auth()->user()->role === 'admin' && isset($pendingPasswordRequests) && count($pendingPasswordRequests) > 0)
<div class="emp-password-requests-panel">
    <div class="emp-requests-header">
        <h3 class="emp-requests-title">
            <i class="bi bi-key"></i> Pending Password Change Requests
            <span class="emp-requests-badge">{{ count($pendingPasswordRequests) }}</span>
        </h3>
    </div>
    <div class="emp-requests-list">
        @foreach($pendingPasswordRequests as $request)
            @php
                $reqEmployee = $request->employee;
                $reqUser = $request->user;
                $reqName = $reqEmployee ? ($reqEmployee->first_name . ' ' . $reqEmployee->last_name) : ($reqUser->name ?? 'Unknown');
                $reqEmail = $reqUser->email ?? '—';
                $reqDate = $request->created_at->format('M d, Y h:i A');
            @endphp
            <div class="emp-request-item">
                <div class="emp-request-info">
                    <div class="emp-request-user">
                        <span class="emp-request-name">{{ $reqName }}</span>
                        <span class="emp-request-email">{{ $reqEmail }}</span>
                    </div>
                    <div class="emp-request-meta">
                        <span class="emp-request-date">
                            <i class="bi bi-clock"></i> {{ $request->created_at->diffForHumans() }}
                        </span>
                        @if($request->reason)
                            <span class="emp-request-reason" title="{{ $request->reason }}">
                                <i class="bi bi-chat-text"></i> {{ Str::limit($request->reason, 50) }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="emp-request-actions">
                    <form action="{{ route('admin.password-request.approve', $request->id) }}" method="POST" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn btn-approve btn-sm" title="Approve">
                            <i class="bi bi-check-lg"></i> Approve
                        </button>
                    </form>
                    <button type="button" class="btn btn-reject btn-sm" 
                            onclick="openRejectModal({{ $request->id }}, '{{ e($reqName) }}')" title="Reject">
                        <i class="bi bi-x-lg"></i> Reject
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

<div class="emp-list-panel">
    <div class="emp-list-header">
        <h3 class="emp-list-title"><i class="bi bi-list-ul"></i> Employee Directory</h3>
        
        <form action="{{ route('employees.index') }}" method="GET" class="emp-search-form">
            <div class="emp-search-wrapper">
                <i class="bi bi-search emp-search-icon"></i>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Search by name, email..." class="emp-search-input">
                @if(request('search'))
                    <button type="button" class="emp-search-clear" onclick="window.location='{{ route('employees.index') }}'">
                        <i class="bi bi-x-lg"></i>
                    </button>
                @endif
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="emp-table-container">
        <table class="emp-table">
            <thead>
                <tr>
                    <th class="th-employee">Employee</th>
                    <th class="th-role">Role</th>
                    <th class="th-status">Status</th>
                    <th class="th-registered">Registered</th>
                    <th class="th-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($employees as $employee)
                @php
                    $user = $employee->user;
                    $email = $user->email ?? '—';
                    $role = $user->role ?? 'employee';
                    $isActive = $user->is_active ?? true;
                    $registered = $user->created_at ? $user->created_at->format('M d, Y') : '—';
                    $profile = $employee->profile_picture
                        ? asset('storage/'.$employee->profile_picture)
                        : asset('images/EmployeeProfile.png');
                @endphp
                <tr class="emp-row {{ !$isActive ? 'emp-row-inactive' : '' }}">
                    <td class="td-employee">
                        <div class="emp-cell-user">
                            <img src="{{ $profile }}" alt="" class="emp-avatar">
                            <div class="emp-user-info">
                                <span class="emp-user-name">{{ $employee->first_name }} {{ $employee->last_name }}</span>
                                <span class="emp-user-email">{{ $email }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="td-role">
                        <span class="emp-badge emp-badge-{{ $role }}">{{ ucfirst($role) }}</span>
                    </td>
                    <td class="td-status">
                        @if($isActive)
                            <span class="emp-status emp-status-active"><span class="emp-status-dot"></span> Active</span>
                        @else
                            <span class="emp-status emp-status-inactive"><span class="emp-status-dot"></span> Inactive</span>
                        @endif
                    </td>
                    <td class="td-registered">{{ $registered }}</td>
                    <td class="td-actions">
                        <div class="emp-action-btns">
                            <button type="button" class="emp-action-btn emp-action-view" 
                                    title="View" onclick="viewEmployeeProfile({{ $employee->id }})">
                                <i class="bi bi-eye"></i>
                            </button>
                            <a href="{{ route('employees.edit', $employee->id) }}" 
                               class="emp-action-btn emp-action-edit" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @if(auth()->user()->role === 'admin')
                                @if($isActive)
                                    <button type="button" class="emp-action-btn emp-action-deactivate" 
                                            title="Deactivate"
                                            onclick="confirmDeactivate({{ $employee->id }}, '{{ e($employee->first_name . ' ' . $employee->last_name) }}')">
                                        <i class="bi bi-person-dash"></i>
                                    </button>
                                @else
                                    <form action="{{ route('employees.activate', $employee->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="emp-action-btn emp-action-activate" title="Activate">
                                            <i class="bi bi-person-check"></i>
                                        </button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="emp-empty-row">
                        <i class="bi bi-inbox"></i><span>No employees found</span>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($employees,'links'))
        <div class="emp-pagination">{{ $employees->appends(['search'=>request('search')])->links() }}</div>
    @endif
</div>

{{-- VIEW EMPLOYEE PROFILE MODAL --}}
<div id="employeeProfileModal" class="app-modal">
    <div class="app-modal-content emp-modal-profile">
        <div class="emp-profile-cover"></div>
        <button type="button" class="emp-profile-close" onclick="closeEmployeeProfileModal()"><i class="bi bi-x-lg"></i></button>
        <div class="emp-profile-header">
            <img id="empProfileAvatar" src="{{ asset('images/EmployeeProfile.png') }}" alt="" class="emp-profile-avatar">
            <div class="emp-profile-name-wrap">
                <h3 id="empProfileName">Employee Name</h3>
                <div class="emp-profile-badges">
                    <span id="empProfileRole" class="emp-badge emp-badge-employee">Employee</span>
                    <span id="empProfileStatus" class="emp-status emp-status-active"><span class="emp-status-dot"></span> Active</span>
                </div>
            </div>
        </div>
        <div class="emp-profile-body">
            <div class="emp-profile-info-section">
                <h4><i class="bi bi-envelope"></i> Contact</h4>
                <div class="emp-profile-info-grid">
                    <div class="emp-profile-info-item"><label>Email</label><span id="empProfileEmail">—</span></div>
                    <div class="emp-profile-info-item"><label>Contact</label><span id="empProfileContact">—</span></div>
                    <div class="emp-profile-info-item full-width"><label>Address</label><span id="empProfileAddress">—</span></div>
                </div>
            </div>
            <div class="emp-profile-info-section">
                <h4><i class="bi bi-shield-check"></i> Account</h4>
                <div class="emp-profile-info-grid">
                    <div class="emp-profile-info-item"><label>SSS Number</label><span id="empProfileSSS">—</span></div>
                    <div class="emp-profile-info-item"><label>Registered</label><span id="empProfileRegistered">—</span></div>
                </div>
            </div>
        </div>
        <div class="emp-profile-footer">
            <a id="empProfileEditLink" href="#" class="btn btn-edit"><i class="bi bi-pencil"></i> Edit</a>
            <button type="button" class="btn btn-secondary" onclick="closeEmployeeProfileModal()">Close</button>
        </div>
    </div>
</div>

{{-- DEACTIVATION MODAL --}}
<div id="deactivateModal" class="app-modal">
    <div class="app-modal-content emp-modal-confirm">
        <div class="emp-confirm-icon"><i class="bi bi-exclamation-triangle"></i></div>
        <h3>Deactivate Employee?</h3>
        <p>Are you sure you want to deactivate <strong id="deactivateName">Employee</strong>?</p>
        <p class="emp-confirm-note">They will no longer be able to log in.</p>
        <form id="deactivateForm" method="POST">
            @csrf
            <div class="emp-confirm-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeactivateModal()">Cancel</button>
                <button type="submit" class="btn btn-delete"><i class="bi bi-person-x"></i> Deactivate</button>
            </div>
        </form>
    </div>
</div>

{{-- REJECT PASSWORD REQUEST MODAL --}}
<div id="rejectPasswordModal" class="app-modal">
    <div class="app-modal-content emp-modal-confirm" style="max-width: 420px;">
        <div class="emp-confirm-icon" style="background: linear-gradient(135deg, #ef4444, #dc2626);"><i class="bi bi-x-circle"></i></div>
        <h3>Reject Password Request</h3>
        <p>Reject password change request from <strong id="rejectRequestName">Employee</strong>?</p>
        <form id="rejectPasswordForm" method="POST">
            @csrf
            <div class="emp-form-group" style="margin: 1rem 0;">
                <label style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem; display: block;">
                    Reason for rejection <span style="color: #9ca3af;">(optional)</span>
                </label>
                <textarea name="admin_comments" rows="3" 
                          style="width: 100%; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.875rem; resize: vertical;"
                          placeholder="e.g., Please verify your identity first..."></textarea>
            </div>
            <div class="emp-confirm-actions">
                <button type="button" class="btn btn-secondary" onclick="closeRejectModal()">Cancel</button>
                <button type="submit" class="btn btn-delete"><i class="bi bi-x-lg"></i> Reject Request</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function openRejectModal(requestId, employeeName) {
    document.getElementById('rejectRequestName').textContent = employeeName;
    document.getElementById('rejectPasswordForm').action = '/password-requests/' + requestId + '/reject';
    document.getElementById('rejectPasswordModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeRejectModal() {
    document.getElementById('rejectPasswordModal').classList.remove('show');
    document.body.style.overflow = '';
}

// Close on backdrop click
document.getElementById('rejectPasswordModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});
</script>
@endsection
