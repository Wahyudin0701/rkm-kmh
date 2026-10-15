@extends('layouts.app')

@section('title', 'Manajemen Role - Super Admin | PT Kerinci Merangin Hidro')

@section('content')
@php
    $currentRole = request('role', 'all');
    $isAdminCategory = in_array($currentRole, ['admin_group', 'super_admin', 'hse_admin', 'qma_admin', 'pg_admin']);
    $isGateCategory = ($currentRole === 'gate_officer');
    $isEmployeeCategory = ($currentRole === 'employee');
    $isAllCategory = ($currentRole === 'all' || empty($currentRole));
    $adminCount = $stats['super_admin'] + $stats['hse_admin'] + $stats['qma_admin'] + $stats['pg_admin'];
@endphp
<div class="w-full space-y-6">

    <!-- Header Judul & Tombol Tambah Akun -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-5 border-b border-slate-100">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-[#1b5e20] mb-1">Super Admin · PT Kerinci Merangin Hidro</p>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Manajemen Role & Hak Akses
            </h2>
            <p class="text-xs text-slate-400 mt-1">
                Kelola akun pengguna sistem: administrator departemen, satpam pos gerbang, dan karyawan.
            </p>
        </div>

        <!-- Tombol Tambah Akun Baru -->
        <div class="w-full sm:w-auto flex-shrink-0">
            <button type="button"
                    onclick="openAddUserModal('{{ $currentRole === 'employee' ? 'employee' : ($currentRole === 'gate_officer' ? 'gate_officer' : '') }}')" 
                    class="w-full sm:w-auto bg-[#1b5e20] hover:bg-[#144919] text-white px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition shadow-sm cursor-pointer">
                <i class="fa-solid fa-user-plus text-xs"></i>
                <span>Tambah Akun Baru</span>
            </button>
        </div>
    </div>

    <!-- Navigasi Tab Kategori Akun (Simple Tanpa Icon) -->
    <div class="flex items-center gap-2 border-b border-slate-200/80 pb-3 overflow-x-auto no-scrollbar">
        <!-- Tab: Semua Akun -->
        <a href="{{ route('superadmin.roles.index', array_merge(request()->except('role', 'page'), ['role' => 'all'])) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $isAllCategory ? 'bg-[#1b5e20] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
            <span>Semua Akun</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] {{ $isAllCategory ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $stats['total'] }}</span>
        </a>

        <!-- Tab: Administrator (Super Admin, HSE, QMA, PG) -->
        <a href="{{ route('superadmin.roles.index', array_merge(request()->except('role', 'page'), ['role' => 'admin_group'])) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $isAdminCategory ? 'bg-emerald-800 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
            <span>Administrator</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] {{ $isAdminCategory ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $adminCount }}</span>
        </a>

        <!-- Tab: Satpam Gerbang -->
        <a href="{{ route('superadmin.roles.index', array_merge(request()->except('role', 'page'), ['role' => 'gate_officer'])) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $isGateCategory ? 'bg-slate-800 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
            <span>Satpam Gerbang</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] {{ $isGateCategory ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $stats['gate_officer'] }}</span>
        </a>

        <!-- Tab: Karyawan -->
        <a href="{{ route('superadmin.roles.index', array_merge(request()->except('role', 'page'), ['role' => 'employee'])) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap {{ $isEmployeeCategory ? 'bg-teal-700 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
            <span>Karyawan</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] {{ $isEmployeeCategory ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $stats['employee'] }}</span>
        </a>
    </div>

    <!-- Tabel Kontainer Akun Pengguna Sesuai Tab Kategori -->
    <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

        <!-- Tabel Header: Judul Kategori + Form Pencarian -->
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-800">
                    @if($isAdminCategory)
                        Daftar Administrator Sistem
                    @elseif($isGateCategory)
                        Daftar Petugas Satpam Pos Gerbang
                    @elseif($isEmployeeCategory)
                        Daftar Akun Pengguna Karyawan
                    @else
                        Daftar Seluruh Akun Pengguna
                    @endif
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">
                    @if(request('search'))
                        Menampilkan hasil pencarian: <span class="font-semibold text-slate-600">"{{ request('search') }}"</span>
                    @elseif($isAdminCategory)
                        Kelola akun pengelola departemen HSE, QMA, PG & Super Admin
                    @elseif($isGateCategory)
                        Akun satpam operasional verifikasi akses kendaraan keluar/masuk pos
                    @elseif($isEmployeeCategory)
                        Akun staf & driver proyek terkait status SIMPER
                    @else
                        Total {{ $users->total() }} akun pengguna terdaftar di sistem
                    @endif
                </p>
            </div>

            <!-- Pencarian Cepat -->
            <form method="GET" action="{{ route('superadmin.roles.index') }}" class="flex items-center gap-2 w-full sm:w-auto">
                <input type="hidden" name="role" value="{{ request('role', 'all') }}">
                <div class="relative w-full sm:w-72">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           id="roleSearchInput"
                           value="{{ request('search') }}" 
                           placeholder="Cari nama, username, email..." 
                           class="w-full text-xs pl-9 pr-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 focus:bg-white transition">
                </div>

                <div id="roleResetContainer" class="flex items-center">
                    @if(request('search'))
                        <a href="{{ route('superadmin.roles.index', ['role' => request('role', 'all')]) }}" 
                           id="roleResetBtn"
                           class="text-xs text-rose-500 hover:text-rose-700 font-bold flex items-center gap-1 transition whitespace-nowrap px-1">
                            <i class="fa-solid fa-xmark text-[11px]"></i>
                            <span>Reset</span>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4 w-12 text-center">No</th>

                        @if($isGateCategory)
                            <!-- Kolom Spesifik Satpam Gerbang -->
                            <th class="py-3 px-4">Nama Petugas</th>
                            <th class="py-3 px-4">ID Badge & Username</th>
                            <th class="py-3 px-4 text-center">Regu Jaga</th>
                            <th class="py-3 px-4">Penempatan Pos Gerbang</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        @elseif($isEmployeeCategory)
                            <!-- Kolom Spesifik Karyawan -->
                            <th class="py-3 px-4">Nama Karyawan</th>
                            <th class="py-3 px-4">NIK / Username</th>
                            <th class="py-3 px-4">Divisi & Jabatan</th>
                            <th class="py-3 px-4 text-center">Status SIMPER</th>
                            <th class="py-3 px-4">Terdaftar</th>
                        @elseif($isAdminCategory)
                            <!-- Kolom Spesifik Administrator -->
                            <th class="py-3 px-4">Nama Lengkap</th>
                            <th class="py-3 px-4">Username & Email</th>
                            <th class="py-3 px-4 text-center">Role / Departemen</th>
                            <th class="py-3 px-4">Cakupan Hak Akses</th>
                            <th class="py-3 px-4">Terdaftar</th>
                        @else
                            <!-- Kolom Global (Semua Akun) -->
                            <th class="py-3 px-4">Nama Lengkap</th>
                            <th class="py-3 px-4">Username & Email</th>
                            <th class="py-3 px-4 text-center">Role / Peran</th>
                            <th class="py-3 px-4">Detail Afiliasi</th>
                            <th class="py-3 px-4">Terdaftar</th>
                        @endif

                        <th class="py-3 px-4 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody id="roleTableBody" class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($users as $index => $u)
                        <tr class="hover:bg-slate-50/60 transition group role-row">
                            <!-- Nomor -->
                            <td class="py-3.5 px-4 text-center font-medium text-slate-400">
                                {{ $users->firstItem() + $index }}
                            </td>

                            @if($isGateCategory)
                                <!-- 1. Tampilan Kolom Khusus Satpam -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-700 text-white font-black text-xs flex items-center justify-center flex-shrink-0 shadow-2xs">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 group-hover:text-[#1b5e20] transition">{{ $u->name }}</p>
                                            <p class="text-[11px] text-slate-400">{{ $u->email }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <p class="font-mono font-bold text-slate-900">{{ optional($u->securityGuard)->badge_id ?? $u->username }}</p>
                                    <p class="text-[11px] text-slate-400 font-mono">{{ $u->username }}</p>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    @if(optional($u->securityGuard)->team)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-800 border border-slate-300 shadow-2xs">
                                            Regu {{ optional($u->securityGuard)->team }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">-</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4">
                                    @php
                                        $guardGate = optional($u->securityGuard)->gate ?? $u->gate;
                                    @endphp
                                    @if($guardGate)
                                        <span class="inline-flex items-center space-x-1.5 text-slate-800 font-bold">
                                            <i class="fa-solid fa-location-dot text-rose-500 text-xs"></i>
                                            <span>{{ $guardGate->name }}</span>
                                        </span>
                                    @else
                                        <span class="text-amber-600 font-medium italic text-[11px]">(Semua Pos / Bebas)</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    @if($u->securityGuard && $u->securityGuard->is_active === false)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">Nonaktif</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Aktif</span>
                                    @endif
                                </td>

                            @elseif($isEmployeeCategory)
                                <!-- 2. Tampilan Kolom Khusus Karyawan -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-full bg-teal-600 text-white font-black text-xs flex items-center justify-center flex-shrink-0 shadow-2xs">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 group-hover:text-[#1b5e20] transition">{{ $u->name }}</p>
                                            <p class="text-[11px] text-slate-400">{{ $u->email }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <p class="font-mono font-bold text-slate-900">{{ optional($u->employee)->nik ?? $u->username }}</p>
                                    <p class="text-[11px] text-slate-400 font-mono">User: {{ $u->username }}</p>
                                </td>

                                <td class="py-3.5 px-4">
                                    @if($u->employee)
                                        <p class="font-semibold text-slate-800">{{ $u->employee->division ?? '-' }}</p>
                                        <p class="text-[11px] text-slate-500">{{ $u->employee->position ?? $u->employee->unit_kerja }}</p>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">-</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    @if($u->employee && $u->employee->is_operator)
                                        @if($u->employee->hasValidSimper())
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                SIMPER Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-300">
                                                SIMPER Expired
                                            </span>
                                        @endif
                                    @elseif($u->employee)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                            Non-Driver
                                        </span>
                                    @else
                                        <span class="text-slate-400 italic text-[11px]">-</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                    {{ $u->created_at ? $u->created_at->format('d M Y') : '-' }}
                                </td>

                            @elseif($isAdminCategory)
                                <!-- 3. Tampilan Kolom Khusus Administrator -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs text-white flex-shrink-0 shadow-2xs
                                            {{ in_array($u->role, ['super_admin', 'superadmin', 'admin']) ? 'bg-amber-600' : '' }}
                                            {{ $u->role === 'hse_admin' ? 'bg-[#1b5e20]' : '' }}
                                            {{ $u->role === 'qma_admin' ? 'bg-blue-600' : '' }}
                                            {{ $u->role === 'pg_admin' ? 'bg-purple-600' : '' }}
                                        ">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 group-hover:text-[#1b5e20] transition">
                                                {{ $u->name }}
                                                @if($u->id === auth()->id())
                                                    <span class="ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800">Anda</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <p class="font-mono font-bold text-slate-800">{{ $u->username }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $u->email }}</p>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    @if(in_array($u->role, ['super_admin', 'superadmin', 'admin']))
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300 shadow-2xs">
                                            Super Admin
                                        </span>
                                    @elseif($u->role === 'hse_admin')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300 shadow-2xs">
                                            Admin HSE
                                        </span>
                                    @elseif($u->role === 'qma_admin')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-blue-100 text-blue-900 border border-blue-300 shadow-2xs">
                                            Admin QMA
                                        </span>
                                    @elseif($u->role === 'pg_admin')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-purple-100 text-purple-900 border border-purple-300 shadow-2xs">
                                            Admin PG
                                        </span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-slate-600 text-xs">
                                    @if(in_array($u->role, ['super_admin', 'superadmin', 'admin']))
                                        <span class="font-medium text-amber-900">Hak Penuh Seluruh Sistem</span>
                                    @elseif($u->role === 'hse_admin')
                                        <span class="font-medium text-emerald-900">Master Data SIMPER & K3</span>
                                    @elseif($u->role === 'qma_admin')
                                        <span class="font-medium text-blue-900">Armada LV, Unit & Log Aktivitas</span>
                                    @elseif($u->role === 'pg_admin')
                                        <span class="font-medium text-purple-900">Manajemen Regu & Pos Satpam</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                    {{ $u->created_at ? $u->created_at->format('d M Y') : '-' }}
                                </td>

                            @else
                                <!-- 4. Tampilan Kolom Tab Semua Akun -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-xs text-white flex-shrink-0 shadow-2xs
                                            {{ in_array($u->role, ['super_admin', 'superadmin', 'admin']) ? 'bg-amber-600' : '' }}
                                            {{ $u->role === 'hse_admin' ? 'bg-[#1b5e20]' : '' }}
                                            {{ $u->role === 'qma_admin' ? 'bg-blue-600' : '' }}
                                            {{ $u->role === 'pg_admin' ? 'bg-purple-600' : '' }}
                                            {{ $u->role === 'gate_officer' ? 'bg-slate-700' : '' }}
                                            {{ $u->role === 'employee' ? 'bg-teal-600' : '' }}
                                        ">
                                            {{ strtoupper(substr($u->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 group-hover:text-[#1b5e20] transition">
                                                {{ $u->name }}
                                                @if($u->id === auth()->id())
                                                    <span class="ml-1 text-[10px] font-bold px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800">Anda</span>
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <p class="font-mono font-bold text-slate-800">{{ $u->username }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $u->email }}</p>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    @if(in_array($u->role, ['super_admin', 'superadmin', 'admin']))
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300 shadow-2xs">Super Admin</span>
                                    @elseif($u->role === 'hse_admin')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-300 shadow-2xs">Admin HSE</span>
                                    @elseif($u->role === 'qma_admin')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-blue-100 text-blue-900 border border-blue-300 shadow-2xs">Admin QMA</span>
                                    @elseif($u->role === 'pg_admin')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-purple-100 text-purple-900 border border-purple-300 shadow-2xs">Admin PG</span>
                                    @elseif($u->role === 'gate_officer')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-800 border border-slate-300 shadow-2xs">Satpam Gerbang</span>
                                    @elseif($u->role === 'employee')
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[11px] font-bold bg-teal-100 text-teal-900 border border-teal-300 shadow-2xs">Karyawan</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-xs">
                                    @if($u->role === 'gate_officer')
                                        @php
                                            $gGate = optional($u->securityGuard)->gate ?? $u->gate;
                                        @endphp
                                        <span class="inline-flex items-center space-x-1 font-semibold text-slate-700">
                                            <i class="fa-solid fa-location-dot text-rose-500 text-[11px]"></i>
                                            <span>{{ $gGate ? $gGate->name : 'Pos Jaga' }}</span>
                                            @if(optional($u->securityGuard)->team)
                                                <span class="text-slate-400 font-normal">· Regu {{ $u->securityGuard->team }}</span>
                                            @endif
                                        </span>
                                    @elseif($u->role === 'employee')
                                        <span class="text-slate-600">
                                            NIK: <strong class="font-mono text-slate-800">{{ optional($u->employee)->nik ?? '-' }}</strong>
                                            @if(optional($u->employee)->division)
                                                · {{ $u->employee->division }}
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-slate-500">Administrator Sistem</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                    {{ $u->created_at ? $u->created_at->format('d M Y') : '-' }}
                                </td>
                            @endif

                            <!-- Tombol Aksi (Detail, Edit & Hapus) -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="flex items-center justify-start space-x-1.5">
                                    <!-- Tombol Detail -->
                                    <button type="button" 
                                            onclick='openDetailUserModal(@json($u))' 
                                            class="w-7 h-7 rounded-lg text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 flex items-center justify-center transition cursor-pointer" 
                                            title="Lihat Detail Akun & Hak Akses">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </button>

                                    <!-- Tombol Edit -->
                                    <button type="button" 
                                            onclick='openEditUserModal(@json($u))' 
                                            class="w-7 h-7 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 flex items-center justify-center transition cursor-pointer" 
                                            title="Edit Akun">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>

                                    <!-- Tombol Hapus (Kecuali Akun Sendiri) -->
                                    @if($u->id !== auth()->id())
                                        <button type="button" 
                                                onclick='openDeleteUserModal(@json($u))' 
                                                class="w-7 h-7 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 flex items-center justify-center transition cursor-pointer" 
                                                title="Hapus Akun">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="serverRoleEmptyRow">
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                @if(request('search'))
                                    <i class="fa-solid fa-magnifying-glass text-3xl mb-3 block text-slate-300"></i>
                                    <p class="text-sm font-semibold text-slate-700">
                                        Data "<span class="text-rose-600 font-bold">{{ request('search') }}</span>" tidak ditemukan di tabel
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        Pastikan ejaan nama, username, NIK, atau email yang dicari sudah benar.
                                    </p>
                                    <div class="mt-4">
                                        <a href="{{ route('superadmin.roles.index', ['role' => request('role', 'all')]) }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                            <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                            <span>Hapus Pencarian</span>
                                        </a>
                                    </div>
                                @else
                                    <div class="w-12 h-12 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3 text-lg">
                                        <i class="fa-solid fa-user-slash"></i>
                                    </div>
                                    <p class="font-bold text-slate-600 text-sm">Tidak Ada Data Akun</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Tidak ditemukan akun pengguna yang cocok dengan kriteria filter.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div id="rolePaginationContainer" class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>

<!-- ============================================================== -->
<!-- 1. MODAL TAMBAH AKUN BARU                                       -->
<!-- ============================================================== -->
<div id="addUserModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" onclick="closeAddUserModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-7 sm:p-8 border border-slate-100 overflow-hidden">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                    Tambah Akun Baru
                </h3>
                <button type="button" onclick="closeAddUserModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form action="{{ route('superadmin.users.store') }}" method="POST" class="mt-5 space-y-4">
                @csrf
                <!-- Nama Lengkap -->
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Nama Lengkap / Identitas <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: Budi Santoso atau Pos Gerbang Adit 1" 
                           class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>

                <!-- Username & Email (Grid 2 Kolom) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label id="addUsernameLabel" class="block text-sm font-bold text-slate-800 mb-2">Username <span class="text-rose-500">*</span></label>
                        <input type="text" name="username" id="addUsernameInput" required placeholder="Contoh: budi.hse" 
                               class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 font-mono transition">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-800 mb-2">Alamat Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" id="addEmailInput" required placeholder="Contoh: budi@kmh.co.id" 
                               class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                    </div>
                </div>

                <!-- Role / Hak Akses -->
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Role / Peran Sistem <span class="text-rose-500">*</span></label>
                    <select name="role" id="addRoleSelect" onchange="handleRoleChange(this.value, 'add')" required 
                            class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-medium">
                        <option value="super_admin">Super Admin</option>
                        <option value="hse_admin">Admin HSE</option>
                        <option value="qma_admin">Admin QMA</option>
                        <option value="pg_admin">Admin PG</option>
                        <option value="gate_officer">Satpam Gerbang</option>
                        <option value="employee">Karyawan</option>
                    </select>
                </div>

                <!-- Pilihan Penempatan Gerbang (Hanya Tampil Jika Role gate_officer) -->
                <div id="addGateContainer" class="hidden">
                    <label class="block text-sm font-bold text-slate-800 mb-2">Penempatan Pos Gerbang</label>
                    <select name="gate_id" class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] transition">
                        <option value="">-- Pilih Pos Gerbang (Opsional/Bebas Pos) --</option>
                        @foreach($gates as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-400 mt-1.5">Jika dipilih, akun pos jaga ini akan otomatis terkunci ke gerbang terkait saat inspeksi.</p>
                </div>

                <!-- Input Khusus Karyawan (Unit Kerja, Divisi, Jabatan) di bawah Role -->
                <div id="addEmployeeContainer" class="hidden space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-sm font-bold text-slate-800 mb-2">Unit Kerja <span class="text-rose-500">*</span></label>
                            <select name="unit_kerja" id="addUnitKerja"
                                    class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                                <option value="">Pilih Unit Kerja</option>
                                <option value="Pembangkit">Pembangkit</option>
                                <option value="Bendungan">Bendungan</option>
                                <option value="Teknik">Teknik</option>
                                <option value="Umum">Umum</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-800 mb-2">Divisi <span class="text-rose-500">*</span></label>
                            <select name="division" id="addDivision"
                                    class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                                <option value="">Pilih Divisi</option>
                                <option value="INFORMATION & TECHNOLOGY">INFORMATION & TECHNOLOGY</option>
                                <option value="K3">K3 / HSE</option>
                                <option value="Quality Management Asset (QMA)">Quality Management Asset (QMA)</option>
                                <option value="GA">General Affairs (GA)</option>
                                <option value="HR">Human Resources (HR)</option>
                                <option value="Keuangan">Keuangan</option>
                                <option value="Logistik">Logistik / Warehouse</option>
                                <option value="Mechanical">Mechanical</option>
                                <option value="Civil & Inviro">Civil & Inviro</option>
                                <option value="PG">Private Guard (PG)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-800 mb-2">Jabatan <span class="text-rose-500">*</span></label>
                        <input type="text" name="position" id="addPosition" placeholder="Contoh: Staff IT, Operator Lapangan, Driver"
                               class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                    </div>
                </div>

                <!-- Password Awal -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-bold text-slate-800">Password Akun</label>
                        <span class="text-[11px] font-semibold text-slate-400">Default: <strong class="text-emerald-700 font-mono">password</strong></span>
                    </div>
                    <input type="text" name="password" value="password" minlength="6" placeholder="password" 
                           class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 font-mono transition">
                    <p class="text-xs text-slate-400 mt-1.5">Otomatis terisi default <strong class="font-mono text-slate-600">password</strong>. Bisa diganti jika ingin menentukan password kustom.</p>
                </div>

                <!-- Modal Actions -->
                <div class="pt-6 border-t border-slate-100 flex items-center justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeAddUserModal()" class="px-6 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-sm transition">
                        Batal
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-sm transition shadow-sm">
                        Simpan Akun
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- 2. MODAL EDIT AKUN PENGGUNA                                     -->
<!-- ============================================================== -->
<div id="editUserModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" onclick="closeEditUserModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-7 sm:p-8 border border-slate-100 overflow-hidden">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                    Perbarui Data Akun
                </h3>
                <button type="button" onclick="closeEditUserModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Modal Form Edit -->
            <form id="editUserForm" method="POST" class="mt-5 space-y-4">
                @csrf
                @method('PUT')

                <!-- Nama Lengkap -->
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Nama Lengkap / Identitas <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" id="editName" required 
                           class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                </div>

                <!-- Username & Email (Grid 2 Kolom) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label id="editUsernameLabel" class="block text-sm font-bold text-slate-800 mb-2">Username <span class="text-rose-500">*</span></label>
                        <input type="text" name="username" id="editUsername" required 
                               class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 font-mono transition">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-800 mb-2">Alamat Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" id="editEmail" required 
                               class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                    </div>
                </div>

                <!-- Role / Hak Akses -->
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Role / Peran Sistem <span class="text-rose-500">*</span></label>
                    <select name="role" id="editRoleSelect" onchange="handleRoleChange(this.value, 'edit')" required 
                            class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition font-medium">
                        <option value="super_admin">Super Admin</option>
                        <option value="hse_admin">Admin HSE</option>
                        <option value="qma_admin">Admin QMA</option>
                        <option value="pg_admin">Admin PG</option>
                        <option value="gate_officer">Satpam Gerbang</option>
                        <option value="employee">Karyawan</option>
                    </select>
                </div>

                <!-- Pilihan Penempatan Gerbang (Hanya Tampil Jika Role gate_officer) -->
                <div id="editGateContainer" class="hidden">
                    <label class="block text-sm font-bold text-slate-800 mb-2">Penempatan Pos Gerbang</label>
                    <select name="gate_id" id="editGateId" class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 text-slate-800 focus:outline-none focus:border-[#1b5e20] transition">
                        <option value="">-- Pilih Pos Gerbang (Opsional/Bebas Pos) --</option>
                        @foreach($gates as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Input Khusus Karyawan (Unit Kerja, Divisi, Jabatan) di bawah Role -->
                <div id="editEmployeeContainer" class="hidden space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-sm font-bold text-slate-800 mb-2">Unit Kerja <span class="text-rose-500">*</span></label>
                            <select name="employee_unit_kerja" id="editEmployeeUnitKerja"
                                    class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                                <option value="">Pilih Unit Kerja</option>
                                <option value="Pembangkit">Pembangkit</option>
                                <option value="Bendungan">Bendungan</option>
                                <option value="Teknik">Teknik</option>
                                <option value="Umum">Umum</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-800 mb-2">Divisi <span class="text-rose-500">*</span></label>
                            <select name="employee_division" id="editEmployeeDivision"
                                    class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                                <option value="">Pilih Divisi</option>
                                <option value="INFORMATION & TECHNOLOGY">INFORMATION & TECHNOLOGY</option>
                                <option value="K3">K3 / HSE</option>
                                <option value="Quality Management Asset (QMA)">Quality Management Asset (QMA)</option>
                                <option value="GA">General Affairs (GA)</option>
                                <option value="HR">Human Resources (HR)</option>
                                <option value="Keuangan">Keuangan</option>
                                <option value="Logistik">Logistik / Warehouse</option>
                                <option value="Mechanical">Mechanical</option>
                                <option value="Civil & Inviro">Civil & Inviro</option>
                                <option value="PG">Private Guard (PG)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-800 mb-2">Jabatan <span class="text-rose-500">*</span></label>
                        <input type="text" name="employee_position" id="editEmployeePosition" placeholder="Contoh: Staff IT, Driver Operasional"
                               class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                    </div>
                </div>

                <!-- Reset Password (Opsional) -->
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/70">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-bold text-slate-800">Ganti Password (Opsional)</label>
                        <button type="button" 
                                onclick="handleResetPassword()" 
                                class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-xl text-xs font-bold text-amber-800 bg-amber-100/90 hover:bg-amber-200 border border-amber-300 transition cursor-pointer"
                                title="Reset password akun ini langsung menjadi 'password'">
                            <i class="fa-solid fa-rotate-left text-[11px]"></i>
                            <span>Reset Password</span>
                        </button>
                    </div>
                    <input type="password" name="password" id="editPassword" minlength="6" placeholder="Kosongkan jika tidak ingin mengubah password..." 
                           class="w-full text-sm px-4 py-2.5 rounded-xl border border-slate-200 text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] bg-white transition">
                    <p class="text-xs text-slate-400 mt-1.5">Isi minimal 6 karakter jika ingin mengganti manual, atau klik <strong>Reset Password</strong> untuk me-reset ke default <em>"password"</em>.</p>
                </div>

                <!-- Modal Actions -->
                <div class="pt-6 border-t border-slate-100 flex items-center justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeEditUserModal()" class="px-6 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-sm transition">
                        Batal
                    </button>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-sm transition shadow-sm">
                        Perbarui Akun
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- 3. MODAL KONFIRMASI HAPUS AKUN                                  -->
<!-- ============================================================== -->
<div id="deleteUserModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" onclick="closeDeleteUserModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 text-center border border-slate-100">
            <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3 text-lg">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="text-base font-bold text-slate-900">Hapus Akun Pengguna?</h3>
            <p class="text-xs text-slate-500 mt-1 mb-5">
                Akun <span id="deleteUserName" class="font-bold text-slate-800"></span> akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.
            </p>

            <form id="deleteUserForm" method="POST" class="flex items-center justify-center space-x-2.5">
                @csrf
                @method('DELETE')
                <button type="button" onclick="closeDeleteUserModal()" class="w-1/2 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="w-1/2 py-2.5 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white transition shadow-sm cursor-pointer">
                    Ya, Hapus
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- 3b. MODAL KONFIRMASI RESET PASSWORD                             -->
<!-- ============================================================== -->
<div id="confirmResetPasswordModal" class="fixed inset-0 z-[60] hidden">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" onclick="closeConfirmResetPasswordModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 text-center border border-slate-100">
            <h3 class="text-base font-bold text-slate-900">Reset Password ke Default?</h3>
            <p class="text-xs text-slate-500 mt-2 mb-6">
                Password akun <strong id="resetTargetUserName" class="text-slate-800"></strong> akan direset menjadi default <span class="font-mono font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">password</span>.
            </p>

            <form id="resetPasswordDirectForm" method="POST" class="flex items-center justify-center space-x-2.5">
                @csrf
                <button type="button" onclick="closeConfirmResetPasswordModal()" class="w-1/2 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 border border-slate-200 transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="w-1/2 py-2.5 rounded-xl text-xs font-bold bg-[#1b5e20] hover:bg-[#144919] text-white transition shadow-sm cursor-pointer">
                    Ya, Reset
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- 4. MODAL DETAIL AKUN & ROLE                                    -->
<!-- ============================================================== -->
<div id="detailUserModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity" onclick="closeDetailUserModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-7 sm:p-8 border border-slate-100 overflow-hidden">
            <!-- Header Modal Sederhana Tanpa Icon Sesuai Referensi -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-xl font-bold text-slate-900 tracking-tight">
                    Detail Data Akun
                </h3>
                <button type="button" onclick="closeDetailUserModal()" class="text-slate-400 hover:text-slate-600 transition p-1">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="mt-5 space-y-4">
                <!-- Nama Lengkap / Identitas -->
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Nama Lengkap / Identitas</label>
                    <div class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 font-bold flex items-center justify-between">
                        <span id="detailName">-</span>
                        <span id="detailSelfBadge" class="hidden text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">Anda</span>
                    </div>
                </div>

                <!-- Username & Email (Grid 2 Kolom) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-sm font-bold text-slate-800 mb-2">Username</label>
                        <div class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 font-mono font-bold flex items-center">
                            <span id="detailUsername">-</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-800 mb-2">Alamat Email</label>
                        <div class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 font-medium flex items-center truncate">
                            <span id="detailEmail">-</span>
                        </div>
                    </div>
                </div>

                <!-- Role / Hak Akses & Penempatan Pos Gerbang (Dinamis Sesuai Role) -->
                <div id="detailRoleGrid" class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div id="detailRoleCol" class="sm:col-span-2">
                        <label class="block text-sm font-bold text-slate-800 mb-2">Role / Peran Sistem</label>
                        <div class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white flex items-center">
                            <span id="detailRoleBadge" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border">
                                <span id="detailRoleText">-</span>
                            </span>
                        </div>
                    </div>
                    <div id="detailGateContainer" class="hidden">
                        <label class="block text-sm font-bold text-slate-800 mb-2">Penempatan Pos Gerbang</label>
                        <div class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 font-semibold flex items-center">
                            <span id="detailGate">-</span>
                        </div>
                    </div>
                </div>

                <!-- ID Badge Satpam (Jika Akun Satpam) -->
                <div id="detailBadgeContainer" class="hidden">
                    <label class="block text-sm font-bold text-slate-800 mb-2">ID Badge & Regu Satpam</label>
                    <div class="w-full text-sm px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 font-mono font-bold flex items-center">
                        <span id="detailBadge">-</span>
                    </div>
                </div>

                <!-- Data Profil Karyawan (Jika Terhubung dengan Data Karyawan) -->
                <div id="detailEmployeeContainer" class="hidden">
                    <label class="block text-sm font-bold text-slate-800 mb-2">Informasi Karyawan / Driver</label>
                    <div class="w-full text-xs px-4 py-3 rounded-2xl border border-slate-200 bg-white text-slate-800 space-y-1.5">
                        <div class="flex items-center justify-between">
                            <span>NIK: <strong id="detailEmpNik" class="font-mono text-slate-900">-</strong></span>
                            <span id="detailEmpSimperBadge" class="font-bold px-2.5 py-0.5 rounded-full text-[10px]"></span>
                        </div>
                        <div class="text-slate-500 flex items-center justify-between">
                            <div><span id="detailEmpUnit">-</span> &bull; <span id="detailEmpPosition">-</span></div>
                            <div id="detailEmpSimperDate" class="text-[11px] font-medium text-slate-600"></div>
                        </div>
                    </div>
                </div>

                <!-- Cakupan Hak Akses Modul -->
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/70 space-y-1">
                    <label class="block text-sm font-bold text-slate-800">Cakupan Hak Akses Modul</label>
                    <p id="detailRoleDesc" class="text-xs text-slate-600 leading-relaxed"></p>
                </div>

                <!-- Footer Tombol & Timestamps Sesuai Format Referensi -->
                <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-6">
                    <div class="text-xs text-slate-400 leading-tight">
                        <div>Terdaftar: <span id="detailCreatedAt" class="font-medium text-slate-600">-</span></div>
                        <div class="mt-0.5">Diperbarui: <span id="detailUpdatedAt" class="font-medium text-slate-600">-</span></div>
                    </div>
                    <div class="flex items-center justify-end space-x-3">
                        <button type="button" onclick="closeDetailUserModal()" 
                                class="px-6 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-bold text-sm transition">
                            Tutup
                        </button>
                        <button type="button" onclick="editFromDetailModal()" 
                                class="px-6 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-sm transition shadow-sm">
                            Edit Akun
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    let currentDetailUser = null;

    // Handler Modal Detail
    function openDetailUserModal(user) {
        currentDetailUser = user;
        
        document.getElementById('detailName').textContent = user.name || '-';
        document.getElementById('detailUsername').textContent = user.username || '-';
        document.getElementById('detailEmail').textContent = user.email || '-';

        // Self badge
        const selfBadge = document.getElementById('detailSelfBadge');
        if (user.id === {{ auth()->id() }}) {
            selfBadge.classList.remove('hidden');
        } else {
            selfBadge.classList.add('hidden');
        }

        // Role styling & description
        let roleText = 'Pengguna';
        let roleDesc = '';
        let roleBadgeClass = 'bg-slate-100 text-slate-800 border-slate-300';
        let roleColor = 'bg-slate-600';

        const r = user.role;
        if (r === 'super_admin' || r === 'superadmin' || r === 'admin') {
            roleText = 'Super Admin';
            roleDesc = 'Memiliki hak akses penuh tanpa batas ke seluruh modul sistem (HSE, QMA, PG, Verifikasi Pos Gerbang, dan Manajemen Pengguna).';
            roleBadgeClass = 'bg-amber-100 text-amber-900 border-amber-300';
            roleColor = 'bg-amber-600';
        } else if (r === 'hse_admin') {
            roleText = 'Admin HSE';
            roleDesc = 'Mengelola master data SIMPER karyawan, validasi sertifikasi K3, dan kepatuhan izin berkendara di area operasional.';
            roleBadgeClass = 'bg-emerald-100 text-emerald-900 border-emerald-300';
            roleColor = 'bg-[#1b5e20]';
        } else if (r === 'qma_admin') {
            roleText = 'Admin QMA';
            roleDesc = 'Mengelola master data armada kendaraan (Light Vehicle & Unit Support), QR Code unit, serta monitoring log aktivitas dan summary km kendaraan.';
            roleBadgeClass = 'bg-blue-100 text-blue-900 border-blue-300';
            roleColor = 'bg-blue-600';
        } else if (r === 'pg_admin') {
            roleText = 'Admin PG';
            roleDesc = 'Mengelola data petugas Satpam, penempatan regu jaga, pemetaan pos gerbang, dan audit log verifikasi lalu lintas pos gerbang.';
            roleBadgeClass = 'bg-purple-100 text-purple-900 border-purple-300';
            roleColor = 'bg-purple-600';
        } else if (r === 'gate_officer') {
            roleText = 'Satpam Gerbang';
            roleDesc = 'Petugas operasional lapangan di pos gerbang yang bertugas memindai QR code dan memverifikasi armada saat keluar/masuk.';
            roleBadgeClass = 'bg-slate-100 text-slate-800 border-slate-300';
            roleColor = 'bg-slate-600';
        } else if (r === 'employee') {
            roleText = 'Karyawan';
            roleDesc = 'Karyawan PT Kerinci Merangin Hidro yang memiliki akses form aktivitas kendaraan operasional proyek (driver/operator) serta data kepemilikan izin SIMPER.';
            roleBadgeClass = 'bg-teal-100 text-teal-900 border-teal-300';
            roleColor = 'bg-teal-600';
        }

        document.getElementById('detailRoleBadge').className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border ' + roleBadgeClass;
        document.getElementById('detailRoleText').textContent = roleText;
        document.getElementById('detailRoleDesc').textContent = roleDesc;

        // Pos Gerbang (Kondisional: Hanya tampil untuk Satpam atau akun yang memiliki penempatan gerbang)
        const isGateRole = (r === 'gate_officer') || (user.gate !== null && user.gate !== undefined);
        const gateContainer = document.getElementById('detailGateContainer');
        const roleCol = document.getElementById('detailRoleCol');

        if (isGateRole) {
            gateContainer.classList.remove('hidden');
            roleCol.classList.remove('sm:col-span-2');
            const gateName = user.gate 
                ? user.gate.name 
                : 'Semua Pos Gerbang (Bebas)';
            document.getElementById('detailGate').textContent = gateName;
        } else {
            gateContainer.classList.add('hidden');
            roleCol.classList.add('sm:col-span-2');
        }

        // Badge Satpam & Regu Jaga
        const guard = user.security_guard || user.securityGuard;
        const badgeContainer = document.getElementById('detailBadgeContainer');
        if (guard && (guard.badge_id || guard.team)) {
            let badgeInfo = guard.badge_id || '-';
            if (guard.team) {
                badgeInfo += ' (Regu ' + guard.team + ')';
            }
            document.getElementById('detailBadge').textContent = badgeInfo;
            badgeContainer.classList.remove('hidden');
        } else {
            badgeContainer.classList.add('hidden');
        }

        // Informasi Karyawan (Jika Terhubung)
        const emp = user.employee;
        const empContainer = document.getElementById('detailEmployeeContainer');
        if (emp && emp.nik) {
            document.getElementById('detailEmpNik').textContent = emp.nik;
            const unitText = (emp.division || '') + (emp.unit_kerja ? ' (' + emp.unit_kerja + ')' : '');
            document.getElementById('detailEmpUnit').textContent = unitText || '-';
            document.getElementById('detailEmpPosition').textContent = emp.position || '-';
            
            const simperBadge = document.getElementById('detailEmpSimperBadge');
            const simperDate = document.getElementById('detailEmpSimperDate');
            if (emp.is_operator) {
                if (emp.simper_expired_at) {
                    const expDate = new Date(emp.simper_expired_at);
                    const now = new Date();
                    now.setHours(0,0,0,0);
                    if (expDate >= now) {
                        simperBadge.className = 'font-bold px-2 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800 border border-emerald-300';
                        simperBadge.textContent = 'SIMPER Aktif';
                    } else {
                        simperBadge.className = 'font-bold px-2 py-0.5 rounded-full text-[10px] bg-rose-100 text-rose-800 border border-rose-300';
                        simperBadge.textContent = 'SIMPER Kedaluwarsa';
                    }
                    simperDate.textContent = 's.d. ' + expDate.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                } else {
                    simperBadge.className = 'font-bold px-2 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-800 border border-amber-300';
                    simperBadge.textContent = 'Operator (Tanpa SIMPER)';
                    simperDate.textContent = '-';
                }
            } else {
                simperBadge.className = 'font-bold px-2 py-0.5 rounded-full text-[10px] bg-slate-100 text-slate-600 border border-slate-200';
                simperBadge.textContent = 'Non-Driver';
                simperDate.textContent = '';
            }
            empContainer.classList.remove('hidden');
        } else {
            empContainer.classList.add('hidden');
        }

        // Tanggal Dibuat & Diperbarui
        if (user.created_at) {
            const d = new Date(user.created_at);
            document.getElementById('detailCreatedAt').textContent = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) + ' WIB';
        } else {
            document.getElementById('detailCreatedAt').textContent = '-';
        }

        if (user.updated_at) {
            const d = new Date(user.updated_at);
            document.getElementById('detailUpdatedAt').textContent = d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) + ' WIB';
        } else {
            document.getElementById('detailUpdatedAt').textContent = '-';
        }

        document.getElementById('detailUserModal').classList.remove('hidden');
    }

    function closeDetailUserModal() {
        document.getElementById('detailUserModal').classList.add('hidden');
    }

    function editFromDetailModal() {
        if (currentDetailUser) {
            closeDetailUserModal();
            openEditUserModal(currentDetailUser);
        }
    }

    // Handler Modal Tambah
    function openAddUserModal(defaultRole) {
        if (defaultRole) {
            document.getElementById('addRoleSelect').value = defaultRole;
        }
        handleRoleChange(document.getElementById('addRoleSelect').value, 'add');
        document.getElementById('addUserModal').classList.remove('hidden');
    }
    function closeAddUserModal() {
        document.getElementById('addUserModal').classList.add('hidden');
    }

    // Toggle Dropdown Gate & Input Karyawan dinamis berdasarkan Role
    function handleRoleChange(role, context) {
        const gateContainer = document.getElementById(context === 'add' ? 'addGateContainer' : 'editGateContainer');
        const empContainer = document.getElementById(context === 'add' ? 'addEmployeeContainer' : 'editEmployeeContainer');
        const usernameLabel = document.getElementById(context === 'add' ? 'addUsernameLabel' : 'editUsernameLabel');
        const usernameInput = document.getElementById(context === 'add' ? 'addUsernameInput' : 'editUsername');

        const unitKerjaInput = document.getElementById(context === 'add' ? 'addUnitKerja' : 'editEmployeeUnitKerja');
        const divisionInput = document.getElementById(context === 'add' ? 'addDivision' : 'editEmployeeDivision');
        const positionInput = document.getElementById(context === 'add' ? 'addPosition' : 'editEmployeePosition');

        if (gateContainer) {
            if (role === 'gate_officer') {
                gateContainer.classList.remove('hidden');
            } else {
                gateContainer.classList.add('hidden');
            }
        }

        if (empContainer) {
            if (role === 'employee') {
                empContainer.classList.remove('hidden');
                if (usernameLabel) {
                    usernameLabel.innerHTML = 'NIK (Nomor Induk Karyawan) <span class="text-rose-500">*</span>';
                }
                if (usernameInput) {
                    usernameInput.placeholder = 'Contoh: 1185 atau 0001';
                }
                if (unitKerjaInput) unitKerjaInput.required = true;
                if (divisionInput) divisionInput.required = true;
                if (positionInput) positionInput.required = true;
            } else {
                empContainer.classList.add('hidden');
                if (usernameLabel) {
                    usernameLabel.innerHTML = 'Username <span class="text-rose-500">*</span>';
                }
                if (usernameInput) {
                    usernameInput.placeholder = 'Contoh: budi.hse';
                }
                if (unitKerjaInput) { unitKerjaInput.required = false; }
                if (divisionInput) { divisionInput.required = false; }
                if (positionInput) { positionInput.required = false; }
            }
        }
    }

    // Handler Modal Edit
    let currentEditUser = null;

    function openEditUserModal(user) {
        currentEditUser = user;
        document.getElementById('editName').value = user.name || '';
        document.getElementById('editUsername').value = user.username || '';
        document.getElementById('editEmail').value = user.email || '';
        document.getElementById('editPassword').value = '';
        
        let role = user.role;
        if (role === 'superadmin' || role === 'admin') {
            role = 'super_admin';
        }
        document.getElementById('editRoleSelect').value = role;
        
        handleRoleChange(role, 'edit');
        document.getElementById('editGateId').value = user.gate_id || '';

        // Isi form employee jika ada
        const emp = user.employee;
        if (emp) {
            document.getElementById('editEmployeeUnitKerja').value = emp.unit_kerja || '';
            document.getElementById('editEmployeeDivision').value = emp.division || '';
            document.getElementById('editEmployeePosition').value = emp.position || '';
        } else {
            document.getElementById('editEmployeeUnitKerja').value = '';
            document.getElementById('editEmployeeDivision').value = '';
            document.getElementById('editEmployeePosition').value = '';
        }

        const form = document.getElementById('editUserForm');
        form.action = "{{ url('superadmin/users') }}/" + user.id;

        document.getElementById('editUserModal').classList.remove('hidden');
    }
    function closeEditUserModal() {
        document.getElementById('editUserModal').classList.add('hidden');
    }

    function handleResetPassword() {
        if (!currentEditUser) return;
        
        document.getElementById('resetTargetUserName').textContent = currentEditUser.name || 'pengguna ini';
        const form = document.getElementById('resetPasswordDirectForm');
        form.action = "{{ url('superadmin/users') }}/" + currentEditUser.id + "/reset-password";
        
        document.getElementById('confirmResetPasswordModal').classList.remove('hidden');
    }

    function closeConfirmResetPasswordModal() {
        document.getElementById('confirmResetPasswordModal').classList.add('hidden');
    }

    // Handler Modal Hapus
    function openDeleteUserModal(user) {
        document.getElementById('deleteUserName').textContent = user.name + ' (' + user.username + ')';
        const form = document.getElementById('deleteUserForm');
        form.action = "{{ url('superadmin/users') }}/" + user.id;

        document.getElementById('deleteUserModal').classList.remove('hidden');
    }
    function closeDeleteUserModal() {
        document.getElementById('deleteUserModal').classList.add('hidden');
    }

    // ==============================================================
    // REAL-TIME CLIENT-SIDE SEARCH (Multi-field Matching & SSR Preservation)
    // ==============================================================
    const roleSearchInput = document.getElementById('roleSearchInput');
    const roleResetContainer = document.getElementById('roleResetContainer');
    const roleTableBody = document.getElementById('roleTableBody');
    const rolePaginationContainer = document.getElementById('rolePaginationContainer');
    const serverRoleEmptyRow = document.getElementById('serverRoleEmptyRow');
    const hasServerRoleSearch = {{ request()->filled('search') ? 'true' : 'false' }};

    function escapeRoleHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    roleSearchInput?.addEventListener('input', function(e) {
        const rawValue = e.target.value;
        const query = rawValue.toLowerCase().trim();
        const rows = document.querySelectorAll('#roleTableBody tr.role-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const match = text.includes(query);
            row.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        if (rolePaginationContainer) {
            rolePaginationContainer.style.display = query.length > 0 ? 'none' : '';
        }

        if (serverRoleEmptyRow) {
            serverRoleEmptyRow.style.display = query.length > 0 ? 'none' : '';
        }

        // Hapus baris pesan kosong pencarian jika ada sebelumnya
        const existingEmptyRow = document.getElementById('roleEmptySearchRow');
        if (existingEmptyRow) {
            existingEmptyRow.remove();
        }

        // Tampilkan pesan jika tidak ada data yang cocok di tabel
        if (visibleCount === 0 && query.length > 0) {
            const emptyTr = document.createElement('tr');
            emptyTr.id = 'roleEmptySearchRow';
            emptyTr.innerHTML = `
                <td colspan="7" class="py-12 text-center text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-3xl mb-3 block text-slate-300"></i>
                    <p class="text-sm font-semibold text-slate-700">
                        Data "<span class="text-rose-600 font-bold">${escapeRoleHtml(rawValue)}</span>" tidak ditemukan di tabel
                    </p>
                    <p class="text-xs text-slate-400 mt-1">
                        Pastikan ejaan nama, username, NIK, atau email yang dicari sudah benar.
                    </p>
                    <div class="mt-4">
                        <button type="button" 
                                onclick="const inp = document.getElementById('roleSearchInput'); if(inp){ inp.value=''; inp.dispatchEvent(new Event('input')); inp.focus(); }" 
                                class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                            <i class="fa-solid fa-rotate-left text-[11px]"></i>
                            <span>Hapus Pencarian</span>
                        </button>
                    </div>
                </td>
            `;
            roleTableBody?.appendChild(emptyTr);
        }

        // Tampilkan/sembunyikan tombol Reset Filter secara real-time saat mengetik
        let resetBtn = document.getElementById('roleResetBtn');
        if (query.length > 0) {
            if (!resetBtn && roleResetContainer) {
                resetBtn = document.createElement('a');
                resetBtn.id = 'roleResetBtn';
                resetBtn.href = "{{ route('superadmin.roles.index', ['role' => request('role', 'all')]) }}";
                resetBtn.className = "text-xs text-rose-500 hover:text-rose-700 font-bold flex items-center gap-1 transition whitespace-nowrap px-1";
                const resetIcon = document.createElement('i');
                resetIcon.className = "fa-solid fa-rotate-left text-[11px]";
                const resetText = document.createElement('span');
                resetText.textContent = "Reset";
                resetBtn.appendChild(resetIcon);
                resetBtn.appendChild(resetText);
                roleResetContainer.appendChild(resetBtn);
            }
        } else if (!hasServerRoleSearch && resetBtn) {
            resetBtn.remove();
        }
    });
</script>
@endsection
