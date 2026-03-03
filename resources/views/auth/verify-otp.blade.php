<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Verify OTP - SubWFour</title>
  <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
  <div class="login-wrapper">
    <div class="login-box">
      <div class="login-header">
        <h1>═════SubWFour═════</h1>
        <p>Enter OTP Code</p>
      </div>

      @if (session('success'))
        <div style="color: #b3ffb3; font-size: 13px; margin-bottom: 15px;">
          {{ session('success') }}
        </div>
      @endif

      <form action="{{ route('password.verify-otp') }}" method="POST">
        @csrf
        <label for="otp">6-Digit OTP Code:</label>
        <input type="text" id="otp" name="otp" maxlength="6" placeholder="Enter 6-digit code" pattern="[0-9]{6}" required style="text-align: center; font-size: 24px; letter-spacing: 8px;">

        <p class="form-help-text">Check your email for the OTP code. It will expire in 10 minutes.</p>

        <button type="submit">Verify OTP</button>
      </form>

      <div class="forgot-password-link">
        <a href="{{ route('password.forgot') }}">← Resend OTP</a>
      </div>

      @if ($errors->any())
        <div style="color: #ffb3b3; font-size: 13px; margin-top: 10px;">
          <ul style="padding-left: 20px;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="login-footer">
        <img src="{{ asset('images/SWFMorel.png') }}" alt="Morel Logo">
      </div>
    </div>
  </div>
</body>
</html>
