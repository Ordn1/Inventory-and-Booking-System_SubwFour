@extends('system')

@section('title', 'Employee Dashboard - SubWFour')

@section('content')
<div class="emp-dash-container">
    {{-- Welcome Header --}}
    <div class="emp-dash-welcome">
        <div class="emp-dash-welcome-content">
            <h1 class="emp-dash-title">Welcome back, {{ $personalInfo['name'] }}!</h1>
            <p class="emp-dash-subtitle">Here's your dashboard overview for today.</p>
        </div>
        <div class="emp-dash-welcome-icon">
            <i class="bi bi-person-fill"></i>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Account Status & Personal Info Row --}}
    <div class="emp-dash-grid emp-dash-grid-2">
        {{-- Account Status Card --}}
        <div class="emp-dash-card emp-dash-account-status">
            <div class="emp-dash-card-header">
                <h3><i class="bi bi-shield-check"></i> Account Status</h3>
            </div>
            <div class="emp-dash-card-body">
                <div class="emp-dash-status-row">
                    <span class="emp-dash-status-label">Status</span>
                    <span class="emp-dash-status-badge {{ $accountStatus['is_active'] ? 'status-active' : 'status-inactive' }}">
                        <span class="emp-dash-status-dot"></span>
                        {{ $accountStatus['is_active'] ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="emp-dash-status-row">
                    <span class="emp-dash-status-label">Last Login</span>
                    <span class="emp-dash-status-value">
                        @if($accountStatus['last_login'])
                            {{ $accountStatus['last_login']->format('M d, Y h:i A') }}
                        @else
                            Never
                        @endif
                    </span>
                </div>
                <div class="emp-dash-status-row">
                    <span class="emp-dash-status-label">Total Logins</span>
                    <span class="emp-dash-status-value">{{ number_format($accountStatus['total_logins']) }}</span>
                </div>

                {{-- Password Change Request Section --}}
                <div class="emp-dash-password-section">
                    <h4>Password Management</h4>
                    @if($pendingRequest)
                        <div class="emp-dash-request-pending">
                            <i class="bi bi-hourglass-split"></i>
                            <div>
                                <strong>Request Pending</strong>
                                <p>Submitted {{ $pendingRequest->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <form action="{{ route('employee.password-request.cancel') }}" method="POST" class="emp-dash-request-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary btn-sm">
                                <i class="bi bi-x-lg"></i> Cancel Request
                            </button>
                        </form>
                    @elseif($latestRequest && $latestRequest->isApproved())
                        <div class="emp-dash-request-approved">
                            <i class="bi bi-check-circle"></i>
                            <div>
                                <strong>Request Approved</strong>
                                <p>You can now change your password</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openPasswordChangeModal()">
                            <i class="bi bi-key"></i> Change Password Now
                        </button>
                    @elseif($latestRequest && $latestRequest->isRejected())
                        <div class="emp-dash-request-rejected">
                            <i class="bi bi-x-circle"></i>
                            <div>
                                <strong>Last Request Rejected</strong>
                                @if($latestRequest->admin_comments)
                                    <p>{{ $latestRequest->admin_comments }}</p>
                                @endif
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" onclick="openPasswordRequestModal()">
                            <i class="bi bi-key"></i> Request Password Change
                        </button>
                    @else
                        <button type="button" class="btn btn-primary btn-sm" onclick="openPasswordRequestModal()">
                            <i class="bi bi-key"></i> Request Password Change
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Personal Info Card --}}
        <div class="emp-dash-card emp-dash-personal-info">
            <div class="emp-dash-card-header">
                <h3><i class="bi bi-person-vcard"></i> Personal Information</h3>
            </div>
            <div class="emp-dash-card-body">
                <div class="emp-dash-info-grid">
                    <div class="emp-dash-info-item">
                        <label><i class="bi bi-person"></i> Full Name</label>
                        <span>{{ $personalInfo['name'] }}</span>
                    </div>
                    <div class="emp-dash-info-item">
                        <label><i class="bi bi-envelope"></i> Email</label>
                        <span>{{ $personalInfo['email'] }}</span>
                    </div>
                    <div class="emp-dash-info-item">
                        <label><i class="bi bi-telephone"></i> Contact</label>
                        <span>{{ $personalInfo['contact'] ?? '—' }}</span>
                    </div>
                    <div class="emp-dash-info-item">
                        <label><i class="bi bi-briefcase"></i> Role</label>
                        <span>{{ $personalInfo['role'] }}</span>
                    </div>
                    <div class="emp-dash-info-item full-width">
                        <label><i class="bi bi-geo-alt"></i> Address</label>
                        <span>{{ $personalInfo['address'] ?? '—' }}</span>
                    </div>
                    <div class="emp-dash-info-item">
                        <label><i class="bi bi-calendar3"></i> Joined</label>
                        <span>{{ $personalInfo['joined'] ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards Row --}}
    <div class="emp-dash-section">
        <h2 class="emp-dash-section-title"><i class="bi bi-graph-up-arrow"></i> Performance Metrics</h2>
        <div class="emp-dash-kpi-grid">
            {{-- Inventory KPI --}}
            <div class="emp-dash-kpi-card">
                <div class="emp-dash-kpi-icon" style="background: linear-gradient(135deg, #3b82f6, #1d4ed8);">
                    <i class="bi {{ $kpis['inventory']['icon'] }}"></i>
                </div>
                <div class="emp-dash-kpi-content">
                    <span class="emp-dash-kpi-value">{{ number_format($kpis['inventory']['total']) }}</span>
                    <span class="emp-dash-kpi-label">{{ $kpis['inventory']['label'] }}</span>
                </div>
            </div>

            {{-- Services KPI --}}
            <div class="emp-dash-kpi-card">
                <div class="emp-dash-kpi-icon" style="background: linear-gradient(135deg, #8b5cf6, #6d28d9);">
                    <i class="bi {{ $kpis['services']['icon'] }}"></i>
                </div>
                <div class="emp-dash-kpi-content">
                    <span class="emp-dash-kpi-value">{{ number_format($kpis['services']['total']) }}</span>
                    <span class="emp-dash-kpi-label">{{ $kpis['services']['label'] }}</span>
                </div>
                <div class="emp-dash-kpi-progress">
                    <div class="emp-dash-progress-bar">
                        <div class="emp-dash-progress-fill" style="width: {{ $kpis['services']['percentage'] }}%;"></div>
                    </div>
                    <span class="emp-dash-progress-text">{{ $kpis['services']['percentage'] }}% of target</span>
                </div>
            </div>

            {{-- Bookings KPI --}}
            <div class="emp-dash-kpi-card">
                <div class="emp-dash-kpi-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="bi {{ $kpis['bookings']['icon'] }}"></i>
                </div>
                <div class="emp-dash-kpi-content">
                    <span class="emp-dash-kpi-value">{{ number_format($kpis['bookings']['completed']) }}<small>/{{ $kpis['bookings']['total'] }}</small></span>
                    <span class="emp-dash-kpi-label">{{ $kpis['bookings']['label'] }}</span>
                </div>
                <div class="emp-dash-kpi-progress">
                    <div class="emp-dash-progress-bar">
                        <div class="emp-dash-progress-fill" style="width: {{ $kpis['bookings']['percentage'] }}%; background: linear-gradient(90deg, #10b981, #34d399);"></div>
                    </div>
                    <span class="emp-dash-progress-text">{{ $kpis['bookings']['percentage'] }}% of target</span>
                </div>
            </div>

            {{-- Completion Rate KPI --}}
            <div class="emp-dash-kpi-card">
                <div class="emp-dash-kpi-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <i class="bi {{ $kpis['completion_rate']['icon'] }}"></i>
                </div>
                <div class="emp-dash-kpi-content">
                    <span class="emp-dash-kpi-value">{{ $kpis['completion_rate']['value'] }}%</span>
                    <span class="emp-dash-kpi-label">{{ $kpis['completion_rate']['label'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Overview Cards Row --}}
    <div class="emp-dash-grid emp-dash-grid-3">
        {{-- Inventory Overview --}}
        <div class="emp-dash-card">
            <div class="emp-dash-card-header">
                <h3><i class="bi bi-box-seam"></i> Inventory Overview</h3>
            </div>
            <div class="emp-dash-card-body">
                <div class="emp-dash-overview-stats">
                    <div class="emp-dash-stat">
                        <span class="emp-dash-stat-value">{{ $inventoryOverview['total_items'] }}</span>
                        <span class="emp-dash-stat-label">Total Items</span>
                    </div>
                    <div class="emp-dash-stat {{ $inventoryOverview['low_stock_count'] > 0 ? 'stat-warning' : '' }}">
                        <span class="emp-dash-stat-value">{{ $inventoryOverview['low_stock_count'] }}</span>
                        <span class="emp-dash-stat-label">Low Stock</span>
                    </div>
                </div>
                @if($inventoryOverview['low_stock_count'] > 0)
                    <div class="emp-dash-alert emp-dash-alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span>{{ $inventoryOverview['low_stock_count'] }} item(s) running low on stock</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Services Overview --}}
        <div class="emp-dash-card">
            <div class="emp-dash-card-header">
                <h3><i class="bi bi-gear"></i> Services Overview</h3>
            </div>
            <div class="emp-dash-card-body">
                <div class="emp-dash-overview-stats">
                    <div class="emp-dash-stat">
                        <span class="emp-dash-stat-value">{{ $servicesOverview['completed'] }}</span>
                        <span class="emp-dash-stat-label">Completed</span>
                    </div>
                    <div class="emp-dash-stat stat-info">
                        <span class="emp-dash-stat-value">{{ $servicesOverview['ongoing'] }}</span>
                        <span class="emp-dash-stat-label">Ongoing</span>
                    </div>
                    <div class="emp-dash-stat stat-pending">
                        <span class="emp-dash-stat-value">{{ $servicesOverview['pending'] }}</span>
                        <span class="emp-dash-stat-label">Pending</span>
                    </div>
                </div>
                <div class="emp-dash-total-row">
                    <span>Total Services</span>
                    <strong>{{ $servicesOverview['total'] }}</strong>
                </div>
            </div>
        </div>

        {{-- Bookings Overview --}}
        <div class="emp-dash-card">
            <div class="emp-dash-card-header">
                <h3><i class="bi bi-calendar-check"></i> Bookings Overview</h3>
            </div>
            <div class="emp-dash-card-body">
                <div class="emp-dash-overview-stats">
                    <div class="emp-dash-stat">
                        <span class="emp-dash-stat-value">{{ $bookingsOverview['today_count'] }}</span>
                        <span class="emp-dash-stat-label">Today</span>
                    </div>
                    <div class="emp-dash-stat">
                        <span class="emp-dash-stat-value">{{ $bookingsOverview['week_count'] }}</span>
                        <span class="emp-dash-stat-label">This Week</span>
                    </div>
                </div>
                @if($bookingsOverview['upcoming']->count() > 0)
                    <div class="emp-dash-upcoming-list">
                        <h4>Upcoming Bookings</h4>
                        @foreach($bookingsOverview['upcoming'] as $booking)
                            <div class="emp-dash-upcoming-item">
                                <span class="emp-dash-upcoming-date">
                                    {{ $booking->preferred_date->format('M d') }}
                                </span>
                                <span class="emp-dash-upcoming-info">
                                    {{ $booking->customer_name ?? 'Booking #'.$booking->booking_id }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="emp-dash-empty-text">No upcoming bookings</p>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Password Change Request Modal --}}
<div id="passwordRequestModal" class="app-modal">
    <div class="app-modal-content emp-dash-modal">
        <div class="emp-dash-modal-header">
            <h3><i class="bi bi-key"></i> Request Password Change</h3>
            <button type="button" class="emp-modal-close" onclick="closePasswordRequestModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form action="{{ route('employee.password-request') }}" method="POST">
            @csrf
            <div class="emp-dash-modal-body">
                <p class="emp-dash-modal-info">
                    <i class="bi bi-info-circle"></i>
                    Your password change request will be sent to the admin for approval. 
                    Once approved, you'll be able to set a new password.
                </p>
                <div class="emp-form-group">
                    <label>Reason for password change <span class="text-muted">(optional)</span></label>
                    <textarea name="reason" rows="3" placeholder="e.g., Security concerns, forgot current password..."></textarea>
                </div>
            </div>
            <div class="emp-dash-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePasswordRequestModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send"></i> Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Password Change Modal (When Approved) --}}
<div id="passwordChangeModal" class="app-modal">
    <div class="app-modal-content emp-dash-modal">
        <div class="emp-dash-modal-header">
            <h3><i class="bi bi-key-fill"></i> Change Your Password</h3>
            <button type="button" class="emp-modal-close" onclick="closePasswordChangeModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form action="{{ route('password.update') }}" method="POST" id="passwordChangeForm">
            @csrf
            <div class="emp-dash-modal-body">
                <div id="passwordChangeErrors" class="alert alert-danger" style="display: none;">
                    <ul style="margin: 0; padding-left: 18px;"></ul>
                </div>
                
                <div class="emp-dash-modal-info" style="margin-bottom: 16px;">
                    <i class="bi bi-shield-check"></i>
                    <div>
                        <strong>Password Requirements:</strong>
                        <ul style="margin: 8px 0 0; padding-left: 16px; font-size: 0.8rem; color: var(--gray-600);">
                            <li>12-18 characters</li>
                            <li>At least one uppercase letter (A-Z)</li>
                            <li>At least one lowercase letter (a-z)</li>
                            <li>At least one number (0-9)</li>
                            <li>Letters and numbers only (no special characters)</li>
                        </ul>
                    </div>
                </div>

                <div class="emp-form-group" style="margin-bottom: 16px;">
                    <label>Current Password <span style="color: var(--brand-red);">*</span></label>
                    <div class="emp-password-input-wrap">
                        <input type="password" name="current_password" id="modalCurrentPassword" required 
                               placeholder="Enter your current password">
                        <button type="button" class="emp-password-toggle" onclick="toggleModalPassword('modalCurrentPassword', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="emp-form-group" style="margin-bottom: 16px;">
                    <label>New Password <span style="color: var(--brand-red);">*</span></label>
                    <div class="emp-password-input-wrap">
                        <input type="password" name="password" id="modalNewPassword" required 
                               minlength="12" maxlength="18" placeholder="Enter new password">
                        <button type="button" class="emp-password-toggle" onclick="toggleModalPassword('modalNewPassword', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="emp-form-group">
                    <label>Confirm New Password <span style="color: var(--brand-red);">*</span></label>
                    <div class="emp-password-input-wrap">
                        <input type="password" name="password_confirmation" id="modalConfirmPassword" required 
                               placeholder="Re-enter new password">
                        <button type="button" class="emp-password-toggle" onclick="toggleModalPassword('modalConfirmPassword', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="emp-dash-modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePasswordChangeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Update Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function openPasswordRequestModal() {
    document.getElementById('passwordRequestModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closePasswordRequestModal() {
    document.getElementById('passwordRequestModal').classList.remove('show');
    document.body.style.overflow = '';
}

function openPasswordChangeModal() {
    document.getElementById('passwordChangeModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closePasswordChangeModal() {
    document.getElementById('passwordChangeModal').classList.remove('show');
    document.body.style.overflow = '';
    // Clear form
    document.getElementById('passwordChangeForm').reset();
    document.getElementById('passwordChangeErrors').style.display = 'none';
}

function toggleModalPassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

// Close modal on backdrop click
document.getElementById('passwordRequestModal')?.addEventListener('click', function(e) {
    if (e.target === this) closePasswordRequestModal();
});
document.getElementById('passwordChangeModal')?.addEventListener('click', function(e) {
    if (e.target === this) closePasswordChangeModal();
});

// Close on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePasswordRequestModal();
        closePasswordChangeModal();
    }
});

// Handle password change form submission via AJAX
document.getElementById('passwordChangeForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const submitBtn = form.querySelector('button[type="submit"]');
    const errorDiv = document.getElementById('passwordChangeErrors');
    const errorList = errorDiv.querySelector('ul');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Updating...';
    errorDiv.style.display = 'none';
    
    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closePasswordChangeModal();
            // Show success and reload
            alert('Password updated successfully!');
            window.location.reload();
        } else {
            // Show errors
            errorList.innerHTML = '';
            if (data.errors) {
                Object.values(data.errors).forEach(errors => {
                    errors.forEach(err => {
                        errorList.innerHTML += '<li>' + err + '</li>';
                    });
                });
            } else if (data.message) {
                errorList.innerHTML = '<li>' + data.message + '</li>';
            }
            errorDiv.style.display = 'block';
        }
    })
    .catch(error => {
        errorList.innerHTML = '<li>An error occurred. Please try again.</li>';
        errorDiv.style.display = 'block';
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Update Password';
    });
});
</script>
@endsection
