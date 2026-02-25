@extends('system')

@section('title', '503 - Service Unavailable')

@section('head')
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
    <style>
        .error-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 60vh;
            text-align: center;
            padding: 40px;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            color: var(--yellow-500);
            line-height: 1;
            margin-bottom: 10px;
            text-shadow: 0 4px 20px rgba(234, 179, 8, 0.3);
        }
        .error-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 16px;
        }
        .error-message {
            font-size: 1rem;
            color: var(--gray-600);
            max-width: 500px;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .error-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .error-btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .error-btn-primary {
            background: var(--brand-red);
            color: white;
        }
        .error-btn-primary:hover {
            background: #d32f2f;
            transform: translateY(-2px);
        }
        .error-icon {
            font-size: 4rem;
            color: var(--yellow-500);
            margin-bottom: 20px;
        }
    </style>
@endsection

@section('content')
<div class="error-container">
    <i class="bi bi-wrench-adjustable error-icon"></i>
    <div class="error-code">503</div>
    <h1 class="error-title">Under Maintenance</h1>
    <p class="error-message">
        The system is currently undergoing scheduled maintenance. 
        We'll be back shortly. Thank you for your patience.
    </p>
    <div class="error-actions">
        <a href="javascript:location.reload()" class="error-btn error-btn-primary">
            <i class="bi bi-arrow-clockwise"></i> Refresh Page
        </a>
    </div>
</div>
@endsection
