@extends('system')

@section('title', 'Employees - SubWFour')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
@endsection

@section('content')
<h2 class="text-accent">EMPLOYEES</h2>

<div class="glass-card glass-card-wide">

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mb-3">{{ session('error') }}</div>
    @endif

    <div class="toolbar-top d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">

        <div class="search-bar-wrapper">
            <form action="{{ route('employees.index') }}" method="GET" class="search-bar" autocomplete="off">
                <span class="search-icon">
                    <i class="bi bi-search"></i>
                </span>
                <input
                    id="employeeSearch"
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search employee name, email, contact or SSS..."
                    class="search-input"
                    aria-label="Search employees">
                @if(request('search'))
                    <button type="button"
                            class="search-clear"
                            title="Clear search"
                            onclick="window.location='{{ route('employees.index') }}'">
                        <i class="bi bi-x-lg"></i>
                    </button>
                @endif
                <button type="submit" class="btn btn-primary btn-search-main">
                    Search
                </button>
            </form>

            <div class="search-meta">
                @php
                    $total = method_exists($employees,'total') ? $employees->total() : $employees->count();
                @endphp
                <span class="result-count">
                    {{ $total }} {{ \Illuminate\Support\Str::plural('result', $total) }}
                    @if(request('search'))
                        for "<strong>{{ e(request('search')) }}</strong>"
                    @endif
                </span>
                @if(request('search'))
                    <span class="active-filter-chip">
                        <i class="bi bi-funnel"></i> Filter active
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th style="width:70px;">Profile</th>
                    <th>Employee Name</th>
                    <th>Email</th>
                    <th>Contact Number</th>
                    <th style="width:130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($employees as $employee)
                @php
                    $email = $employee->user->email ?? '—';
                    $profile = $employee->profile_picture
                        ? 'storage/'.$employee->profile_picture
                        : 'images/TCEmployeeProfile.png';
                @endphp
                <tr>
                    <td>
                        <img src="{{ asset($profile) }}" alt="Profile" class="profile-picture-sm">
                    </td>
                    <td>{{ $employee->first_name }} {{ $employee->last_name }}</td>
                    <td>{{ $email }}</td>
                    <td>{{ $employee->contact_number ?? '—' }}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="{{ route('employees.edit', $employee->id) }}"
                               class="btn btn-edit"
                               title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="{{ route('employees.destroy', $employee->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('Delete this employee?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-delete" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-row text-center">No employees found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($employees,'links'))
        <div class="mt-3 pagination-wrapper">
            {{ $employees->appends(['search'=>request('search')])->links() }}
        </div>
    @endif
</div>
@endsection