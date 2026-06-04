<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem GJM dan GKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap"
        rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .login-wrapper {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }

        .login-box {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 35px 40px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .login-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .logo-container {
            margin-bottom: 10px;
        }

        .logo {
            max-width: 90px;
            height: auto;
            display: inline-block;
        }

        .institute-name {
            color: #2E7BA8;
            font-size: 14px;
            font-weight: 600;
            margin-top: 6px;
            letter-spacing: 0.3px;
        }

        .system-title {
            color: #5A5A5A;
            font-size: 18px;
            font-weight: 600;
            margin-top: 10px;
            margin-bottom: 4px;
        }

        .system-subtitle {
            color: #7A7A7A;
            font-size: 13px;
            line-height: 1.4;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            color: #5A5A5A;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: 0.2px;
            transition: color 0.2s;
        }

        .password-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #d0d0d0;
            border-radius: 3px;
            font-size: 13px;
            font-family: inherit;
            transition: border-color 0.2s, background-color 0.2s;
            background-color: #f9f9f9;
        }

        .password-wrapper .form-control {
            padding-right: 38px;
        }

        .form-control:focus {
            outline: none;
            border-color: #999;
            box-shadow: none;
            background-color: white;
        }

        .form-control::placeholder {
            color: #999;
        }

        .form-group.has-success .form-control:not(:placeholder-shown) {
            border-color: #16a34a;
            background-color: #f0fdf4;
        }

        .form-group.has-success .form-label:has(+ .form-control:not(:placeholder-shown)) {
            color: #16a34a;
        }

        .form-group.has-success .form-control:not(:placeholder-shown):focus {
            border-color: #16a34a;
            background-color: white;
        }

        .form-group.has-error .form-label {
            color: #dc2626;
        }

        .form-group.has-error .form-control {
            border-color: #dc2626;
            background-color: #fef2f2;
        }

        .form-group.has-error .form-control:focus {
            border-color: #dc2626;
            background-color: white;
        }

        .error-message {
            color: #dc2626;
            font-size: 12px;
            margin-top: 6px;
            display: block;
        }

        /* Toggle icon formal dengan Bootstrap Icons */
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #6c757d;
            background: transparent;
            border: none;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            transition: color 0.2s;
        }

        .password-toggle:hover {
            color: #2E7BA8;
        }

        .form-group.has-error .password-toggle {
            color: #dc2626;
        }

        .form-group.has-error .password-toggle:hover {
            color: #b91c1c;
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .remember-me input[type="checkbox"] {
            cursor: pointer;
            width: 14px;
            height: 14px;
        }

        .remember-me label {
            cursor: pointer;
            margin: 0;
            color: #666;
            font-weight: 400;
        }

        .btn-login {
            width: auto;
            padding: 10px 30px;
            background-color: #2E7BA8;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn-login:hover {
            background-color: #1f5a7f;
        }

        .btn-login:active {
            background-color: #164454;
        }

        .alert {
            border-radius: 3px;
            margin-bottom: 20px;
            padding: 12px 15px;
            font-size: 13px;
        }

        .alert-danger {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }

        .alert ul {
            margin: 5px 0 0 20px;
            padding: 0;
        }

        .alert li {
            margin: 3px 0;
        }

        .register-link {
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
            color: #666;
        }

        .register-link a {
            color: #0066cc;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .login-box {
                padding: 30px 20px;
            }

            .system-title {
                font-size: 16px;
            }

            .system-subtitle {
                font-size: 12px;
            }

            .password-toggle {
                font-size: 16px;
                right: 8px;
            }

            .password-wrapper .form-control {
                padding-right: 34px;
            }
        }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <div class="login-box">
            <div class="login-header">
                <div class="logo-container">
                    <img src="{{ asset('images/logo-itdel.jpg') }}" alt="Institut Teknologi Del" class="logo">
                    <div class="institute-name">Institut Teknologi Del</div>
                </div>
                <div class="system-title">Sistem GJM dan GKM</div>
                <div class="system-subtitle">Otomatisasi Administrasi Gugus Jaminan Mutu dan Gugus Kendali Mutu</div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Login Gagal!</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <div class="form-group @error('email') has-error @enderror">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                        name="email" value="{{ old('email') }}" required autofocus placeholder="">
                    @error('email')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group @error('password') has-error @enderror" id="password-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                            id="password" name="password" required placeholder="">
                        <button type="button" class="password-toggle" id="togglePassword"
                            aria-label="Tampilkan password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-footer">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <label for="remember">Ingat Info Login</label>
                    </div>
                    <button type="submit" class="btn-login">Masuk</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const emailGroup = emailInput.closest('.form-group');
            const passwordGroup = document.getElementById('password-group');
            const toggleBtn = document.getElementById('togglePassword');
            const eyeIcon = toggleBtn.querySelector('i');

            function updateFieldState(input, group) {
                if (input.value.trim() !== '') {
                    group.classList.add('has-success');
                } else {
                    group.classList.remove('has-success');
                }
            }

            // Toggle password visibility - LOGIKA DIPERBAIKI
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const isPassword = passwordInput.getAttribute('type') === 'password';

                    if (isPassword) {
                        // Password tersembunyi -> ingin dilihat
                        passwordInput.setAttribute('type', 'text');
                        eyeIcon.classList.remove('bi-eye-slash');
                        eyeIcon.classList.add('bi-eye');
                        toggleBtn.setAttribute('aria-label', 'Sembunyikan password');
                    } else {
                        // Password terlihat -> ingin disembunyikan
                        passwordInput.setAttribute('type', 'password');
                        eyeIcon.classList.remove('bi-eye');
                        eyeIcon.classList.add('bi-eye-slash');
                        toggleBtn.setAttribute('aria-label', 'Tampilkan password');
                    }
                });
            }

            updateFieldState(emailInput, emailGroup);
            updateFieldState(passwordInput, passwordGroup);

            emailInput.addEventListener('input', function() {
                updateFieldState(this, emailGroup);
            });

            passwordInput.addEventListener('input', function() {
                updateFieldState(this, passwordGroup);
            });
        });
    </script>
</body>

</html>
