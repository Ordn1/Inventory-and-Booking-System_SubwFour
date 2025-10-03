@extends('system')

@section('title', 'Edit Employee - SubWFour')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="employee-edit">
    <div class="glass-card glass-card-wide mx-auto">

        <h2 class="app-modal-header" style="text-align:left;">EDIT EMPLOYEE</h2>

        @if ($errors->any())
            <div class="alert alert-danger mb-3">
                <ul class="m-0 ps-3" style="font-size:.75rem;">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger mb-3">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-success mb-3">{{ session('success') }}</div>
        @endif

        <form action="{{ route('employees.update', $employee->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <h4 class="section-heading">Employee Account</h4>
            <div class="row gap-row mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label" for="name">Name</label>
                        <input id="name"
                               type="text"
                               name="name"
                               class="form-control dark-input"
                               value="{{ old('name', $employee->user->name ?? '') }}"
                               required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input id="email"
                               type="email"
                               name="email"
                               class="form-control dark-input"
                               value="{{ old('email', $employee->user->email ?? '') }}"
                               required>
                    </div>
                </div>
            </div>

            <div class="row gap-row mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label" for="password">Password (leave blank)</label>
                        <input id="password"
                               type="password"
                               name="password"
                               class="form-control dark-input">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label" for="password_confirmation">Confirm Password</label>
                        <input id="password_confirmation"
                               type="password"
                               name="password_confirmation"
                               class="form-control dark-input">
                    </div>
                </div>
            </div>

            <h4 class="section-heading">Employee Information</h4>
            <div class="row gap-row mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label" for="first_name">First Name</label>
                        <input id="first_name"
                               type="text"
                               name="first_name"
                               class="form-control dark-input"
                               value="{{ old('first_name', $employee->first_name) }}"
                               required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label" for="last_name">Last Name</label>
                        <input id="last_name"
                               type="text"
                               name="last_name"
                               class="form-control dark-input"
                               value="{{ old('last_name', $employee->last_name) }}"
                               required>
                    </div>
                </div>
            </div>

            <div class="row gap-row mb-4">
                <div class="col-md-12">
                    <div class="form-group mb-3">
                        <label class="form-label" for="address">Address</label>
                        <input id="address"
                               type="text"
                               name="address"
                               class="form-control dark-input"
                               value="{{ old('address', $employee->address) }}"
                               required>
                    </div>
                </div>
            </div>

            <div class="row gap-row mb-4">
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label" for="contact_number">Contact Number</label>
                        <input id="contact_number"
                               type="text"
                               name="contact_number"
                               class="form-control dark-input"
                               value="{{ old('contact_number', $employee->contact_number) }}"
                               required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-3">
                        <label class="form-label" for="sss_number">SSS Number</label>
                        <input id="sss_number"
                               type="text"
                               name="sss_number"
                               class="form-control dark-input"
                               value="{{ old('sss_number', $employee->sss_number) }}"
                               required>
                    </div>
                </div>
            </div>

            <div class="row gap-row mb-4">
                <div class="col-md-12">
                    <div class="form-group mb-1">
                        <label class="form-label" for="profile_picture">Profile Picture</label>
                        <input id="profile_picture"
                               type="file"
                               name="profile_picture"
                               class="form-control dark-input"
                               accept="image/*">
                    </div>
                    @php
                        $currentProfile = $employee->profile_picture
                            ? asset('storage/'.$employee->profile_picture)
                            : asset('images/TCEmployeeProfile.png');
                    @endphp
                    <div class="mt-2" style="font-size:.7rem; color:var(--gray-600);">
                        Current:
                        <img src="{{ $currentProfile }}" alt="Current" style="height:48px;width:48px;border-radius:10px;border:2px solid #2c2c2c;object-fit:cover;margin-left:6px;">
                    </div>
                </div>
            </div>

            <div class="button-row mt-4" style="display:flex;gap:10px;justify-content:flex-end;">
                <a href="{{ route('employees.index') }}"
                    class="btn-secondary"
                    style="display:inline-flex;align-items:center;justify-content:center;min-width:180px;padding:10px 22px;">
                    Cancel
                </a>
                <button type="submit"
                        class="btn-update"
                        style="display:inline-flex;align-items:center;justify-content:center;min-width:180px;padding:10px 22px;">
                    Update Employee
                </button>
            </div>
        </form>
    </div>
</div>
@endsection