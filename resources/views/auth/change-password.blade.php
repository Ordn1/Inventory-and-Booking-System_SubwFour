<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Change Password - SubWFour</title>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .password-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .password-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .password-header i {
            font-size: 48px;
            color: #007bff;
            margin-bottom: 15px;
        }
        .password-header h1 {
            font-size: 24px;
            color: #333;
            margin: 0;
        }
        .password-header p {
            color: #666;
            margin-top: 10px;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
        }
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        .password-requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .password-requirements h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #333;
        }
        .password-requirements ul {
            margin: 0;
            padding-left: 20px;
        }
        .password-requirements li {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
        }
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .error-text {
            color: #dc3545;
            font-size: 13px;
            margin-top: 5px;
        }
        .expiry-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .expiry-badge.expired {
            background: #dc3545;
            color: white;
        }
        .expiry-badge.warning {
            background: #ffc107;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="password-container">
        <div class="password-header">
            <i class="fas fa-key"></i>
            <h1>Change Password</h1>
            @if($isExpired)
                <span class="expiry-badge expired">Password Expired</span>
                <p>Your password has expired. Please set a new password to continue.</p>
            @elseif($mustChange)
                <span class="expiry-badge warning">Password Change Required</span>
                <p>An administrator has requested that you change your password.</p>
            @elseif($daysRemaining !== null && $daysRemaining <= 14)
                <span class="expiry-badge warning">Expires in {{ $daysRemaining }} days</span>
                <p>Your password will expire soon. Consider changing it now.</p>
            @else
                <p>Enter your current password and choose a new secure password.</p>
            @endif
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-info">
                {{ session('success') }}
            </div>
        @endif

        <div class="password-requirements">
            <h4><i class="fas fa-shield-alt"></i> Password Requirements</h4>
            <ul>
                <li>Minimum {{ config('security.password.min_length', 8) }} characters</li>
                <li>At least one uppercase letter (A-Z)</li>
                <li>At least one lowercase letter (a-z)</li>
                <li>At least one number (0-9)</li>
                <li>At least one special character (!@#$%^&*)</li>
                <li>Cannot reuse your last {{ config('security.password.history_count', 5) }} passwords</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            
            <div class="form-group">
                <label for="current_password">
                    <i class="fas fa-lock"></i> Current Password
                </label>
                <input type="password" 
                       id="current_password" 
                       name="current_password" 
                       required 
                       autocomplete="current-password">
                @error('current_password')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">
                    <i class="fas fa-key"></i> New Password
                </label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       required 
                       autocomplete="new-password">
                @error('password')
                    <div class="error-text">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">
                    <i class="fas fa-check-double"></i> Confirm New Password
                </label>
                <input type="password" 
                       id="password_confirmation" 
                       name="password_confirmation" 
                       required 
                       autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Change Password
            </button>
        </form>

        @if(!$isExpired && !$mustChange)
            <p style="text-align: center; margin-top: 20px;">
                <a href="{{ route('dashboard') }}" style="color: #666;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </p>
        @endif
    </div>
</body>
</html>
