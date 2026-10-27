@extends('layouts.app')

@section('title', 'P2H Heavy Equipment - Admin QMA')

@section('content')
<div class="w-full space-y-6">
    <!-- Header Judul & Tombol Aksi (Tanpa Card Pembungkus Sesuai Panduan Desain QMA) -->
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
                        title="Panduan Halaman P2H Heavy Equipment">
                    <i class="fa-solid fa-info"></i>
                </button>
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                P2H Heavy Equipment
            </h2>
        </div>

        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 sm:gap-2.5 w-full sm:w-auto">
            <!-- Dropdown Menu Ekspor Format it.allrole.web.id -->
            <div class="relative inline-block flex-1 sm:flex-none" id="exportP2hDropdownContainer">
                <button type="button" 
                        onclick="toggleP2hExportMenu(event)" 
                        id="btnP2hExportTrigger"
                        class="w-full sm:w-auto flex items-center justify-center space-x-2 border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3.5 sm:px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                    <i class="fa-regular fa-file-excel text-emerald-700 text-sm"></i>
                    <span class="truncate">Ekspor P2H</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
                </button>
                <div id="exportMenuP2h" style="display: none;" class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-slate-100 z-50 overflow-hidden">
                    <a href="{{ route('qma.p2h.export_pdf', request()->query()) }}" target="_blank" class="flex items-center px-4 py-3 text-xs text-slate-700 hover:bg-slate-50 hover:text-[#1b5e20] font-bold transition">
                        <i class="fa-regular fa-file-pdf text-rose-600 text-sm mr-2.5"></i>
                        <span>Format PDF (Cetak)</span>
                    </a>
                    <div class="h-px bg-slate-100 mx-2"></div>
                    <a href="{{ route('qma.p2h.export_excel', request()->query()) }}" class="flex items-center px-4 py-3 text-xs text-emerald-700 hover:bg-emerald-50 font-bold transition">
                        <i class="fa-regular fa-file-excel text-emerald-600 text-sm mr-2.5"></i>
                        <span>Format Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            @can('qma.summary.view')
            <a href="{{ route('qma.p2h.summary') }}" class="flex-1 sm:flex-none inline-flex items-center justify-center space-x-2 px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 shadow-sm transition group">
                <i class="fa-solid fa-chart-pie text-slate-400 group-hover:text-slate-700"></i>
                <span class="whitespace-nowrap">Summary Aktivitas</span>
            </a>
            @endcan
        </div>
    </div>



    <!-- Kotak Pencarian & Filter Status P2H (Tanpa Card Pembungkus Sesuai Gambar Referensi) -->
    <form method="GET" action="{{ route('qma.p2h.index') }}" id="p2hFilterForm" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        @if(request('date'))
            <input type="hidden" name="date" value="{{ request('date') }}">
        @endif

        <!-- Sisi Kiri: Kotak Pencarian -->
        <div class="relative w-full max-w-sm sm:max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" 
                   id="p2hSearchInput"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Cari No. TRX, kode unit (EX01), nama/NIK operator..." 
                   class="w-full text-xs pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/10 shadow-sm transition">
        </div>

        <!-- Sisi Kanan: Filter Dropdown & Reset -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Filter Status Approval -->
            <select name="validation_status" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20] shadow-sm">
                <option value="">Semua Status Approval</option>
                <option value="pending" {{ request('validation_status') === 'pending' ? 'selected' : '' }}>Menunggu Approval</option>
                <option value="approved" {{ request('validation_status') === 'approved' ? 'selected' : '' }}>Disetujui (Approved)</option>
                <option value="rejected" {{ request('validation_status') === 'rejected' ? 'selected' : '' }}>Ditolak (Rejected)</option>
            </select>

            <!-- Filter Kondisi Fisik -->
            <select name="summary_condition" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20] shadow-sm">
                <option value="">Semua Kondisi Fisik</option>
                <option value="good" {{ request('summary_condition') === 'good' ? 'selected' : '' }}>Semua Baik (Normal)</option>
                <option value="has_defect" {{ request('summary_condition') === 'has_defect' ? 'selected' : '' }}>Ada Temuan Rusak</option>
            </select>

            <!-- Filter Jumlah Per Halaman -->
            <select name="per_page" onchange="this.form.submit()" class="text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20] shadow-sm">
                <option value="15" {{ request('per_page', '15') == '15' ? 'selected' : '' }}>15 / Hal</option>
                <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50 / Hal</option>
                <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>Semua (100)</option>
            </select>

            <!-- Tombol Submit Pencarian -->
            <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold rounded-xl transition shadow-sm cursor-pointer whitespace-nowrap">
                Cari
            </button>

            <div id="p2hResetWrapper">
                @if(request()->anyFilled(['search', 'validation_status', 'summary_condition', 'date', 'per_page']))
                    <a id="p2hResetBtn" href="{{ route('qma.p2h.index') }}" class="text-xs text-rose-600 hover:text-rose-800 font-semibold flex items-center space-x-1 whitespace-nowrap ml-1" title="Reset Semua Filter">
                        <i class="fa-solid fa-rotate-left"></i>
                        <span>Reset</span>
                    </a>
                @endif
            </div>
        </div>
    </form>

    <!-- Tabel Data Riwayat P2H -->
    <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">No</th>
                        <th class="py-3.5 px-4">Waktu Inspeksi</th>
                        <th class="py-3.5 px-4">No. Transaksi</th>
                        <th class="py-3.5 px-4">Heavy Equipment</th>
                        <th class="py-3.5 px-4">Operator (SIMPER)</th>
                        <th class="py-3.5 px-4 text-center">Shift</th>
                        <th class="py-3.5 px-4 text-right">HM Operasi</th>
                        <th class="py-3.5 px-4 text-center">Kondisi Fisik</th>
                        <th class="py-3.5 px-4 text-center">Status QMA</th>
                        <th class="py-3.5 px-4 text-center w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody id="p2hTableBody" class="divide-y divide-slate-100">
                    @forelse($records as $rec)
                        <tr class="p2h-row hover:bg-slate-50/80 transition">
                            <td class="py-3.5 px-4 text-center text-slate-400 font-medium">
                                {{ ($records->currentPage() - 1) * $records->perPage() + $loop->iteration }}
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-bold text-slate-800">{{ $rec->inspected_at->format('d/m/Y') }}</span>
                                <span class="text-slate-400 block text-[10px]">{{ $rec->inspected_at->format('H:i') }} WIB</span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded text-[11px]">{{ $rec->transaction_no }}</span>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-extrabold text-slate-900 text-sm leading-tight">{{ $rec->vehicle->code }}</div>
                                <div class="text-[11px] text-slate-500 truncate max-w-[160px]">{{ $rec->vehicle->brand_type }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-800">{{ $rec->employee->name ?? '-' }}</div>
                                <div class="text-[10px] text-slate-400">NIK: {{ $rec->employee->nik ?? '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-700">
                                    {{ Str::contains($rec->shift, '1') ? 'Shift 1' : 'Shift 2' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <span class="font-black text-slate-900">{{ number_format($rec->start_hm, 1, ',', '.') }}</span>
                                <span class="text-[10px] text-slate-400 font-bold ml-0.5">HM</span>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($rec->hasDefects())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <i class="fa-solid fa-triangle-exclamation mr-1 text-rose-600"></i> {{ $rec->defect_count }} Item Rusak
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-circle-check mr-1 text-emerald-600"></i> Normal (Baik)
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($rec->isPending())
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse mr-1.5"></span>
                                        Menunggu Review
                                    </span>
                                @elseif($rec->isApproved())
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        <i class="fa-solid fa-check mr-1"></i> Disetujui QMA
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                        <i class="fa-solid fa-xmark mr-1"></i> Ditolak
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('qma.p2h.show', $rec->id) }}" class="inline-flex items-center space-x-1 px-2.5 py-1.5 rounded-xl text-xs font-bold text-white bg-[#1b5e20] hover:bg-[#144918] shadow-sm transition" title="Buka Review Detail P2H">
                                        <i class="fa-solid fa-magnifying-glass text-[10px]"></i>
                                        <span>Review</span>
                                    </a>
                                    <a href="{{ route('qma.p2h.export_single_pdf', $rec->id) }}" target="_blank" class="w-7 h-7 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center transition border border-rose-200 shadow-2xs" title="Download Lembar P2H (PDF)">
                                        <i class="fa-solid fa-file-pdf text-xs"></i>
                                    </a>
                                    <a href="{{ route('qma.p2h.export_single_excel', $rec->id) }}" class="w-7 h-7 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 flex items-center justify-center transition border border-emerald-200 shadow-2xs" title="Download Lembar P2H (Excel)">
                                        <i class="fa-solid fa-file-excel text-xs"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="serverEmptyRow">
                            <td colspan="10" class="py-16 text-center">
                                @if(request('search'))
                                    <p class="text-sm font-bold text-slate-700">
                                        Data "<span class="text-rose-600">{{ request('search') }}</span>" tidak ditemukan di tabel
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        Pastikan kata kunci yang dicari sudah benar.
                                    </p>
                                    <div class="mt-4">
                                        <a href="{{ route('qma.p2h.index') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                            <span>Hapus Pencarian</span>
                                        </a>
                                    </div>
                                @else
                                    <p class="text-sm font-bold text-slate-700">Belum ada catatan riwayat P2H Heavy Equipment</p>
                                    <p class="text-xs text-slate-400 mt-1">Form P2H yang dikirim oleh operator alat berat akan otomatis muncul pada daftar ini.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div id="p2hPaginationContainer" class="p-4 border-t border-slate-100">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ========================================================================= -->
<!-- ========================================================================= -->
<!-- JAVASCRIPT: REAL-TIME CLIENT SEARCH & MODAL                               -->
<!-- ========================================================================= -->
<script>
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('p2hSearchInput');
    const filterForm = document.getElementById('p2hFilterForm');
    let searchDebounce = null;

    if (searchInput && searchInput.value) {
        searchInput.focus();
        const len = searchInput.value.length;
        searchInput.setSelectionRange(len, len);
    }

    searchInput?.addEventListener('input', function() {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(function() {
            filterForm?.submit();
        }, 500);
    });
});

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

    // Dropdown Menu Ekspor P2H Heavy Equipment
    function toggleP2hExportMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('exportMenuP2h');
        if (menu) {
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }
    }

    document.addEventListener('click', function(event) {
        const container = document.getElementById('exportP2hDropdownContainer');
        const menu = document.getElementById('exportMenuP2h');
        if (container && !container.contains(event.target)) {
            if (menu) {
                menu.style.display = 'none';
            }
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGuideModal();
            const exportMenu = document.getElementById('exportMenuP2h');
            if (exportMenu) exportMenu.style.display = 'none';
        }
    });
</script>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PANDUAN HALAMAN (LOG P2H HEAVY EQUIPMENT)                   -->
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
                    <p class="text-xs text-slate-500">P2H Heavy Equipment</p>
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
                Halaman <strong>P2H Heavy Equipment</strong> digunakan untuk memantau, memeriksa, dan memvalidasi riwayat pemeriksaan kelayakan operasi harian alat berat proyek yang diajukan oleh operator.
            </p>

            <div class="space-y-3">
                <!-- Fitur 1 (Hijau) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-emerald-200/50">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Review & Validasi Kelayakan</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Buka detail setiap pengajuan P2H untuk meninjau status checklist kelayakan alat dan beri persetujuan (Approve) atau penolakan (Reject).
                        </p>
                    </div>
                </div>

                <!-- Fitur 2 (Amber) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-amber-200/50">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Deteksi Temuan Kerusakan</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Pantau kartu statistik 'Temuan Rusak' dan 'Menunggu QMA' untuk memprioritaskan unit yang membutuhkan perbaikan atau tindakan cepat.
                        </p>
                    </div>
                </div>

                <!-- Fitur 3 (Biru) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-100/70 text-blue-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-blue-200/50">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Master Checksheet P2H</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Akses daftar 28 standarisasi template checksheet pertanyaan P2H melalui tombol 'Master 28 Template' di bagian kanan atas.
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
