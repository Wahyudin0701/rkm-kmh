@extends('layouts.app')

@section('title', 'Backup & Restore Sistem - Super Admin RKM')

@section('content')
<div class="space-y-6">
    <!-- Header Halaman (Clean Minimalist) -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-100">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                Backup &amp; Restore Sistem
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Pencadangan terenkripsi berformat <code>.kmh</code> khusus untuk sistem RKM-KMH. File tidak dapat dibaca oleh sistem luar.
            </p>
        </div>
        <div class="flex items-center gap-2.5">
            <button type="button" 
                    onclick="openUploadBackupModal()" 
                    class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition shadow-2xs">
                Unggah File .kmh
            </button>
            <button type="button" 
                    onclick="openCreateBackupModal()" 
                    class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold transition shadow-xs">
                Buat Backup Baru
            </button>
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @if(session('success'))
        <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between text-emerald-900 text-xs font-medium">
            <span>{{ session('success') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 ml-3 text-sm">&times;</button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl flex items-center justify-between text-rose-900 text-xs font-medium">
            <span>{{ session('error') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 ml-3 text-sm">&times;</button>
        </div>
    @endif

    <!-- Ringkasan Metrik (Clean & Tanpa Ikon Berlebihan) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Total File Backup</span>
            <div class="flex items-baseline gap-2 mt-1.5">
                <span class="text-2xl font-bold text-slate-900">{{ number_format($stats['total_backups']) }}</span>
                <span class="text-xs text-slate-500 font-medium">file (.kmh)</span>
            </div>
            <span class="text-[11px] text-slate-400 block mt-1">Total kapasitas: {{ $stats['total_size_human'] }}</span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Backup Terakhir</span>
            <div class="mt-1.5">
                <span class="text-sm font-bold text-slate-900 block truncate">
                    {{ $stats['last_backup']['created_at_human'] ?? 'Belum ada backup' }}
                </span>
                <span class="text-[11px] text-slate-400 block mt-1 truncate font-mono">
                    {{ $stats['last_backup']['filename'] ?? '-' }}
                </span>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-2xs">
            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Database Aktif</span>
            <div class="flex items-baseline gap-2 mt-1.5">
                <span class="text-base font-bold text-slate-900 font-mono">{{ $stats['database_name'] }}</span>
                <span class="text-xs text-slate-500">({{ $stats['table_count'] }} Tabel)</span>
            </div>
            <span class="text-[11px] text-slate-400 block mt-1">Estimasi data: {{ $stats['estimated_db_size_human'] }}</span>
        </div>
    </div>

    <!-- Tabel Daftar File Backup -->
    <div class="bg-white border border-slate-200/80 rounded-2xl shadow-2xs overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600">
                Daftar Arsip Backup
            </h2>
            <span class="text-[11px] text-slate-400 font-mono">
                storage/app/backups/
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50/70 border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3 px-5">Nama File</th>
                        <th class="py-3 px-4">Tipe Backup</th>
                        <th class="py-3 px-4">Ukuran</th>
                        <th class="py-3 px-4">Tanggal Dibuat</th>
                        <th class="py-3 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($backups as $b)
                        @php
                            $isFull = ($b['type'] === 'semua');
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition">
                            <!-- Nama File -->
                            <td class="py-3 px-5">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-semibold text-slate-800 text-xs">
                                        {{ $b['filename'] }}
                                    </span>
                                    @if($b['is_kmh'])
                                        <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            .kmh
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Tipe Backup -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if($isFull)
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        Backup Semua
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        Pilih Backup
                                    </span>
                                @endif
                            </td>

                            <!-- Ukuran File -->
                            <td class="py-3 px-4 whitespace-nowrap font-mono text-slate-600">
                                {{ $b['size_human'] }}
                            </td>

                            <!-- Tanggal Dibuat -->
                            <td class="py-3 px-4 whitespace-nowrap text-slate-600">
                                <span>{{ $b['created_at_human'] }}</span>
                            </td>

                            <!-- Aksi (Clean text buttons) -->
                            <td class="py-3 px-5 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('admin.backups.download', $b['filename']) }}" 
                                       class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-[11px] transition">
                                        Unduh
                                    </a>

                                    <button type="button" 
                                            onclick="openRestoreModal('{{ $b['filename'] }}', '{{ $b['type_label'] }}', '{{ $b['size_human'] }}')" 
                                            class="px-2.5 py-1 rounded-lg border border-amber-300 bg-amber-50 hover:bg-amber-100 text-amber-900 font-semibold text-[11px] transition">
                                        Pulihkan
                                    </button>

                                    <button type="button" 
                                            onclick="openDeleteBackupModal('{{ $b['filename'] }}')" 
                                            class="px-2.5 py-1 rounded-lg border border-rose-200 bg-white hover:bg-rose-50 text-rose-700 font-semibold text-[11px] transition">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center">
                                <p class="text-xs text-slate-500 font-medium">Belum ada file backup yang tersimpan di server.</p>
                                <button type="button" 
                                        onclick="openCreateBackupModal()" 
                                        class="mt-2.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition">
                                    Buat Backup Pertama
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
<!-- MODAL: BUAT BACKUP (2 PILIHAN: PILIH BACKUP / BACKUP SEMUA) -->
<!-- ========================================================================= -->
<div id="createBackupModal" data-backdrop="static" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl max-w-2xl w-full shadow-xl overflow-hidden animate-scale-in border border-slate-100 flex flex-col max-h-[92vh]">
        <!-- Header Modal -->
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
            <div>
                <h3 class="text-base font-bold text-slate-900">Buat Backup Baru</h3>
                <p class="text-xs text-slate-400 mt-0.5">Pilih mode pencadangan terenkripsi berformat .kmh.</p>
            </div>
            <button type="button" onclick="closeCreateBackupModal()" class="text-slate-400 hover:text-slate-600 text-lg p-1">&times;</button>
        </div>

        <form id="createBackupForm" method="POST" action="{{ route('admin.backups.store') }}" onsubmit="handleBackupSubmit(this)" class="flex flex-col flex-1 min-h-0">
            @csrf

            <div class="p-5 space-y-4 overflow-y-auto flex-1">
                <!-- Dua Pilihan Utama -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Opsi 1: Pilih Backup (Tabel Tertentu) -->
                    <label id="scopeCardPilihan" class="p-4 rounded-xl border-2 border-indigo-600 bg-indigo-50/30 cursor-pointer transition block">
                        <div class="flex items-start gap-2.5">
                            <input type="radio" 
                                   name="backup_scope" 
                                   value="pilihan" 
                                   id="radioScopePilihan" 
                                   checked 
                                   onchange="toggleBackupScopeUI('pilihan')" 
                                   class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="block text-xs font-bold text-slate-900">Pilih Backup</span>
                                <span class="block text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                    Pilih tabel atau modul data tertentu yang ingin dicadangkan sesuai kebutuhan.
                                </span>
                            </div>
                        </div>
                    </label>

                    <!-- Opsi 2: Backup Semua -->
                    <label id="scopeCardSemua" class="p-4 rounded-xl border-2 border-slate-200 hover:border-slate-300 bg-white cursor-pointer transition block">
                        <div class="flex items-start gap-2.5">
                            <input type="radio" 
                                   name="backup_scope" 
                                   value="semua" 
                                   id="radioScopeSemua" 
                                   onchange="toggleBackupScopeUI('semua')" 
                                   class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="block text-xs font-bold text-slate-900">Backup Semua</span>
                                <span class="block text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                    Cadangkan seluruh tabel database (29 tabel) + seluruh file media upload.
                                </span>
                            </div>
                        </div>
                    </label>
                </div>

                <!-- Bagian Detail Checklist untuk "Pilih Backup" -->
                <div id="pilihanTableSection" class="space-y-3 pt-2">
                    <div class="flex items-center justify-between pb-1 border-b border-slate-100">
                        <div>
                            <span class="text-xs font-bold text-slate-800">Daftar Tabel yang Dicadangkan</span>
                            <span class="text-[11px] text-slate-400 block">Centang tabel yang ingin dimasukkan ke dalam file .kmh</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="selectAllTables(true)" class="text-[11px] text-indigo-600 hover:underline font-semibold">
                                Pilih Semua
                            </button>
                            <span class="text-slate-300">&bull;</span>
                            <button type="button" onclick="selectAllTables(false)" class="text-[11px] text-slate-500 hover:underline font-semibold">
                                Batal Pilih
                            </button>
                        </div>
                    </div>

                    <!-- Kelompok Modul Tabel -->
                    <div class="space-y-3 max-h-64 overflow-y-auto pr-1">
                        @foreach($tableGroups as $groupKey => $group)
                            @if(!empty($group['tables']))
                            <div class="border border-slate-200/80 rounded-xl p-3 bg-slate-50/50">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-bold text-slate-800">{{ $group['name'] }}</span>
                                    <span class="text-[10px] text-slate-400">{{ count($group['tables']) }} Tabel</span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                    @foreach($group['tables'] as $tbl)
                                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer select-none py-0.5 hover:text-slate-900">
                                        <input type="checkbox" 
                                               name="selected_tables[]" 
                                               value="{{ $tbl }}" 
                                               checked 
                                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 table-checkbox">
                                        <span class="font-mono text-[11px]">{{ $tbl }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        @endforeach
                    </div>

                    <!-- Opsi Sertakan Media untuk Pilih Backup -->
                    <label class="flex items-center gap-2 text-xs text-slate-700 cursor-pointer pt-2 select-none border-t border-slate-100">
                        <input type="checkbox" name="include_media" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs font-medium">Sertakan juga berkas media upload di <code>storage/app/public</code></span>
                    </label>
                </div>

                <!-- Bagian Penjelasan Ringkas untuk "Backup Semua" -->
                <div id="semuaTableSection" class="hidden p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-1 leading-relaxed">
                    <p class="font-bold text-slate-800">Cakupan Backup Semua:</p>
                    <ul class="list-disc list-inside text-[11px] space-y-0.5 text-slate-600 pl-1">
                        <li>Seluruh tabel dan struktur database MySQL sistem RKM-KMH.</li>
                        <li>Seluruh file media upload (foto profil karyawan, foto armada kendaraan, stiker izin SIMPER).</li>
                        <li>Dikompresi dan dienkripsi AES-256-CBC ke dalam format tunggal <strong>.kmh</strong>.</li>
                    </ul>
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-2.5 flex-shrink-0">
                <button type="button" onclick="closeCreateBackupModal()" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 font-semibold text-xs transition">
                    Batal
                </button>
                <button type="submit" id="createBackupSubmitBtn" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-xs">
                    <span id="createBackupBtnText">Mulai Pencadangan (.kmh)</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: UNGGAH FILE BACKUP (.KMH) -->
<!-- ========================================================================= -->
<div id="uploadBackupModal" data-backdrop="static" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-xl overflow-hidden animate-scale-in border border-slate-100">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Unggah File Backup (.kmh)</h3>
            <button type="button" onclick="closeUploadBackupModal()" class="text-slate-400 hover:text-slate-600 text-lg p-1">&times;</button>
        </div>

        <form id="uploadBackupForm" method="POST" action="{{ route('admin.backups.upload') }}" enctype="multipart/form-data" class="p-5 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-2">Pilih File .kmh</label>
                <div class="border-2 border-dashed border-slate-300 hover:border-indigo-500 rounded-xl p-5 text-center bg-slate-50 hover:bg-white transition cursor-pointer"
                     onclick="document.getElementById('backupFileInput').click()">
                    <p class="text-xs font-bold text-slate-800" id="uploadFileName">Klik untuk memilih file</p>
                    <p class="text-[11px] text-slate-400 mt-1">Format arsip terenkripsi: .kmh (Maks. 200 MB)</p>
                    <input type="file" 
                           name="backup_file" 
                           id="backupFileInput" 
                           accept=".kmh,.sql,.zip" 
                           required 
                           class="hidden" 
                           onchange="handleUploadFileChange(this)">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeUploadBackupModal()" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 font-semibold text-xs transition">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs transition shadow-xs">
                    Unggah File
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: KONFIRMASI PEMULIHAN (RESTORE) -->
<!-- ========================================================================= -->
<div id="restoreModal" data-backdrop="static" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full shadow-xl overflow-hidden animate-scale-in border border-slate-100">
        <div class="px-5 py-4 bg-amber-50/70 border-b border-amber-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-amber-900">Konfirmasi Pemulihan Sistem</h3>
            <button type="button" onclick="closeRestoreModal()" class="text-slate-400 hover:text-slate-600 text-lg p-1">&times;</button>
        </div>

        <form id="restoreForm" method="POST" action="" onsubmit="handleRestoreSubmit(this)" class="p-5 space-y-4">
            @csrf

            <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-900 leading-relaxed">
                <strong>Peringatan:</strong> Proses ini akan menimpa data di database saat ini dengan isi arsip backup yang dipilih.
            </div>

            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-xs space-y-1 font-mono">
                <div class="flex justify-between">
                    <span class="text-slate-400">File:</span>
                    <span id="restoreTargetFilename" class="font-bold text-slate-800"></span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-800 mb-1">
                    Ketik kata <span class="font-mono font-bold text-slate-900 bg-slate-100 px-1 py-0.5 rounded">PULIHKAN</span> atau masukkan password Anda:
                </label>
                <input type="text" 
                       name="confirmation" 
                       id="restoreConfirmationInput" 
                       placeholder="PULIHKAN atau password..." 
                       required 
                       autocomplete="off" 
                       class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:outline-none focus:border-amber-500 font-semibold">
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeRestoreModal()" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-600 font-semibold text-xs transition">
                    Batal
                </button>
                <button type="submit" id="restoreSubmitBtn" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs transition shadow-xs">
                    <span id="restoreBtnText">Ya, Pulihkan Sistem</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: KONFIRMASI HAPUS -->
<!-- ========================================================================= -->
<div id="deleteBackupModal" data-backdrop="static" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-sm w-full p-5 shadow-xl border border-slate-100 text-center">
        <h3 class="text-sm font-bold text-slate-900">Hapus File Backup?</h3>
        <p class="text-xs text-slate-500 mt-1">
            File <span id="deleteBackupFilename" class="font-mono font-semibold text-slate-800"></span> akan dihapus permanen dari server.
        </p>

        <form id="deleteBackupForm" method="POST" action="" class="mt-5 flex items-center justify-center gap-2">
            @csrf
            @method('DELETE')
            <button type="button" onclick="closeDeleteBackupModal()" class="flex-1 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition">
                Batal
            </button>
            <button type="submit" class="flex-1 px-3 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-semibold transition">
                Hapus
            </button>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT LOGIC INTERAKTIF -->
<!-- ========================================================================= -->
<script>
    function toggleBackupScopeUI(scope) {
        const cardPilihan = document.getElementById('scopeCardPilihan');
        const cardSemua = document.getElementById('scopeCardSemua');
        const sectionPilihan = document.getElementById('pilihanTableSection');
        const sectionSemua = document.getElementById('semuaTableSection');

        if (scope === 'pilihan') {
            cardPilihan.classList.add('border-indigo-600', 'bg-indigo-50/30');
            cardPilihan.classList.remove('border-slate-200', 'bg-white');
            cardSemua.classList.remove('border-indigo-600', 'bg-indigo-50/30');
            cardSemua.classList.add('border-slate-200', 'bg-white');

            sectionPilihan.classList.remove('hidden');
            sectionSemua.classList.add('hidden');
        } else {
            cardSemua.classList.add('border-indigo-600', 'bg-indigo-50/30');
            cardSemua.classList.remove('border-slate-200', 'bg-white');
            cardPilihan.classList.remove('border-indigo-600', 'bg-indigo-50/30');
            cardPilihan.classList.add('border-slate-200', 'bg-white');

            sectionPilihan.classList.add('hidden');
            sectionSemua.classList.remove('hidden');
        }
    }

    function selectAllTables(checked) {
        document.querySelectorAll('.table-checkbox').forEach(cb => {
            cb.checked = checked;
        });
    }

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
        if (text) text.textContent = 'Memproses Arsip .kmh...';
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
        if (text) text.textContent = 'Memulihkan Sistem...';
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
