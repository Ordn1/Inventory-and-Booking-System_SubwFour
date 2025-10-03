<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title','Booking Portal - SubWFour')</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('css/portal.css') }}" rel="stylesheet">
    <link href="{{ asset('css/pages.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    @yield('head')
</head>
<body class="booking-portal-body">
    <div class="portal-wrapper">
        @yield('content')
    </div>
    <script src="{{ asset('js/system.js') }}"></script>
    @yield('scripts')
</body>
</html>