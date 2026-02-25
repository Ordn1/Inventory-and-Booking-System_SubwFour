@extends('system')

@section('title', '404 - Page Not Found')

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
            color: var(--brand-red);
            line-height: 1;
            margin-bottom: 10px;
            text-shadow: 0 4px 20px rgba(239, 53, 53, 0.3);
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
        .error-btn-secondary {
            background: var(--gray-300);
            color: var(--gray-800);
        }
        .error-btn-secondary:hover {
            background: var(--gray-350);
        }
        .error-icon {
            font-size: 4rem;
            color: var(--gray-500);
            margin-bottom: 20px;
        }
    </style>
@endsection

@section('content')
<div class="error-container">
    <i class="bi bi-question-circle error-icon"></i>
    <div class="error-code">404</div>
    <h1 class="error-title">Page Not Found</h1>
    <p class="error-message">
        The page you're looking for doesn't exist or has been moved. 
        Please check the URL or navigate back to safety.
    </p>
    <div class="error-actions">
        <a href="{{ route('system') }}" class="error-btn error-btn-primary">
            <i class="bi bi-house"></i> Go to Dashboard
        </a>
        <a href="javascript:history.back()" class="error-btn error-btn-secondary">
            <i class="bi bi-arrow-left"></i> Go Back
        </a>
    </div>
</div>
@endsection
