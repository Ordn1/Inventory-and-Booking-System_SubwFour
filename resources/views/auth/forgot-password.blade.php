<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Forgot Password - SubWFour</title>
  <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
  <div class="login-wrapper">
    <div class="login-box">
      <div class="login-header">
        <h1>═════SubWFour═════</h1>
        <p>Password Recovery</p>
      </div>

      <form action="{{ route('password.send-otp') }}" method="POST">
        @csrf
        <label for="email">Email Address:</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="Enter your email address" required>

        <p class="form-help-text">We'll send a 6-digit OTP code to your email to verify your identity.</p>

        <button type="submit">Send OTP</button>
      </form>

      <div class="forgot-password-link">
        <a href="{{ route('login') }}">← Back to Login</a>
      </div>

      @if (session('success'))
        <div style="color: #b3ffb3; font-size: 13px; margin-top: 10px;">
          {{ session('success') }}
        </div>
      @endif

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
