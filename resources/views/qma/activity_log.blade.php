@extends('layouts.app')

@section('title', 'Timesheet LV QMA')

@section('content')
<div class="w-full space-y-6">
    <!-- Header Judul & Total Catatan -->
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
                        title="Panduan Halaman Timesheet LV">
                    <i class="fa-solid fa-info"></i>
                </button>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Timesheet LV
            </h2>
        </div>

        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 sm:gap-2.5 w-full sm:w-auto">
            <!-- Dropdown Menu Ekspor Format it.allrole.web.id -->
            <div class="relative inline-block flex-1 sm:flex-none" id="exportActivityDropdownContainer">
                <button type="button" 
                        onclick="toggleActivityExportMenu(event)" 
                        id="btnActivityExportTrigger"
                        class="w-full sm:w-auto flex items-center justify-center space-x-2 border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3.5 sm:px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                    <i class="fa-regular fa-file-excel text-emerald-700 text-sm"></i>
                    <span class="truncate">Ekspor Timesheet</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
                </button>
                <div id="exportMenuActivity" style="display: none;" class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-slate-100 z-50 overflow-hidden">
                    <a href="{{ route('qma.activity.export_pdf', request()->query()) }}" target="_blank" class="flex items-center px-4 py-3 text-xs text-slate-700 hover:bg-slate-50 hover:text-[#1b5e20] font-bold transition">
                        <i class="fa-regular fa-file-pdf text-rose-600 text-sm mr-2.5"></i>
                        <span>Format PDF (Cetak)</span>
                    </a>
                    <div class="h-px bg-slate-100 mx-2"></div>
                    <a href="{{ route('qma.activity.export_excel', request()->query()) }}" class="flex items-center px-4 py-3 text-xs text-emerald-700 hover:bg-emerald-50 font-bold transition">
                        <i class="fa-regular fa-file-excel text-emerald-600 text-sm mr-2.5"></i>
                        <span>Format Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            <a href="{{ route('qma.activity.summary') }}" class="flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-sm transition group">
                <i class="fa-solid fa-chart-pie mr-2 text-slate-500 group-hover:text-slate-800"></i>
                <span class="whitespace-nowrap">Summary Aktifitas</span>
            </a>
        </div>
    </div>

    @php
        $activeFilterCount = count(array_filter(request()->only(['start_date', 'end_date', 'gate_id', 'direction', 'status'])));
        $isFilterOpen = request()->anyFilled(['start_date', 'end_date', 'gate_id', 'direction', 'status']);
    @endphp

    <!-- Form Filter & Pencarian Cepat -->
    <form method="GET" action="{{ route('qma.activity.log') }}" class="space-y-3">
        <!-- Baris Atas: Search Bar Mandiri di Luar Card Filter & Tombol Toggle -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <!-- Search Bar Utama (Real-Time) -->
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
                <input type="text" 
                       name="search" 
                       id="activityLogSearchInput"
                       value="{{ request('search') }}" 
                       placeholder="Cari Kode LV, Driver, Plat, Keperluan..." 
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

                <div id="headerResetFilterContainer">
                    @if(request()->anyFilled(['search', 'start_date', 'end_date', 'gate_id', 'direction', 'status']))
                        <a href="{{ route('qma.activity.log') }}" 
                           id="headerResetFilterBtn" 
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
            <!-- Header Card Filter -->
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

            <!-- Baris Input Filter (Grid Responsif 12 Kolom) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5">
                <!-- Rentang Mulai Tanggal -->
                <div class="lg:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Mulai Tanggal
                    </label>
                    <input type="date" 
                           name="start_date" 
                           value="{{ request('start_date', $startDate ?? now()->format('Y-m-d')) }}" 
                           onchange="this.form.submit()"
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>

                <!-- Rentang Sampai Tanggal -->
                <div class="lg:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Sampai Tanggal
                    </label>
                    <input type="date" 
                           name="end_date" 
                           value="{{ request('end_date', $endDate ?? now()->format('Y-m-d')) }}" 
                           onchange="this.form.submit()"
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>

                <!-- Pos Gerbang -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Pos Gerbang
                    </label>
                    <select name="gate_id" onchange="this.form.submit()" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Gerbang</option>
                        @foreach($gates as $g)
                            <option value="{{ $g->id }}" {{ request('gate_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Arah Akses -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Arah Akses
                    </label>
                    <select name="direction" onchange="this.form.submit()" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Arah</option>
                        <option value="in" {{ request('direction') === 'in' ? 'selected' : '' }}>MASUK (In)</option>
                        <option value="out" {{ request('direction') === 'out' ? 'selected' : '' }}>KELUAR (Out)</option>
                    </select>
                </div>

                <!-- Status Validasi Pos Gerbang -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Status PG
                    </label>
                    <select name="status" onchange="this.form.submit()" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Status</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu PG</option>
                    </select>
                </div>
            </div>
        </div>
    </form>

    <!-- Tabel Log Aktivitas (Pada Mobile: Format Kotak Tanpa Border Radius) -->
    <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">Tanggal</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">Kode LV</th>
                        <th class="py-3.5 px-4 whitespace-nowrap min-w-[220px]">Unit</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">Penanggung Jawab</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">Driver</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">Gate</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">Akses</th>
                        <th class="py-3.5 px-4 text-right whitespace-nowrap">KM Sebelumnya</th>
                        <th class="py-3.5 px-4 text-right whitespace-nowrap">KM Berjalan</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">Aktivitas</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">Status PG</th>
                    </tr>
                </thead>
                <tbody id="activityLogTableBody" class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($activities as $act)
                        <tr class="activity-row hover:bg-slate-50/70 transition">
                            <!-- Tanggal & Waktu -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="font-bold text-slate-900">{{ $act->recorded_at->locale('id')->translatedFormat('d M Y') }}</div>
                                <div class="text-xs text-slate-400 font-medium mt-0.5">{{ $act->recorded_at->format('H:i') }} WIB</div>
                            </td>

                            <!-- Kode LV -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap font-mono">
                                <span class="inline-block bg-emerald-50 text-emerald-800 font-bold px-2.5 py-1 rounded border border-emerald-200">
                                    {{ $act->vehicle->code }}
                                </span>
                            </td>

                            <!-- Unit & Plat -->
                            <td class="py-3.5 px-4 min-w-[220px]">
                                <div class="font-bold text-slate-900 whitespace-nowrap">{{ $act->vehicle->brand_type }}</div>
                                @if($act->vehicle->license_plate)
                                    <span class="inline-block text-[10px] font-mono font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 mt-0.5 whitespace-nowrap">
                                        {{ $act->vehicle->license_plate }}
                                    </span>
                                @else
                                    <span class="text-[10px] text-slate-400 italic">-</span>
                                @endif
                            </td>

                            <!-- Penanggung Jawab -->
                            <td class="py-3.5 px-4 text-slate-600 font-medium">
                                {{ $act->vehicle->ownership ?? '-' }}
                            </td>

                            <!-- Driver -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">{{ $act->driver->name }}</div>
                                <div class="text-[10px] text-slate-400 font-normal">{{ $act->driver->division ?? '-' }}</div>
                            </td>

                            <!-- Gate -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $act->gate->name }}
                                </span>
                            </td>

                            <!-- Akses (In / Out) -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($act->direction === 'in')
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        MASUK
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        KELUAR
                                    </span>
                                @endif
                            </td>

                            <!-- KM Sebelumnya -->
                            <td class="py-3.5 px-4 text-right font-mono font-medium text-slate-600 whitespace-nowrap">
                                {{ number_format($act->previous_km, 0, ',', '.') }}
                                <span class="text-[10px] text-slate-400 font-sans font-normal">KM</span>
                            </td>

                            <!-- KM Berjalan & Akhir -->
                            <td class="py-3.5 px-4 text-right font-mono whitespace-nowrap">
                                <div class="font-bold text-emerald-600 text-xs">
                                    +{{ number_format($act->distance, 0, ',', '.') }} <span class="text-[10px] font-sans font-normal">KM</span>
                                </div>
                                <div class="text-[10px] text-slate-400 font-normal">
                                    Akhir: {{ number_format($act->current_km, 0, ',', '.') }}
                                </div>
                            </td>

                            <!-- Aktivitas -->
                            <td class="py-3.5 px-4 text-slate-700 max-w-xs" title="{{ $act->purpose }}">
                                <div class="truncate">{{ $act->purpose }}</div>
                            </td>

                            <!-- Status PG -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($act->validation_status === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Divalidasi oleh: {{ $act->securityGuard->name ?? 'Satpam' }}">
                                        <i class="fa-solid fa-circle-check mr-1 text-emerald-600"></i> Disetujui
                                    </span>
                                @elseif($act->validation_status === 'rejected')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="Alasan: {{ $act->rejection_note }}">
                                        <i class="fa-solid fa-circle-xmark mr-1 text-rose-600"></i> Ditolak
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 animate-pulse">
                                        <i class="fa-solid fa-clock mr-1 text-amber-600"></i> Menunggu PG
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr id="serverActivityEmptyRow">
                            <td colspan="11" class="py-12 px-4 text-center">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-2xl">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </div>
                                    @if(request('search'))
                                        <p class="text-sm font-semibold text-slate-700">
                                            Data "<span class="text-rose-600 font-bold">{{ request('search') }}</span>" tidak ditemukan di tabel
                                        </p>
                                        <p class="text-xs text-slate-400 mt-1">
                                            Pastikan kata kunci yang dicari sudah benar.
                                        </p>
                                        <div class="mt-4">
                                            <a href="{{ route('qma.activity.log') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                                <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                                <span>Hapus Pencarian</span>
                                            </a>
                                        </div>
                                    @else
                                        <div class="text-sm font-bold text-slate-700">Belum Ada Data Log Aktivitas</div>
                                        <p class="text-xs text-slate-400 max-w-sm">
                                            Tidak ditemukan catatan aktivitas kendaraan yang sesuai dengan kriteria filter saat ini.
                                        </p>
                                        @if(request()->anyFilled(['start_date', 'end_date', 'gate_id', 'direction', 'status']))
                                            <a href="{{ route('qma.activity.log') }}" class="mt-2 inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-[#1b5e20] bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition">
                                                <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                                <span>Reset Filter</span>
                                            </a>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($activities->hasPages())
            <div id="activityPaginationContainer" class="p-4 bg-slate-50 border-t border-slate-100">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
</div>

<script>
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('activityLogSearchInput');
    const tableBody = document.getElementById('activityLogTableBody');
    const resetContainer = document.getElementById('headerResetFilterContainer');
    const paginationContainer = document.getElementById('activityPaginationContainer');
    const serverEmptyRow = document.getElementById('serverActivityEmptyRow');
    const hasServerFilters = {{ request()->anyFilled(['start_date', 'end_date', 'gate_id', 'direction', 'status']) ? 'true' : 'false' }};

    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function (e) {
            const rawValue = e.target.value;
            const query = rawValue.toLowerCase().trim();
            const rows = tableBody.querySelectorAll('tr.activity-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (paginationContainer) {
                paginationContainer.style.display = query.length > 0 ? 'none' : '';
            }

            const existingEmptyRow = document.getElementById('clientSearchEmptyRow');
            if (existingEmptyRow) existingEmptyRow.remove();

            if (serverEmptyRow) {
                serverEmptyRow.style.display = query.length > 0 ? 'none' : '';
            }

            if (visibleCount === 0 && query.length > 0) {
                const emptyTr = document.createElement('tr');
                emptyTr.id = 'clientSearchEmptyRow';
                emptyTr.innerHTML = `
                    <td colspan="11" class="py-12 px-4 text-center">
                        <i class="fa-solid fa-magnifying-glass text-3xl mb-3 block text-slate-300"></i>
                        <p class="text-sm font-semibold text-slate-700">
                            Data "<span class="text-rose-600 font-bold">${escapeHtml(rawValue)}</span>" tidak ditemukan di tabel
                        </p>
                        <p class="text-xs text-slate-400 mt-1">
                            Pastikan kata kunci yang dicari sudah benar.
                        </p>
                        <div class="mt-4">
                            <button type="button" 
                                    onclick="const inp = document.getElementById('activityLogSearchInput'); if(inp){ inp.value=''; inp.dispatchEvent(new Event('input')); inp.focus(); }" 
                                    class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                <span>Hapus Pencarian</span>
                            </button>
                        </div>
                    </td>
                `;
                tableBody.appendChild(emptyTr);
            }

            if (resetContainer) {
                let resetBtn = document.getElementById('headerResetFilterBtn');
                if (query.length > 0 || hasServerFilters) {
                    if (!resetBtn) {
                        resetBtn = document.createElement('a');
                        resetBtn.href = "{{ route('qma.activity.log') }}";
                        resetBtn.id = 'headerResetFilterBtn';
                        resetBtn.className = 'text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center space-x-1 transition';
                        const resetIcon = document.createElement('i');
                        resetIcon.className = 'fa-solid fa-rotate-left text-[11px]';
                        const resetText = document.createElement('span');
                        resetText.textContent = 'Reset Filter';

                        resetBtn.appendChild(resetIcon);
                        resetBtn.appendChild(resetText);
                        resetContainer.appendChild(resetBtn);
                    }
                } else if (!hasServerFilters && resetBtn) {
                    resetBtn.remove();
                }
            }
        });
    }
});

    // Toggle Card Filter Lanjutan
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

    // Buka & Tutup Modal Panduan Halaman
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

    // Dropdown Menu Ekspor Timesheet LV
    function toggleActivityExportMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('exportMenuActivity');
        if (menu) {
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }
    }

    document.addEventListener('click', function(event) {
        const container = document.getElementById('exportActivityDropdownContainer');
        const menu = document.getElementById('exportMenuActivity');
        if (container && !container.contains(event.target)) {
            if (menu) {
                menu.style.display = 'none';
            }
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGuideModal();
            const exportMenu = document.getElementById('exportMenuActivity');
            if (exportMenu) exportMenu.style.display = 'none';
        }
    });
</script>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PANDUAN HALAMAN (LOG AKTIVITAS KENDARAAN)                    -->
<!-- ========================================================================= -->
<div id="guideModal" 
     onclick="if(event.target === this) closeGuideModal()" 
     class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-7 border border-slate-100 transform transition-all my-8">
        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-sm border border-slate-200">
                    <i class="fa-solid fa-info"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight leading-tight">Panduan Halaman</h3>
                    <p class="text-xs text-slate-500">Log Aktivitas Kendaraan</p>
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
                Selamat datang di halaman <strong>Log Aktivitas Kendaraan</strong>! Halaman ini memuat seluruh riwayat operasional kendaraan yang telah terverifikasi oleh petugas pos gerbang. Di halaman ini Anda dapat:
            </p>

            <div class="space-y-3">
                <!-- Fitur 1 (Hijau) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-emerald-200/50">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Catatan Perjalanan Kendaraan</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Melihat log perjalanan setiap kendaraan operasional lengkap dengan tanggal, nomor plat, nama driver, serta tujuan penggunaan dinas.
                        </p>
                    </div>
                </div>

                <!-- Fitur 2 (Biru) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-100/70 text-blue-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-blue-200/50">
                        <i class="fa-solid fa-gauge"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Validasi Odometer Masuk & Keluar</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Memeriksa angka kilometer riil sebelum keluar dan saat kembali masuk untuk memantau jarak tempuh perjalanan unit.
                        </p>
                    </div>
                </div>

                <!-- Fitur 3 (Amber) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-amber-200/50">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Analitik & Filter Real-time</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Gunakan kotak pencarian dan filter cepat, atau klik tombol 'Lihat Summary Aktivitas' untuk rekapitulasi data per unit dan driver.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Tombol -->
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
