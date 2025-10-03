<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SubWFour</title>
  <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
  <div class="login-wrapper">
    <div class="login-box">
      <div class="login-header">
        <h1>═════SubWFour═════</h1>
        <p>To hear is to believe.</p>
      </div>

      <form action="{{ route('login.post') }}" method="POST">
        @csrf
        <label for="name">Login:</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Login</button>
      </form>

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