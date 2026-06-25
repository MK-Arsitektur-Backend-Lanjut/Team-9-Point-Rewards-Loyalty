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

        * { box-sizing: border-box; }

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
            width: min(100%, 960px);
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            padding: 28px;
            box-shadow: 0 18px 40px rgba(19, 99, 223, 0.08);
        }

        .hero {
            margin-bottom: 20px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.6;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 18px;
        }

        .panel {
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 18px;
            background: linear-gradient(180deg, #ffffff, #fbfdff);
        }

        .panel h2 {
            margin: 0 0 10px;
            font-size: 20px;
        }

        form {
            display: grid;
            gap: 12px;
        }

        label {
            font-weight: 600;
            font-size: 14px;
        }

        input {
            width: 100%;
            border: 1px solid #cbd6eb;
            border-radius: 10px;
            padding: 11px 12px;
            font: inherit;
        }

        button {
            border: 0;
            border-radius: 10px;
            background: var(--brand);
            color: #fff;
            font-weight: 700;
            padding: 12px 14px;
            cursor: pointer;
        }

        .secondary {
            background: var(--brand-soft);
            color: #0d3f8f;
        }

        .error {
            color: var(--danger);
            font-size: 13px;
            margin-top: -4px;
        }

        .note {
            margin-top: 18px;
            font-size: 13px;
            color: var(--muted);
        }
    </style>
</head>
<body>


        <div class="grid">
            <div class="panel">
                <h2>Daftar Akun</h2>
                <p style="margin-bottom: 12px;">Buat akun baru agar dapat melihat dashboard, statement, dan modul lainnya.</p>

                <form action="{{ route('register') }}" method="POST">
                    @csrf

                    <div>
                        <label for="register_name">Nama Lengkap</label>
                        <input id="register_name" name="name" type="text" value="{{ old('name') }}" placeholder="Nama lengkap" required autofocus>
                        @error('name')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="register_email">Email</label>
                        <input id="register_email" name="email" type="email" value="{{ old('email') }}" placeholder="you@example.com" required>
                        @error('email')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="register_password">Password</label>
                        <input id="register_password" name="password" type="password" placeholder="Minimal 6 karakter" required>
                        @error('password')
                            <div class="error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="register_password_confirmation">Konfirmasi Password</label>
                        <input id="register_password_confirmation" name="password_confirmation" type="password" placeholder="Ulangi password" required>
                    </div>

                    <button type="submit">Daftar</button>
                </form>
            </div>

</body>
</html>