<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>Sign In — GTA VI Auto Clipper</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        body {
            font-family: 'Instrument Sans', system-ui, -apple-system, sans-serif;
            background-color: #09090b;
            color: #f4f4f5;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
            box-sizing: border-box;
        }
        .login-card {
            background-color: #141417;
            border: 1px solid #27272a;
            border-radius: 1rem;
            padding: 2rem 1.75rem;
            width: 100%;
            max-width: 24rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }
        .gta-gradient-text {
            background: linear-gradient(135deg, #f43f5e 0%, #ec4899 50%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .form-label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #a1a1aa;
            margin-bottom: 0.375rem;
        }
        .form-input {
            width: 100%;
            background-color: #09090b;
            border: 1px solid #27272a;
            border-radius: 0.5rem;
            padding: 0.75rem 0.875rem;
            color: #ffffff;
            font-size: 0.9375rem;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-input:focus {
            border-color: #ec4899;
        }
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #e11d48 0%, #d946ef 100%);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.9375rem;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: opacity 0.2s;
            margin-top: 1rem;
        }
        .btn-submit:hover {
            opacity: 0.9;
        }
        .error-alert {
            background-color: rgba(136, 19, 55, 0.5);
            border: 1px solid #881337;
            color: #fecdd3;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div style="text-align: center; margin-bottom: 1.75rem;">
            <div style="font-size: 2.25rem; margin-bottom: 0.5rem;">🌴</div>
            <h1 style="font-size: 1.375rem; font-weight: 700; margin: 0;">
                <span class="gta-gradient-text">GTA VI</span> Auto Clipper
            </h1>
            <p style="font-size: 0.8125rem; color: #a1a1aa; margin: 0.25rem 0 0 0;">
                Mobile Administrator Access
            </p>
        </div>

        @if ($errors->any())
            <div class="error-alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if (session('status'))
            <div style="background-color: rgba(6, 78, 59, 0.5); border: 1px solid #064e3b; color: #a7f3d0; padding: 0.75rem; border-radius: 0.5rem; font-size: 0.8125rem; margin-bottom: 1.25rem;">
                {{ session('status') }}
            </div>
        @endif

        <form action="{{ route('clipper.login') }}" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label class="form-label" for="email">Admin Email</label>
                <input class="form-input" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="admin@example.com">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label class="form-label" for="password">Password</label>
                <input class="form-input" type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8125rem; color: #a1a1aa; cursor: pointer;">
                    <input type="checkbox" name="remember" value="1" style="accent-color: #ec4899;">
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" class="btn-submit">
                Sign In to Cloud Clipper
            </button>
        </form>
    </div>
</body>
</html>

