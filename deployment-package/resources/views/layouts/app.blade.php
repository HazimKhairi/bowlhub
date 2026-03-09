<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ukhuwah Strike Challenge - Sistem Kejohanan Boling')</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    @stack('styles')
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <i class="fas fa-bowling-ball"></i>
                <span>Ukhuwah Strike Challenge</span>
            </div>
            <ul class="nav-menu">
                @if(session('admin_logged_in'))
                    <li><a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">Utama</a></li>
                    <li><a href="{{ route('leaderboard') }}" class="nav-link {{ request()->routeIs('leaderboard') ? 'active' : '' }}">Kedudukan</a></li>
                    <li><a href="{{ route('admin') }}" class="nav-link {{ request()->routeIs('admin') ? 'active' : '' }}">Admin</a></li>
                    <li>
                        <form id="logoutForm" method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                        </form>
                        <a href="#" onclick="document.getElementById('logoutForm').submit()" class="nav-link">Logout</a>
                    </li>
                @else
                    <li><a href="{{ route('home') }}" class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}">Utama</a></li>
                    <li><a href="{{ route('leaderboard') }}" class="nav-link {{ request()->routeIs('leaderboard') ? 'active' : '' }}">Kedudukan</a></li>
                    <li><a href="{{ route('registration') }}" class="nav-link {{ request()->routeIs('registration') ? 'active' : '' }}">Pendaftaran</a></li>
                @endif
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        @yield('content')
    </main>

    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    @stack('scripts')
</body>
</html>
