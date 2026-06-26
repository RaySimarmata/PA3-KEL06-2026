<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistem GJK & GKM')</title>
    <link rel="icon" href="{{ asset('images/logo-itdel.jpg') }}" type="image/jpeg">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- GKM Global Styles -->
    @if (auth()->check() && auth()->user()->isGKM())
        <link rel="stylesheet" href="{{ asset('css/gkm-style.css') }}">
    @endif

    <!-- GJM Global Styles -->
    @if (auth()->check() && auth()->user()->isGJM())
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
            --primary-light: #2a5298;
            --primary-dark: #0f2b4f;
            --accent-color: #4a90e2;
            --sidebar-width: clamp(12.5rem, 16vw, 14.5rem);
            --sidebar-bg-start: #0f2b4f;
            --sidebar-bg-end: #1e3c72;
        }

        html,
        body {
            height: 100%;
            width: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: 'Segoe UI', 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
        }

        /* ==================== SIDEBAR STYLES ==================== */
        .wrapper {
            display: flex;
            width: 100%;
            min-height: 100vh;
            position: relative;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--sidebar-bg-start) 0%, var(--sidebar-bg-end) 100%);
            position: fixed;
            height: 100vh;
            z-index: 1000;
            color: white;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        /* Logo Section */
        .sidebar .logo {
            padding: 1.5rem 1rem;
            text-align: center;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.02) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
        }

        .sidebar .logo::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 10%;
            width: 80%;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        }

        .sidebar .logo h5 {
            margin: 0;
            font-weight: 800;
            font-size: 1.4rem;
            background: linear-gradient(135deg, #fff 0%, #e0e8f5 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: 1px;
        }

        .sidebar .logo small {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.7rem;
            display: block;
            margin-top: 6px;
            font-weight: 400;
        }

        /* User Info Section */
        .sidebar .user-info {
            padding: 1.25rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0) 100%);
        }

        .avatar-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, #fff 0%, #e0e8f5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .avatar-icon:hover {
            transform: scale(1.05);
        }

        .avatar-icon i {
            font-size: 2.8rem;
            color: var(--primary-color);
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .sidebar .user-info p {
            margin: 8px 0 0 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: white;
        }

        .sidebar .user-info small {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.7rem;
            display: inline-block;
            margin-top: 5px;
            padding: 3px 10px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            font-weight: 500;
        }

        /* Navigation Menu */
        .sidebar .nav-menu {
            padding: 1rem 0.75rem;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar .nav-menu::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar .nav-menu::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .sidebar .nav-menu::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        .sidebar .nav-item {
            padding: 0.7rem 0.9rem;
            margin-bottom: 0.25rem;
            border-radius: 12px;
            color: rgba(255, 255, 255, 0.85);
            cursor: pointer;
            transition: all 0.25s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .sidebar .nav-item>span {
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }

        .sidebar .nav-item:hover {
            background: rgba(255, 255, 255, 0.12);
            color: white;
            transform: translateX(4px);
        }

        .sidebar .nav-item.active {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-item i {
            font-size: 1.1rem;
            width: 1.5rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .dropdown-icon {
            font-size: 0.7rem;
            transition: transform 0.25s ease;
            opacity: 0.7;
        }

        .nav-dropdown.open .dropdown-icon {
            transform: rotate(180deg);
            opacity: 1;
        }

        .dropdown-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            margin-left: 1.8rem;
            border-left: 2px solid rgba(255, 255, 255, 0.15);
        }

        .nav-dropdown.open .dropdown-submenu {
            max-height: 500px;
        }

        /* Perbaikan alignment submenu */
        .dropdown-submenu .nav-item {
            padding: 0.6rem 0.8rem;
            font-size: 0.8rem;
            margin-bottom: 0.15rem;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }

        .dropdown-submenu .nav-item i,
        .dropdown-submenu .nav-item .menu-icon {
            font-size: 0.9rem;
            width: 1.3rem;
            text-align: center;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .dropdown-submenu .nav-item .menu-text {
            flex: 1;
            text-align: left;
        }

        .dropdown-submenu .nav-item:hover {
            transform: translateX(6px);
        }

        /* ==================== MAIN CONTENT ==================== */
        .main-content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f5 100%);
        }

        /* Topbar */
        .topbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 0.75rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 0;
        }

        .sidebar-toggle {
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            width: 38px;
            height: 38px;
            display: none;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .sidebar-toggle:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(30, 60, 114, 0.3);
        }

        .topbar-title {
            font-size: 1.3rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Topbar User Dropdown */
        .topbar-user {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 40px;
            transition: all 0.2s ease;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .topbar-user:hover {
            background: #f8f9fa;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .topbar-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            box-shadow: none;
        }

        .topbar-avatar i {
            font-size: 1.4rem;
            color: white;
        }

        .topbar-user .user-name-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .topbar-user small {
            font-weight: 600;
            color: var(--primary-color);
        }

        .dropdown-chevron-top {
            font-size: 10px;
            color: #888;
            transition: transform 0.2s ease;
        }

        .topbar-user.open .dropdown-chevron-top {
            transform: rotate(180deg);
        }

        .topbar-dropdown-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.25s ease;
            overflow: hidden;
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
            padding: 12px 18px;
            color: #333;
            text-decoration: none;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-size: 0.85rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .topbar-dropdown-menu a:hover,
        .topbar-dropdown-menu button:hover {
            background: linear-gradient(135deg, #f0f4fa, #e8edf5);
            color: var(--primary-color);
        }

        .topbar-dropdown-menu i {
            font-size: 1.1rem;
            color: var(--primary-light);
        }

        .topbar-dropdown-divider {
            height: 1px;
            background: #e9ecef;
            margin: 0;
        }

        .content {
            flex: 1;
            padding: 1.25rem;
            margin: 0;
            overflow-x: hidden;
        }

        .layout-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 900;
        }

        .wrapper.sidebar-open .layout-overlay {
            opacity: 1;
            visibility: visible;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: none;
            }

            .wrapper.sidebar-open .sidebar {
                transform: translateX(0);
                box-shadow: 4px 0 25px rgba(0, 0, 0, 0.25);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .sidebar-toggle {
                display: inline-flex;
            }

            .topbar {
                padding: 0.75rem 1rem;
            }

            .topbar-user .user-name-wrapper {
                display: none;
            }

            .topbar-user {
                padding: 6px;
            }
        }

        @media (max-width: 480px) {
            .content {
                padding: 0.75rem;
            }

            .topbar-title {
                font-size: 1rem;
            }
        }
    </style>
    @yield('styles')
</head>

<body>
    <div class="wrapper">
        <div class="layout-overlay" onclick="closeSidebar()"></div>
        @auth
            <!-- Sidebar -->
            <div class="sidebar">
                <div class="logo">
                    <h5>GJM & GKM</h5>
                    <small>Quality Assurance System</small>
                </div>

                <div class="user-info">
                    <div class="avatar-icon">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <p><strong>{{ auth()->user()->name }}</strong></p>
                    <small>{{ auth()->user()->isGKM() ? 'GKM Administrator' : 'GJM Administrator' }}</small>
                </div>

                <div class="nav-menu">
                    @if (auth()->user()->isGKM())
                        <a href="{{ route('gkm.dashboard') }}"
                            class="nav-item {{ request()->routeIs('gkm.dashboard') ? 'active' : '' }}">
                            <span><i class="bi bi-speedometer2"></i> Dashboard</span>
                        </a>
                        <a href="{{ route('gkm.data-master.index') }}"
                            class="nav-item {{ request()->routeIs('gkm.data-master.*') ? 'active' : '' }}">
                            <span><i class="bi bi-database"></i> Data Master</span>
                        </a>

                        <!-- Pengelolaan RPS -->
                        <div class="nav-dropdown {{ request()->routeIs('gkm.monitoring-rps.*') ? 'open' : '' }}">
                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gkm.monitoring-rps.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span>
                                    <img src="{{ asset('images/pengelolaan1.png') }}" alt="Rps" width="20"
                                        height="20" style="margin-right: 8px; filter: brightness(0) invert(1);">
                                    Pengelolaan RPS
                                </span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gkm.monitoring-rps.index') }}"
                                    class="nav-item {{ request()->routeIs('gkm.monitoring-rps.index') ? 'active' : '' }}">
                                    <i class="bi bi-eye menu-icon"></i>
                                    <span class="menu-text">Monitoring RPS</span>
                                </a>
                                <a href="{{ route('gkm.monitoring-rps.history') }}"
                                    class="nav-item {{ request()->routeIs('gkm.monitoring-rps.ceklist') || request()->routeIs('gkm.monitoring-rps.history') ? 'active' : '' }}">
                                    <i class="bi bi-clock-history menu-icon"></i>
                                    <span class="menu-text">History Reminder RPS</span>
                                </a>
                            </div>
                        </div>

                        <!-- Pengelolaan Perkuliahan -->
                        <div class="nav-dropdown {{ request()->routeIs('gkm.monitoring-perkuliahan.*') ? 'open' : '' }}">
                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gkm.monitoring-perkuliahan.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span>
                                    <img src="{{ asset('images/pengelolaan1.png') }}" alt="Perkuliahan" width="20"
                                        height="20" style="margin-right: 8px; filter: brightness(0) invert(1);">
                                    Pengelolaan Perkuliahan
                                </span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gkm.monitoring-perkuliahan.index') }}"
                                    class="nav-item {{ request()->routeIs('gkm.monitoring-perkuliahan.index') ? 'active' : '' }}">
                                    <i class="bi bi-eye menu-icon"></i>
                                    <span class="menu-text">Monitoring Perkuliahan</span>
                                </a>
                                <a href="{{ route('gkm.monitoring-perkuliahan.history') }}"
                                    class="nav-item {{ request()->routeIs('gkm.monitoring-perkuliahan.history') ? 'active' : '' }}">
                                    <i class="bi bi-send menu-icon"></i>
                                    <span class="menu-text">History Reminder Upload Materi</span>
                                </a>
                            </div>
                        </div>

                        <a href="{{ route('gkm.monitoring-kuesioner.index') }}"
                            class="nav-item {{ request()->routeIs('gkm.monitoring-kuesioner.*') ? 'active' : '' }}">
                            <span>
                                <img src="{{ asset('images/pengelolaan1.png') }}" alt="Kuesioner" width="20"
                                    height="20" style="margin-right: 8px; filter: brightness(0) invert(1);">
                                Pengelolaan Kuesioner
                            </span>
                        </a>

                        <!-- Pelaporan -->
                        <div
                            class="nav-dropdown {{ request()->routeIs('gkm.pelaporan.*') || request()->routeIs('gkm.laporan-kuesioner.*') || request()->routeIs('gkm.laporan-artefak.*') ? 'open' : '' }}">
                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gkm.pelaporan.*') || request()->routeIs('gkm.laporan-kuesioner.*') || request()->routeIs('gkm.laporan-artefak.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span><i class="bi bi-file-earmark-text"></i> Pelaporan</span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gkm.laporan-artefak.index') }}"
                                    class="nav-item {{ request()->routeIs('gkm.laporan-artefak.*') ? 'active' : '' }}">
                                    <i class="bi bi-file-earmark-text menu-icon"></i>
                                    <span class="menu-text">Laporan Bulanan</span>
                                </a>
                                <a href="{{ route('gkm.laporan-kuesioner.index') }}"
                                    class="nav-item {{ request()->routeIs('gkm.laporan-kuesioner.*') ? 'active' : '' }}">
                                    <i class="bi bi-file-earmark-text menu-icon"></i>
                                    <span class="menu-text">Laporan Kuesioner</span>
                                </a>
                            </div>
                        </div>

                        <!-- Reminder Agent -->
                        <div class="nav-dropdown {{ request()->routeIs('gkm.reminder-agent.*') ? 'open' : '' }}">
                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gkm.reminder-agent.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span><i class="bi bi-bell"></i> Reminder Agent</span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gkm.reminder-agent.index') }}"
                                    class="nav-item {{ request()->routeIs('gkm.reminder-agent.index') ? 'active' : '' }}">
                                    <i class="bi bi-bell"></i>
                                    <span class="menu-text">Jadwal Reminder</span>
                                </a>
                                <a href="{{ route('gkm.reminder-agent.log') }}"
                                    class="nav-item {{ request()->routeIs('gkm.reminder-agent.log') ? 'active' : '' }}">
                                    <i class="bi bi-file-earmark-text menu-icon"></i>
                                    <span class="menu-text">Log Reminder</span>
                                </a>
                            </div>
                        </div>

                        <!-- Kirim Laporan -->
                        <div class="nav-dropdown {{ request()->routeIs('gkm.kirim-laporan.*') ? 'open' : '' }}">
                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gkm.kirim-laporan.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span><i class="bi bi-send"></i> Kirim Laporan</span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gkm.kirim-laporan.index') }}"
                                    class="nav-item {{ request()->routeIs('gkm.kirim-laporan.index') ? 'active' : '' }}">
                                    <i class="bi bi-send menu-icon"></i>
                                    <span class="menu-text">Kirim Laporan</span>
                                </a>
                                <a href="{{ route('gkm.kirim-laporan.history') }}"
                                    class="nav-item {{ request()->routeIs('gkm.kirim-laporan.history') ? 'active' : '' }}">
                                    <i class="bi bi-clock-history menu-icon"></i>
                                    <span class="menu-text">History Pengiriman</span>
                                </a>
                            </div>
                        </div>
                        <!-- Reminder Agent -->
                        <!-- <div class="nav-dropdown {{ request()->routeIs('gkm.reminder-agent.*') ? 'open' : '' }}">
                                <a href="javascript:void(0)"
                                    class="nav-item {{ request()->routeIs('gkm.reminder-agent.*') ? 'active' : '' }}"
                                    onclick="toggleDropdown(this)">
                                    <span><i class="bi bi-bell"></i> Reminder Agent</span>
                                    <i class="bi bi-chevron-down dropdown-icon"></i>
                                </a>
                                <div class="dropdown-submenu">
                                    <a href="{{ route('gkm.reminder-agent.index') }}"
                                        class="nav-item {{ request()->routeIs('gkm.reminder-agent.index') ? 'active' : '' }}">
                                        <i class="bi bi-file-earmark-text menu-icon"></i>
                                        <span class="menu-text">Jadwal Reminder</span>
                                    </a>
                                    <a href="{{ route('gkm.reminder-agent.log') }}"
                                        class="nav-item {{ request()->routeIs('gkm.reminder-agent.log') ? 'active' : '' }}">
                                        <i class="bi bi-file-earmark-text menu-icon"></i>
                                        <span class="menu-text">Log Reminder</span>
                                    </a>
                                </div>
                            </div> -->
                    @elseif (auth()->user()->isGJM())
                        <a href="{{ route('gjm.dashboard') }}"
                            class="nav-item {{ request()->routeIs('gjm.dashboard') ? 'active' : '' }}">
                            <span><i class="bi bi-speedometer2"></i> Dashboard</span>
                        </a>

                        <div class="nav-dropdown {{ request()->routeIs('gjm.buat-laporan.*') ? 'open' : '' }}">
                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gjm.buat-laporan.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span><img src="{{ asset('images/pengelolaan1.png') }}" alt="Kuesioner" width="23"
                                        height="23" style="margin-right:15px;" filter: brightness(0) invert(1);>
                                    Pelaporan</span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gjm.buat-laporan.triwulan.index') }}"
                                    class="nav-item {{ request()->routeIs('gjm.buat-laporan.triwulan.*') ? 'active' : '' }}">
                                    <span><img src="{{ asset('images/pengelolaan1.png') }}" alt="Kuesioner"
                                            width="23" height="23" style="margin-right:15px;" filter:
                                            brightness(0) invert(1);></i> Buat Laporan Triwulan</span>
                                </a>
                                <a href="{{ route('gjm.buat-laporan.semester.index') }}"
                                    class="nav-item {{ request()->routeIs('gjm.buat-laporan.semester.*') ? 'active' : '' }}">
                                    <span><img src="{{ asset('images/pengelolaan1.png') }}" alt="Kuesioner"
                                            width="23" height="23" style="margin-right:15px;" filter:
                                            brightness(0) invert(1);></i> Buat Laporan Semester</span>
                                </a>
                                <a href="{{ route('gjm.buat-laporan.vmts.index') }}"
                                    class="nav-item {{ request()->routeIs('gjm.buat-laporan.vmts.*') ? 'active' : '' }}">
                                    <span><img src="{{ asset('images/pengelolaan1.png') }}" alt="Kuesioner"
                                            width="23" height="23" style="margin-right:15px;" filter:
                                            brightness(0) invert(1);></i> Buat Laporan VMTS</span>
                                </a>
                            </div>
                        </div>

                        <div
                            class="nav-dropdown {{ request()->routeIs('gjm.buat-ppt.*') || request()->routeIs('gjm.presentasi.*') ? 'open' : '' }}">
                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gjm.buat-ppt.*') || request()->routeIs('gjm.presentasi.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span><i class="bi bi-file-earmark-slides"></i> Presentasi</span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gjm.buat-ppt.index') }}"
                                    class="nav-item {{ request()->routeIs('gjm.buat-ppt.*') ? 'active' : '' }}">
                                    <i class="bi bi-file-earmark-plus menu-icon"></i>
                                    <span class="menu-text">Buat PPT</span>
                                </a>
                                <a href="{{ route('gjm.buat-ppt.archive') }}"
                                    class="nav-item {{ request()->routeIs('gjm.buat-ppt.archive') ? 'active' : '' }}">
                                    <i class="bi bi-archive menu-icon"></i>
                                    <span class="menu-text">Arsip PPT</span>
                                </a>
                            </div>
                        </div>

                        <a href="{{ route('gjm.kirim-laporan.index') }}"
                            class="nav-item {{ request()->routeIs('gjm.kirim-laporan.*') ? 'active' : '' }}">
                            <span><i class="bi bi-send"></i> Kirim Laporan</span>
                        </a>
                    @endif
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <div class="topbar">
                    <div class="topbar-left">
                        <button type="button" class="sidebar-toggle" onclick="toggleSidebar()"
                            aria-label="Toggle sidebar">
                            <i class="bi bi-list"></i>
                        </button>
                        <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
                    </div>
                    <div class="topbar-right">
                        <div class="topbar-user" onclick="toggleTopbarDropdown(this)">
                            <div class="topbar-avatar">
                                <i class="bi bi-person-circle"></i>
                            </div>
                            <div class="user-name-wrapper">
                                <small>{{ auth()->user()->name }}</small>
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
                    @if (isset($errors) && $errors->any())
                        <div class="alert-app danger mb-3">
                            <i class="bi bi-exclamation-triangle-fill alert-app-icon"></i>
                            <div class="alert-app-body">
                                <div class="alert-app-title">Error!</div>
                                <ul class="mb-0" style="font-size:0.875rem; padding-left:1.25rem; margin-top:0.25rem;">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
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

    {{-- ===== GLOBAL ALERT & CONFIRM STYLES ===== --}}
    <style>
        /* Universal alert style - works for all roles */
        .alert-app {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.9rem 1.1rem;
            border-radius: 10px;
            border: none;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }

        .alert-app .alert-app-icon {
            font-size: 1.1rem;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .alert-app .alert-app-body {
            flex: 1;
        }

        .alert-app .alert-app-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .alert-app .alert-app-close {
            background: none;
            border: none;
            cursor: pointer;
            opacity: 0.5;
            font-size: 1rem;
            padding: 0;
            line-height: 1;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .alert-app .alert-app-close:hover {
            opacity: 1;
        }

        .alert-app.success {
            background-color: #d1f0e0;
            color: #0f5132;
            border-left: 4px solid #198754;
        }

        .alert-app.danger {
            background-color: #fde8ea;
            color: #842029;
            border-left: 4px solid #dc3545;
        }

        .alert-app.warning {
            background-color: #fff4cc;
            color: #664d03;
            border-left: 4px solid #ffc107;
        }

        .alert-app.info {
            background-color: #dceefb;
            color: #084298;
            border-left: 4px solid #0d6efd;
        }

        /* Keep alert-gkm and alert-gjm for backward compat but align them */
        .alert-gkm,
        .alert-gjm {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.9rem 1.1rem;
            border-radius: 10px;
            border: none;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .alert-gkm.success,
        .alert-gjm.success {
            background-color: #d1f0e0;
            color: #0f5132;
            border-left: 4px solid #198754;
        }

        .alert-gkm.danger,
        .alert-gjm.danger {
            background-color: #fde8ea;
            color: #842029;
            border-left: 4px solid #dc3545;
        }

        .alert-gkm.warning,
        .alert-gjm.warning {
            background-color: #fff4cc;
            color: #664d03;
            border-left: 4px solid #ffc107;
        }

        .alert-gkm.info,
        .alert-gjm.info {
            background-color: #dceefb;
            color: #084298;
            border-left: 4px solid #0d6efd;
        }

        /* Global Confirm Modal */
        #globalConfirmModal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 99999;
            align-items: center;
            justify-content: center;
        }

        #globalConfirmModal.active {
            display: flex;
        }

        #globalConfirmBackdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(2px);
        }

        #globalConfirmDialog {
            position: relative;
            background: #fff;
            border-radius: 1rem;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            animation: confirmFadeIn .18s ease;
        }

        @keyframes confirmFadeIn {
            from {
                opacity: 0;
                transform: scale(.95) translateY(-6px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        #globalConfirmDialog .confirm-icon {
            text-align: center;
            margin-bottom: 1rem;
        }

        #globalConfirmDialog .confirm-icon span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #fde8ea;
            font-size: 1.5rem;
        }

        #globalConfirmDialog h6 {
            text-align: center;
            font-weight: 700;
            font-size: 1rem;
            color: #1a1a2e;
            margin-bottom: 0.4rem;
        }

        #globalConfirmDialog p {
            text-align: center;
            color: #6c757d;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
        }

        #globalConfirmDialog .confirm-buttons {
            display: flex;
            gap: 0.75rem;
        }

        #globalConfirmDialog .btn-confirm-cancel {
            flex: 1;
            padding: 0.55rem;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.9rem;
        }

        #globalConfirmDialog .btn-confirm-ok {
            flex: 1;
            padding: 0.55rem;
            border: none;
            border-radius: 0.5rem;
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.9rem;
        }

        #globalConfirmDialog .btn-confirm-ok:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
    </style>

    {{-- Global Confirm Modal --}}
    <div id="globalConfirmModal" role="dialog" aria-modal="true">
        <div id="globalConfirmBackdrop"></div>
        <div id="globalConfirmDialog">
            <div class="confirm-icon">
                <span><i class="bi bi-trash3-fill" style="color:#dc3545;"></i></span>
            </div>
            <h6 id="globalConfirmTitle">Konfirmasi</h6>
            <p id="globalConfirmMessage">Apakah Anda yakin?</p>
            <div class="confirm-buttons">
                <button class="btn-confirm-cancel" id="globalConfirmCancel">
                    <i class="bi bi-x-lg"></i> Batal
                </button>
                <button class="btn-confirm-ok" id="globalConfirmOk">
                    <i class="bi bi-check-lg"></i> Ya, Lanjutkan
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script>
        function toggleDropdown(element) {
            const dropdown = element.closest('.nav-dropdown');
            if (!dropdown) return;

            const isOpen = dropdown.classList.contains('open');
            document.querySelectorAll('.sidebar .nav-dropdown.open').forEach(el => {
                if (el !== dropdown) {
                    el.classList.remove('open');
                }
            });

            dropdown.classList.toggle('open', !isOpen);
        }

        function toggleTopbarDropdown(element) {
            const wasOpen = element.classList.contains('open');
            document.querySelectorAll('.topbar-user.open').forEach(el => {
                el.classList.remove('open');
            });
            if (!wasOpen) {
                element.classList.add('open');
            }
        }

        function toggleSidebar() {
            const wrapper = document.querySelector('.wrapper');
            if (!wrapper) return;
            wrapper.classList.toggle('sidebar-open');
        }

        function closeSidebar() {
            const wrapper = document.querySelector('.wrapper');
            if (!wrapper) return;
            wrapper.classList.remove('sidebar-open');
        }

        document.addEventListener('click', function(event) {
            const topbarUser = document.querySelector('.topbar-user');
            if (topbarUser && !topbarUser.contains(event.target)) {
                topbarUser.classList.remove('open');
            }

            if (window.innerWidth <= 768) {
                const sidebar = document.querySelector('.sidebar');
                const toggleBtn = document.querySelector('.sidebar-toggle');
                if (sidebar && !sidebar.contains(event.target) && toggleBtn && !toggleBtn.contains(event.target)) {
                    closeSidebar();
                }
            }
        });

        document.querySelectorAll('.sidebar .nav-item').forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            });
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        });
    </script>
    @yield('scripts')
    @stack('scripts')

    <script>
        // ===== GLOBAL CONFIRM MODAL =====
        (function() {
            let _pendingForm = null;
            let _pendingCallback = null;

            const modal = document.getElementById('globalConfirmModal');
            const title = document.getElementById('globalConfirmTitle');
            const message = document.getElementById('globalConfirmMessage');
            const btnOk = document.getElementById('globalConfirmOk');
            const btnCancel = document.getElementById('globalConfirmCancel');
            const backdrop = document.getElementById('globalConfirmBackdrop');

            function show(opts) {
                title.textContent = opts.title || 'Konfirmasi';
                message.textContent = opts.message || 'Apakah Anda yakin?';
                // swap icon based on type
                const iconEl = modal.querySelector('.confirm-icon span');
                if (opts.type === 'delete') {
                    iconEl.innerHTML = '<i class="bi bi-trash3-fill" style="color:#dc3545;"></i>';
                    iconEl.style.background = '#fde8ea';
                    btnOk.style.background = 'linear-gradient(135deg,#dc3545,#c82333)';
                } else if (opts.type === 'process') {
                    iconEl.innerHTML = '<i class="bi bi-cpu-fill" style="color:#198754;"></i>';
                    iconEl.style.background = '#d1f0e0';
                    btnOk.style.background = 'linear-gradient(135deg,#198754,#20c997)';
                } else {
                    iconEl.innerHTML = '<i class="bi bi-question-circle-fill" style="color:#0d6efd;"></i>';
                    iconEl.style.background = '#dceefb';
                    btnOk.style.background = 'linear-gradient(135deg,#0d6efd,#0b5ed7)';
                }
                btnOk.textContent = opts.okText || 'Ya, Lanjutkan';
                btnOk.disabled = false;
                modal.classList.add('active');
            }

            function hide() {
                modal.classList.remove('active');
                _pendingForm = null;
                _pendingCallback = null;
            }

            btnOk.addEventListener('click', function() {
                btnOk.disabled = true;
                if (_pendingCallback) _pendingCallback();
                else if (_pendingForm) _pendingForm.submit();
                hide();
            });
            btnCancel.addEventListener('click', hide);
            backdrop.addEventListener('click', hide);
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') hide();
            });

            // Public API
            window.AppConfirm = {
                delete: function(form, msg) {
                    _pendingForm = form;
                    show({
                        type: 'delete',
                        title: 'Hapus Data?',
                        message: msg || 'Data yang dihapus tidak dapat dikembalikan.',
                        okText: 'Ya, Hapus'
                    });
                },
                ask: function(form, title, msg) {
                    _pendingForm = form;
                    show({
                        type: 'info',
                        title: title,
                        message: msg,
                        okText: 'Ya, Lanjutkan'
                    });
                },
                process: function(form, title, msg) {
                    _pendingForm = form;
                    show({
                        type: 'process',
                        title: title,
                        message: msg,
                        okText: 'Ya, Proses'
                    });
                },
                custom: function(opts, callback) {
                    _pendingCallback = callback;
                    show(opts);
                }
            };

            // Auto-dismiss .alert-app after 6s
            document.querySelectorAll('.alert-app[data-auto-dismiss]').forEach(function(el) {
                setTimeout(function() {
                    el.style.transition = 'opacity .4s';
                    el.style.opacity = '0';
                    setTimeout(function() {
                        el.remove();
                    }, 400);
                }, 6000);
            });
        })();
    </script>

</body>

</html>
