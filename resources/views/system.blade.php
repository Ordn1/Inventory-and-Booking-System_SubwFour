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
                <li><a href="{{ route('system') }}" class="nav-link"><i class="bi bi-activity"></i> Dashboard</a></li>
            @endif
            <li><a href="{{ route('stock_in.index') }}" class="nav-link"><i class="bi bi-dropbox"></i> Stock-In</a></li>
            <li><a href="{{ route('inventory.index') }}" class="nav-link"><i class="bi bi-inboxes-fill"></i> Inventory</a></li>
            <li><a href="{{ route('services.index') }}" class="nav-link"><i class="bi bi-wrench"></i> Service</a></li>
            <li><a href="{{ route('bookings.index') }}" class="nav-link"><i class="bi bi-person-lines-fill"></i> Bookings</a></li>
            <li><a href="{{ route('suppliers.index') }}" class="nav-link"><i class="bi bi-person-fill-down"></i> Suppliers</a></li>
            @if($user->role === 'admin')
                <li><a href="{{ route('reports.index') }}" class="nav-link"><i class="bi bi-list-columns"></i> Reports</a></li>
                <li><a href="{{ route('employees.index') }}" class="nav-link"><i class="bi bi-people-fill"></i> Employees</a></li>
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
            <div class="modal-content">
                <h2>Profile</h2>
                <p><strong>Name:</strong> {{ $user->name }}</p>
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Role:</strong> {{ ucfirst($user->role) }}</p>
                <button class="close-btn" data-close>Close</button>
            </div>
        </div>

        @if($user->role === 'admin')
        <div class="modal hidden" id="createEmployeeModal" data-modal>
            <div class="modal-content" style="max-width:640px;">
                <h2 style="margin-bottom:14px;">Register Employee</h2>

                @if($errors->any() && url()->current() === route('system'))
                    <div class="alert alert-danger mb-2">
                        <ul class="m-0 ps-3" style="font-size:.7rem;">
                            @foreach($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if(session('success') && url()->current() === route('system'))
                    <div class="alert alert-success mb-2">{{ session('success') }}</div>
                @endif

                <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data" id="employeeCreateForm">
                    @csrf

                    <h4 class="section-heading" style="margin:10px 0 8px;">Account</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Name</label>
                            <input name="name" class="form-input" required value="{{ old('name') }}">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input name="email" type="email" class="form-input" required value="{{ old('email') }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Password</label>
                            <input name="password" type="password" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm</label>
                            <input name="password_confirmation" type="password" class="form-input" required>
                        </div>
                    </div>

                    <h4 class="section-heading" style="margin:14px 0 8px;">Information</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input name="first_name" class="form-input" required value="{{ old('first_name') }}">
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input name="last_name" class="form-input" required value="{{ old('last_name') }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="flex:1 0 100%;">
                            <label>Address</label>
                            <input name="address" class="form-input" required value="{{ old('address') }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Contact #</label>
                            <input name="contact_number" class="form-input" required value="{{ old('contact_number') }}">
                        </div>
                        <div class="form-group">
                            <label>SSS #</label>
                            <input name="sss_number" class="form-input" required value="{{ old('sss_number') }}">
                        </div>
                    </div>

                    <div class="form-row" style="margin-top:10px;">
                        <div class="form-group" style="flex:1 0 100%;">
                            <label>Profile Picture (optional)</label>
                            <input type="file" name="profile_picture" accept="image/*" class="form-input" id="createProfileInput">
                            <div id="createProfilePreview" style="margin-top:6px; display:none;">
                                <img src="" alt="Preview" style="height:60px;width:60px;border-radius:10px;object-fit:cover;border:1px solid var(--gray-300);">
                            </div>
                        </div>
                    </div>

                    <div class="button-row" style="margin-top:18px; display:flex; gap:10px; justify-content:flex-end;">
                        <button type="button" class="btn-secondary" data-close>Cancel</button>
                        <button type="submit" class="btn-primary">Create</button>
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