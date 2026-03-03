<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Password - SubWFour</title>
  <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
  <div class="login-wrapper">
    <div class="login-box">
      <div class="login-header">
        <h1>═════SubWFour═════</h1>
        <p>Set New Password</p>
      </div>

      @if (session('success'))
        <div style="color: #b3ffb3; font-size: 13px; margin-bottom: 15px;">
          {{ session('success') }}
        </div>
      @endif

      <form action="{{ route('password.reset') }}" method="POST">
        @csrf
        <label for="password">New Password:</label>
        <input type="password" id="password" name="password" placeholder="Enter new password (min 8 characters)" required>

        <label for="password_confirmation">Confirm Password:</label>
        <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirm new password" required>

        <button type="submit">Reset Password</button>
      </form>

      <div class="forgot-password-link">
        <a href="{{ route('login') }}">← Back to Login</a>
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
