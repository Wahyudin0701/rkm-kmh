@extends('layouts.app')

@section('title', 'Backup & Restore Sistem - Super Admin RKM')

@section('content')
<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight flex items-center gap-2.5">
                <span class="w-2.5 h-7 rounded-full bg-indigo-600 inline-block"></span>
                <span>Backup & Restore Sistem</span>
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Pencadangan database MySQL dan file media sistem secara berkala untuk perlindungan data dan pemulihan bencana (<em>disaster recovery</em>).
            </p>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <button type="button" 
                    onclick="openUploadBackupModal()" 
                    class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs transition flex items-center gap-2 shadow-2xs">
                <i class="fa-solid fa-cloud-arrow-up text-indigo-600"></i>
                <span>Unggah File Backup</span>
            </button>
            <button type="button" 
                    onclick="openCreateBackupModal()" 
                    class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition flex items-center gap-2 shadow-sm shadow-indigo-600/20">
                <i class="fa-solid fa-database text-xs"></i>
                <span>Buat Backup Baru</span>
            </button>
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center justify-between text-emerald-800 text-xs font-semibold animate-scale-in">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base flex-shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 p-1">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-center justify-between text-rose-800 text-xs font-semibold animate-scale-in">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base flex-shrink-0"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 p-1">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- Kartu Metrik Ringkasan Backup & Database -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- Total File Backup -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs relative overflow-hidden group hover:border-indigo-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total File Backup</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-server"></i>
                </span>
            </div>
            <span class="text-xl sm:text-2xl font-black text-slate-900 mt-2 block">{{ number_format($stats['total_backups']) }}</span>
            <span class="text-[10px] text-slate-400 font-medium block mt-1">Arsip tersimpan di server</span>
        </div>

        <!-- Total Ukuran Storage -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs relative overflow-hidden group hover:border-indigo-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Ukuran Arsip</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-hard-drive"></i>
                </span>
            </div>
            <span class="text-xl sm:text-2xl font-black text-slate-900 mt-2 block">{{ $stats['total_size_human'] }}</span>
            <span class="text-[10px] text-slate-400 font-medium block mt-1">Kapasitas folder storage/app/backups</span>
        </div>

        <!-- Backup Terakhir -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs relative overflow-hidden group hover:border-indigo-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Backup Terakhir</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </span>
            </div>
            <span class="text-sm sm:text-base font-bold text-slate-900 mt-2 block truncate" title="{{ $stats['last_backup']['created_at_human'] ?? '-' }}">
                {{ $stats['last_backup']['created_at_human'] ?? 'Belum ada backup' }}
            </span>
            <span class="text-[10px] text-slate-400 font-medium block mt-1 truncate" title="{{ $stats['last_backup']['filename'] ?? '-' }}">
                {{ $stats['last_backup']['filename'] ?? 'Klik Buat Backup Baru' }}
            </span>
        </div>

        <!-- Info Database Aktif -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs relative overflow-hidden group hover:border-indigo-300 transition">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Database Aktif</span>
                <span class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-database"></i>
                </span>
            </div>
            <span class="text-sm sm:text-base font-bold text-slate-900 mt-2 block truncate">
                {{ $stats['database_name'] }}
            </span>
            <span class="text-[10px] text-slate-400 font-medium block mt-1">
                {{ $stats['table_count'] }} Tabel &bull; Estimasi {{ $stats['estimated_db_size_human'] }}
            </span>
        </div>
    </div>

    <!-- Banner Panduan Praktik Terbaik Backup -->
    <div class="bg-gradient-to-r from-indigo-50/80 via-slate-50 to-emerald-50/60 border border-indigo-100 rounded-2xl p-4 sm:p-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-2xl bg-indigo-600 text-white flex items-center justify-center text-base flex-shrink-0 shadow-sm shadow-indigo-600/20">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div class="space-y-0.5">
                <h4 class="text-xs font-bold text-slate-800 flex items-center gap-2">
                    <span>Panduan Keamanan & Pemulihan Sistem</span>
                    <span class="px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-700 text-[10px] font-bold">Rekomendasi</span>
                </h4>
                <p class="text-[11px] text-slate-600 leading-relaxed max-w-3xl">
                    Disarankan untuk mengunduh arsip backup secara teratur ke perangkat fisik atau penyimpanan cloud terpisah. Sebelum melakukan pembaruan besar atau perubahan skema data, buat selalu backup terbaru sebagai cadangan darurat.
                </p>
            </div>
        </div>
        <button type="button" 
                onclick="openCreateBackupModal()" 
                class="px-3.5 py-2 rounded-xl bg-white border border-indigo-200 text-indigo-700 hover:bg-indigo-50 font-bold text-xs transition flex items-center gap-1.5 flex-shrink-0 shadow-2xs">
            <i class="fa-solid fa-circle-plus text-indigo-600"></i>
            <span>Cadangkan Sekarang</span>
        </button>
    </div>

    <!-- Tabel Daftar File Backup -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-4 sm:px-6 border-b border-slate-100 flex items-center justify-between gap-4 flex-wrap">
            <div>
                <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-folder-open text-indigo-600"></i>
                    <span>Daftar Arsip Backup Tersimpan</span>
                </h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Seluruh file tersimpan secara aman di direktori lokal server <code>storage/app/backups/</code></p>
            </div>
            <div class="text-[11px] font-bold text-slate-500 bg-slate-50 border border-slate-200 px-3 py-1 rounded-xl">
                <span>{{ count($backups) }} File Tersedia</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider text-[11px]">
                        <th class="py-3 px-4 sm:px-6">Nama File Backup</th>
                        <th class="py-3 px-4">Jenis Backup</th>
                        <th class="py-3 px-4">Ukuran File</th>
                        <th class="py-3 px-4">Tanggal Dibuat</th>
                        <th class="py-3 px-4 sm:px-6 text-center">Aksi & Pemulihan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($backups as $b)
                        @php
                            $isFull = ($b['type'] === 'full');
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <!-- Nama File -->
                            <td class="py-3.5 px-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm flex-shrink-0 {{ $isFull ? 'bg-amber-50 text-amber-600 border border-amber-200' : 'bg-indigo-50 text-indigo-600 border border-indigo-200' }}">
                                        @if($isFull)
                                            <i class="fa-solid fa-file-zipper"></i>
                                        @else
                                            <i class="fa-solid fa-file-code"></i>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-900 text-xs font-mono truncate" title="{{ $b['filename'] }}">
                                            {{ $b['filename'] }}
                                        </p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">
                                            {{ $isFull ? 'Arsip ZIP (Database + Media Upload)' : 'File Skrip SQL Murni' }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Jenis Backup -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($isFull)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        <i class="fa-solid fa-box-archive text-[9px]"></i>
                                        <span>Full Backup + Media</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        <i class="fa-solid fa-database text-[9px]"></i>
                                        <span>Database SQL</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Ukuran File -->
                            <td class="py-3.5 px-4 whitespace-nowrap font-mono text-slate-700 font-semibold text-xs">
                                {{ $b['size_human'] }}
                            </td>

                            <!-- Tanggal Dibuat -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="text-slate-800 font-semibold block text-xs">{{ $b['created_at_human'] }}</span>
                                <span class="text-[10px] text-slate-400">{{ $b['created_at']->diffForHumans() }}</span>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-4 sm:px-6 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-2">
                                    <!-- Unduh -->
                                    <a href="{{ route('admin.backups.download', $b['filename']) }}" 
                                       class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 text-xs font-bold transition flex items-center gap-1.5 border border-slate-200 hover:border-indigo-200"
                                       title="Unduh file backup ke komputer lokal">
                                        <i class="fa-solid fa-download text-[11px]"></i>
                                        <span class="hidden sm:inline">Unduh</span>
                                    </a>

                                    <!-- Pulihkan / Restore -->
                                    <button type="button" 
                                            onclick="openRestoreModal('{{ $b['filename'] }}', '{{ $b['type_label'] }}', '{{ $b['size_human'] }}')" 
                                            class="px-2.5 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 hover:text-amber-900 text-xs font-bold transition flex items-center gap-1.5 border border-amber-200"
                                            title="Pulihkan database & data sistem dari backup ini">
                                        <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                        <span>Pulihkan</span>
                                    </button>

                                    <!-- Hapus -->
                                    <button type="button" 
                                            onclick="openDeleteBackupModal('{{ $b['filename'] }}')" 
                                            class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-400 hover:text-rose-600 flex items-center justify-center transition border border-transparent hover:border-rose-200"
                                            title="Hapus file backup ini dari server">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 px-4 text-center">
                                <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl mx-auto mb-3">
                                    <i class="fa-solid fa-database"></i>
                                </div>
                                <h4 class="text-sm font-bold text-slate-800">Belum Ada File Backup Tersimpan</h4>
                                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                                    Amankan data sistem Anda dengan membuat cadangan database pertama sekarang.
                                </p>
                                <button type="button" 
                                        onclick="openCreateBackupModal()" 
                                        class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition inline-flex items-center gap-2">
                                    <i class="fa-solid fa-circle-plus"></i>
                                    <span>Buat Backup Baru</span>
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: BUAT BACKUP BARU -->
<!-- ========================================================================= -->
<div id="createBackupModal" data-backdrop="static" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl overflow-hidden animate-scale-in border border-slate-100">
        <div class="px-6 py-4.5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-database text-indigo-600"></i>
                <span>Buat Backup Baru</span>
            </h3>
            <button type="button" onclick="closeCreateBackupModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="createBackupForm" method="POST" action="{{ route('admin.backups.store') }}" onsubmit="handleBackupSubmit(this)" class="p-6 space-y-4">
            @csrf

            <p class="text-xs text-slate-500 leading-relaxed">
                Pilih cakupan data yang ingin dicadangkan. Proses ini akan berjalan di latar belakang dan menyimpan file hasil cadangan di server.
            </p>

            <!-- Pilihan Jenis Backup -->
            <div class="space-y-3">
                <!-- Opsi 1: Database Saja -->
                <label class="block p-4 rounded-2xl border-2 border-indigo-600 bg-indigo-50/40 cursor-pointer transition relative has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50/40">
                    <div class="flex items-start gap-3">
                        <input type="radio" name="type" value="database" checked class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                        <div class="flex-1">
                            <span class="block text-xs font-bold text-slate-900">Database Saja (.sql)</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                Mencadangkan seluruh 29 tabel aktif MySQL dan data operasional. Proses sangat cepat dan ukuran file ringan (~500 KB).
                            </span>
                            <span class="inline-block mt-2 px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 font-bold text-[10px]">
                                Direkomendasikan Rutin
                            </span>
                        </div>
                    </div>
                </label>

                <!-- Opsi 2: Full Backup -->
                <label class="block p-4 rounded-2xl border-2 border-slate-200 hover:border-slate-300 bg-white cursor-pointer transition relative has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50/40">
                    <div class="flex items-start gap-3">
                        <input type="radio" name="type" value="full" class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                        <div class="flex-1">
                            <span class="block text-xs font-bold text-slate-900">Full Backup Lengkap (.zip)</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                Mencadangkan Database SQL + seluruh berkas media upload di <code>storage/app/public</code> (foto karyawan, foto kendaraan, SIMPER).
                            </span>
                            <span class="inline-block mt-2 px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-bold text-[10px]">
                                Menyeluruh & Total
                            </span>
                        </div>
                    </div>
                </label>
            </div>

            <!-- Tombol Footer -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeCreateBackupModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition">
                    Batal
                </button>
                <button type="submit" id="createBackupSubmitBtn" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-2">
                    <i class="fa-solid fa-play text-[10px]"></i>
                    <span id="createBackupBtnText">Mulai Pencadangan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: UNGGAH FILE BACKUP -->
<!-- ========================================================================= -->
<div id="uploadBackupModal" data-backdrop="static" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl max-w-md w-full shadow-2xl overflow-hidden animate-scale-in border border-slate-100">
        <div class="px-6 py-4.5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up text-indigo-600"></i>
                <span>Unggah File Backup</span>
            </h3>
            <button type="button" onclick="closeUploadBackupModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="uploadBackupForm" method="POST" action="{{ route('admin.backups.upload') }}" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf

            <!-- Upload Drop Area -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Pilih File Arsip Backup <span class="text-rose-500">*</span></label>
                <div class="border-2 border-dashed border-slate-300 hover:border-indigo-500 rounded-2xl p-6 text-center bg-slate-50/60 hover:bg-white transition cursor-pointer relative"
                     onclick="document.getElementById('backupFileInput').click()">
                    <i class="fa-solid fa-file-arrow-up text-3xl text-indigo-600 mb-2 block"></i>
                    <p class="text-xs font-bold text-slate-800" id="uploadFileName">Klik untuk memilih file backup</p>
                    <p class="text-[11px] text-slate-400 mt-1">Mendukung format SQL (.sql), Arsip ZIP (.zip), atau Gzip (.sql.gz)</p>
                    <input type="file" 
                           name="backup_file" 
                           id="backupFileInput" 
                           accept=".sql,.zip,.gz" 
                           required 
                           class="hidden" 
                           onchange="handleUploadFileChange(this)">
                </div>
                <p class="text-[10px] text-slate-400 mt-1.5 flex items-center gap-1">
                    <i class="fa-solid fa-circle-info text-indigo-600"></i>
                    <span>Ukuran maksimal yang diizinkan hingga 200 MB.</span>
                </p>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeUploadBackupModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-2">
                    <i class="fa-solid fa-upload text-xs"></i>
                    <span>Unggah ke Server</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: KONFIRMASI PEMULIHAN (RESTORE) -->
<!-- ========================================================================= -->
<div id="restoreModal" data-backdrop="static" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl max-w-lg w-full shadow-2xl overflow-hidden animate-scale-in border border-slate-100">
        <!-- Header Bahaya -->
        <div class="px-6 py-4.5 bg-amber-50/80 border-b border-amber-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-amber-900 flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                <span>Konfirmasi Pemulihan Sistem (Restore)</span>
            </h3>
            <button type="button" onclick="closeRestoreModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="restoreForm" method="POST" action="" onsubmit="handleRestoreSubmit(this)" class="p-6 space-y-4">
            @csrf

            <!-- Warning Card -->
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-900 space-y-1.5">
                <p class="font-black text-rose-950 flex items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-600 text-sm"></i>
                    <span>PERINGATAN KRITIKAL: TINDAKAN INI AKAN MENIMPA DATA</span>
                </p>
                <p class="text-[11px] leading-relaxed text-rose-800">
                    Proses pemulihan akan menimpa (overwrite) seluruh database saat ini dengan isi arsip backup yang dipilih. Data yang dibuat setelah tanggal backup ini akan tergantikan.
                </p>
            </div>

            <!-- Rincian Target File Backup -->
            <div class="bg-slate-50 rounded-2xl p-3.5 border border-slate-200/80 text-xs space-y-1">
                <div class="flex justify-between">
                    <span class="text-slate-400 text-[11px]">Nama Arsip:</span>
                    <span id="restoreTargetFilename" class="font-bold font-mono text-slate-800"></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400 text-[11px]">Tipe & Ukuran:</span>
                    <span id="restoreTargetMeta" class="font-semibold text-slate-700"></span>
                </div>
            </div>

            <!-- Input Verifikasi Keamanan -->
            <div>
                <label class="block text-xs font-bold text-slate-800 mb-1">
                    Konfirmasi Keamanan <span class="text-rose-500">*</span>
                </label>
                <p class="text-[11px] text-slate-500 mb-2 leading-relaxed">
                    Untuk melanjutkan, ketik kata <strong class="text-slate-900 font-mono bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">PULIHKAN</strong> pada kolom di bawah atau masukkan password akun Super Admin Anda:
                </p>
                <input type="text" 
                       name="confirmation" 
                       id="restoreConfirmationInput" 
                       placeholder="Ketik PULIHKAN atau password akun Anda..." 
                       required 
                       autocomplete="off" 
                       class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-bold focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-500/10">
            </div>

            <!-- Tombol Footer -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeRestoreModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition">
                    Batal
                </button>
                <button type="submit" id="restoreSubmitBtn" class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-sm transition flex items-center gap-2">
                    <i class="fa-solid fa-rotate-left text-xs"></i>
                    <span id="restoreBtnText">Ya, Pulihkan Sistem Sekarang</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: KONFIRMASI HAPUS BACKUP -->
<!-- ========================================================================= -->
<div id="deleteBackupModal" data-backdrop="static" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl overflow-hidden animate-scale-in border border-slate-100 text-center">
        <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl mx-auto mb-3">
            <i class="fa-solid fa-trash-can"></i>
        </div>
        <h3 class="text-base font-bold text-slate-900">Hapus File Backup?</h3>
        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
            Apakah Anda yakin ingin menghapus file backup <span id="deleteBackupFilename" class="font-mono font-bold text-slate-800"></span> dari server? File yang dihapus tidak dapat dipulihkan kembali.
        </p>

        <form id="deleteBackupForm" method="POST" action="" class="mt-6 flex items-center justify-center gap-2.5">
            @csrf
            @method('DELETE')
            <button type="button" onclick="closeDeleteBackupModal()" class="flex-1 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition">
                Batal
            </button>
            <button type="submit" class="flex-1 px-4 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-sm transition">
                Hapus File
            </button>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT HELPER MODAL & INTERAKSI -->
<!-- ========================================================================= -->
<script>
    function openCreateBackupModal() {
        document.getElementById('createBackupModal').classList.remove('hidden');
        document.documentElement.classList.add('modal-open');
    }

    function closeCreateBackupModal() {
        document.getElementById('createBackupModal').classList.add('hidden');
        document.documentElement.classList.remove('modal-open');
    }

    function handleBackupSubmit(form) {
        const btn = document.getElementById('createBackupSubmitBtn');
        const text = document.getElementById('createBackupBtnText');
        if (btn) {
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
        }
        if (text) {
            text.textContent = 'Memproses Pencadangan...';
        }
    }

    function openUploadBackupModal() {
        document.getElementById('uploadBackupModal').classList.remove('hidden');
        document.documentElement.classList.add('modal-open');
    }

    function closeUploadBackupModal() {
        document.getElementById('uploadBackupModal').classList.add('hidden');
        document.documentElement.classList.remove('modal-open');
    }

    function handleUploadFileChange(input) {
        const nameDisplay = document.getElementById('uploadFileName');
        if (input.files && input.files[0]) {
            nameDisplay.textContent = input.files[0].name + ' (' + (input.files[0].size / 1024 / 1024).toFixed(2) + ' MB)';
        }
    }

    function openRestoreModal(filename, typeLabel, sizeHuman) {
        document.getElementById('restoreForm').action = `/admin/backups/${encodeURIComponent(filename)}/restore`;
        document.getElementById('restoreTargetFilename').textContent = filename;
        document.getElementById('restoreTargetMeta').textContent = `${typeLabel} (${sizeHuman})`;
        document.getElementById('restoreConfirmationInput').value = '';
        document.getElementById('restoreModal').classList.remove('hidden');
        document.documentElement.classList.add('modal-open');
    }

    function closeRestoreModal() {
        document.getElementById('restoreModal').classList.add('hidden');
        document.documentElement.classList.remove('modal-open');
    }

    function handleRestoreSubmit(form) {
        const btn = document.getElementById('restoreSubmitBtn');
        const text = document.getElementById('restoreBtnText');
        if (btn) {
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
        }
        if (text) {
            text.textContent = 'Memulihkan Sistem (Mohon Tunggu)...';
        }
    }

    function openDeleteBackupModal(filename) {
        document.getElementById('deleteBackupForm').action = `/admin/backups/${encodeURIComponent(filename)}`;
        document.getElementById('deleteBackupFilename').textContent = filename;
        document.getElementById('deleteBackupModal').classList.remove('hidden');
        document.documentElement.classList.add('modal-open');
    }

    function closeDeleteBackupModal() {
        document.getElementById('deleteBackupModal').classList.add('hidden');
        document.documentElement.classList.remove('modal-open');
    }
</script>
@endsection
