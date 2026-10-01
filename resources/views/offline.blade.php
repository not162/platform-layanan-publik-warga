<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Sedang Luring (Offline) - Layanan Publik Warga</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,600,700&display=swap" rel="stylesheet" />

    <style>
        :root {
            color-scheme: light;
            --color-primary: #1B365D;
            --color-teal: #3A9696;
            --color-slate: #475569;
            --color-slate-light: #F8FAFC;
            --color-border: #E2E8F0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--color-slate-light);
            color: #1E293B;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            text-align: center;
        }

        .offline-card {
            background: #FFFFFF;
            border: 1px solid var(--color-border);
            border-radius: 1rem;
            padding: 3rem 2rem;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }

        .offline-icon {
            font-size: 3.5rem;
            margin-bottom: 1.25rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-primary);
            margin-bottom: 0.75rem;
        }

        p {
            font-size: 0.95rem;
            color: var(--color-slate);
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background-color: var(--color-primary);
            color: #FFFFFF;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: background-color 0.2s ease;
        }

        .btn:hover {
            background-color: #12243F;
        }
    </style>
</head>
<body>
    <div class="offline-card">
        <div class="offline-icon">📡</div>
        <h1>Anda Sedang Luring (Offline)</h1>
        <p>
            Koneksi internet Anda saat ini terputus. Pastikan perangkat Anda terhubung ke jaringan seluler atau Wi-Fi untuk mengakses data dan layanan terbaru.
        </p>
        <button onclick="window.location.reload()" class="btn">
            <span>🔄</span> Coba Muat Ulang
        </button>
    </div>
</body>
</html>
