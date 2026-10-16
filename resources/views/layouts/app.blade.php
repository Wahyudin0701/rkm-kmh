<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistem RKM - PT Kerinci Merangin Hidro')</title>
    <!-- Compiled Assets via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* Active Sidebar Item Style (Seperti Sistem IT All Role) */
        .sidebar-item-active {
            background-color: #1b5e20;
            color: #ffffff;
            font-weight: 600;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(27, 94, 32, 0.25);
        }
        .sidebar-item-active i,
        .sidebar-item-active svg {
            color: #ffffff;
        }

        .sidebar-item-inactive {
            color: #475569;
            font-weight: 500;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
        }
        .sidebar-item-inactive:hover {
            background-color: #f1f5f9;
            color: #0f172a;
        }
        .sidebar-item-inactive i,
        .sidebar-item-inactive svg {
            color: #64748b;
        }
        .sidebar-item-inactive:hover i,
        .sidebar-item-inactive:hover svg {
            color: #1b5e20;
        }

        /* Modern Sleek Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Cegah Scrollbar Melompat & Hilangkan Gap Putih saat Modal Aktif */
        html.modal-open,
        body.modal-open {
            overflow: hidden !important;
            padding-right: 0px !important;
            margin-right: 0px !important;
        }

        /* Scrollbar Halus & Transparan untuk Dialog Modal */
        html.modal-open::-webkit-scrollbar,
        body.modal-open::-webkit-scrollbar {
            width: 6px;
            background-color: rgba(15, 23, 42, 0.6);
        }
        html.modal-open::-webkit-scrollbar-track,
        body.modal-open::-webkit-scrollbar-track {
            background-color: rgba(15, 23, 42, 0.6);
        }
        html.modal-open::-webkit-scrollbar-thumb,
        body.modal-open::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.2);
            border: 2px solid transparent;
            background-clip: padding-box;
        }
        html.modal-open::-webkit-scrollbar-thumb:hover,
        body.modal-open::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 255, 255, 0.35);
        }

        /* Dukungan Firefox */
        * {
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.45) transparent;
        }
        html.modal-open,
        body.modal-open {
            scrollbar-color: rgba(255, 255, 255, 0.2) rgba(15, 23, 42, 0.6);
        }

        /* Hilangkan Scrollbar pada Sidebar Desktop & Mobile (tetap dapat di-scroll) */
        aside .overflow-y-auto,
        #mobileDrawerPanel .overflow-y-auto,
        .no-scrollbar {
            -ms-overflow-style: none !important; /* IE dan Edge */
            scrollbar-width: none !important; /* Firefox */
        }
        aside .overflow-y-auto::-webkit-scrollbar,
        #mobileDrawerPanel .overflow-y-auto::-webkit-scrollbar,
        .no-scrollbar::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased font-sans min-h-screen">

@php
    $isStandaloneForm = request()->routeIs('vehicle.activity.*') 
        || request()->is('gate-activity*') 
        || request()->routeIs('p2h.form', 'p2h.store') 
        || (request()->is('p2h/*') && !request()->routeIs('qma.*'));
    $isFormMobil = $isStandaloneForm;
@endphp

@if($isStandaloneForm)
    <!-- ========================================================================= -->
    <!-- LAYOUT KHUSUS FORM STANDALONE / FORM MOBIL & P2H (TANPA SIDEBAR & TOPBAR) -->
    <!-- ========================================================================= -->
    <div class="min-h-screen bg-slate-50 flex flex-col justify-between py-6 px-4 sm:px-6">
        <main class="w-full">
            @yield('content')
        </main>
        <footer class="mt-8 text-center text-xs text-slate-400">
            &copy; 2026 PT Kerinci Merangin Hidro &bull; PLTA Merangin 350 MW
        </footer>
    </div>

@elseif(auth()->check())
    <!-- ========================================================================= -->
    <!-- LAYOUT AUTHENTICATED: SIDEBAR + TOPBAR (PERSIS SISTEM IT ALL ROLE) -->
    <!-- ========================================================================= -->

    <!-- 1. SIDEBAR DESKTOP (Fixed di Sebelah Kiri) -->
    <aside class="hidden md:flex flex-col justify-between w-64 bg-white border-r border-slate-200 h-screen fixed left-0 top-0 z-30 shadow-sm">
        <div class="flex flex-col h-full overflow-hidden">
            <!-- Header Sidebar: Logo Kerinci Hydropower (Tengah Sidebar) -->
            <div class="h-16 px-6 flex items-center justify-center border-b border-slate-100 flex-shrink-0">
                <a href="{{ url('/') }}" class="flex items-center justify-center">
                    <img src="{{ asset('images/logo-kerinci.png') }}" 
                         alt="Kerinci Hydropower" 
                         class="h-8 w-auto object-contain">
                </a>
            </div>

            <!-- Menu Navigasi Sidebar -->
            <div class="flex-1 overflow-y-auto px-4 py-5 space-y-6 no-scrollbar">
                <!-- Grup: Navigasi Utama -->
                <div>
                    <nav class="space-y-1">
                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.dashboard') }}" 
                               class="flex items-center space-x-3 px-3.5 py-2.5 text-sm {{ request()->routeIs('admin.dashboard') || request()->routeIs('superadmin.dashboard') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="14" width="7" height="7" rx="2"></rect>
                                    <rect x="3" y="14" width="7" height="7" rx="2"></rect>
                                </svg>
                                <span>Dashboard Admin</span>
                            </a>
                        @elseif(auth()->user()->isHseAdmin())
                            @can('hse.employees.view')
                            <a href="{{ route('hse.dashboard') }}" 
                               class="flex items-center space-x-3 px-3.5 py-2.5 text-sm {{ request()->routeIs('hse.dashboard') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="14" width="7" height="7" rx="2"></rect>
                                    <rect x="3" y="14" width="7" height="7" rx="2"></rect>
                                </svg>
                                <span>Dashboard</span>
                            </a>
                            @endcan
                        @elseif(auth()->user()->isQmaAdmin())
                            @can('qma.vehicles.view')
                            <a href="{{ route('qma.dashboard') }}" 
                               class="flex items-center space-x-3 px-3.5 py-2.5 text-sm {{ request()->routeIs('qma.dashboard') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="14" width="7" height="7" rx="2"></rect>
                                    <rect x="3" y="14" width="7" height="7" rx="2"></rect>
                                </svg>
                                <span>Dashboard</span>
                            </a>
                            @endcan
                        @elseif(auth()->user()->isPgAdmin())
                            @can('pg.guards.view')
                            <a href="{{ route('pg.dashboard') }}" 
                               class="flex items-center space-x-3 px-3.5 py-2.5 text-sm {{ request()->routeIs('pg.dashboard') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="14" width="7" height="7" rx="2"></rect>
                                    <rect x="3" y="14" width="7" height="7" rx="2"></rect>
                                </svg>
                                <span>Dashboard</span>
                            </a>
                            @endcan
                        @elseif(auth()->user()->isGateOfficer())
                            @can('gate.inspect')
                            <a href="{{ route('pg.gate.inspection') }}" 
                               class="flex items-center space-x-3 px-3.5 py-2.5 text-sm {{ request()->routeIs('pg.gate.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <i class="fa-solid fa-shield-halved text-base w-5 text-center"></i>
                                <span>Verifikasi Pos Gerbang</span>
                            </a>
                            @endcan
                        @elseif(auth()->user()->isEmployee())
                            <a href="{{ route('employee.dashboard') }}" 
                               class="flex items-center space-x-3 px-3.5 py-2.5 text-sm {{ request()->routeIs('employee.dashboard') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="14" width="7" height="7" rx="2"></rect>
                                    <rect x="3" y="14" width="7" height="7" rx="2"></rect>
                                </svg>
                                <span>Dashboard</span>
                            </a>
                            <a href="{{ route('p2h.index') }}" 
                               class="flex items-center space-x-3 px-3.5 py-2.5 text-sm {{ request()->routeIs('p2h.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <i class="fa-solid fa-clipboard-check text-base w-5 text-center"></i>
                                <span>Pemeriksaan P2H</span>
                            </a>
                            <a href="{{ route('timesheet.index') }}" 
                               class="flex items-center space-x-3 px-3.5 py-2.5 text-sm {{ request()->routeIs('timesheet.*') || request()->routeIs('mobilisasi.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <i class="fa-solid fa-car text-base w-5 text-center"></i>
                                <span>Timesheet</span>
                            </a>
                        @endif
                    </nav>
                </div>

                <!-- Grup: Super Admin & RBAC -->
                @if(auth()->user()->isSuperAdmin())
                <div>
                    <p class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                        SUPER ADMIN & RBAC
                    </p>
                    <nav class="space-y-1">
                        <a href="{{ route('admin.users.index') }}" 
                           class="flex items-center space-x-3 px-3.5 py-2.5 text-sm {{ request()->routeIs('admin.users.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                            <i class="fa-solid fa-users-gear text-base w-5 text-center"></i>
                            <span>Kelola Pengguna</span>
                        </a>

                        <a href="{{ route('admin.roles.index') }}" 
                           class="flex items-center space-x-3 px-3.5 py-2.5 text-sm {{ request()->routeIs('admin.roles.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                            <i class="fa-solid fa-shield-halved text-base w-5 text-center"></i>
                            <span>Role & Permission</span>
                        </a>
                    </nav>
                </div>
                @endif

                <!-- Grup: Manajemen HSE -->
                @can('hse.employees.view')
                <div>
                    <p class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                        MANAJEMEN HSE
                    </p>
                    <nav class="space-y-1">
                        <a href="{{ route('hse.employees.index') }}" 
                           class="flex items-center space-x-3 px-3.5 py-2.5 text-sm {{ request()->routeIs('hse.employees.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                            <i class="fa-solid fa-id-card text-base w-5 text-center"></i>
                            <span>Data Simper</span>
                        </a>
                    </nav>
                </div>
                @endcan

                <!-- Grup: Manajemen QMA -->
                @if(auth()->user()->canAny(['qma.vehicles.view', 'qma.logs.view', 'qma.summary.view', 'qma.p2h.view', 'qma.p2h.templates']))
                <div>
                    <p class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                        MANAJEMEN QMA
                    </p>
                    <nav class="space-y-1">
                        @can('qma.vehicles.view')
                            @php
                                $isVehicleRoute = request()->routeIs('qma.vehicles.*');
                            @endphp
                            <!-- Dropdown Menu: Data Kendaraan -->
                            <div>
                                <button type="button" 
                                        onclick="toggleVehicleMenu('desktop')" 
                                        class="w-full flex items-center justify-between px-3.5 py-2.5 text-sm rounded-xl transition group {{ $isVehicleRoute ? 'bg-emerald-50/70 text-[#1b5e20] font-bold' : 'sidebar-item-inactive' }}">
                                    <div class="flex items-center space-x-3">
                                        <i class="fa-solid fa-car text-base w-5 text-center {{ $isVehicleRoute ? 'text-[#1b5e20]' : '' }}"></i>
                                        <span>Data Kendaraan</span>
                                    </div>
                                    <i id="vehicleChevronDesktop" class="fa-solid fa-chevron-down text-[11px] transition-transform duration-200 {{ $isVehicleRoute ? 'rotate-180 text-[#1b5e20]' : 'text-slate-400 group-hover:text-slate-600' }}"></i>
                                </button>

                                <!-- Submenu Dropdown: Light Vehicle & Unit Support (Tanpa Icon) -->
                                <div id="vehicleSubmenuDesktop" class="{{ $isVehicleRoute ? '' : 'hidden' }} mt-1 pl-7 pr-1 space-y-1">
                                    <a href="{{ route('qma.vehicles.lv') }}" 
                                       class="block px-3.5 py-2 text-xs rounded-xl transition {{ request()->routeIs('qma.vehicles.lv') ? 'bg-[#1b5e20] text-white font-bold shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium' }}">
                                        <span>Light Vehicle</span>
                                    </a>

                                    <a href="{{ route('qma.vehicles.support') }}" 
                                       class="block px-3.5 py-2 text-xs rounded-xl transition {{ request()->routeIs('qma.vehicles.support') || request()->routeIs('qma.vehicles.support.units*') ? 'bg-[#1b5e20] text-white font-bold shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium' }}">
                                        <span>Unit Support</span>
                                    </a>
                                </div>
                            </div>
                        @endcan

                        @if(auth()->user()->canAny(['qma.logs.view', 'qma.p2h.view']))
                            @php
                                $isActivityRoute = request()->routeIs('qma.activity.log') || request()->routeIs('qma.p2h.index') || request()->routeIs('qma.p2h.show');
                            @endphp
                            <!-- Dropdown Menu: Log Aktifitas -->
                            <div>
                                <button type="button" 
                                        onclick="toggleActivityMenu('desktop')" 
                                        class="w-full flex items-center justify-between px-3.5 py-2.5 text-sm rounded-xl transition group {{ $isActivityRoute ? 'bg-emerald-50/70 text-[#1b5e20] font-bold' : 'sidebar-item-inactive' }}">
                                    <div class="flex items-center space-x-3">
                                        <i class="fa-solid fa-clipboard-list text-base w-5 text-center {{ $isActivityRoute ? 'text-[#1b5e20]' : '' }}"></i>
                                        <span>Log Aktifitas</span>
                                    </div>
                                    <i id="activityChevronDesktop" class="fa-solid fa-chevron-down text-[11px] transition-transform duration-200 {{ $isActivityRoute ? 'rotate-180 text-[#1b5e20]' : 'text-slate-400 group-hover:text-slate-600' }}"></i>
                                </button>

                                <!-- Submenu Dropdown: Timesheet LV & P2H Unit Support (Tanpa Icon) -->
                                <div id="activitySubmenuDesktop" class="{{ $isActivityRoute ? '' : 'hidden' }} mt-1 pl-7 pr-1 space-y-1">
                                    @can('qma.logs.view')
                                    <a href="{{ route('qma.activity.log') }}" 
                                       class="block px-3.5 py-2 text-xs rounded-xl transition {{ request()->routeIs('qma.activity.log') ? 'bg-[#1b5e20] text-white font-bold shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium' }}">
                                        <span>Timesheet LV</span>
                                    </a>
                                    @endcan

                                    @can('qma.p2h.view')
                                    <a href="{{ route('qma.p2h.index') }}" 
                                       class="block px-3.5 py-2 text-xs rounded-xl transition {{ request()->routeIs('qma.p2h.index') || request()->routeIs('qma.p2h.show') ? 'bg-[#1b5e20] text-white font-bold shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium' }}">
                                        <span>P2H Unit Support</span>
                                    </a>
                                    @endcan
                                </div>
                            </div>
                        @endif

                        @can('qma.p2h.templates')
                            <a href="{{ route('qma.p2h.templates') }}" 
                               class="flex items-center space-x-3 px-3.5 py-2.5 text-sm {{ request()->routeIs('qma.p2h.templates*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <i class="fa-solid fa-layer-group text-base w-5 text-center"></i>
                                <span>Master Template P2H</span>
                            </a>
                        @endcan

                        @can('qma.summary.view')
                            <a href="{{ route('qma.activity.summary') }}" 
                               class="flex items-center space-x-3 px-3.5 py-2.5 text-sm {{ request()->routeIs('qma.activity.summary') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <i class="fa-solid fa-chart-pie text-base w-5 text-center"></i>
                                <span>Summary Aktivitas</span>
                            </a>
                        @endcan
                    </nav>
                </div>
                @endif

                <!-- Grup: Manajemen Private Guard (PG) -->
                @if(auth()->user()->canAny(['pg.guards.view', 'pg.logs.view', 'gate.inspect']))
                <div>
                    <p class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                        MANAJEMEN PRIVATE GUARD (PG)
                    </p>
                    <nav class="space-y-1">
                        @can('pg.guards.view')
                            <a href="{{ route('pg.guards.index') }}" 
                               class="flex items-center space-x-3 px-3.5 py-2.5 text-sm {{ request()->routeIs('pg.guards.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <i class="fa-solid fa-user-shield text-base w-5 text-center"></i>
                                <span>Data Satpam</span>
                            </a>
                        @endcan

                        @can('pg.logs.view')
                            <a href="{{ route('pg.logs.index') }}" 
                               class="flex items-center space-x-3 px-3.5 py-2.5 text-sm {{ request()->routeIs('pg.logs.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <i class="fa-solid fa-clock-rotate-left text-base w-5 text-center"></i>
                                <span>Log Verifikasi Gerbang</span>
                            </a>
                        @endcan

                        @if(!auth()->user()->isGateOfficer())
                            @can('gate.inspect')
                                <a href="{{ route('pg.gate.inspection') }}" 
                                   class="flex items-center space-x-3 px-3.5 py-2.5 text-sm {{ request()->routeIs('pg.gate.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                    <i class="fa-solid fa-shield-halved text-base w-5 text-center"></i>
                                    <span>Verifikasi Pos Gerbang</span>
                                </a>
                            @endcan
                        @endif
                    </nav>
                </div>
                @endif

                <!-- Grup: Pengaturan Akun -->
                <div class="mt-6">
                    <p class="px-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                        PENGATURAN
                    </p>
                    <nav class="space-y-1">
                        <a href="{{ route('profile.index') }}" 
                           class="flex items-center space-x-3 px-3.5 py-2.5 text-sm {{ request()->routeIs('profile.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                            <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span>Profil Saya</span>
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Footer Bawah Sidebar: Profil Pengguna & Tombol Logout (Persis Gambar IT All Role) -->
            <div class="p-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                <a href="{{ route('profile.index') }}" class="flex items-center space-x-2.5 min-w-0 hover:opacity-80 transition group" title="Buka Profil Saya">
                    <div class="w-8 h-8 rounded-full bg-[#1b5e20] text-white font-black text-xs flex items-center justify-center flex-shrink-0 shadow-sm group-hover:ring-2 ring-emerald-500/30">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="truncate">
                        <p class="text-xs font-bold text-slate-800 truncate leading-tight group-hover:text-[#1b5e20]">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-slate-400 capitalize truncate">{{ str_replace('_', ' ', auth()->user()->role) }}</p>
                    </div>
                </a>

                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" title="Logout" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition">
                        <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- 2. SIDEBAR MOBILE (Drawer Modal untuk Layar Kecil) -->
    <div id="mobileDrawer" class="fixed inset-0 z-50 hidden md:hidden">
        <!-- Backdrop gelap -->
        <div id="mobileBackdrop" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity duration-300 ease-in-out opacity-0"></div>

        <!-- Drawer Content -->
        <div id="mobileDrawerPanel" class="fixed inset-y-0 left-0 w-72 bg-white shadow-2xl flex flex-col justify-between z-10 transform -translate-x-full transition-transform duration-300 ease-in-out">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <img src="{{ asset('images/logo-kerinci.png') }}" alt="Kerinci Hydropower" class="h-7 w-auto object-contain">
                <button id="closeDrawerBtn" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-4 space-y-4 flex-1 overflow-y-auto no-scrollbar">
                <!-- Grup: Navigasi Utama Mobile -->
                <div>
                    <nav class="space-y-1">
                        @if(auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('admin.dashboard') || request()->routeIs('superadmin.dashboard') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="14" width="7" height="7" rx="2"></rect>
                                    <rect x="3" y="14" width="7" height="7" rx="2"></rect>
                                </svg>
                                <span>Dashboard Admin</span>
                            </a>
                        @elseif(auth()->user()->isHseAdmin())
                            @can('hse.employees.view')
                            <a href="{{ route('hse.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('hse.dashboard') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="14" width="7" height="7" rx="2"></rect>
                                    <rect x="3" y="14" width="7" height="7" rx="2"></rect>
                                </svg>
                                <span>Dashboard</span>
                            </a>
                            @endcan
                        @elseif(auth()->user()->isQmaAdmin())
                            @can('qma.vehicles.view')
                            <a href="{{ route('qma.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('qma.dashboard') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="14" width="7" height="7" rx="2"></rect>
                                    <rect x="3" y="14" width="7" height="7" rx="2"></rect>
                                </svg>
                                <span>Dashboard</span>
                            </a>
                            @endcan
                        @elseif(auth()->user()->isPgAdmin())
                            @can('pg.guards.view')
                            <a href="{{ route('pg.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('pg.dashboard') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="14" width="7" height="7" rx="2"></rect>
                                    <rect x="3" y="14" width="7" height="7" rx="2"></rect>
                                </svg>
                                <span>Dashboard</span>
                            </a>
                            @endcan
                        @elseif(auth()->user()->isGateOfficer())
                            @can('gate.inspect')
                            <a href="{{ route('pg.gate.inspection') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('pg.gate.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <i class="fa-solid fa-shield-halved text-base w-5 text-center"></i>
                                <span>Verifikasi Pos Gerbang</span>
                            </a>
                            @endcan
                        @elseif(auth()->user()->isEmployee())
                            <a href="{{ route('employee.dashboard') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('employee.dashboard') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="3" width="7" height="7" rx="2"></rect>
                                    <rect x="14" y="14" width="7" height="7" rx="2"></rect>
                                    <rect x="3" y="14" width="7" height="7" rx="2"></rect>
                                </svg>
                                <span>Dashboard</span>
                            </a>
                            <a href="{{ route('p2h.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('p2h.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <i class="fa-solid fa-clipboard-check text-base w-5 text-center"></i>
                                <span>Pemeriksaan P2H</span>
                            </a>
                            <a href="{{ route('timesheet.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('timesheet.*') || request()->routeIs('mobilisasi.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <i class="fa-solid fa-car text-base w-5 text-center"></i>
                                <span>Timesheet</span>
                            </a>
                        @endif
                    </nav>
                </div>

                <!-- Grup: Super Admin & RBAC Mobile -->
                @if(auth()->user()->isSuperAdmin())
                <div>
                    <p class="px-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                        SUPER ADMIN & RBAC
                    </p>
                    <nav class="space-y-1">
                        <a href="{{ route('admin.users.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('admin.users.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                            <i class="fa-solid fa-users-gear w-5 text-center"></i>
                            <span>Kelola Pengguna</span>
                        </a>
                        <a href="{{ route('admin.roles.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('admin.roles.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                            <i class="fa-solid fa-shield-halved w-5 text-center"></i>
                            <span>Role & Permission</span>
                        </a>
                    </nav>
                </div>
                @endif

                <!-- Grup: Manajemen HSE Mobile -->
                @can('hse.employees.view')
                <div>
                    <p class="px-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                        MANAJEMEN HSE
                    </p>
                    <nav class="space-y-1">
                        <a href="{{ route('hse.employees.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('hse.employees.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                            <i class="fa-solid fa-id-card w-5 text-center"></i>
                            <span>Data Simper</span>
                        </a>
                    </nav>
                </div>
                @endcan

                <!-- Grup: Manajemen QMA Mobile -->
                @if(auth()->user()->canAny(['qma.vehicles.view', 'qma.logs.view', 'qma.summary.view', 'qma.p2h.view', 'qma.p2h.templates']))
                <div>
                    <p class="px-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                        MANAJEMEN QMA
                    </p>
                    <nav class="space-y-1">
                        @can('qma.vehicles.view')
                            @php
                                $isVehicleRouteMob = request()->routeIs('qma.vehicles.*');
                            @endphp
                            <div>
                                <button type="button" 
                                        onclick="toggleVehicleMenu('mobile')" 
                                        class="w-full flex items-center justify-between px-3 py-2.5 text-sm rounded-xl transition group {{ $isVehicleRouteMob ? 'bg-emerald-50/70 text-[#1b5e20] font-bold' : 'sidebar-item-inactive' }}">
                                    <div class="flex items-center space-x-3">
                                        <i class="fa-solid fa-car w-5 text-center {{ $isVehicleRouteMob ? 'text-[#1b5e20]' : '' }}"></i>
                                        <span>Data Kendaraan</span>
                                    </div>
                                    <i id="vehicleChevronMobile" class="fa-solid fa-chevron-down text-[11px] transition-transform duration-200 {{ $isVehicleRouteMob ? 'rotate-180 text-[#1b5e20]' : 'text-slate-400 group-hover:text-slate-600' }}"></i>
                                </button>

                                <div id="vehicleSubmenuMobile" class="{{ $isVehicleRouteMob ? '' : 'hidden' }} mt-1 pl-7 pr-1 space-y-1">
                                    <a href="{{ route('qma.vehicles.lv') }}" 
                                       class="block px-3.5 py-2 text-xs rounded-xl transition {{ request()->routeIs('qma.vehicles.lv') ? 'bg-[#1b5e20] text-white font-bold shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium' }}">
                                        <span>Light Vehicle</span>
                                    </a>

                                    <a href="{{ route('qma.vehicles.support') }}" 
                                       class="block px-3.5 py-2 text-xs rounded-xl transition {{ request()->routeIs('qma.vehicles.support') || request()->routeIs('qma.vehicles.support.units*') ? 'bg-[#1b5e20] text-white font-bold shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium' }}">
                                        <span>Unit Support</span>
                                    </a>
                                </div>
                            </div>
                        @endcan

                        @if(auth()->user()->canAny(['qma.logs.view', 'qma.p2h.view']))
                            @php
                                $isActivityRouteMob = request()->routeIs('qma.activity.log') || request()->routeIs('qma.p2h.index') || request()->routeIs('qma.p2h.show');
                            @endphp
                            <div>
                                <button type="button" 
                                        onclick="toggleActivityMenu('mobile')" 
                                        class="w-full flex items-center justify-between px-3 py-2.5 text-sm rounded-xl transition group {{ $isActivityRouteMob ? 'bg-emerald-50/70 text-[#1b5e20] font-bold' : 'sidebar-item-inactive' }}">
                                    <div class="flex items-center space-x-3">
                                        <i class="fa-solid fa-clipboard-list w-5 text-center {{ $isActivityRouteMob ? 'text-[#1b5e20]' : '' }}"></i>
                                        <span>Log Aktifitas</span>
                                    </div>
                                    <i id="activityChevronMobile" class="fa-solid fa-chevron-down text-[11px] transition-transform duration-200 {{ $isActivityRouteMob ? 'rotate-180 text-[#1b5e20]' : 'text-slate-400 group-hover:text-slate-600' }}"></i>
                                </button>

                                <div id="activitySubmenuMobile" class="{{ $isActivityRouteMob ? '' : 'hidden' }} mt-1 pl-7 pr-1 space-y-1">
                                    @can('qma.logs.view')
                                    <a href="{{ route('qma.activity.log') }}" 
                                       class="block px-3.5 py-2 text-xs rounded-xl transition {{ request()->routeIs('qma.activity.log') ? 'bg-[#1b5e20] text-white font-bold shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium' }}">
                                        <span>Timesheet LV</span>
                                    </a>
                                    @endcan

                                    @can('qma.p2h.view')
                                    <a href="{{ route('qma.p2h.index') }}" 
                                       class="block px-3.5 py-2 text-xs rounded-xl transition {{ request()->routeIs('qma.p2h.index') || request()->routeIs('qma.p2h.show') ? 'bg-[#1b5e20] text-white font-bold shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-medium' }}">
                                        <span>P2H Unit Support</span>
                                    </a>
                                    @endcan
                                </div>
                            </div>
                        @endif

                        @can('qma.p2h.templates')
                            <a href="{{ route('qma.p2h.templates') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('qma.p2h.templates*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <i class="fa-solid fa-layer-group w-5 text-center"></i>
                                <span>Master Template P2H</span>
                            </a>
                        @endcan

                        @can('qma.summary.view')
                            <a href="{{ route('qma.activity.summary') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('qma.activity.summary') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <i class="fa-solid fa-chart-pie w-5 text-center"></i>
                                <span>Summary Aktivitas</span>
                            </a>
                        @endcan
                    </nav>
                </div>
                @endif

                <!-- Grup: Manajemen Private Guard (PG) Mobile -->
                @if(auth()->user()->canAny(['pg.guards.view', 'pg.logs.view', 'gate.inspect']))
                <div>
                    <p class="px-2 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">
                        MANAJEMEN PRIVATE GUARD (PG)
                    </p>
                    <nav class="space-y-1">
                        @can('pg.guards.view')
                            <a href="{{ route('pg.guards.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('pg.guards.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <i class="fa-solid fa-user-shield w-5 text-center"></i>
                                <span>Data Satpam</span>
                            </a>
                        @endcan
                        @can('pg.logs.view')
                            <a href="{{ route('pg.logs.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('pg.logs.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                <i class="fa-solid fa-clock-rotate-left w-5 text-center"></i>
                                <span>Log Verifikasi Gerbang</span>
                            </a>
                        @endcan
                        @if(!auth()->user()->isGateOfficer())
                            @can('gate.inspect')
                                <a href="{{ route('pg.gate.inspection') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('pg.gate.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                                    <i class="fa-solid fa-shield-halved w-5 text-center"></i>
                                    <span>Verifikasi Pos Gerbang</span>
                                </a>
                            @endcan
                        @endif
                    </nav>
                </div>
                @endif

                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-2 mt-4">PENGATURAN</p>
                <div class="space-y-1">
                    <a href="{{ route('profile.index') }}" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-sm {{ request()->routeIs('profile.*') ? 'sidebar-item-active' : 'sidebar-item-inactive' }}">
                        <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span>Profil Saya</span>
                    </a>
                </div>
            </div>

            <div class="p-4 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                <a href="{{ route('profile.index') }}" class="flex items-center space-x-2.5 min-w-0 hover:opacity-80 transition group" title="Buka Profil Saya">
                    <div class="w-8 h-8 rounded-full bg-[#1b5e20] text-white font-black text-xs flex items-center justify-center flex-shrink-0 shadow-sm group-hover:ring-2 ring-emerald-500/30">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="truncate">
                        <p class="text-xs font-bold text-slate-800 truncate leading-tight group-hover:text-[#1b5e20]">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-slate-400 capitalize truncate">{{ str_replace('_', ' ', auth()->user()->role) }}</p>
                    </div>
                </a>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" title="Logout" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition">
                        <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- 3. TOPBAR HEADER (Di Bagian Atas Samping Sidebar) -->
    <header class="md:ml-64 bg-white border-b border-slate-200 h-16 sticky top-0 z-20 flex items-center justify-between px-6 sm:px-8 shadow-sm">
        <!-- Sisi Kiri: Judul Halaman / Hamburger Button -->
        <div class="flex items-center space-x-3">
            <button id="openDrawerBtn" class="md:hidden text-slate-600 hover:text-slate-900 p-1.5 rounded-lg border border-slate-200">
                <i class="fa-solid fa-bars text-base"></i>
            </button>
            <h1 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">
                @if(request()->routeIs('admin.dashboard') || request()->routeIs('superadmin.dashboard'))
                    Dashboard Admin
                @elseif(request()->routeIs('admin.users.*'))
                    Kelola Pengguna
                @elseif(request()->routeIs('admin.roles.*'))
                    Role & Permission
                @elseif(request()->routeIs('hse.dashboard') || request()->routeIs('qma.dashboard'))
                    Dashboard
                @elseif(request()->routeIs('hse.employees.*'))
                    Data Simper
                @elseif(request()->routeIs('qma.vehicles.lv'))
                    Light Vehicle
                @elseif(request()->routeIs('qma.vehicles.support.units*'))
                    Daftar Unit Fisik Support
                @elseif(request()->routeIs('qma.vehicles.support'))
                    Unit Support
                @elseif(request()->routeIs('qma.vehicles.*'))
                    Data Kendaraan
                @elseif(request()->routeIs('qma.activity.log'))
                    Timesheet LV
                @elseif(request()->routeIs('qma.p2h.summary'))
                    Summary P2H Unit Support
                @elseif(request()->routeIs('qma.p2h.templates*'))
                    Master Template P2H
                @elseif(request()->routeIs('qma.p2h.*'))
                    P2H Unit Support
                @elseif(request()->routeIs('qma.activity.summary'))
                    Summary Aktivitas
                @elseif(request()->routeIs('profile.*'))
                    Profil Saya
                @elseif(request()->routeIs('pg.dashboard'))
                    Dashboard
                @elseif(request()->routeIs('pg.gate.*'))
                    Verifikasi Pos Gerbang
                @elseif(request()->routeIs('pg.guards.*'))
                    Data Satpam
                @elseif(request()->routeIs('pg.logs.*'))
                    Log Verifikasi Gerbang
                @elseif(request()->routeIs('employee.dashboard'))
                    Dashboard Karyawan
                @elseif(request()->routeIs('p2h.*'))
                    Pemeriksaan P2H
                @elseif(request()->routeIs('timesheet.*') || request()->routeIs('mobilisasi.*'))
                    Timesheet
                @else
                    Dashboard
                @endif
            </h1>
        </div>

        <!-- Sisi Kanan: Profil Pengguna (Avatar Bulat + Nama + Role Huruf Kapital + Menu Melayang) Sesuai Gambar IT All Role -->
        <div id="userProfileContainer" class="relative">
            <button type="button" 
                    id="userProfileDropdownBtn" 
                    onclick="toggleProfileDropdown()" 
                    class="flex items-center space-x-2.5 p-1 sm:px-2.5 sm:py-1.5 rounded-xl hover:bg-slate-50 transition group focus:outline-none cursor-pointer"
                    aria-expanded="false"
                    aria-haspopup="true">
                <div class="w-9 h-9 rounded-full bg-[#1b5e20] text-white font-bold text-sm flex items-center justify-center shadow-sm group-hover:ring-2 ring-emerald-500/30 flex-shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="text-left hidden sm:block">
                    <p class="text-xs font-bold text-slate-800 leading-tight group-hover:text-[#1b5e20]">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider mt-0.5">
                        {{ str_replace('_', ' ', auth()->user()->role) }}
                    </p>
                </div>
                <i id="userProfileChevron" class="fa-solid fa-chevron-down text-slate-400 text-[11px] ml-1 transition-transform duration-200 group-hover:text-slate-600"></i>
            </button>

            <!-- Menu Melayang (Dropdown Menu) Sesuai Referensi Gambar -->
            <div id="userProfileDropdownMenu" 
                 class="hidden absolute right-0 top-full mt-2 w-52 bg-white rounded-2xl shadow-xl border border-slate-100 py-1.5 z-50 transform origin-top-right transition-all">
                <a href="{{ route('profile.index') }}" 
                   class="flex items-center space-x-3 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:text-slate-900 hover:bg-slate-50 transition">
                    <i class="fa-regular fa-user text-sm text-slate-400 w-4 text-center"></i>
                    <span>Profil Saya</span>
                </a>

                <div class="border-t border-slate-100 my-1"></div>

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" 
                            class="w-full flex items-center space-x-3 px-4 py-2.5 text-xs font-bold text-rose-600 hover:text-rose-700 hover:bg-rose-50/70 transition text-left cursor-pointer">
                        <i class="fa-solid fa-arrow-right-from-bracket text-sm w-4 text-center text-rose-500"></i>
                        <span>Keluar Sistem</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- 4. KONTEN UTAMA DASHBOARD (ATURAN EMAS: WRAPPER MAX-WIDTH HANYA DI SATU TEMPAT) -->
    <main class="md:ml-64 flex-1 min-h-[calc(100vh-4rem)]">
        <div class="p-4 md:p-8 max-w-[1400px] mx-auto w-full">
            @yield('content')
        </div>
    </main>

@else
    <!-- ========================================================================= -->
    <!-- LAYOUT PUBLIC (KHUSUS FORM SCAN QR LAPANGAN TANPA LOGIN) -->
    <!-- ========================================================================= -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
        <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center space-x-2">
                <img src="{{ asset('images/logo-kerinci.png') }}" alt="Kerinci Hydropower" class="h-8 w-auto object-contain">
                <span class="font-extrabold text-[#1b5e20] text-sm hidden sm:inline">RKM</span>
            </a>
            <div class="flex items-center space-x-4">
                <a href="{{ route('home') }}" class="text-xs font-semibold text-slate-600 hover:text-[#1b5e20] flex items-center space-x-1.5">
                    <i class="fa-solid fa-house text-[11px]"></i>
                    <span>Beranda RKM</span>
                </a>
                <a href="{{ route('login') }}" class="text-xs font-semibold text-white bg-[#1b5e20] hover:bg-[#144918] px-3 py-1.5 rounded-lg flex items-center space-x-1 shadow-sm">
                    <span>Login Petugas</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto p-4 sm:p-6 min-h-[calc(100vh-5rem)]">
        @yield('content')
    </main>

    <footer class="py-4 text-center text-xs text-slate-400 border-t border-slate-200 bg-white">
        &copy; 2026 PT Kerinci Merangin Hidro &bull; PLTA Merangin 350 MW
    </footer>
@endif

    <!-- Toast Notification Container (Pojok Kanan Bawah) -->
    <div id="toastContainer" class="fixed bottom-6 right-6 z-50 flex flex-col space-y-3 pointer-events-none">
        @if(session('success'))
            <div id="toast-success" class="pointer-events-auto bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden min-w-[320px] max-w-sm sm:max-w-md transform transition-all duration-300 ease-out translate-y-0 opacity-100">
                <div class="px-5 py-4 flex items-center justify-between gap-4">
                    <div class="flex items-center space-x-3.5">
                        <div class="flex-shrink-0 text-[#10b981]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.3" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9" stroke="currentColor"></circle>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 12.5l2.5 2.5 4.5-5"></path>
                            </svg>
                        </div>
                        <div class="text-sm font-bold text-slate-800 leading-snug">
                            {{ session('success') }}
                        </div>
                    </div>
                    <button type="button" onclick="dismissToast('toast-success')" class="text-slate-400 hover:text-slate-600 transition-colors p-1 flex-shrink-0 focus:outline-none" title="Tutup">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <!-- Progress Countdown Bar -->
                <div class="w-full bg-slate-100 h-1 overflow-hidden">
                    <div id="toast-progress-success" class="h-full bg-[#10b981] transition-all ease-linear" style="transition-duration: 4000ms; width: 100%;"></div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div id="toast-error" class="pointer-events-auto bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden min-w-[320px] max-w-sm sm:max-w-md transform transition-all duration-300 ease-out translate-y-0 opacity-100">
                <div class="px-5 py-4 flex items-center justify-between gap-4">
                    <div class="flex items-center space-x-3.5">
                        <div class="flex-shrink-0 text-rose-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.3" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9" stroke="currentColor"></circle>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01"></path>
                            </svg>
                        </div>
                        <div class="text-sm font-bold text-slate-800 leading-snug">
                            {{ session('error') }}
                        </div>
                    </div>
                    <button type="button" onclick="dismissToast('toast-error')" class="text-slate-400 hover:text-slate-600 transition-colors p-1 flex-shrink-0 focus:outline-none" title="Tutup">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <!-- Progress Countdown Bar -->
                <div class="w-full bg-slate-100 h-1 overflow-hidden">
                    <div id="toast-progress-error" class="h-full bg-rose-500 transition-all ease-linear" style="transition-duration: 4000ms; width: 100%;"></div>
                </div>
            </div>
        @endif
    </div>

    <!-- Script Javascript untuk Toast Notification -->
    <script>
        // Fungsi Menutup Toast
        function dismissToast(id) {
            const toast = document.getElementById(id);
            if (!toast) return;
            toast.classList.add('opacity-0', 'translate-y-3');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }

        // Auto dismiss Toast dengan countdown progress bar
        document.addEventListener('DOMContentLoaded', () => {
            const successToast = document.getElementById('toast-success');
            if (successToast) {
                const bar = document.getElementById('toast-progress-success');
                setTimeout(() => {
                    if (bar) bar.style.width = '0%';
                }, 50);
                setTimeout(() => {
                    dismissToast('toast-success');
                }, 4100);
            }

            const errorToast = document.getElementById('toast-error');
            if (errorToast) {
                const bar = document.getElementById('toast-progress-error');
                setTimeout(() => {
                    if (bar) bar.style.width = '0%';
                }, 50);
                setTimeout(() => {
                    dismissToast('toast-error');
                }, 4100);
            }
        });

        // =========================================================================
        // ATURAN EMAS MODAL: PENANGANAN BACKDROP KLIK & ESCAPE TERPUSAT
        // =========================================================================
        function closeAnyActiveModal(modal) {
            if (!modal) return;

            // 1. Trigger tombol close/batal jika tersedia
            const closeBtn = modal.querySelector('[data-modal-close], button[onclick*="close"], button[onclick*="Modal"], .modal-close');
            if (closeBtn && typeof closeBtn.click === 'function') {
                closeBtn.click();
                return;
            }

            // 2. Jika modal masih belum tertutup, sembunyikan langsung
            if (!modal.classList.contains('hidden')) {
                modal.classList.add('hidden');
            }

            // 3. Normalisasi status overflow body jika semua modal sudah tertutup
            const openModals = document.querySelectorAll('.fixed.inset-0:not(.hidden):not(#mobileDrawer)');
            if (openModals.length === 0) {
                document.body.classList.remove('modal-open', 'overflow-hidden');
                document.documentElement.classList.remove('modal-open');
            }
        }

        let isBackdropClickTarget = false;
        let activeBackdropTargetModal = null;

        document.addEventListener('mousedown', function(e) {
            const modal = e.target.closest('[id$="Modal"], [id$="modal"], .fixed.inset-0.z-50');
            if (modal && !modal.classList.contains('hidden') && modal.id !== 'mobileDrawer') {
                const dialogCard = modal.querySelector('.bg-white, [role="dialog"], .modal-dialog, div[class*="rounded-"]');
                if (dialogCard && !dialogCard.contains(e.target)) {
                    isBackdropClickTarget = true;
                    activeBackdropTargetModal = modal;
                    return;
                }
            }
            isBackdropClickTarget = false;
            activeBackdropTargetModal = null;
        });

        document.addEventListener('mouseup', function(e) {
            if (isBackdropClickTarget && activeBackdropTargetModal) {
                const modal = activeBackdropTargetModal;
                if (!modal.classList.contains('hidden')) {
                    const dialogCard = modal.querySelector('.bg-white, [role="dialog"], .modal-dialog, div[class*="rounded-"]');
                    if (dialogCard && !dialogCard.contains(e.target)) {
                        closeAnyActiveModal(modal);
                    }
                }
            }
            isBackdropClickTarget = false;
            activeBackdropTargetModal = null;
        });

        // Dukungan klik langsung pada backdrop
        document.addEventListener('click', function(e) {
            const modal = e.target.closest('[id$="Modal"], [id$="modal"], .fixed.inset-0.z-50');
            if (modal && !modal.classList.contains('hidden') && modal.id !== 'mobileDrawer') {
                const dialogCard = modal.querySelector('.bg-white, [role="dialog"], .modal-dialog, div[class*="rounded-"]');
                if (dialogCard && !dialogCard.contains(e.target)) {
                    closeAnyActiveModal(modal);
                }
            }
        });

        // Dukungan tombol Escape keyboard untuk menutup modal aktif
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const openModals = document.querySelectorAll('[id$="Modal"]:not(.hidden), [id$="modal"]:not(.hidden), .fixed.inset-0.z-50:not(.hidden)');
                openModals.forEach(modal => {
                    if (modal.id !== 'mobileDrawer') {
                        closeAnyActiveModal(modal);
                    }
                });
            }
        });
    </script>

    @if(auth()->check() && !$isFormMobil)
    <!-- Script Javascript untuk Drawer Mobile & Menu Admin -->
    <script>
        const openBtn = document.getElementById('openDrawerBtn');
        const closeBtn = document.getElementById('closeDrawerBtn');
        const drawer = document.getElementById('mobileDrawer');
        const backdrop = document.getElementById('mobileBackdrop');
        const drawerPanel = document.getElementById('mobileDrawerPanel');
        let drawerTimer = null;

        if (openBtn && drawer && closeBtn && backdrop && drawerPanel) {
            const openDrawer = () => {
                if (drawerTimer) clearTimeout(drawerTimer);
                drawer.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                // Beri waktu 2 frame agar browser menyelesaikan repaint sebelum memulai transisi CSS
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        backdrop.classList.remove('opacity-0');
                        backdrop.classList.add('opacity-100');
                        drawerPanel.classList.remove('-translate-x-full');
                        drawerPanel.classList.add('translate-x-0');
                    });
                });
            };

            const closeDrawer = () => {
                backdrop.classList.remove('opacity-100');
                backdrop.classList.add('opacity-0');
                drawerPanel.classList.remove('translate-x-0');
                drawerPanel.classList.add('-translate-x-full');
                document.body.classList.remove('overflow-hidden');

                if (drawerTimer) clearTimeout(drawerTimer);
                drawerTimer = setTimeout(() => {
                    drawer.classList.add('hidden');
                }, 300);
            };

            openBtn.addEventListener('click', openDrawer);
            closeBtn.addEventListener('click', closeDrawer);
            backdrop.addEventListener('click', closeDrawer);

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !drawer.classList.contains('hidden')) {
                    closeDrawer();
                }
            });
        }

        // Toggle Dropdown Menu Data Kendaraan (Desktop & Mobile)
        function toggleVehicleMenu(target) {
            const submenu = document.getElementById(target === 'desktop' ? 'vehicleSubmenuDesktop' : 'vehicleSubmenuMobile');
            const chevron = document.getElementById(target === 'desktop' ? 'vehicleChevronDesktop' : 'vehicleChevronMobile');
            if (submenu) {
                submenu.classList.toggle('hidden');
            }
            if (chevron) {
                chevron.classList.toggle('rotate-180');
            }
        }

        // Toggle Dropdown Menu Log Aktifitas (Desktop & Mobile)
        function toggleActivityMenu(target) {
            const submenu = document.getElementById(target === 'desktop' ? 'activitySubmenuDesktop' : 'activitySubmenuMobile');
            const chevron = document.getElementById(target === 'desktop' ? 'activityChevronDesktop' : 'activityChevronMobile');
            if (submenu) {
                submenu.classList.toggle('hidden');
            }
            if (chevron) {
                chevron.classList.toggle('rotate-180');
            }
        }

        // Toggle Dropdown Menu Profil Pengguna di Pojok Kanan Atas
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('userProfileDropdownMenu');
            const chevron = document.getElementById('userProfileChevron');
            const btn = document.getElementById('userProfileDropdownBtn');
            if (dropdown) {
                const isHidden = dropdown.classList.toggle('hidden');
                if (btn) btn.setAttribute('aria-expanded', !isHidden);
            }
            if (chevron) {
                chevron.classList.toggle('rotate-180');
            }
        }

        // Tutup dropdown profil jika mengklik di luar area
        document.addEventListener('click', function(event) {
            const container = document.getElementById('userProfileContainer');
            const dropdown = document.getElementById('userProfileDropdownMenu');
            const chevron = document.getElementById('userProfileChevron');
            const btn = document.getElementById('userProfileDropdownBtn');
            if (container && dropdown && !container.contains(event.target)) {
                dropdown.classList.add('hidden');
                if (chevron) chevron.classList.remove('rotate-180');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            }
        });

        // Tutup dropdown profil jika menekan tombol Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const dropdown = document.getElementById('userProfileDropdownMenu');
                const chevron = document.getElementById('userProfileChevron');
                const btn = document.getElementById('userProfileDropdownBtn');
                if (dropdown && !dropdown.classList.contains('hidden')) {
                    dropdown.classList.add('hidden');
                    if (chevron) chevron.classList.remove('rotate-180');
                    if (btn) btn.setAttribute('aria-expanded', 'false');
                }
            }
        });
    </script>
    @endif

    @yield('scripts')
</body>
</html>
