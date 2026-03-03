<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
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

      <form id="login-form">
        @csrf
        <label for="name">Login:</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Login</button>
      </form>

      <div class="forgot-password-link">
        <a href="{{ route('password.forgot') }}">Forgot Password?</a>
      </div>

      @if (session('success'))
            <div style="color: #b3ffb3; font-size: 13px; margin-top: 10px;">
                {{ session('success') }}
            </div>
        @endif

      <div id="login-error" style="color: #ffb3b3; font-size: 13px; margin-top: 10px; display: none;">
        <ul style="padding-left: 20px;">
          <li id="error-message"></li>
        </ul>
      </div>

      <div class="login-footer">
        <img src="{{ asset('images/SWFMorel.png') }}" alt="Morel Logo">
      </div>
    </div>
  </div>

  <!-- Captcha Modal -->
  <div id="captcha-modal" class="captcha-modal">
    <div class="captcha-modal-overlay"></div>
    <div class="captcha-modal-content">
      <div class="captcha-modal-header">
        <h2>Security Verification</h2>
        <p>Please enter the captcha code to continue</p>
      </div>
      <form id="captcha-form">
        @csrf
        <div class="captcha-modal-body">
          <div class="captcha-display">
            <img src="{{ captcha_src('flat') }}" alt="captcha" id="captcha-image">
            <button type="button" class="refresh-captcha" onclick="refreshCaptcha()" title="Refresh Captcha">↻</button>
          </div>
          <input type="text" id="captcha" name="captcha" placeholder="Enter captcha code" required autocomplete="off">
          <div id="captcha-error" class="captcha-error"></div>
        </div>
        <div class="captcha-modal-footer">
          <button type="button" class="captcha-cancel-btn" onclick="closeCaptchaModal()">Cancel</button>
          <button type="submit" class="captcha-verify-btn">Verify & Login</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    const loginForm = document.getElementById('login-form');
    const captchaModal = document.getElementById('captcha-modal');
    const captchaForm = document.getElementById('captcha-form');
    const loginError = document.getElementById('login-error');
    const errorMessage = document.getElementById('error-message');
    const captchaError = document.getElementById('captcha-error');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Handle login form submission
    loginForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      hideError();

      const formData = new FormData(loginForm);

      try {
        const response = await fetch('{{ route("login.post") }}', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
          },
          body: formData
        });

        const data = await response.json();

        if (data.success && data.require_captcha) {
          // Show captcha modal
          openCaptchaModal();
        } else if (!data.success) {
          showError(data.message);
        }
      } catch (error) {
        showError('An error occurred. Please try again.');
      }
    });

    // Handle captcha form submission
    captchaForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      captchaError.textContent = '';

      const formData = new FormData(captchaForm);

      try {
        const response = await fetch('{{ route("login.verify-captcha") }}', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
          },
          body: formData
        });

        const data = await response.json();

        if (data.success && data.redirect) {
          window.location.href = data.redirect;
        } else if (!data.success) {
          captchaError.textContent = data.message || 'Invalid captcha. Please try again.';
          refreshCaptcha();
          document.getElementById('captcha').value = '';
        }
      } catch (error) {
        captchaError.textContent = 'An error occurred. Please try again.';
        refreshCaptcha();
      }
    });

    function openCaptchaModal() {
      captchaModal.classList.add('active');
      refreshCaptcha();
      document.getElementById('captcha').value = '';
      document.getElementById('captcha').focus();
    }

    function closeCaptchaModal() {
      captchaModal.classList.remove('active');
      captchaError.textContent = '';
    }

    function refreshCaptcha() {
      document.getElementById('captcha-image').src = '{{ captcha_src("flat") }}' + '?' + Math.random();
    }

    function showError(message) {
      errorMessage.textContent = message;
      loginError.style.display = 'block';
    }

    function hideError() {
      loginError.style.display = 'none';
      errorMessage.textContent = '';
    }

    // Close modal on overlay click
    document.querySelector('.captcha-modal-overlay').addEventListener('click', closeCaptchaModal);

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && captchaModal.classList.contains('active')) {
        closeCaptchaModal();
      }
    });
  </script>
</body>
</html>