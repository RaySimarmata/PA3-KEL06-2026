<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem GJK & GKM')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- GKM Global Styles -->
    @if(auth()->check() && auth()->user()->isGKM())
        <link rel="stylesheet" href="{{ asset('css/gkm-style.css') }}">
    @endif
    
    <!-- GJM Global Styles -->
    @if(auth()->check() && auth()->user()->isGJM())
        <link rel="stylesheet" href="{{ asset('css/gjm-style.css') }}">
    @endif
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
            --sidebar-width: 280px;
        }

        html,
        body {
            height: 100%;
            width: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }

        .wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            position: fixed;
            height: 100vh;
            z-index: 1000;
            color: white;
            display: flex;
            flex-direction: column;
        }

        .sidebar .logo {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            text-align: center;
            flex-shrink: 0;
            background: rgba(0, 0, 0, 0.1);
        }

        .sidebar .logo h5 {
            margin: 0;
            font-weight: 700;
            font-size: 18px;
            color: white !important;
            letter-spacing: 0.5px;
        }

        .sidebar .logo small {
            color: rgba(255, 255, 255, 0.85) !important;
            font-size: 12px;
            display: block;
            margin-top: 4px;
        }

        .sidebar .user-info {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            text-align: center;
            flex-shrink: 0;
            background: rgba(0, 0, 0, 0.05);
        }

        .sidebar .user-info img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            margin-bottom: 12px;
            border: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .sidebar .user-info p {
            margin: 8px 0 0 0;
            font-size: 14px;
            color: white !important;
        }

        .sidebar .user-info small {
            color: rgba(255, 255, 255, 0.85) !important;
            font-size: 12px;
            display: block;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 500;
        }

        .sidebar .user-info strong {
            color: white !important;
            font-weight: 600;
        }

        .sidebar .nav-menu {
            padding: 20px 0;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar .nav-menu::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar .nav-menu::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-menu::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .sidebar .nav-menu::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        .sidebar .nav-item {
            padding: 13px 20px;
            border-left: 4px solid transparent;
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            font-size: 14px;
            font-weight: 400;
        }

        .sidebar .nav-item:hover {
            background: rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.95);
        }

        .sidebar .nav-item.active {
            background: rgba(255, 255, 255, 0.15);
            border-left-color: white;
            color: white !important;
            font-weight: 500;
        }

        .sidebar .nav-item i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 16px;
            color: white !important;
        }

        /* Dropdown Menu Styles */
        .nav-dropdown {
            position: relative;
        }

        .nav-dropdown>.nav-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-dropdown .dropdown-icon {
            font-size: 12px;
            transition: transform 0.3s ease;
            color: white !important;
        }

        .nav-dropdown.open .dropdown-icon {
            transform: rotate(180deg);
        }

        .dropdown-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background-color: rgba(0, 0, 0, 0.15);
        }

        .nav-dropdown.open .dropdown-submenu {
            max-height: 500px;
        }

        .dropdown-submenu .nav-item {
            padding-left: 52px;
            font-size: 13px;
            border-left: none;
        }

        .dropdown-submenu .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .dropdown-submenu .nav-item.active {
            background: rgba(255, 255, 255, 0.12);
            border-left: none;
        }

        .dropdown-submenu .nav-item i {
            font-size: 14px;
            color: white !important;
        }



        .main-content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            background: white;
            border-bottom: 1px solid #e0e0e0;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .topbar-title {
            font-size: 20px;
            font-weight: bold;
            color: var(--primary-color);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: background 0.2s ease;
        }

        .topbar-user:hover {
            background: #f5f7fa;
        }

        .topbar-user img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
        }

        .topbar-user .user-name-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .topbar-user .dropdown-chevron-top {
            font-size: 12px;
            color: #666;
            transition: transform 0.3s ease;
        }

        .topbar-user.open .dropdown-chevron-top {
            transform: rotate(180deg);
        }

        .topbar-dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }

        .topbar-user.open .topbar-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .topbar-dropdown-menu a,
        .topbar-dropdown-menu button {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-size: 14px;
            transition: background 0.2s ease;
            cursor: pointer;
            border-radius: 8px;
        }

        .topbar-dropdown-menu a:hover,
        .topbar-dropdown-menu button:hover {
            background: #f5f7fa;
        }

        .topbar-dropdown-menu i {
            font-size: 16px;
            color: #666;
        }

        .topbar-dropdown-divider {
            height: 1px;
            background: #e0e0e0;
            margin: 0;
        }

        .content {
            flex: 1;
            background-color: #f5f7fa;
            width: 100%;
            padding: 0;
            margin: 0;
        }

        /* Force full width for all content */
        .content>* {
            width: 100% !important;
            max-width: 100% !important;
        }

        .card {
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            color: white;
        }

        .btn-primary:hover {
            color: white;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(30, 60, 114, 0.4);
        }

        .table thead {
            background-color: #f5f7fa;
            border-bottom: 2px solid #e0e0e0;
        }

        .table th {
            color: var(--primary-color);
            font-weight: 600;
            border: none;
        }

        /* Override Bootstrap container constraints */
        .container-fluid {
            max-width: 100% !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .row {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        @media (max-width: 768px) {
            :root {
                --sidebar-width: 0;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
    @yield('styles')
</head>

<body>
    <div class="wrapper">
        @auth
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="logo">
                    <h5>GJM & GKM</h5>
                    <small>Admin System</small>
                </div>

                <div class="user-info">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=1e3c72&color=fff"
                        alt="User">
                    <p style="margin: 10px 0 0 0;"><strong>{{ auth()->user()->name }}</strong></p>
                    <small>{{ auth()->user()->role }}</small>
                </div>

                <div class="nav-menu">
                    @if (auth()->user()->isGKM())
                        <a href="{{ route('gkm.dashboard') }}"
                            class="nav-item {{ request()->routeIs('gkm.dashboard') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        <a href="{{ route('gkm.data-master.index') }}"
                            class="nav-item {{ request()->routeIs('gkm.data-master.*') ? 'active' : '' }}">
                            <i class="bi bi-database"></i> Data Master
                        </a>

                        <!-- Pengelolaan RPS -->
                        <div class="nav-dropdown {{ request()->routeIs('gkm.monitoring-rps.*') ? 'open' : '' }}">
                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gkm.monitoring-rps.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span><i class="bi bi-check-circle"></i> Pengelolaan RPS</span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gkm.monitoring-rps.index') }}"
                                    class="nav-item {{ request()->routeIs('gkm.monitoring-rps.index') ? 'active' : '' }}">
                                    <i class="bi bi-clipboard-check"></i> Monitoring RPS
                                </a>
                                <a href="{{ route('gkm.monitoring-rps.ceklist') }}"
                                    class="nav-item {{ request()->routeIs('gkm.monitoring-rps.ceklist') || request()->routeIs('gkm.monitoring-rps.history') ? 'active' : '' }}">
                                    <i class="bi bi-send"></i> Kirim Pesan Pengingat
                                </a>
                            </div>
                        </div>

                        <!-- Pengelolaan Perkuliahan -->
                        <div class="nav-dropdown {{ request()->routeIs('gkm.monitoring-perkuliahan.*') ? 'open' : '' }}">
                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gkm.monitoring-perkuliahan.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span><i class="bi bi-journal-check"></i> Pengelolaan Perkuliahan</span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gkm.monitoring-perkuliahan.index') }}"
                                    class="nav-item {{ request()->routeIs('gkm.monitoring-perkuliahan.index') ? 'active' : '' }}">
                                    <i class="bi bi-clipboard-data"></i> Monitoring Perkuliahan
                                </a>
                                <a href="{{ route('gkm.monitoring-perkuliahan.kirim-pengingat') }}"
                                    class="nav-item {{ request()->routeIs('gkm.monitoring-perkuliahan.kirim-pengingat') || request()->routeIs('gkm.monitoring-perkuliahan.perwalian') || request()->routeIs('gkm.monitoring-perkuliahan.materi') || request()->routeIs('gkm.monitoring-perkuliahan.soal') ? 'active' : '' }}">
                                    <i class="bi bi-send"></i> Kirim Pesan Pengingat
                                </a>
                            </div>
                        </div>

                        <!-- Pengelolaan Kuesioner -->
                        <div class="nav-dropdown {{ request()->routeIs('gkm.monitoring-kuesioner.*') ? 'open' : '' }}">
                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gkm.monitoring-kuesioner.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span><i class="bi bi-list-check"></i> Pengelolaan Kuesioner</span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gkm.monitoring-kuesioner.index') }}"
                                    class="nav-item {{ request()->routeIs('gkm.monitoring-kuesioner.*') ? 'active' : '' }}">
                                    <i class="bi bi-clipboard-data"></i> Monitoring Kuesioner
                                </a>
                            </div>
                        </div>

                        <!-- Pelaporan -->
                        <div class="nav-dropdown {{ request()->routeIs('gkm.pelaporan.*') || request()->routeIs('gkm.laporan-kuesioner.*') || request()->routeIs('gkm.laporan-artefak.*') ? 'open' : '' }}">
                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gkm.pelaporan.*') || request()->routeIs('gkm.laporan-kuesioner.*') || request()->routeIs('gkm.laporan-artefak.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span><i class="bi bi-file-earmark-text"></i> Pelaporan</span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gkm.laporan-artefak.index') }}"
                                    class="nav-item {{ request()->routeIs('gkm.laporan-artefak.*') ? 'active' : '' }}">
                                    <i class="bi bi-file-earmark-check"></i> Laporan Artefak RPS & Materi
                                </a>
                                <a href="{{ route('gkm.laporan-kuesioner.index') }}"
                                    class="nav-item {{ request()->routeIs('gkm.laporan-kuesioner.*') ? 'active' : '' }}">
                                    <i class="bi bi-robot"></i> Laporan Kuesioner Bulanan
                                </a>
                            </div>
                        </div>

                        <a href="{{ route('gkm.reminder-agent.index') }}"
                            class="nav-item {{ request()->routeIs('gkm.reminder-agent.*') ? 'active' : '' }}">
                            <i class="bi bi-bell"></i> Reminder Agent
                        </a>
                        <a href="{{ route('gkm.kirim-laporan.index') }}"
                            class="nav-item {{ request()->routeIs('gkm.kirim-laporan.*') ? 'active' : '' }}">
                            <i class="bi bi-send"></i> Kirim Laporan
                        </a>
                    @elseif (auth()->user()->isGJM())
                        <a href="{{ route('gjm.dashboard') }}"
                            class="nav-item {{ request()->routeIs('gjm.dashboard') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>

                        <div class="nav-dropdown {{ request()->routeIs('gjm.buat-laporan.*') || request()->routeIs('gjm.laporan-gjm.*') ? 'open' : '' }}">
                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gjm.buat-laporan.*') || request()->routeIs('gjm.laporan-gjm.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span><i class="bi bi-file-earmark-text"></i> Pelaporan</span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gjm.buat-laporan.triwulan') }}"
                                    class="nav-item {{ request()->routeIs('gjm.buat-laporan.triwulan') ? 'active' : '' }}">
                                    <i class="bi bi-calendar3"></i> Buat Laporan Triwulan
                                </a>
                                <a href="{{ route('gjm.buat-laporan.semester') }}"
                                    class="nav-item {{ request()->routeIs('gjm.buat-laporan.semester') ? 'active' : '' }}">
                                    <i class="bi bi-file-earmark-plus"></i> Buat Laporan Semester
                                </a>
                                <a href="{{ route('gjm.laporan-gjm.index') }}"
                                    class="nav-item {{ request()->routeIs('gjm.laporan-gjm.index') ? 'active' : '' }}">
                                    <i class="bi bi-archive"></i> Arsip Laporan
                                </a>
                            </div>
                        </div>

                        <div class="nav-dropdown {{ request()->routeIs('gjm.buat-ppt.*') || request()->routeIs('gjm.presentasi.*') ? 'open' : '' }}">
                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gjm.buat-ppt.*') || request()->routeIs('gjm.presentasi.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span><i class="bi bi-file-earmark-slides"></i> Presentasi</span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gjm.buat-ppt.index') }}"
                                    class="nav-item {{ request()->routeIs('gjm.buat-ppt.*') ? 'active' : '' }}">
                                    <i class="bi bi-file-earmark-plus"></i> Buat PPT
                                </a>
                                <a href="{{ route('gjm.buat-ppt.archive') }}"
                                    class="nav-item {{ request()->routeIs('gjm.buat-ppt.archive') ? 'active' : '' }}">
                                    <i class="bi bi-archive"></i> Arsip PPT
                                </a>
                            </div>
                        </div>

                        <a href="{{ route('gjm.kirim-laporan.index') }}" 
                            class="nav-item {{ request()->routeIs('gjm.kirim-laporan.*') ? 'active' : '' }}">
                            <i class="bi bi-send"></i> Kirim Laporan
                        </a>
                    @endif
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <div class="topbar">
                    <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
                    <div class="topbar-right">
                        <div class="topbar-user" onclick="toggleTopbarDropdown(this)">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=1e3c72&color=fff"
                                alt="User">
                            <div class="user-name-wrapper">
                                <div>
                                    <small class="text-muted">{{ auth()->user()->name }}</small>
                                </div>
                                <i class="bi bi-chevron-down dropdown-chevron-top"></i>
                            </div>
                            
                            <div class="topbar-dropdown-menu">
                                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                                    @csrf
                                    <button type="submit" onclick="event.stopPropagation();">
                                        <i class="bi bi-box-arrow-right"></i>
                                        <span>Logout</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                            <strong>Error!</strong>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        @else
            <div class="main-content" style="margin-left: 0; width: 100%;">
                <div class="content" style="display: flex; align-items: center; justify-content: center;">
                    @yield('content')
                </div>
            </div>
        @endauth
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script>
        function toggleDropdown(element) {
            const dropdown = element.closest('.nav-dropdown');
            dropdown.classList.toggle('open');
        }

        function toggleUserDropdown(element) {
            // Close if clicking outside
            const wasOpen = element.classList.contains('open');
            
            // Close all user dropdowns
            document.querySelectorAll('.user-info.open').forEach(el => {
                el.classList.remove('open');
            });
            
            // Toggle current
            if (!wasOpen) {
                element.classList.add('open');
            }
        }

        function toggleTopbarDropdown(element) {
            // Close if clicking outside
            const wasOpen = element.classList.contains('open');
            
            // Close all topbar dropdowns
            document.querySelectorAll('.topbar-user.open').forEach(el => {
                el.classList.remove('open');
            });
            
            // Toggle current
            if (!wasOpen) {
                element.classList.add('open');
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userInfo = document.querySelector('.user-info');
            if (userInfo && !userInfo.contains(event.target)) {
                userInfo.classList.remove('open');
            }
            
            const topbarUser = document.querySelector('.topbar-user');
            if (topbarUser && !topbarUser.contains(event.target)) {
                topbarUser.classList.remove('open');
            }
        });

        // Auto-open dropdown if submenu is active on page load
        document.addEventListener('DOMContentLoaded', function() {
            const activeDropdown = document.querySelector('.nav-dropdown.open');
            if (activeDropdown) {
                // Already opened by server-side check
            }
        });
    </script>
    @yield('scripts')
</body>

</html>
