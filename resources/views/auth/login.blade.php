<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login & Registrasi - Membership System</title>

    <style>
        :root {
            --bg: #f4f7fb;
            --ink: #172033;
            --muted: #5a667f;
            --panel: #ffffff;
            --line: #d8e0ef;
            --brand: #1363df;
            --brand-soft: #e8f1ff;
            --radius: 14px;
            --danger: #c62828;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--ink);
            background: linear-gradient(180deg, #eff6ff 0%, #f8fbff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            width: 100%;
            max-width: 500px;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 28px;
            box-shadow: 0 18px 40px rgba(19, 99, 223, 0.08);
        }

        .hero {
            text-align: center;
            margin-bottom: 24px;
        }

        h1 {
            margin: 0 0 10px;
            font-size: 28px;
        }

        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 22px;
        }

        .tab-btn {
            flex: 1;
            border: 1px solid var(--line);
            background: #fff;
            color: var(--ink);
            padding: 12px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            transition: 0.2s;
        }

        .tab-btn.active {
            background: var(--brand);
            color: white;
            border-color: var(--brand);
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 20px;
            background: linear-gradient(180deg, #ffffff, #fbfdff);
        }

        .panel h2 {
            margin: 0 0 10px;
            font-size: 22px;
        }

        form {
            display: grid;
            gap: 14px;
        }

        label {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
            display: inline-block;
        }

        input {
            width: 100%;
            border: 1px solid #cbd6eb;
            border-radius: 10px;
            padding: 11px 12px;
            font: inherit;
        }

        input:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(19, 99, 223, 0.15);
        }

        button.submit-btn {
            border: 0;
            border-radius: 10px;
            background: var(--brand);
            color: #fff;
            font-weight: 700;
            padding: 12px 14px;
            cursor: pointer;
            transition: 0.2s;
        }

        button.submit-btn:hover {
            opacity: 0.95;
        }

        .error {
            color: var(--danger);
            font-size: 13px;
            margin-top: 5px;
        }

        .hidden {
            display: none;
        }

        .form-wrapper {
            position: relative;
            min-height: 520px;
        }

        .form-panel {
            position: absolute;
            width: 100%;
            top: 0;
            left: 0;
            transition: opacity 0.25s ease;
        }

        .hidden {
            opacity: 0;
            pointer-events: none;
            visibility: hidden;
        }

        .show {
            opacity: 1;
            visibility: visible;
        }
    </style>
</head>
<body>

<div class="card">

    <div class="hero">
        <h1>Membership System</h1>
        <p>Pilih menu masuk atau daftar untuk melanjutkan.</p>
    </div>

    <div class="tabs">
        <button class="tab-btn active" id="btn-login" onclick="showForm('login')">
            Masuk
        </button>

        <button class="tab-btn" id="btn-register" onclick="showForm('register')">
            Daftar
        </button>
    </div>
<div class="form-wrapper">

    <!-- FORM LOGIN -->
    <div class="panel form-panel show" id="login-form">

        <h2>Masuk</h2>

        <p style="margin-bottom: 18px;">
            Silakan masuk menggunakan akun Anda.
        </p>

        <form action="{{ route('login') }}" method="POST">
            @csrf

            <div>
                <label for="login_email">Email</label>

                <input
                    id="login_email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    placeholder="you@example.com"
                    required
                >

                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="login_password">Password</label>

                <input
                    id="login_password"
                    name="password"
                    type="password"
                    placeholder="Masukkan password"
                    required
                >

                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <button class="submit-btn" type="submit">
                Masuk
            </button>
        </form>
    </div>

    <!-- FORM REGISTER -->
    <div class="panel form-panel hidden" id="register-form">

        <h2>Daftar Akun</h2>

        <p style="margin-bottom: 18px;">
            Buat akun baru untuk menggunakan sistem.
        </p>

        <form action="{{ route('register') }}" method="POST">
            @csrf

            <div>
                <label for="register_name">Nama Lengkap</label>

                <input
                    id="register_name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    placeholder="Nama lengkap"
                    required
                >

                @error('name')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="register_email">Email</label>

                <input
                    id="register_email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    placeholder="you@example.com"
                    required
                >

                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="register_password">Password</label>

                <input
                    id="register_password"
                    name="password"
                    type="password"
                    placeholder="Minimal 6 karakter"
                    required
                >

                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div>
                <label for="register_password_confirmation">
                    Konfirmasi Password
                </label>

                <input
                    id="register_password_confirmation"
                    name="password_confirmation"
                    type="password"
                    placeholder="Ulangi password"
                    required
                >
            </div>

            <button class="submit-btn" type="submit">
                Daftar
            </button>
        </form>
    </div>

</div>
<script>
    function showForm(type) {

        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');

        const btnLogin = document.getElementById('btn-login');
        const btnRegister = document.getElementById('btn-register');

        if (type === 'login') {

            loginForm.classList.remove('hidden');
            registerForm.classList.add('hidden');

            btnLogin.classList.add('active');
            btnRegister.classList.remove('active');

        } else {

            registerForm.classList.remove('hidden');
            loginForm.classList.add('hidden');

            btnRegister.classList.add('active');
            btnLogin.classList.remove('active');
        }
    }
</script>

</body>
</html>