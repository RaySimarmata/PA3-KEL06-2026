<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem GJK & GKM')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #1e3c72;
            --secondary-color: #2a5298;
            --sidebar-width: 250px;
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
            overflow-y: auto;
            z-index: 1000;
            color: white;
            padding-bottom: 80px;
        }

        .sidebar .logo {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar .logo h5 {
            margin: 0;
            font-weight: bold;
            font-size: 16px;
        }

        .sidebar .user-info {
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar .user-info img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-bottom: 10px;
        }

        .sidebar .user-info p {
            margin: 5px 0;
            font-size: 13px;
        }

        .sidebar .nav-menu {
            padding: 20px 0;
        }

        .sidebar .nav-item {
            padding: 12px 20px;
            border-left: 4px solid transparent;
            color: rgba(255, 255, 255, 0.7);
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            font-size: 14px;
        }

        .sidebar .nav-item:hover,
        .sidebar .nav-item.active {
            background: rgba(255, 255, 255, 0.1);
            border-left-color: white;
            color: white;
        }

        .sidebar .nav-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
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
        }

        .nav-dropdown.open .dropdown-icon {
            transform: rotate(180deg);
        }

        .dropdown-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background-color: rgba(0, 0, 0, 0.2);
        }

        .nav-dropdown.open .dropdown-submenu {
            max-height: 500px;
        }

        .dropdown-submenu .nav-item {
            padding-left: 50px;
            font-size: 13px;
        }

        .dropdown-submenu .nav-item i {
            font-size: 14px;
        }

        .sidebar .logout-btn {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            padding: 10px;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            width: calc(100% - 40px);
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
        }

        .topbar-user img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
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
                        <a href="{{ route('gkm.monitoring-rps.index') }}"
                            class="nav-item {{ request()->routeIs('gkm.monitoring-rps.*') ? 'active' : '' }}">
                            <i class="bi bi-check-circle"></i> Monitoring RPS
                        </a>
                        <a href="{{ route('gkm.monitoring-perkuliahan.index') }}"
                            class="nav-item {{ request()->routeIs('gkm.monitoring-perkuliahan.*') && !request()->routeIs('gkm.pelaporan.*') ? 'active' : '' }}">
                            <i class="bi bi-journal-check"></i> Monitoring Perkuliahan
                        </a>
                        <a href="{{ route('gkm.monitoring-kuesioner.index') }}"
                            class="nav-item {{ request()->routeIs('gkm.monitoring-kuesioner.*') ? 'active' : '' }}">
                            <i class="bi bi-clipboard-data"></i> Monitoring Kuesioner
                        </a>
                        <div
                            class="nav-dropdown {{ request()->routeIs('gkm.pelaporan.*') || request()->routeIs('gkm.laporan-kuesioner.*') ? 'open' : '' }}">
                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gkm.pelaporan.*') || request()->routeIs('gkm.laporan-kuesioner.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span><i class="bi bi-file-earmark-text"></i> Pelaporan</span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gkm.pelaporan.artefak') }}"
                                    class="nav-item {{ request()->routeIs('gkm.pelaporan.artefak') ? 'active' : '' }}">
                                    <i class="bi bi-file-earmark-check"></i> Laporan Artefak Perkuliahan
                                </a>
                                <a href="{{ route('gkm.laporan-kuesioner.index') }}"
                                    class="nav-item {{ request()->routeIs('gkm.laporan-kuesioner.*') ? 'active' : '' }}">
                                    <i class="bi bi-robot"></i> Laporan Kuesioner (AI)
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

                        <div class="nav-dropdown {{ request()->routeIs('gjm.buat-laporan.*') || request()->routeIs('gjm.laporan.*') ? 'open' : '' }}">
                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gjm.buat-laporan.*') || request()->routeIs('gjm.laporan.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span><i class="bi bi-file-earmark-text"></i> Pelaporan</span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gjm.buat-laporan.index') }}"
                                    class="nav-item {{ request()->routeIs('gjm.buat-laporan.*') ? 'active' : '' }}">
                                    <i class="bi bi-file-earmark-plus"></i> Buat Laporan
                                </a>
                                <a href="{{ route('gjm.laporan.index') }}"
                                    class="nav-item {{ request()->routeIs('gjm.laporan.index') ? 'active' : '' }}">
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

                        <a href="{{ route('gjm.dashboard') }}" class="nav-item">
                            <i class="bi bi-send"></i> Kirim Laporan <small class="text-muted"></small>
                        </a>
                    @endif
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <div class="topbar">
                    <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
                    <div class="topbar-right">
                        <div class="topbar-user">
                            <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=1e3c72&color=fff"
                                alt="User">
                            <div>
                                <small class="text-muted">{{ auth()->user()->name }}</small>
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
