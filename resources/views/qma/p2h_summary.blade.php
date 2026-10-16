@extends('layouts.app')

@section('title', 'Summary P2H Unit Support - Admin QMA')

@section('content')
<div class="w-full space-y-6">
    <!-- Header Halaman Summary P2H -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center space-x-1.5 mb-1">
                <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20]">
                    MANAJEMEN SISTEM
                </span>
                <!-- Tombol Info Panduan Halaman Sejajar Kicker -->
                <button type="button" 
                        onclick="openGuideModal()" 
                        class="w-3.5 h-3.5 rounded-full bg-slate-200/90 hover:bg-slate-300 text-slate-500 hover:text-slate-800 text-[8px] inline-flex items-center justify-center font-bold transition shadow-2xs cursor-pointer focus:outline-none" 
                        title="Panduan Halaman Summary P2H">
                    <i class="fa-solid fa-info"></i>
                </button>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Summary P2H Unit Support
            </h2>
        </div>

        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 sm:gap-2.5 w-full sm:w-auto">
            <!-- Dropdown Menu Ekspor Format it.allrole.web.id -->
            <div class="relative inline-block flex-1 sm:flex-none" id="exportP2hSummaryDropdownContainer">
                <button type="button" 
                        onclick="toggleP2hSummaryExportMenu(event)" 
                        id="btnP2hSummaryExportTrigger"
                        class="w-full sm:w-auto flex items-center justify-center space-x-2 border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3.5 sm:px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                    <i class="fa-regular fa-file-excel text-emerald-700 text-sm"></i>
                    <span class="truncate">Ekspor Summary</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
                </button>
                <div id="exportMenuP2hSummary" style="display: none;" class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-slate-100 z-50 overflow-hidden">
                    <a href="{{ route('qma.p2h.summary.export_pdf', request()->query()) }}" target="_blank" class="flex items-center px-4 py-3 text-xs text-slate-700 hover:bg-slate-50 hover:text-[#1b5e20] font-bold transition">
                        <i class="fa-regular fa-file-pdf text-rose-600 text-sm mr-2.5"></i>
                        <span>Format PDF (Cetak)</span>
                    </a>
                    <div class="h-px bg-slate-100 mx-2"></div>
                    <a href="{{ route('qma.p2h.summary.export_excel', request()->query()) }}" class="flex items-center px-4 py-3 text-xs text-emerald-700 hover:bg-emerald-50 font-bold transition">
                        <i class="fa-regular fa-file-excel text-emerald-600 text-sm mr-2.5"></i>
                        <span>Format Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            <a href="{{ route('qma.p2h.index') }}" class="flex-1 sm:flex-none flex items-center justify-center space-x-2 px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-sm transition group">
                <i class="fa-solid fa-clipboard-check text-slate-400 group-hover:text-slate-700"></i>
                <span class="truncate">Detail P2H</span>
            </a>
            <a href="{{ route('qma.p2h.templates') }}" class="flex-1 sm:flex-none flex items-center justify-center space-x-2 px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-sm transition group">
                <i class="fa-solid fa-layer-group text-slate-400 group-hover:text-slate-700"></i>
                <span class="truncate">Master Template</span>
            </a>
        </div>
    </div>

    @php
        $activeFilterCount = count(array_filter(request()->only(['search', 'start_date', 'end_date', 'vehicle_id', 'validation_status', 'shift'])));
        $isFilterOpen = request()->anyFilled(['start_date', 'end_date', 'vehicle_id', 'validation_status', 'shift']);
    @endphp

    <!-- Form Filter & Pencarian Cepat -->
    <form method="GET" action="{{ route('qma.p2h.summary') }}" class="space-y-3">
        <!-- Baris Atas: Search Bar Mandiri di Luar Card Filter & Tombol Toggle -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <!-- Search Bar Utama (Real-Time) -->
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
                <input type="text" 
                       name="search" 
                       id="p2hSummarySearchInput"
                       value="{{ request('search') }}" 
                       placeholder="Cari No. TRX, kode unit (EX01), model, nama operator..." 
                       class="w-full text-xs rounded-xl border border-slate-200 bg-white hover:border-slate-300 focus:bg-white pl-9 pr-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition shadow-2xs">
            </div>

            <!-- Tombol Buka/Tutup Filter Card & Reset Filter -->
            <div class="flex items-center gap-2.5 self-end sm:self-auto w-full sm:w-auto justify-between sm:justify-start">
                <button type="button" 
                        id="filterToggleBtn"
                        onclick="toggleFilterCard()" 
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 shadow-2xs transition group cursor-pointer">
                    <i class="fa-solid fa-sliders text-slate-500 mr-2 group-hover:text-[#1b5e20] transition-colors"></i>
                    <span>Filter</span>
                    @if($activeFilterCount > 0)
                        <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] font-extrabold bg-[#1b5e20] text-white">
                            {{ $activeFilterCount }}
                        </span>
                    @endif
                    <i id="filterChevron" class="fa-solid fa-chevron-down ml-2 text-[10px] text-slate-400 transition-transform duration-200 {{ $isFilterOpen ? 'rotate-180' : '' }}"></i>
                </button>

                <div id="p2hSummaryResetContainer">
                    @if(request()->anyFilled(['search', 'start_date', 'end_date', 'vehicle_id', 'validation_status', 'shift']))
                        <a href="{{ route('qma.p2h.summary') }}" 
                           id="p2hSummaryResetBtn"
                           class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center space-x-1 transition px-3 py-2 rounded-xl hover:bg-rose-50 border border-transparent hover:border-rose-100">
                            <i class="fa-solid fa-rotate-left text-[11px]"></i>
                            <span>Reset Filter</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Card Filter Lanjutan (Bisa Di Buka-Tutup / Collapsible) -->
        <div id="collapsibleFilterCard" class="{{ $isFilterOpen ? '' : 'hidden ' }}bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 transition-all">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3.5">
                <div class="flex items-center space-x-2">
                    <span class="text-slate-800 font-bold text-xs uppercase tracking-wider">
                        Opsi Filter Lanjutan
                    </span>
                    @if($activeFilterCount > 0)
                        <span class="text-[11px] text-slate-400 font-medium">({{ $activeFilterCount }} filter aktif)</span>
                    @endif
                </div>
                <button type="button" 
                        onclick="toggleFilterCard()" 
                        class="text-xs text-slate-400 hover:text-slate-600 transition flex items-center space-x-1 cursor-pointer">
                    <span>Tutup</span>
                    <i class="fa-solid fa-xmark text-[11px]"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                <!-- Mulai Tanggal -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Mulai Tanggal</label>
                    <input type="date" 
                           name="start_date" 
                           value="{{ request('start_date', $startDate ?? now()->startOfMonth()->format('Y-m-d')) }}" 
                           onchange="this.form.submit()"
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>

                <!-- Sampai Tanggal -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Sampai Tanggal</label>
                    <input type="date" 
                           name="end_date" 
                           value="{{ request('end_date', $endDate ?? now()->format('Y-m-d')) }}" 
                           onchange="this.form.submit()"
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>

                <!-- Unit Support -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Unit Support</label>
                    <select name="vehicle_id" onchange="this.form.submit()" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Unit Support</option>
                        @foreach($supportVehicles as $sv)
                            <option value="{{ $sv->id }}" {{ request('vehicle_id') == $sv->id ? 'selected' : '' }}>
                                {{ $sv->code }} - {{ $sv->brand_type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Validasi -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Status Validasi</label>
                    <select name="validation_status" onchange="this.form.submit()" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('validation_status') === 'pending' ? 'selected' : '' }}>Menunggu Approval</option>
                        <option value="approved" {{ request('validation_status') === 'approved' ? 'selected' : '' }}>Disetujui (Approved)</option>
                        <option value="rejected" {{ request('validation_status') === 'rejected' ? 'selected' : '' }}>Ditolak (Rejected)</option>
                    </select>
                </div>

                <!-- Shift Kerja -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Shift Kerja</label>
                    <select name="shift" onchange="this.form.submit()" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Shift</option>
                        <option value="1" {{ request('shift') === '1' ? 'selected' : '' }}>Shift 1 (Pagi)</option>
                        <option value="2" {{ request('shift') === '2' ? 'selected' : '' }}>Shift 2 (Malam)</option>
                    </select>
                </div>
            </div>
        </div>
    </form>

    <!-- 4 Kartu KPI Metrik Ringkasan P2H -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <!-- 1. Total Inspeksi P2H -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <p class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Total Inspeksi P2H</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">{{ number_format($totalInspections, 0, ',', '.') }}</h3>
            </div>
            <div class="flex flex-wrap items-center gap-1 sm:space-x-2 mt-2 text-[10px] sm:text-[11px]">
                <span class="text-emerald-700 font-semibold"><i class="fa-solid fa-check mr-0.5"></i> {{ $totalApproved }} Disetujui</span>
                <span class="text-slate-300 hidden sm:inline">•</span>
                <span class="text-amber-600 font-semibold"><i class="fa-solid fa-hourglass-half mr-0.5"></i> {{ $totalPending }} Menunggu</span>
            </div>
        </div>

        <!-- 2. Kondisi Fisik Alat Berat -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <p class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Kondisi Alat Berat</p>
                <h3 class="text-xl sm:text-2xl font-black text-emerald-700 mt-1">
                    {{ $totalInspections > 0 ? round(($totalNormal / $totalInspections) * 100) : 0 }}% <span class="text-xs font-sans font-bold text-slate-500">Normal</span>
                </h3>
            </div>
            <div class="flex flex-wrap items-center gap-1 sm:space-x-2 mt-2 text-[10px] sm:text-[11px]">
                <span class="text-emerald-700 font-semibold">{{ $totalNormal }} Unit Baik</span>
                <span class="text-slate-300 hidden sm:inline">•</span>
                <span class="text-rose-600 font-semibold">{{ $totalDefects }} Ada Temuan Rusak</span>
            </div>
        </div>

        <!-- 3. Status Penolakan / Tindak Lanjut -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <p class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Unit Ditolak / Reject</p>
                <h3 class="text-xl sm:text-2xl font-black text-rose-600 mt-1">{{ number_format($totalRejected, 0, ',', '.') }}</h3>
            </div>
            <div class="flex flex-wrap items-center gap-1 sm:space-x-2 mt-2 text-[10px] sm:text-[11px]">
                <span class="text-rose-600 font-semibold"><i class="fa-solid fa-triangle-exclamation mr-0.5"></i> {{ $totalRejected }} Perlu Perbaikan</span>
            </div>
        </div>

        <!-- 4. Total Akumulasi Hour Meter (HM) -->
        <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <p class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider">Total HM Tercatat</p>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 font-mono mt-1">
                    {{ number_format($totalHm, 1, ',', '.') }} <span class="text-xs font-sans font-bold text-slate-500">Jam</span>
                </h3>
            </div>
            <div class="flex flex-wrap items-center gap-1 sm:space-x-2 mt-2 text-[10px] sm:text-[11px]">
                <span class="text-slate-500 font-semibold"><i class="fa-solid fa-clock mr-0.5"></i> Hour Meter Operasi</span>
            </div>
        </div>
    </div>

    <!-- Tabel Rekapitulasi per Unit Support -->
    <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Rekapitulasi Pemeriksaan per Unit Support</h3>
                <p class="text-xs text-slate-400 mt-0.5">Ringkasan kepatuhan dan status kelayakan inspeksi masing-masing unit alat berat.</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                {{ $unitSummary->count() }} Unit Aktif
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">No</th>
                        <th class="py-3.5 px-4">Kode Unit</th>
                        <th class="py-3.5 px-4">Model & Kategori</th>
                        <th class="py-3.5 px-4 text-center">Total P2H</th>
                        <th class="py-3.5 px-4 text-center">Disetujui</th>
                        <th class="py-3.5 px-4 text-center">Menunggu</th>
                        <th class="py-3.5 px-4 text-center">Ditolak</th>
                        <th class="py-3.5 px-4 text-center">Temuan Rusak</th>
                        <th class="py-3.5 px-4 text-right">HM Terakhir</th>
                        <th class="py-3.5 px-4 text-center">Terakhir Diperiksa</th>
                    </tr>
                </thead>
                <tbody id="p2hSummaryTableBody" class="divide-y divide-slate-100">
                    @forelse($unitSummary as $idx => $us)
                        <tr class="p2h-summary-row hover:bg-slate-50/80 transition">
                            <td class="py-3.5 px-4 text-center text-slate-400 font-medium">{{ $loop->iteration }}</td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-bold text-slate-900">{{ $us['vehicle']->code ?? '-' }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="text-slate-800 font-semibold block">{{ $us['vehicle']->brand_type ?? '-' }}</span>
                                <span class="text-slate-400 text-[10px]">{{ $us['vehicle']->unit_category ?? 'Alat Berat' }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap font-bold text-slate-900">
                                {{ number_format($us['total']) }}
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap font-semibold text-emerald-700">
                                {{ number_format($us['approved']) }}
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap font-semibold text-amber-600">
                                {{ number_format($us['pending']) }}
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap font-semibold {{ $us['rejected'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                {{ number_format($us['rejected']) }}
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($us['has_defect'] > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        {{ $us['has_defect'] }} Temuan
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Normal
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-800 whitespace-nowrap">
                                {{ $us['latest_hm'] ? number_format($us['latest_hm'], 1, ',', '.') : '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap text-slate-500">
                                {{ $us['latest_inspection'] ? \Carbon\Carbon::parse($us['latest_inspection'])->format('d/m/Y H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr id="serverP2hSummaryEmptyRow">
                            <td colspan="10" class="py-12 px-4 text-center">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-xl shadow-xs">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </div>
                                    @if(request('search'))
                                        <h4 class="text-sm font-bold text-slate-800">
                                            Data "<span class="text-rose-600 font-bold">{{ request('search') }}</span>" tidak ditemukan di tabel
                                        </h4>
                                        <p class="text-xs text-slate-400 max-w-sm">
                                            Pastikan kata kunci yang dicari sudah benar atau gunakan filter lainnya.
                                        </p>
                                        <div class="mt-4">
                                            <a href="{{ route('qma.p2h.summary') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                                <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                                <span>Hapus Pencarian</span>
                                            </a>
                                        </div>
                                    @else
                                        <div class="text-sm font-bold text-slate-700">Belum Ada Data Rekapitulasi</div>
                                        <p class="text-xs text-slate-400">Tidak ada data rekapitulasi P2H untuk rentang tanggal yang dipilih.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT MODAL PANDUAN HALAMAN                                          -->
<!-- ========================================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('p2hSummarySearchInput');
    const tableBody = document.getElementById('p2hSummaryTableBody');
    const resetContainer = document.getElementById('p2hSummaryResetContainer');
    const serverEmptyRow = document.getElementById('serverP2hSummaryEmptyRow');
    const hasServerFilters = {{ request()->anyFilled(['start_date', 'end_date', 'vehicle_id', 'validation_status', 'shift']) ? 'true' : 'false' }};

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function (e) {
            const rawValue = e.target.value;
            const query = rawValue.toLowerCase().trim();
            const rows = tableBody.querySelectorAll('tr.p2h-summary-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const match = text.includes(query);
                row.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });

            // Sembunyikan baris SSR jika user sedang mengetik
            if (serverEmptyRow) {
                serverEmptyRow.style.display = query.length > 0 ? 'none' : '';
            }

            // Hapus baris pesan kosong pencarian client jika ada sebelumnya
            const existingEmptyRow = document.getElementById('p2hSummaryClientEmptyRow');
            if (existingEmptyRow) {
                existingEmptyRow.remove();
            }

            // Tampilkan pesan jika tidak ada data yang cocok di tabel
            if (visibleCount === 0 && query.length > 0) {
                const emptyTr = document.createElement('tr');
                emptyTr.id = 'p2hSummaryClientEmptyRow';
                emptyTr.innerHTML = `
                    <td colspan="10" class="py-12 px-4 text-center">
                        <div class="flex flex-col items-center justify-center space-y-3">
                            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-xl shadow-xs">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </div>
                            <h4 class="text-sm font-bold text-slate-800">
                                Data "<span class="text-rose-600 font-bold">${escapeHtml(rawValue)}</span>" tidak ditemukan di tabel
                            </h4>
                            <p class="text-xs text-slate-400 max-w-sm">
                                Pastikan kata kunci yang dicari sudah benar atau gunakan filter lainnya.
                            </p>
                            <div class="mt-4">
                                <button type="button" 
                                        onclick="const inp = document.getElementById('p2hSummarySearchInput'); if(inp){ inp.value=''; inp.dispatchEvent(new Event('input')); inp.focus(); }" 
                                        class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                    <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                    <span>Hapus Pencarian</span>
                                </button>
                            </div>
                        </div>
                    </td>
                `;
                tableBody.appendChild(emptyTr);
            }

            // Tampilkan atau sembunyikan tombol Reset Filter secara real-time
            if (resetContainer) {
                let resetBtn = document.getElementById('p2hSummaryResetBtn');
                if (query.length > 0 || hasServerFilters) {
                    if (!resetBtn) {
                        resetBtn = document.createElement('a');
                        resetBtn.href = "{{ route('qma.p2h.summary') }}";
                        resetBtn.id = 'p2hSummaryResetBtn';
                        resetBtn.className = 'text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center space-x-1 transition px-3 py-2 rounded-xl hover:bg-rose-50 border border-transparent hover:border-rose-100';

                        const resetIcon = document.createElement('i');
                        resetIcon.className = 'fa-solid fa-rotate-left text-[11px]';
                        const resetText = document.createElement('span');
                        resetText.textContent = 'Reset Filter';

                        resetBtn.appendChild(resetIcon);
                        resetBtn.appendChild(resetText);
                        resetContainer.appendChild(resetBtn);
                    }
                } else {
                    if (resetBtn && !hasServerFilters) {
                        resetBtn.remove();
                    }
                }
            }
        });
    }
});

    function toggleFilterCard() {
        const card = document.getElementById('collapsibleFilterCard');
        const chevron = document.getElementById('filterChevron');
        if (card) {
            card.classList.toggle('hidden');
        }
        if (chevron) {
            chevron.classList.toggle('rotate-180');
        }
    }

    function openGuideModal() {
        const modal = document.getElementById('guideModal');
        if (modal) {
            modal.classList.remove('hidden');
            document.documentElement.classList.add('modal-open');
            document.body.classList.add('modal-open');
        }
    }

    function closeGuideModal() {
        const modal = document.getElementById('guideModal');
        if (modal) {
            modal.classList.add('hidden');
            document.documentElement.classList.remove('modal-open');
            document.body.classList.remove('modal-open');
        }
    }

    // Dropdown Menu Ekspor Summary P2H Unit Support
    function toggleP2hSummaryExportMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('exportMenuP2hSummary');
        if (menu) {
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }
    }

    document.addEventListener('click', function(event) {
        const container = document.getElementById('exportP2hSummaryDropdownContainer');
        const menu = document.getElementById('exportMenuP2hSummary');
        if (container && !container.contains(event.target)) {
            if (menu) {
                menu.style.display = 'none';
            }
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGuideModal();
            const exportMenu = document.getElementById('exportMenuP2hSummary');
            if (exportMenu) exportMenu.style.display = 'none';
        }
    });
</script>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PANDUAN HALAMAN (SUMMARY P2H)                                -->
<!-- ========================================================================= -->
<div id="guideModal" 
     onclick="if(event.target === this) closeGuideModal()" 
     class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-7 border border-slate-100 transform transition-all my-8">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-sm border border-slate-200">
                    <i class="fa-solid fa-info"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight leading-tight">Panduan Halaman</h3>
                    <p class="text-xs text-slate-500">Summary P2H Unit Support</p>
                </div>
            </div>
            <button type="button" 
                    onclick="closeGuideModal()" 
                    class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-100 cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="mt-4 space-y-4">
            <p class="text-xs text-slate-600 leading-relaxed">
                Halaman <strong>Summary P2H Unit Support</strong> menampilkan rekapitulasi performa dan tingkat kelaikan operasi harian alat berat.
            </p>

            <div class="space-y-3">
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-emerald-200/50">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Rekapitulasi per Unit Support</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Melihat total inspeksi, jumlah lolos persetujuan, unit dengan temuan kerusakan, serta catatan jam kerja (HM) setiap alat.
                        </p>
                    </div>
                </div>

                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-amber-200/50">
                        <i class="fa-solid fa-filter"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Filter Fleksibel</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Pilih rentang tanggal, filter berdasarkan unit spesifik, status approval, atau shift kerja.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-4 mt-5 border-t border-slate-100 flex items-center justify-end">
            <button type="button" 
                    onclick="closeGuideModal()" 
                    class="px-6 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer shadow-2xs">
                Saya Mengerti
            </button>
        </div>
    </div>
</div>
@endsection
