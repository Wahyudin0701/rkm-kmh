@extends('layouts.app')

@section('title', 'Log Riwayat Aktivitas Admin - Super Admin RKM')

@section('content')
<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2.5">
                <span class="w-2.5 h-7 rounded-full bg-indigo-600 inline-block"></span>
                <span>Log Riwayat Aktivitas Admin</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Catatan lengkap kegiatan admin: penambahan akun, perubahan data, pengaturan hak akses, dan penghapusan data di sistem RKM-KMH.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Rekaman Keamanan Sistem</span>
            </span>
        </div>
    </div>

    <!-- Metrik Ringkasan Aktivitas -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Hari Ini</span>
                    <span class="text-xl sm:text-2xl font-black text-slate-900 mt-1 block">{{ number_format($stats['today_count']) }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
            </div>
            <span class="text-[10px] text-slate-400 font-medium block mt-2">Aktivitas admin hari ini</span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider block">Tambah Baru</span>
                    <span class="text-xl sm:text-2xl font-black text-slate-900 mt-1 block">{{ number_format($stats['create_count']) }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-circle-plus"></i>
                </div>
            </div>
            <span class="text-[10px] text-slate-400 font-medium block mt-2">Akun / data baru dibuat</span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-amber-600 uppercase tracking-wider block">Ubah & Hak Akses</span>
                    <span class="text-xl sm:text-2xl font-black text-slate-900 mt-1 block">{{ number_format($stats['update_count']) }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
            </div>
            <span class="text-[10px] text-slate-400 font-medium block mt-2">Data atau izin yang diperbarui</span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold text-rose-600 uppercase tracking-wider block">Hapus Data</span>
                    <span class="text-xl sm:text-2xl font-black text-slate-900 mt-1 block">{{ number_format($stats['delete_count']) }}</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
            </div>
            <span class="text-[10px] text-slate-400 font-medium block mt-2">Data dihapus dari sistem</span>
        </div>
    </div>

    <!-- Filter & Pencarian Cepat -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
        <form method="GET" action="{{ route('admin.activity_logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Input Pencarian Kata Kunci -->
            <div class="lg:col-span-2">
                <label for="search" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Cari Aktivitas</label>
                <div class="relative">
                    <input type="text" 
                           id="search" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Cari nama admin, nama akun, kata kunci..." 
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white pl-9 pr-3 py-2.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition shadow-2xs">
                    <i class="fa-solid fa-magnifying-glass text-slate-400 text-xs absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                </div>
            </div>

            <!-- Jenis Kegiatan -->
            <div>
                <label for="action_type" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Jenis Kegiatan</label>
                <select id="action_type" 
                        name="action_type" 
                        class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition shadow-2xs">
                    <option value="">Semua Jenis Kegiatan</option>
                    <option value="create" {{ request('action_type') === 'create' ? 'selected' : '' }}>🟢 Penambahan Data Baru</option>
                    <option value="update" {{ request('action_type') === 'update' ? 'selected' : '' }}>🟡 Perubahan / Edit Data</option>
                    <option value="permission" {{ request('action_type') === 'permission' ? 'selected' : '' }}>🔵 Pengaturan Hak Akses</option>
                    <option value="delete" {{ request('action_type') === 'delete' ? 'selected' : '' }}>🔴 Penghapusan Data</option>
                </select>
            </div>

            <!-- Tanggal Mulai -->
            <div>
                <label for="start_date" class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Dari Tanggal</label>
                <input type="date" 
                       id="start_date" 
                       name="start_date" 
                       value="{{ request('start_date') }}" 
                       class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition shadow-2xs">
            </div>

            <!-- Tombol Submit & Reset -->
            <div class="flex items-end gap-2">
                <button type="submit" 
                        class="flex-1 py-2.5 px-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-2xs transition flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-filter text-xs"></i>
                    <span>Terapkan</span>
                </button>
                @if(request()->anyFilled(['search', 'action_type', 'start_date', 'end_date']))
                    <a href="{{ route('admin.activity_logs.index') }}" 
                       class="py-2.5 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition flex items-center justify-center cursor-pointer"
                       title="Reset Filter">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabel Riwayat Aktivitas Admin -->
    <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <!-- Header Navy Solid Gelap #06213f -->
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">NO</th>
                        <th class="py-3.5 px-4 w-36">TANGGAL & WAKTU</th>
                        <th class="py-3.5 px-4 w-44">ADMIN PELAKSANA</th>
                        <th class="py-3.5 px-4 text-center w-28">JENIS AKSI</th>
                        <th class="py-3.5 px-4 w-36">BAGIAN / MENU</th>
                        <th class="py-3.5 px-4">RINGKASAN KEGIATAN</th>
                        <th class="py-3.5 px-4 text-center w-28">RINCIAN</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($logs as $index => $log)
                        @php
                            $rowNum = $logs->firstItem() ? ($logs->firstItem() + $index) : ($index + 1);
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <!-- Nomor -->
                            <td class="py-3.5 px-4 text-center font-medium text-slate-400">{{ $rowNum }}</td>

                            <!-- Waktu & Tanggal -->
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-600 whitespace-nowrap">
                                <span class="font-bold text-slate-900 block">{{ $log->created_at->format('d M Y') }}</span>
                                <span class="text-slate-400">{{ $log->created_at->format('H:i') }} WIB</span>
                            </td>

                            <!-- Pelaku Admin -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-700 font-bold text-[10px] flex items-center justify-center flex-shrink-0 border border-indigo-200/60">
                                        {{ strtoupper(substr($log->user_name ?? 'A', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 block truncate leading-tight">{{ $log->user_name ?? 'Super Admin' }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono block truncate">{{ $log->user_username ?? 'superadmin' }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Jenis Aksi -->
                            <td class="py-3.5 px-4 text-center">
                                @if($log->action_type === 'create')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-800 border border-emerald-300">
                                        <i class="fa-solid fa-plus text-[9px]"></i>
                                        <span>TAMBAH</span>
                                    </span>
                                @elseif($log->action_type === 'update')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-50 text-amber-800 border border-amber-300">
                                        <i class="fa-solid fa-pen text-[9px]"></i>
                                        <span>UBAH</span>
                                    </span>
                                @elseif($log->action_type === 'permission')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-blue-50 text-blue-800 border border-blue-300">
                                        <i class="fa-solid fa-shield-halved text-[9px]"></i>
                                        <span>HAK AKSES</span>
                                    </span>
                                @elseif($log->action_type === 'delete')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-50 text-rose-800 border border-rose-300">
                                        <i class="fa-solid fa-trash text-[9px]"></i>
                                        <span>HAPUS</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                        {{ strtoupper($log->action_type) }}
                                    </span>
                                @endif
                            </td>

                            <!-- Bagian / Menu -->
                            <td class="py-3.5 px-4 font-semibold text-slate-700 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-[11px] border border-slate-200/80 font-medium">
                                    {{ $log->module }}
                                </span>
                            </td>

                            <!-- Ringkasan Kegiatan -->
                            <td class="py-3.5 px-4 text-slate-800 leading-relaxed font-medium">
                                <div>{{ $log->description }}</div>
                                @if($log->target_name)
                                    <span class="inline-block mt-0.5 text-[10px] font-semibold text-indigo-600 bg-indigo-50/70 px-2 py-0.5 rounded-md border border-indigo-100">
                                        Target: {{ $log->target_name }}
                                    </span>
                                @endif
                            </td>

                            <!-- Tombol Rincian Modal -->
                            <td class="py-3.5 px-4 text-center">
                                @if(!empty($log->old_values) || !empty($log->new_values))
                                    <button type="button" 
                                            onclick='openLogDetailModal(@json($log))' 
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200/80 text-[11px] font-bold transition cursor-pointer" 
                                            title="Lihat Rincian Perubahan">
                                        <i class="fa-solid fa-list-check text-xs"></i>
                                        <span>Rincian</span>
                                    </button>
                                @else
                                    <span class="text-slate-300 text-[11px]">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <div class="max-w-xs mx-auto space-y-2">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </div>
                                    <p class="font-bold text-slate-700 text-sm">Belum Ada Riwayat Kegiatan</p>
                                    <p class="text-xs text-slate-400">Setiap kali admin menambah akun, mengedit data, atau mengatur hak akses, catatannya akan otomatis tersimpan di sini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL RINCIAN DATA PERUBAHAN (RAMAH UNTUK PENGGUNA NON-IT)                 -->
<!-- ========================================================================= -->
<div id="logDetailModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden transform transition-all">
        <!-- Header Modal -->
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold text-sm">
                    <i class="fa-solid fa-file-lines"></i>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900" id="detailModalTitle">Rincian Perubahan Data</h3>
                    <p class="text-[11px] text-slate-400" id="detailModalSubtitle">Catatan riwayat aktivitas</p>
                </div>
            </div>
            <button type="button" onclick="closeLogDetailModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Isi Modal -->
        <div class="p-5 space-y-4 max-h-[75vh] overflow-y-auto">
            <!-- Informasi Umum Kegiatan -->
            <div class="bg-indigo-50/40 rounded-xl p-3.5 border border-indigo-100 text-xs">
                <div class="font-bold text-slate-800 text-sm mb-1" id="detailModalDescription"></div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-[11px] text-slate-600 mt-2.5 pt-2.5 border-t border-indigo-100/80">
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Admin Pelaksana:</span>
                        <strong class="text-slate-800 font-semibold" id="detailModalActor"></strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Waktu Kejadian:</span>
                        <strong class="text-slate-800 font-semibold" id="detailModalTime"></strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Bagian Menu:</span>
                        <strong class="text-slate-800 font-semibold" id="detailModalModule"></strong>
                    </div>
                </div>
            </div>

            <!-- Wadah Render Visual yang Ramah Non-IT -->
            <div id="detailHumanFriendlyContainer" class="space-y-3">
                <!-- Diisi secara dinamis oleh JavaScript -->
            </div>

            <!-- Bagian Opsional untuk Teknis / Raw Data (Bisa Dibuka-Tutup) -->
            <div class="pt-2">
                <button type="button" 
                        onclick="toggleTechnicalDetails()" 
                        class="text-[11px] font-bold text-slate-400 hover:text-indigo-600 flex items-center gap-1 transition cursor-pointer">
                    <i class="fa-solid fa-code text-[10px]"></i>
                    <span id="techToggleText">Tampilkan Rincian Format Teknis (JSON)</span>
                </button>
                <div id="technicalDetailsBlock" class="hidden mt-2 p-3 bg-slate-900 text-slate-200 rounded-xl font-mono text-[10px] space-y-2 overflow-x-auto">
                    <div>
                        <span class="text-rose-400 font-bold block mb-1">Old Values:</span>
                        <pre id="detailOldValuesText" class="whitespace-pre-wrap break-words"></pre>
                    </div>
                    <div class="pt-2 border-t border-slate-800">
                        <span class="text-emerald-400 font-bold block mb-1">New Values:</span>
                        <pre id="detailNewValuesText" class="whitespace-pre-wrap break-words"></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Modal -->
        <div class="px-5 py-3 border-t border-slate-100 bg-slate-50 flex justify-end">
            <button type="button" 
                    onclick="closeLogDetailModal()" 
                    class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold transition cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    // Kamus terjemahan istilah ramah pengguna (Non-IT Friendly Dictionary)
    const FIELD_LABELS = {
        'name': 'Nama Lengkap',
        'username': 'Username Akun',
        'email': 'Alamat Email',
        'role': 'Peran / Hak Akses',
        'gate_id': 'Penugasan Pos Gerbang',
        'password': 'Kata Sandi',
        'password_changed': 'Status Password',
        'permissions': 'Daftar Izin Akses',
        'status': 'Status Akun'
    };

    const ROLE_LABELS = {
        'super_admin': 'Super Administrator (Akses Penuh)',
        'hse_admin': 'Admin HSE (Keselamatan & Karyawan)',
        'qma_admin': 'Admin QMA (Armada Kendaraan)',
        'pg_admin': 'Admin PG (Keamanan & Satpam)',
        'gate_officer': 'Petugas Pos Gerbang',
        'employee': 'Karyawan Biasa'
    };

    const ACTION_TITLES = {
        'create': 'Penambahan Data Baru',
        'update': 'Perubahan Data Akun',
        'permission': 'Pengaturan Hak Akses',
        'delete': 'Penghapusan Data'
    };

    function formatFieldValue(key, val) {
        if (val === null || val === undefined || val === '') {
            return '<span class="text-slate-400 italic">(Tidak diatur / Kosong)</span>';
        }
        if (key === 'role') {
            return `<strong class="text-indigo-700 font-semibold">${ROLE_LABELS[val] || val}</strong>`;
        }
        if (key === 'gate_id') {
            return `Pos Gerbang #${val}`;
        }
        if (key === 'password_changed') {
            return val ? '<span class="text-emerald-700 font-bold">Kata Sandi Baru Telah Ditetapkan</span>' : 'Tidak Berubah';
        }
        if (Array.isArray(val)) {
            if (val.length === 0) return '<span class="text-slate-400 italic">(Tidak ada izin yang dipilih)</span>';
            return `<div class="flex flex-wrap gap-1 max-h-36 overflow-y-auto p-1.5 bg-slate-50 rounded-lg border border-slate-200">
                ${val.map(item => `<span class="px-2 py-0.5 rounded text-[10px] font-medium bg-white text-slate-700 border border-slate-200/80 shadow-2xs">${item}</span>`).join('')}
            </div>`;
        }
        return `<span>${val}</span>`;
    }

    function openLogDetailModal(log) {
        const actionTitle = ACTION_TITLES[log.action_type] || log.action_type.toUpperCase();
        document.getElementById('detailModalTitle').textContent = `Rincian: ${actionTitle}`;
        document.getElementById('detailModalSubtitle').textContent = `Bagian: ${log.module} • ID Log: #${log.id}`;
        document.getElementById('detailModalDescription').textContent = log.description;
        document.getElementById('detailModalActor').textContent = `${log.user_name || 'Admin'} (${log.user_username || 'system'})`;
        document.getElementById('detailModalModule').textContent = log.module;
        
        const dateObj = new Date(log.created_at);
        document.getElementById('detailModalTime').textContent = dateObj.toLocaleDateString('id-ID', {
            day: 'numeric', month: 'short', year: 'numeric'
        }) + ' ' + dateObj.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';

        const container = document.getElementById('detailHumanFriendlyContainer');
        container.innerHTML = '';

        const oldVals = log.old_values || {};
        const newVals = log.new_values || {};
        const hasOld = Object.keys(oldVals).length > 0;
        const hasNew = Object.keys(newVals).length > 0;

        // Mode 1: Edit / Update (Bandingkan Sebelum vs Sesudah)
        if (hasOld && hasNew) {
            const allKeys = Array.from(new Set([...Object.keys(oldVals), ...Object.keys(newVals)]));
            
            let tableRows = '';
            allKeys.forEach(key => {
                const label = FIELD_LABELS[key] || key.replace('_', ' ').toUpperCase();
                const oldFmt = formatFieldValue(key, oldVals[key]);
                const newFmt = formatFieldValue(key, newVals[key]);
                const isDifferent = JSON.stringify(oldVals[key]) !== JSON.stringify(newVals[key]);

                const rowBg = isDifferent ? 'bg-amber-50/40' : 'bg-white';
                const statusBadge = isDifferent 
                    ? '<span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800">Berubah</span>' 
                    : '<span class="text-slate-300 text-[10px]">Sama</span>';

                tableRows += `
                    <tr class="${rowBg} border-b border-slate-100">
                        <td class="py-2.5 px-3 font-semibold text-slate-700 whitespace-nowrap">
                            ${label}
                            <div class="mt-0.5">${statusBadge}</div>
                        </td>
                        <td class="py-2.5 px-3 text-slate-600 bg-rose-50/20">${oldFmt}</td>
                        <td class="py-2.5 px-3 text-slate-800 bg-emerald-50/20 font-medium">${newFmt}</td>
                    </tr>
                `;
            });

            container.innerHTML = `
                <div class="rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-100 text-slate-600 font-bold text-[10px] uppercase">
                            <tr>
                                <th class="py-2 px-3 w-36">Kolom Data</th>
                                <th class="py-2 px-3 bg-rose-50/60 text-rose-800 w-1/2">Data Sebelum Diubah</th>
                                <th class="py-2 px-3 bg-emerald-50/60 text-emerald-800 w-1/2">Data Sesudah Diubah</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRows}
                        </tbody>
                    </table>
                </div>
            `;
        } 
        // Mode 2: Penambahan Baru (Hanya Ada Data Baru)
        else if (hasNew) {
            let rows = '';
            Object.entries(newVals).forEach(([key, val]) => {
                const label = FIELD_LABELS[key] || key.replace('_', ' ').toUpperCase();
                rows += `
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between py-2 border-b border-slate-100 gap-1">
                        <span class="text-slate-500 font-medium w-44">${label}</span>
                        <div class="text-slate-900 font-semibold flex-1">${formatFieldValue(key, val)}</div>
                    </div>
                `;
            });

            container.innerHTML = `
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/20 p-4">
                    <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider block mb-2 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-plus"></i>
                        <span>Rincian Data Baru yang Ditambahkan:</span>
                    </span>
                    <div class="text-xs divide-y divide-slate-100 bg-white rounded-lg p-3 border border-emerald-100">
                        ${rows}
                    </div>
                </div>
            `;
        }
        // Mode 3: Penghapusan (Hanya Ada Data Lama / Terhapus)
        else if (hasOld) {
            let rows = '';
            Object.entries(oldVals).forEach(([key, val]) => {
                const label = FIELD_LABELS[key] || key.replace('_', ' ').toUpperCase();
                rows += `
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between py-2 border-b border-slate-100 gap-1">
                        <span class="text-slate-500 font-medium w-44">${label}</span>
                        <div class="text-slate-900 font-semibold flex-1">${formatFieldValue(key, val)}</div>
                    </div>
                `;
            });

            container.innerHTML = `
                <div class="rounded-xl border border-rose-200 bg-rose-50/20 p-4">
                    <span class="text-[11px] font-bold text-rose-800 uppercase tracking-wider block mb-2 flex items-center gap-1.5">
                        <i class="fa-solid fa-trash-can"></i>
                        <span>Rincian Data Sebelum Dihapus:</span>
                    </span>
                    <div class="text-xs divide-y divide-slate-100 bg-white rounded-lg p-3 border border-rose-100">
                        ${rows}
                    </div>
                </div>
            `;
        } else {
            container.innerHTML = `
                <p class="text-xs text-slate-400 italic text-center py-4">Tidak ada rincian data tersimpan untuk aktivitas ini.</p>
            `;
        }

        // Set raw JSON for developers if opened
        document.getElementById('detailOldValuesText').textContent = hasOld ? JSON.stringify(oldVals, null, 2) : '(none)';
        document.getElementById('detailNewValuesText').textContent = hasNew ? JSON.stringify(newVals, null, 2) : '(none)';

        // Reset tech view block to hidden
        document.getElementById('technicalDetailsBlock').classList.add('hidden');
        document.getElementById('techToggleText').textContent = 'Tampilkan Rincian Format Teknis (JSON)';

        document.getElementById('logDetailModal').classList.remove('hidden');
    }

    function toggleTechnicalDetails() {
        const block = document.getElementById('technicalDetailsBlock');
        const text = document.getElementById('techToggleText');
        if (block.classList.contains('hidden')) {
            block.classList.remove('hidden');
            text.textContent = 'Sembunyikan Rincian Format Teknis (JSON)';
        } else {
            block.classList.add('hidden');
            text.textContent = 'Tampilkan Rincian Format Teknis (JSON)';
        }
    }

    function closeLogDetailModal() {
        document.getElementById('logDetailModal').classList.add('hidden');
    }

    // Tutup saat klik di luar modal / Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeLogDetailModal();
        }
    });

    document.getElementById('logDetailModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeLogDetailModal();
        }
    });
</script>
@endsection
