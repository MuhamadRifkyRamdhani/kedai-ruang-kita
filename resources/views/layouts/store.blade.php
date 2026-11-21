<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#23483d">
    <title>@yield('title', 'Kedai Ruang Kita')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="site-header">
        <nav class="container-xl navbar navbar-expand-md" aria-label="Navigasi utama">
            <a class="brand-lockup" href="{{ route('home') }}" aria-label="Kedai Ruang Kita, beranda">
                <span class="brand-mark" aria-hidden="true">rk</span>
                <span class="brand-copy">
                    <span class="brand-name">Kedai Ruang Kita</span>
                    <span class="brand-note">Dapur kecil, ruang untuk semua</span>
                </span>
            </a>
            <div class="header-actions">
                @yield('header-extra')
            </div>
        </nav>
    </header>

    @yield('content')

    <footer class="site-footer">
        <div class="container-xl footer-inner">
            <a class="brand-lockup footer-brand" href="{{ route('home') }}">
                <span class="brand-mark" aria-hidden="true">rk</span>
                <span class="brand-copy">
                    <span class="brand-name">Kedai Ruang Kita</span>
                    <span class="brand-note">Makan pelan-pelan, cerita panjang-panjang.</span>
                </span>
            </a>
            <span class="footer-copyright">© {{ date('Y') }} Kedai Ruang Kita</span>
        </div>
    </footer>
</body>
</html>
