<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk - Replenishment Matching App</title>

    {{-- Alpine.js --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        body {
            background-color: #0F1B2D;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            color: #0F172A;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }

        /* Brand Header */
        .brand-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .brand-logo-box {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: rgba(37, 99, 235, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px auto;
            color: #60A5FA;
        }

        .brand-title {
            font-size: 20px;
            font-weight: 800;
            color: #FFFFFF;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .brand-subtitle {
            font-size: 13px;
            color: #94A3B8;
            margin-top: 4px;
        }

        /* Alert Box */
        .alert-box {
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background-color: #ECFDF5;
            border: 1px solid #A7F3D0;
            color: #065F46;
        }

        .alert-error {
            background-color: #FEF2F2;
            border: 1px solid #FECACA;
            color: #991B1B;
        }

        /* Login Card */
        .login-card {
            background-color: #FFFFFF;
            border-radius: 18px;
            padding: 28px 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-heading {
            font-size: 17px;
            font-weight: 800;
            color: #0F172A;
            margin-bottom: 4px;
        }

        .card-subheading {
            font-size: 12px;
            color: #64748B;
            margin-bottom: 20px;
        }

        /* Form Inputs */
        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: #94A3B8;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            height: 42px;
            padding: 0 14px 0 38px;
            font-size: 13px;
            border: 1px solid #CBD5E1;
            border-radius: 10px;
            background-color: #F8FAFC;
            color: #0F172A;
            outline: none;
            transition: all 0.15s ease;
        }

        .form-input:focus {
            border-color: #2563EB;
            background-color: #FFFFFF;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 12px;
            color: #64748B;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
        }

        .remember-checkbox {
            width: 16px;
            height: 16px;
            accent-color: #2563EB;
            cursor: pointer;
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            height: 42px;
            background-color: #2563EB;
            color: #FFFFFF;
            font-size: 13px;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.15s ease;
            box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.3);
        }

        .btn-submit:hover {
            background-color: #1D4ED8;
            box-shadow: 0 6px 12px -2px rgba(37, 99, 235, 0.4);
        }

        /* Demo Accounts Section */
        .demo-section {
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid #E2E8F0;
        }

        .demo-title {
            font-size: 10px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            text-align: center;
            margin-bottom: 10px;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .demo-btn {
            padding: 8px 10px;
            border-radius: 8px;
            text-align: left;
            cursor: pointer;
            border: 1px solid #E2E8F0;
            background-color: #F8FAFC;
            transition: all 0.15s ease;
        }

        .demo-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .demo-btn-admin:hover {
            background-color: #FAF5FF;
            border-color: #D8B4FE;
        }

        .demo-btn-operator:hover {
            background-color: #F0FDF4;
            border-color: #86EFAC;
        }

        .demo-btn-viewer:hover {
            background-color: #EFF6FF;
            border-color: #93C5FD;
        }

        .demo-role-name {
            font-size: 11px;
            font-weight: 700;
            line-height: 1.2;
        }

        .demo-role-sub {
            font-size: 9px;
            color: #64748B;
            margin-top: 2px;
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
            color: #64748B;
        }
    </style>
</head>
<body>
    <div class="login-wrapper" x-data="{
        fillCredentials(email, pwd) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = pwd;
        }
    }">
        {{-- Brand Logo & Title --}}
        <div class="brand-header">
            <div class="brand-logo-box">
                <svg style="width: 24px; height: 24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <h1 class="brand-title">Replenishment Matching</h1>
            <p class="brand-subtitle">Sistem Rekonsiliasi & Analisis Stok Gudang</p>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="alert-box alert-success">
                <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="alert-box alert-error">
                <svg style="width: 16px; height: 16px; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        {{-- Login Card --}}
        <div class="login-card">
            <h2 class="card-heading">Masuk ke Sistem</h2>
            <p class="card-subheading">Gunakan email dan password Anda untuk melanjutkan</p>

            <form action="{{ route('login.post') }}" method="POST">
                @csrf

                {{-- Email Input --}}
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                        </svg>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            required
                            value="{{ old('email') }}"
                            placeholder="nama@perusahaan.com"
                            class="form-input"
                        >
                    </div>
                </div>

                {{-- Password Input --}}
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            required
                            placeholder="••••••••"
                            class="form-input"
                        >
                    </div>
                </div>

                {{-- Remember Me --}}
                <div class="remember-row">
                    <label class="remember-label">
                        <input type="checkbox" name="remember" class="remember-checkbox">
                        <span>Ingat saya</span>
                    </label>
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="btn-submit">
                    Masuk ke Sistem
                </button>
            </form>

            {{-- Demo Accounts Quick Fill --}}
            <div class="demo-section">
                <div class="demo-title">
                    Coba Akun Demo (Klik untuk isi cepat)
                </div>
                <div class="demo-grid">
                    {{-- Admin --}}
                    <button
                        type="button"
                        @click="fillCredentials('admin@replenishment.test', 'password')"
                        class="demo-btn demo-btn-admin"
                    >
                        <div class="demo-role-name" style="color: #7E22CE;">Admin</div>
                        <div class="demo-role-sub">Akses Penuh</div>
                    </button>

                    {{-- Operator --}}
                    <button
                        type="button"
                        @click="fillCredentials('operator@replenishment.test', 'password')"
                        class="demo-btn demo-btn-operator"
                    >
                        <div class="demo-role-name" style="color: #15803D;">Operator</div>
                        <div class="demo-role-sub">Upload & Match</div>
                    </button>

                    {{-- Viewer --}}
                    <button
                        type="button"
                        @click="fillCredentials('viewer@replenishment.test', 'password')"
                        class="demo-btn demo-btn-viewer"
                    >
                        <div class="demo-role-name" style="color: #0369A1;">Viewer</div>
                        <div class="demo-role-sub">Hanya Lihat</div>
                    </button>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="login-footer">
            Replenishment App &copy; {{ date('Y') }} • Hak Akses & Keamanan Sistem
        </div>
    </div>
</body>
</html>
