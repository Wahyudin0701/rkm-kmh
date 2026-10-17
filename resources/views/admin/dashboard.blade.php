@extends('layouts.app')

@section('title', 'Dashboard Super Admin - RKM KMH')

@section('content')
<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2.5">
                <span class="w-2.5 h-7 rounded-full bg-indigo-600 inline-block"></span>
                Dashboard Super Admin & RBAC
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Pusat kendali arsitektur sistem, pengelolaan pengguna, dan matriks hak akses Role-Based Access Control (RBAC).
            </p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.users.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                <i class="fa-solid fa-users-gear text-sm"></i>
                <span>Kelola Pengguna</span>
            </a>
            <a href="{{ route('admin.roles.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold shadow-sm transition">
                <i class="fa-solid fa-shield-halved text-indigo-600 text-sm"></i>
                <span>Matriks Role & Izin</span>
            </a>
            <a href="{{ route('admin.activity_logs.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-bold shadow-sm transition">
                <i class="fa-solid fa-clock-rotate-left text-indigo-600 text-sm"></i>
                <span>Log Aktivitas</span>
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between text-emerald-800 text-xs font-semibold">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- Kartu Metrik Utama -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total Pengguna -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden group hover:border-indigo-300 transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Akun</span>
                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-800">{{ $metrics['total_users'] }}</div>
            <p class="text-[11px] text-slate-500 mt-1">Terdaftar dalam sistem</p>
        </div>

        <!-- Total Roles Spatie -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden group hover:border-violet-300 transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Role RBAC</span>
                <div class="w-8 h-8 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-800">{{ $metrics['total_roles'] }}</div>
            <p class="text-[11px] text-slate-500 mt-1">Tingkatan wewenang</p>
        </div>

        <!-- Master Data Operasional (HSE & QMA) -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden group hover:border-emerald-300 transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Master Data</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-database"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-800">{{ $metrics['total_vehicles'] }}</span>
                <span class="text-xs text-slate-400 font-semibold">Unit</span>
                <span class="text-slate-300">/</span>
                <span class="text-lg font-black text-slate-700">{{ $metrics['total_employees'] }}</span>
                <span class="text-xs text-slate-400 font-semibold">Karyawan</span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">Kendaraan & Simper</p>
        </div>

        <!-- Aktivitas Hari Ini -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden group hover:border-amber-300 transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Aktivitas Hari Ini</span>
                <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-800">{{ $metrics['activities_today'] }}</span>
                <span class="text-xs text-slate-400 font-semibold">Total</span>
                @if($metrics['activities_pending'] > 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-700">
                        {{ $metrics['activities_pending'] }} antre
                    </span>
                @endif
            </div>
            <p class="text-[11px] text-slate-500 mt-1">{{ $metrics['activities_approved'] }} sudah diverifikasi</p>
        </div>
    </div>

    <!-- Shortcut Lintas Modul Sistem (Super Admin Quick Nav) -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-800 mb-3 flex items-center gap-2">
            <i class="fa-solid fa-compass text-indigo-600"></i>
            Akses Langsung ke Seluruh Modul Operasional (Super Admin Bypass)
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- HSE Card -->
            <a href="{{ route('hse.dashboard') }}" class="p-3.5 rounded-xl border border-slate-100 bg-slate-50/70 hover:bg-emerald-50/60 hover:border-emerald-200 transition group flex items-start gap-3">
                <div class="w-9 h-9 rounded-lg bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition">
                    <i class="fa-solid fa-id-card text-sm"></i>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-800 group-hover:text-emerald-800">Modul HSE</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Kelola SIMPER & Data Karyawan</p>
                </div>
            </a>

            <!-- QMA Card -->
            <a href="{{ route('qma.dashboard') }}" class="p-3.5 rounded-xl border border-slate-100 bg-slate-50/70 hover:bg-blue-50/60 hover:border-blue-200 transition group flex items-start gap-3">
                <div class="w-9 h-9 rounded-lg bg-blue-600 text-white flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition">
                    <i class="fa-solid fa-car text-sm"></i>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-800 group-hover:text-blue-800">Modul QMA</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Master Kendaraan & Log Aktivitas</p>
                </div>
            </a>

            <!-- PG Card -->
            <a href="{{ route('pg.dashboard') }}" class="p-3.5 rounded-xl border border-slate-100 bg-slate-50/70 hover:bg-purple-50/60 hover:border-purple-200 transition group flex items-start gap-3">
                <div class="w-9 h-9 rounded-lg bg-purple-600 text-white flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition">
                    <i class="fa-solid fa-user-shield text-sm"></i>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-800 group-hover:text-purple-800">Modul Private Guard</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Data Satpam & Log Verifikasi</p>
                </div>
            </a>

            <!-- Pos Gerbang -->
            <a href="{{ route('pg.gate.inspection') }}" class="p-3.5 rounded-xl border border-slate-100 bg-slate-50/70 hover:bg-amber-50/60 hover:border-amber-200 transition group flex items-start gap-3">
                <div class="w-9 h-9 rounded-lg bg-amber-600 text-white flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition">
                    <i class="fa-solid fa-shield-halved text-sm"></i>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-slate-800 group-hover:text-amber-800">Inspeksi Pos Gerbang</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Layar Verifikasi Antrean Unit</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Grid Dua Kolom: Distribusi Role & Pengguna Terbaru -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Distribusi Role Pengguna -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm lg:col-span-1">
            <h2 class="text-sm font-bold text-slate-800 mb-4 flex items-center justify-between">
                <span>Distribusi Pengguna per Role</span>
                <span class="text-[11px] font-semibold text-slate-400">{{ $metrics['total_users'] }} User</span>
            </h2>
            <div class="space-y-3">
                @foreach($usersByRole as $roleName => $count)
                    @php
                        $percentage = $metrics['total_users'] > 0 ? round(($count / $metrics['total_users']) * 100) : 0;
                        $badgeColor = match($roleName) {
                            'super_admin' => 'bg-indigo-500',
                            'hse_admin' => 'bg-emerald-500',
                            'qma_admin' => 'bg-blue-500',
                            'pg_admin' => 'bg-purple-500',
                            'gate_officer' => 'bg-amber-500',
                            default => 'bg-slate-400'
                        };
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-semibold text-slate-700 capitalize">
                                {{ str_replace('_', ' ', $roleName) }}
                            </span>
                            <span class="font-bold text-slate-900">{{ $count }} ({{ $percentage }}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="{{ $badgeColor }} h-2 rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.roles.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center justify-between">
                    <span>Lihat Konfigurasi Matriks Role</span>
                    <i class="fa-solid fa-arrow-right text-[11px]"></i>
                </a>
            </div>
        </div>

        <!-- Akun Pengguna Terbaru -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-user-clock text-slate-400"></i>
                    Pengguna Sistem Terdaftar Terbaru
                </h2>
                <a href="{{ route('admin.users.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">
                    Lihat Semua User &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 font-semibold">
                            <th class="pb-2.5">Nama & Username</th>
                            <th class="pb-2.5">Email</th>
                            <th class="pb-2.5">Role</th>
                            <th class="pb-2.5">Pos Gerbang</th>
                            <th class="pb-2.5 text-right">Terdaftar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentUsers as $user)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-2.5 pr-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-indigo-50 text-indigo-700 font-bold flex items-center justify-center text-[10px] flex-shrink-0">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-800">{{ $user->name }}</p>
                                            <p class="text-[10px] text-slate-400 font-mono">{{ $user->username }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5 pr-2 text-slate-600">{{ $user->email }}</td>
                                <td class="py-2.5 pr-2">
                                    @php
                                        $primaryRole = $user->roles->first()?->name ?? $user->role;
                                        $badgeStyle = match($primaryRole) {
                                            'super_admin' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                            'hse_admin' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'qma_admin' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'pg_admin' => 'bg-purple-50 text-purple-700 border-purple-200',
                                            'gate_officer' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            default => 'bg-slate-100 text-slate-600 border-slate-200',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $badgeStyle }} capitalize">
                                        {{ str_replace('_', ' ', $primaryRole) }}
                                    </span>
                                </td>
                                <td class="py-2.5 pr-2 text-slate-600">
                                    {{ $user->gate?->name ?? '-' }}
                                </td>
                                <td class="py-2.5 text-right text-slate-400 text-[11px]">
                                    {{ $user->created_at->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-slate-400">Belum ada data pengguna.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Log Riwayat Aktivitas Terkini (Super Admin) -->
    <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-indigo-600"></i>
                    Aktivitas Administratif Terkini
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">5 tindakan terakhir yang dilakukan oleh Super Admin</p>
            </div>
            <a href="{{ route('admin.activity_logs.index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                <span>Lihat Semua Riwayat</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold">
                        <th class="pb-2.5 w-36">Tanggal & Waktu</th>
                        <th class="pb-2.5 w-28 text-center">Jenis Aksi</th>
                        <th class="pb-2.5 w-36">Bagian</th>
                        <th class="pb-2.5">Keterangan Kegiatan</th>
                        <th class="pb-2.5 text-right w-36">Admin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($recentAdminLogs as $log)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-2.5 pr-2 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                {{ $log->created_at->format('d M, H:i') }} WIB
                            </td>
                            <td class="py-2.5 pr-2 text-center">
                                @if($log->action_type === 'create')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-50 text-emerald-800 border border-emerald-300">TAMBAH</span>
                                @elseif($log->action_type === 'update')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black bg-amber-50 text-amber-800 border border-amber-300">UBAH</span>
                                @elseif($log->action_type === 'permission')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black bg-blue-50 text-blue-800 border border-blue-300">HAK AKSES</span>
                                @elseif($log->action_type === 'delete')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-black bg-rose-50 text-rose-800 border border-rose-300">HAPUS</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 text-slate-700">{{ strtoupper($log->action_type) }}</span>
                                @endif
                            </td>
                            <td class="py-2.5 pr-2 text-slate-600 font-medium">
                                <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 text-[10px]">{{ $log->module }}</span>
                            </td>
                            <td class="py-2.5 pr-2 text-slate-800 font-medium">
                                {{ $log->description }}
                            </td>
                            <td class="py-2.5 text-right font-bold text-slate-700">
                                {{ $log->user_name ?? 'Super Admin' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-slate-400">Belum ada riwayat aktivitas yang tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
