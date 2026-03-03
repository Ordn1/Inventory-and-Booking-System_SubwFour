@extends('system')

@section('title', 'Edit Employee - SubWFour')

@section('content')
<div class="emp-edit-page">
    {{-- Page Header --}}
    <div class="emp-edit-header">
        <a href="{{ route('employees.index') }}" class="emp-edit-back">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="emp-edit-header-info">
            <h1>Edit Employee</h1>
            <p>{{ $employee->first_name }} {{ $employee->last_name }}</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="m-0 ps-3">
                @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('employees.update', $employee->id) }}" method="POST" enctype="multipart/form-data" class="emp-edit-form">
        @csrf
        @method('PUT')

        <div class="emp-edit-grid">
            {{-- Left Column: Profile Card --}}
            <div class="emp-edit-sidebar">
                <div class="emp-edit-profile-card">
                    @php
                        $currentProfile = $employee->profile_picture
                            ? asset('storage/'.$employee->profile_picture)
                            : asset('images/EmployeeProfile.png');
                    @endphp
                    <div class="emp-edit-avatar-wrap">
                        <img src="{{ $currentProfile }}" alt="Profile" class="emp-edit-avatar" id="avatarPreview">
                        <label class="emp-edit-avatar-overlay" for="profile_picture">
                            <i class="bi bi-camera"></i>
                            <span>Change</span>
                        </label>
                        <input type="file" name="profile_picture" id="profile_picture" accept=".jpg,.jpeg,.png" 
                               style="display:none" onchange="previewAvatar(this)">
                    </div>
                    <h3 class="emp-edit-profile-name">{{ $employee->first_name }} {{ $employee->last_name }}</h3>
                    <span class="emp-badge emp-badge-{{ $employee->user->role ?? 'employee' }}">
                        {{ ucfirst($employee->user->role ?? 'employee') }}
                    </span>
                    <div class="emp-edit-profile-meta">
                        <span><i class="bi bi-envelope"></i> {{ $employee->user->email ?? '—' }}</span>
                        <span><i class="bi bi-calendar3"></i> Joined {{ $employee->created_at->format('M Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- Right Column: Form Sections --}}
            <div class="emp-edit-main">
                {{-- Account Section --}}
                <div class="emp-edit-section">
                    <h3 class="emp-edit-section-title"><i class="bi bi-shield-lock"></i> Account Settings</h3>
                    <div class="emp-edit-fields">
                        <div class="emp-edit-row">
                            <div class="emp-edit-field">
                                <label for="name">Display Name</label>
                                <input type="text" name="name" id="name" 
                                       value="{{ old('name', $employee->user->name ?? '') }}" required>
                            </div>
                            <div class="emp-edit-field">
                                <label for="email">Email Address</label>
                                <input type="email" name="email" id="email" 
                                       value="{{ old('email', $employee->user->email ?? '') }}" required>
                            </div>
                        </div>
                        <div class="emp-edit-row">
                            <div class="emp-edit-field">
                                <label for="password">New Password <span class="optional">(leave blank to keep)</span></label>
                                <div class="emp-password-input-wrap">
                                    <input type="password" name="password" id="password" minlength="12" maxlength="18"
                                           placeholder="••••••••••••">
                                    <button type="button" class="emp-password-toggle" onclick="togglePasswordVisibility('password', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <small class="emp-password-hint"><i class="bi bi-shield-check"></i> Password is currently set</small>
                            </div>
                            <div class="emp-edit-field">
                                <label for="password_confirmation">Confirm Password</label>
                                <div class="emp-password-input-wrap">
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                           placeholder="Re-enter new password">
                                    <button type="button" class="emp-password-toggle" onclick="togglePasswordVisibility('password_confirmation', this)">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Personal Info Section --}}
                <div class="emp-edit-section">
                    <h3 class="emp-edit-section-title"><i class="bi bi-person"></i> Personal Information</h3>
                    <div class="emp-edit-fields">
                        <div class="emp-edit-row">
                            <div class="emp-edit-field">
                                <label for="first_name">First Name</label>
                                <input type="text" name="first_name" id="first_name" 
                                       value="{{ old('first_name', $employee->first_name) }}" required>
                            </div>
                            <div class="emp-edit-field">
                                <label for="last_name">Last Name</label>
                                <input type="text" name="last_name" id="last_name" 
                                       value="{{ old('last_name', $employee->last_name) }}" required>
                            </div>
                        </div>
                        <div class="emp-edit-row">
                            <div class="emp-edit-field full-width">
                                <label for="address">Address</label>
                                <input type="text" name="address" id="address" 
                                       value="{{ old('address', $employee->address) }}" required>
                            </div>
                        </div>
                        <div class="emp-edit-row">
                            <div class="emp-edit-field">
                                <label for="contact_number">Contact Number</label>
                                <input type="text" name="contact_number" id="editContactNumber" 
                                       value="{{ old('contact_number', $employee->contact_number) }}" required
                                       maxlength="11" inputmode="numeric" placeholder="09XXXXXXXXX">
                                <small>11 digits only</small>
                            </div>
                            <div class="emp-edit-field">
                                <label for="sss_number">SSS Number</label>
                                <input type="text" name="sss_number" id="editSSSNumber" 
                                       value="{{ old('sss_number', $employee->sss_number) }}" required
                                       maxlength="12" inputmode="numeric" placeholder="XX-XXXXXXX-X">
                                <small>Auto-formatted</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="emp-edit-actions">
                    <a href="{{ route('employees.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function togglePasswordVisibility(inputId, btn) {
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

// Contact Number: digits only, max 11
const contactInput = document.getElementById('editContactNumber');
if (contactInput) {
    // Format existing value on load
    contactInput.value = contactInput.value.replace(/\D/g, '').substring(0, 11);
    
    contactInput.addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '');
        if (value.length > 11) value = value.substring(0, 11);
        this.value = value;
    });
    contactInput.addEventListener('keypress', function(e) {
        if (!/[0-9]/.test(e.key)) e.preventDefault();
    });
    contactInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        this.value = paste.replace(/\D/g, '').substring(0, 11);
    });
}

// SSS Number: auto-format XX-XXXXXXX-X
const sssInput = document.getElementById('editSSSNumber');
if (sssInput) {
    // Format existing value on load
    let digits = sssInput.value.replace(/\D/g, '').substring(0, 10);
    let formatted = '';
    if (digits.length > 0) formatted = digits.substring(0, 2);
    if (digits.length > 2) formatted += '-' + digits.substring(2, 9);
    if (digits.length > 9) formatted += '-' + digits.substring(9, 10);
    sssInput.value = formatted;
    
    sssInput.addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '');
        if (value.length > 10) value = value.substring(0, 10);
        let fmt = '';
        if (value.length > 0) fmt = value.substring(0, 2);
        if (value.length > 2) fmt += '-' + value.substring(2, 9);
        if (value.length > 9) fmt += '-' + value.substring(9, 10);
        this.value = fmt;
    });
    sssInput.addEventListener('keypress', function(e) {
        if (!/[0-9]/.test(e.key)) e.preventDefault();
    });
    sssInput.addEventListener('paste', function(e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        let digits = paste.replace(/\D/g, '').substring(0, 10);
        let fmt = '';
        if (digits.length > 0) fmt = digits.substring(0, 2);
        if (digits.length > 2) fmt += '-' + digits.substring(2, 9);
        if (digits.length > 9) fmt += '-' + digits.substring(9, 10);
        this.value = fmt;
    });
}
</script>
@endsection
