<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem GJM & GKM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .welcome-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            padding: 60px 40px;
            text-align: center;
            max-width: 600px;
        }

        .welcome-container h1 {
            color: #1e3c72;
            font-size: 42px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .welcome-container p {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .btn-welcome {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin: 10px;
            cursor: pointer;
        }

        .btn-welcome:hover {
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(30, 60, 114, 0.4);
        }
    </style>
</head>

<body>
    <div class="welcome-container">
        <h1>Sistem GJM & GKM</h1>
        <p>Sistem Otomatisasi Administrasi Gugus Kendali Mutu dan Gugus Jaminan Mutu Fakultas Vokasi</p>

        @if (Route::has('login'))
            @auth
                <a href="{{ url('/') }}" class="btn-welcome">Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn-welcome">Login</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn-welcome">Register</a>
                @endif
            @endauth
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
