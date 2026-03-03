<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title','System - SubWFour')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('css/system.css') }}" rel="stylesheet" />
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    @yield('head')
    <script src="{{ asset('js/system.js') }}" defer></script>
</head>
<body class="{{ session('first_login') ? 'fade-in' : '' }}">
@php
    session()->forget('first_login');
    $user = Auth::user();
    $profilePicture = $user->name === 'Admin' ? 'AdminProfile.png' : 'default-profile.jpg';
@endphp

<div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <img src="{{ asset('images/SubWFourLogo.png') }}" alt="SubWFour Logo">
    </div>
    <center>
        <ul>
            @if($user->role === 'admin')
                {{-- Admin-only navigation --}}
                <li><a href="{{ route('system') }}" class="nav-link"><i class="bi bi-activity"></i> Dashboard</a></li>
                <li><a href="{{ route('reports.index') }}" class="nav-link"><i class="bi bi-bar-chart-line"></i> Reports</a></li>
                <li><a href="{{ route('audit_logs.index') }}" class="nav-link"><i class="bi bi-list-columns"></i> Audit Logs</a></li>
                <li><a href="{{ route('employees.index') }}" class="nav-link"><i class="bi bi-people-fill"></i> Employees</a></li>
                <li><a href="{{ route('security.index') }}" class="nav-link"><i class="bi bi-shield-lock"></i> Security</a></li>
                <li><a href="{{ route('incidents.index') }}" class="nav-link"><i class="bi bi-exclamation-triangle"></i> Incidents</a></li>
                <li><a href="{{ route('system_logs.index') }}" class="nav-link"><i class="bi bi-journal-text"></i> System Logs</a></li>
            @else
                {{-- Employee-only navigation --}}
                <li><a href="{{ route('employee.dashboard') }}" class="nav-link"><i class="bi bi-activity"></i> Dashboard</a></li>
                <li><a href="{{ route('stock_in.index') }}" class="nav-link"><i class="bi bi-dropbox"></i> Stock-In</a></li>
                <li><a href="{{ route('inventory.index') }}" class="nav-link"><i class="bi bi-inboxes-fill"></i> Inventory</a></li>
                <li><a href="{{ route('services.index') }}" class="nav-link"><i class="bi bi-wrench"></i> Service</a></li>
                <li><a href="{{ route('bookings.index') }}" class="nav-link"><i class="bi bi-person-lines-fill"></i> Bookings</a></li>
                <li><a href="{{ route('suppliers.index') }}" class="nav-link"><i class="bi bi-person-fill-down"></i> Suppliers</a></li>
                <li><a href="{{ route('stock_out.index') }}" class="nav-link"><i class="bi bi-box-arrow-up"></i> Stock-Out</a></li>
                <li><a href="{{ route('reports.index') }}" class="nav-link"><i class="bi bi-bar-chart-line"></i> Reports</a></li>
            @endif
        </ul>
    </center>
</div>

<div class="header">
    <button class="toggle-btn" type="button" data-toggle="sidebar">☰</button>
    <h1>SubWFour Inventory System</h1>

    <div class="user-profile" id="userProfile">
        <span>Welcome, {{ $user->name }}!</span>
        <div class="profile-picture" id="profileTrigger">
            <img src="{{ $user->role === 'employee'
                ? asset('images/EmployeeProfile.png')
                : asset('images/' . $profilePicture) }}" alt="Profile Picture">
        </div>

        <div class="dropdown-menu hidden" id="dropdownMenu" data-dropdown-menu>
            <button class="dropdown-item" data-action="view-profile">View Profile</button>
            @if($user->role === 'admin')
                <a href="{{ route('employees.index') }}" class="dropdown-item">View Employees</a>
                <button class="dropdown-item" data-action="register-employee">Register Employee</button>
            @endif
            <form action="{{ route('logout') }}" method="GET">
                @csrf
                <button type="submit" class="logout-btn dropdown-item" data-action="logout">Log-Out</button>
            </form>
        </div>

        <div class="modal hidden" id="viewProfileModal" data-modal>
            <div class="modal-content fb-profile-modal">
                {{-- Cover Section --}}
                <div class="fb-profile-cover">
                    <div class="fb-profile-cover-gradient"></div>
                    <button type="button" class="fb-profile-close" data-close>
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                {{-- Avatar & Name --}}
                <div class="fb-profile-header">
                    <div class="fb-profile-avatar-wrap">
                        <img src="{{ $user->role === 'employee' 
                            ? ($user->employee && $user->employee->profile_picture 
                                ? asset('storage/'.$user->employee->profile_picture) 
                                : asset('images/EmployeeProfile.png'))
                            : asset('images/AdminProfile.png') }}" 
                             alt="Profile" 
                             class="fb-profile-avatar">
                        <span class="fb-profile-status-dot {{ $user->is_active ? 'online' : 'offline' }}"></span>
                    </div>
                    <div class="fb-profile-name-section">
                        <h2 class="fb-profile-name">{{ $user->name }}</h2>
                        <span class="fb-profile-role-badge fb-role-{{ $user->role }}">{{ ucfirst($user->role) }}</span>
                        <span class="fb-profile-status-badge {{ $user->is_active ? 'active' : 'inactive' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                </div>

                {{-- Info Sections --}}
                <div class="fb-profile-body">
                    <div class="fb-profile-section">
                        <h4 class="fb-profile-section-title">
                            <i class="bi bi-person-vcard"></i> Account Information
                        </h4>
                        <div class="fb-profile-info-row">
                            <span class="fb-info-label"><i class="bi bi-envelope"></i> Email</span>
                            <span class="fb-info-value">{{ $user->email }}</span>
                        </div>
                        <div class="fb-profile-info-row">
                            <span class="fb-info-label"><i class="bi bi-shield-check"></i> Role</span>
                            <span class="fb-info-value">{{ ucfirst($user->role) }}</span>
                        </div>
                        <div class="fb-profile-info-row">
                            <span class="fb-info-label"><i class="bi bi-calendar3"></i> Member Since</span>
                            <span class="fb-info-value">{{ $user->created_at?->format('F d, Y') ?? '—' }}</span>
                        </div>
                    </div>

                    @if($user->role === 'employee' && $user->employee)
                    <div class="fb-profile-section">
                        <h4 class="fb-profile-section-title">
                            <i class="bi bi-person-lines-fill"></i> Personal Details
                        </h4>
                        <div class="fb-profile-info-row">
                            <span class="fb-info-label"><i class="bi bi-person"></i> Full Name</span>
                            <span class="fb-info-value">{{ $user->employee->first_name }} {{ $user->employee->last_name }}</span>
                        </div>
                        <div class="fb-profile-info-row">
                            <span class="fb-info-label"><i class="bi bi-telephone"></i> Contact</span>
                            <span class="fb-info-value">{{ $user->employee->masked_contact ?? '—' }}</span>
                        </div>
                        <div class="fb-profile-info-row">
                            <span class="fb-info-label"><i class="bi bi-geo-alt"></i> Address</span>
                            <span class="fb-info-value">{{ $user->employee->address ?? '—' }}</span>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="fb-profile-footer">
                    <button type="button" class="btn btn-secondary" data-close>Close</button>
                </div>
            </div>
        </div>

        @if($user->role === 'admin')
        <div class="app-modal" id="createEmployeeModal" data-modal>
            <div class="app-modal-content emp-modal-add">
                <div class="emp-modal-header">
                    <h2><i class="bi bi-person-plus"></i> Register New Employee</h2>
                    <button type="button" class="emp-modal-close" data-close><i class="bi bi-x-lg"></i></button>
                </div>

                <div id="employeeFormErrors" class="alert alert-danger" style="margin: 0 24px 16px; font-size: 0.85rem; display: none;">
                    <ul style="margin: 0; padding-left: 18px;"></ul>
                </div>
                <div id="employeeFormSuccess" class="alert alert-success" style="margin: 0 24px 16px; display: none;"></div>

                <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data" id="employeeCreateForm">
                    @csrf
                    <div class="emp-modal-body">
                        <div class="emp-form-section">
                            <h4>Account Details</h4>
                            <div class="emp-form-row emp-form-row-2">
                                <div class="emp-form-group">
                                    <label>Display Name <span class="required">*</span></label>
                                    <input type="text" name="name" required value="{{ old('name') }}" placeholder="e.g. John Doe">
                                </div>
                                <div class="emp-form-group">
                                    <label>Email Address <span class="required">*</span></label>
                                    <input type="email" name="email" required value="{{ old('email') }}" placeholder="employee@example.com">
                                </div>
                            </div>
                            <div class="emp-form-row emp-form-row-2">
                                <div class="emp-form-group">
                                    <label>Password <span class="required">*</span></label>
                                    <input type="password" name="password" required minlength="12" maxlength="18">
                                    <small>12-18 chars, alphanumeric, 1 upper/lower/number</small>
                                </div>
                                <div class="emp-form-group">
                                    <label>Confirm Password <span class="required">*</span></label>
                                    <input type="password" name="password_confirmation" required>
                                </div>
                            </div>
                        </div>

                        <div class="emp-form-section">
                            <h4>Personal Information</h4>
                            <div class="emp-form-row emp-form-row-2">
                                <div class="emp-form-group">
                                    <label>First Name <span class="required">*</span></label>
                                    <input type="text" name="first_name" required value="{{ old('first_name') }}">
                                </div>
                                <div class="emp-form-group">
                                    <label>Last Name <span class="required">*</span></label>
                                    <input type="text" name="last_name" required value="{{ old('last_name') }}">
                                </div>
                            </div>
                            <div class="emp-form-row">
                                <div class="emp-form-group">
                                    <label>Address <span class="required">*</span></label>
                                    <textarea name="address" rows="2" required placeholder="Full address...">{{ old('address') }}</textarea>
                                </div>
                            </div>
                            <div class="emp-form-row emp-form-row-2">
                                <div class="emp-form-group">
                                    <label>Contact Number <span class="required">*</span></label>
                                    <input type="text" name="contact_number" id="empContactNumber" required value="{{ old('contact_number') }}" placeholder="09XXXXXXXXX" maxlength="11" inputmode="numeric">
                                    <small>11 digits only (e.g. 09123456789)</small>
                                </div>
                                <div class="emp-form-group">
                                    <label>SSS Number <span class="required">*</span></label>
                                    <input type="text" name="sss_number" id="empSSSNumber" required value="{{ old('sss_number') }}" placeholder="XX-XXXXXXX-X" maxlength="12" inputmode="numeric">
                                    <small>Auto-formatted (e.g. 01-0123456-1)</small>
                                </div>
                            </div>
                            <div class="emp-form-row">
                                <div class="emp-form-group">
                                    <label>Profile Picture</label>
                                    <input type="file" name="profile_picture" accept=".jpg,.jpeg,.png" id="createProfileInput">
                                    <small>Optional. Max 1MB. JPG/PNG only. Defaults to EmployeeProfile.png</small>
                                    <div id="createProfilePreview" style="margin-top:8px; display:none;">
                                        <img src="" alt="Preview" style="height:60px;width:60px;border-radius:10px;object-fit:cover;border:1px solid var(--gray-300);">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="emp-modal-footer">
                        <button type="submit" class="btn btn-primary" id="employeeSubmitBtn"><i class="bi bi-check-lg"></i> Register Employee</button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="main-content">
    @yield('content')
</div>

<div class="footer">
    <p>&copy; 2025 SubWFour. All rights reserved.</p>
</div>

@yield('scripts')
</body>
</html>