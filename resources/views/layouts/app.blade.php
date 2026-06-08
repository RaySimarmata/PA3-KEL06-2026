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

        .dropdown-submenu .nav-item {
            padding: 0.6rem 0.8rem;
            font-size: 0.8rem;
            margin-bottom: 0.15rem;
        }

        .dropdown-submenu .nav-item i {
            font-size: 0.9rem;
            width: 1.3rem;
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
                                <span><i class="bi bi-check-circle"></i> Pengelolaan RPS</span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>

                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gkm.monitoring-rps.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span><img src="{{ asset('images/pengelolaan1.png') }}"
         alt="Rps"
         width="23"
         height="23"
         style="margin-right:2px;"
         filter: brightness(0) invert(1);></i> Pengelolaan RPS</span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gkm.monitoring-rps.index') }}"
                                    class="nav-item {{ request()->routeIs('gkm.monitoring-rps.index') ? 'active' : '' }}">
                                    <i class="bi bi-eye"></i>Monitoring RPS
                                </a>
                                <a href="{{ route('gkm.monitoring-rps.history') }}"
                                    class="nav-item {{ request()->routeIs('gkm.monitoring-rps.ceklist') || request()->routeIs('gkm.monitoring-rps.history') ? 'active' : '' }}">
                                    <i class="bi bi-clock-history"></i> History Reminder RPS
                                </a>
                            </div>
                        </div>

                        <!-- Pengelolaan Perkuliahan -->
                        <div class="nav-dropdown {{ request()->routeIs('gkm.monitoring-perkuliahan.*') ? 'open' : '' }}">
                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gkm.monitoring-perkuliahan.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span><img src="{{ asset('images/pengelolaan1.png') }}"
         alt="Kuesioner"
         width="23"
         height="23"
         style="margin-right:2px;"
         filter: brightness(0) invert(1);></i> Pengelolaan Perkuliahan</span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gkm.monitoring-perkuliahan.index') }}"
                                    class="nav-item {{ request()->routeIs('gkm.monitoring-perkuliahan.index') ? 'active' : '' }}">
                                    <i class="bi bi-eye"></i> Monitoring Perkuliahan
                                </a>
                                <a href="{{ route('gkm.monitoring-perkuliahan.kirim-pengingat') }}"
                                    class="nav-item {{ request()->routeIs('gkm.monitoring-perkuliahan.kirim-pengingat') ? 'active' : '' }}">
                                    <span><i class="bi bi-send"></i> Kirim Pesan Pengingat</span>
                                </a>
                            </div>
                        </div>

                        {{-- <!-- Pengelolaan Kuesioner -->
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
                                    <span><i class="bi bi-clipboard-data"></i> Monitoring Kuesioner</span>
                                </a>
                            </div>
                        </div> --}}
                        <a href="{{ route('gkm.monitoring-kuesioner.index') }}"
                            class="nav-item {{ request()->routeIs('gkm.monitoring-kuesioner.*') ? 'active' : '' }}">
                            <i class="bi bi-eye"></i> Pengelolaan Kuesioner
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
                                    <i class="bi bi-file-earmark-text"></i> Laporan Bulanan
                                </a>
                                <a href="{{ route('gkm.laporan-kuesioner.index') }}"
                                    class="nav-item {{ request()->routeIs('gkm.laporan-kuesioner.*') ? 'active' : '' }}">
                                    <i class="bi bi-file-earmark-text"></i> Laporan Kuesioner Bulanan
                                </a>
                            </div>
                        </div>

                        <a href="{{ route('gkm.reminder-agent.index') }}"
                            class="nav-item {{ request()->routeIs('gkm.reminder-agent.*') ? 'active' : '' }}">
                            <span><i class="bi bi-bell"></i> Reminder Agent</span>
                        </a>
                        <a href="{{ route('gkm.kirim-laporan.index') }}"
                            class="nav-item {{ request()->routeIs('gkm.kirim-laporan.*') ? 'active' : '' }}">
                            <span><i class="bi bi-send"></i> Kirim Laporan</span>
                        </a>
                    @elseif (auth()->user()->isGJM())
                        <a href="{{ route('gjm.dashboard') }}"
                            class="nav-item {{ request()->routeIs('gjm.dashboard') ? 'active' : '' }}">
                            <span><i class="bi bi-speedometer2"></i> Dashboard</span>
                        </a>

                        <div class="nav-dropdown {{ request()->routeIs('gjm.buat-laporan.*') ? 'open' : '' }}">
                            <a href="javascript:void(0)"
                                class="nav-item {{ request()->routeIs('gjm.buat-laporan.*') ? 'active' : '' }}"
                                onclick="toggleDropdown(this)">
                                <span><i class="bi bi-file-earmark-text"></i> Pelaporan</span>
                                <i class="bi bi-chevron-down dropdown-icon"></i>
                            </a>
                            <div class="dropdown-submenu">
                                <a href="{{ route('gjm.buat-laporan.triwulan.index') }}"
                                    class="nav-item {{ request()->routeIs('gjm.buat-laporan.triwulan.*') ? 'active' : '' }}">
                                    <span><i class="bi bi-calendar3"></i> Buat Laporan Triwulan</span>
                                </a>
                                <a href="{{ route('gjm.buat-laporan.semester.index') }}"
                                    class="nav-item {{ request()->routeIs('gjm.buat-laporan.semester.*') ? 'active' : '' }}">
                                    <span><i class="bi bi-file-earmark-plus"></i> Buat Laporan Semester</span>
                                </a>
                                <a href="{{ route('gjm.buat-laporan.vmts.index') }}"
                                    class="nav-item {{ request()->routeIs('gjm.buat-laporan.vmts.*') ? 'active' : '' }}">
                                    <span><i class="bi bi-file-earmark-text"></i> Buat Laporan VMTS</span>
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
                                    <span><i class="bi bi-file-earmark-plus"></i> Buat PPT</span>
                                </a>
                                <a href="{{ route('gjm.buat-ppt.archive') }}"
                                    class="nav-item {{ request()->routeIs('gjm.buat-ppt.archive') ? 'active' : '' }}">
                                    <span><i class="bi bi-archive"></i> Arsip PPT</span>
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
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error!</strong>
                            <ul class="mb-0 mt-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
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
</body>

</html>
