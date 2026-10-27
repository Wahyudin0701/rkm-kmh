@extends('layouts.app')

@section('title', 'Katalog Kendaraan Ringan (LV) - Mobilisasi Operasional')

@section('content')
<div class="w-full space-y-6">
    <!-- Header Halaman Simple Kayak Satpam (Seamless, Tanpa Card Wrapper) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200/80">
        <div class="min-w-0">
            <div class="flex items-center flex-wrap gap-2.5">
                <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                    Timesheet Kendaraan Ringan & Armada Mobile
                </h1>
                <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                    Kendaraan Ringan (LV) & Heavy Mobile
                </span>
            </div>
            <div class="text-sm sm:text-base text-slate-600 font-medium truncate mt-1 flex items-center flex-wrap gap-1.5">
                <span>Pengemudi: <strong class="text-slate-900 font-bold">{{ $employee ? $employee->name : auth()->user()->name }}</strong></span>
                @if($employee && $employee->nik)
                    <span class="text-slate-500 font-mono text-xs sm:text-sm font-semibold">({{ $employee->nik }})</span>
                @endif
                @if($employee && $employee->hasValidSimper())
                    <span class="inline-flex items-center text-[10px] font-bold text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-md ml-1">
                        <i class="fa-solid fa-circle-check mr-1 text-emerald-600"></i> SIMPER Aktif
                    </span>
                @elseif($employee)
                    <span class="inline-flex items-center text-[10px] font-bold text-rose-700 bg-rose-100/80 px-2 py-0.5 rounded-md ml-1">
                        <i class="fa-solid fa-circle-xmark mr-1 text-rose-600"></i> SIMPER Exp
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Segmented Filter Tab: Semua Armada / LV / Heavy Mobile -->
    <div class="flex items-center space-x-1 sm:space-x-2 border-b border-slate-200/80 overflow-x-auto pb-px">
        <a href="{{ route('mobilisasi.index', array_filter(['search' => request('search'), 'type' => 'all'])) }}" 
           class="px-4 py-2 text-xs font-bold rounded-t-xl transition whitespace-nowrap {{ ($type ?? 'all') === 'all' ? 'bg-[#1b5e20] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <i class="fa-solid fa-layer-group mr-1.5"></i>Semua Armada
        </a>
        <a href="{{ route('mobilisasi.index', array_filter(['search' => request('search'), 'type' => 'light_vehicle'])) }}" 
           class="px-4 py-2 text-xs font-bold rounded-t-xl transition whitespace-nowrap {{ ($type ?? '') === 'light_vehicle' ? 'bg-[#1b5e20] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <i class="fa-solid fa-car-side mr-1.5"></i>Light Vehicle (LV)
        </a>
        <a href="{{ route('mobilisasi.index', array_filter(['search' => request('search'), 'type' => 'unit_support'])) }}" 
           class="px-4 py-2 text-xs font-bold rounded-t-xl transition whitespace-nowrap {{ ($type ?? '') === 'unit_support' ? 'bg-[#1b5e20] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <i class="fa-solid fa-truck-moving mr-1.5"></i>Heavy Equipment Mobile
        </a>
    </div>

    <!-- Filter & Search Bar (Tanpa Card Wrapper, Format Referensi Sesuai Standar) -->
    <form method="GET" action="{{ route('mobilisasi.index') }}" id="lvFilterForm" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 sm:gap-3">
        <input type="hidden" name="type" value="{{ $type ?? 'all' }}">
        <!-- Pencarian Cepat di Kiri dengan Tombol Cari -->
        <div class="relative w-full sm:max-w-md flex items-center">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 z-10">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" 
                   name="search" 
                   id="lvSearchInput"
                   value="{{ request('search') }}" 
                   placeholder="Cari kode unit (LV01), nopol, atau model..." 
                   autocomplete="off"
                   class="w-full text-xs pl-10 pr-20 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 shadow-sm transition">
            <button type="submit" 
                    id="lvSearchBtn"
                    class="absolute right-1.5 inset-y-1.5 px-3 bg-[#1b5e20] hover:bg-[#144918] active:scale-95 text-white font-bold text-xs rounded-lg flex items-center gap-1.5 transition shadow-sm cursor-pointer z-10">
                <span>Cari</span>
            </button>
        </div>

        <!-- Tombol Reset Filter di Kanan -->
        <div id="lvResetContainer" class="flex items-center justify-end">
            @if(request()->filled('search'))
                <a href="{{ route('mobilisasi.index', ['type' => $type ?? 'all']) }}" 
                   id="lvResetBtn"
                   class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center justify-center sm:justify-start space-x-1.5 transition whitespace-nowrap py-1 sm:py-0">
                    <i class="fa-solid fa-rotate-left text-[11px]"></i>
                    <span>Reset Filter</span>
                </a>
            @endif
        </div>
    </form>

    <!-- Grid Kartu Kendaraan Ringan (LV) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="lvCardsContainer">
        @forelse($vehicles as $v)
            <div class="lv-card bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md transition p-5 flex flex-col justify-between">
                <div>
                    <!-- Badge Kategori & Status -->
                    <div class="flex items-center justify-between mb-3">
                        @if($v->isLightVehicle())
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                <i class="fa-solid fa-car-side mr-1 text-emerald-600"></i> LV
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                <i class="fa-solid fa-truck-moving mr-1 text-amber-600"></i> Heavy Mobile
                            </span>
                        @endif
                        
                        @php
                            $statusBadge = match($v->status) {
                                'Operation' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'Standby' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'Breakdown' => 'bg-rose-50 text-rose-700 border-rose-200',
                                default => 'bg-slate-50 text-slate-700 border-slate-200',
                            };
                            $nextDir = $v->determineNextAllowedDirection();
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $statusBadge }}">
                            {{ $v->status }}
                        </span>
                    </div>

                    <!-- Kode & Nopol -->
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-xl font-black text-slate-900 leading-tight">{{ $v->code }}</h3>
                        <span class="font-mono text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200/80 px-2 py-0.5 rounded-md">
                            {{ $v->license_plate ?? 'Tanpa Plat' }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-600 font-medium mt-1 line-clamp-1">{{ $v->brand_type }}</p>

                    <!-- Info Atribut -->
                    <div class="mt-4 space-y-1.5 text-xs text-slate-500 bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Odometer Terakhir:</span>
                            <span class="font-extrabold text-slate-800">{{ number_format($v->current_km, 0, ',', '.') }} KM</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Penanggung Jawab:</span>
                            <span class="font-semibold text-slate-700 truncate max-w-[150px]">{{ $v->ownership ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Status Pergerakan:</span>
                            <span class="font-bold {{ $nextDir === 'Keluar' ? 'text-amber-700' : 'text-blue-700' }}">
                                Menunggu {{ $nextDir }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tombol Buka Form Mobilisasi -->
                <div class="mt-5 pt-3 border-t border-slate-100">
                    @if($v->status === 'Breakdown')
                        <div class="w-full bg-rose-50 border border-rose-200 text-rose-700 font-bold py-2.5 px-4 rounded-xl text-center text-xs">
                            <i class="fa-solid fa-triangle-exclamation mr-1.5"></i>
                            <span>Unit Sedang Breakdown</span>
                        </div>
                    @else
                        @if($v->isLightVehicle())
                            <a href="{{ route('vehicle.activity.form', ['code' => $v->code]) }}" class="w-full bg-[#1b5e20] hover:bg-[#144918] active:scale-[0.99] text-white font-bold py-2.5 px-4 rounded-xl shadow-sm transition text-center block text-xs">
                                Isi Form Mobilisasi LV
                            </a>
                        @else
                            <a href="{{ route('vehicle.activity.form', ['code' => $v->code]) }}" class="w-full bg-[#1b5e20] hover:bg-[#144918] active:scale-[0.99] text-white font-bold py-2.5 px-4 rounded-xl shadow-sm transition text-center block text-xs">
                                Isi Form Mobilisasi Unit
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <div id="lvServerEmptyCard" class="col-span-full bg-white rounded-2xl border border-slate-200 p-12 text-center">
                <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-magnifying-glass text-2xl"></i>
                </div>
                @if(request('search'))
                    <h3 class="text-base font-bold text-slate-800">
                        Data "<span class="text-rose-600 font-bold">{{ request('search') }}</span>" tidak ditemukan di tabel
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">Pastikan kode unit atau nopol kendaraan yang dicari sudah benar.</p>
                    <div class="mt-4">
                        <a href="{{ route('mobilisasi.index') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                            <i class="fa-solid fa-rotate-left text-[11px]"></i>
                            <span>Hapus Pencarian</span>
                        </a>
                    </div>
                @else
                    <h3 class="text-base font-bold text-slate-800">Kendaraan Ringan Tidak Ditemukan</h3>
                    <p class="text-xs text-slate-500 mt-1">Belum ada unit kendaraan LV yang terdaftar.</p>
                @endif
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($vehicles->hasPages())
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm" id="lvPaginationContainer">
            {{ $vehicles->links() }}
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search Real-Time Client-Side & Smart Server Query
    const searchInput = document.getElementById('lvSearchInput');
    const resetContainer = document.getElementById('lvResetContainer');
    const cardsContainer = document.getElementById('lvCardsContainer');
    const paginationContainer = document.getElementById('lvPaginationContainer');
    const serverEmptyCard = document.getElementById('lvServerEmptyCard');
    const filterForm = document.getElementById('lvFilterForm');
    const hasServerFilters = {{ request()->filled('search') ? 'true' : 'false' }};

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    searchInput?.addEventListener('input', function(e) {
        const rawValue = e.target.value;
        const query = rawValue.toLowerCase().trim();
        const cleanQuery = query.replace(/[\s\-_]/g, '');
        const items = document.querySelectorAll('#lvCardsContainer .lv-card');
        let visibleCount = 0;

        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            const cleanText = text.replace(/[\s\-_]/g, '');
            const match = text.includes(query) || (cleanQuery.length > 0 && cleanText.includes(cleanQuery));
            item.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        // Sembunyikan pesan SSR kosong saat mengetik
        if (serverEmptyCard) {
            serverEmptyCard.style.display = query.length > 0 ? 'none' : '';
        }

        // Hapus pesan kosong pencarian jika ada sebelumnya
        const existingEmptyCard = document.getElementById('lvEmptySearchCard');
        if (existingEmptyCard) {
            existingEmptyCard.remove();
        }

        // Tampilkan notifikasi jika tidak ada data yang cocok di halaman ini
        if (visibleCount === 0 && query.length > 0) {
            const emptyDiv = document.createElement('div');
            emptyDiv.id = 'lvEmptySearchCard';
            emptyDiv.className = 'col-span-full bg-white rounded-2xl border border-slate-200 p-8 sm:p-12 text-center shadow-sm';
            emptyDiv.innerHTML = `
                <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-magnifying-glass text-2xl"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800">
                    Unit "<span class="text-rose-600 font-bold">${escapeHtml(rawValue)}</span>" tidak ada di halaman ini
                </h3>
                <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                    Unit mungkin berada di halaman lain. Tekan tombol <strong>Cari di Semua Halaman</strong> untuk mencari di seluruh database kendaraan.
                </p>
                <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                    <button type="submit" form="lvFilterForm" 
                            class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl text-xs font-bold text-white bg-[#1b5e20] hover:bg-[#144918] active:scale-95 transition shadow-sm cursor-pointer">
                        <i class="fa-solid fa-magnifying-glass text-[11px]"></i>
                        <span>Cari di Semua Halaman</span>
                    </button>
                    <button type="button" 
                            onclick="const inp = document.getElementById('lvSearchInput'); if(inp){ inp.value=''; inp.dispatchEvent(new Event('input')); inp.focus(); }" 
                            class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                        <i class="fa-solid fa-rotate-left text-[11px]"></i>
                        <span>Hapus Pencarian</span>
                    </button>
                </div>
            `;
            cardsContainer?.appendChild(emptyDiv);
        }

        // Sembunyikan pagination saat client-side search aktif
        if (paginationContainer) {
            paginationContainer.style.display = query.length > 0 ? 'none' : '';
        }

        // Tampilkan / sembunyikan tombol Reset Filter secara real-time
        let resetBtn = document.getElementById('lvResetBtn');
        if (query.length > 0) {
            if (!resetBtn && resetContainer) {
                resetBtn = document.createElement('a');
                resetBtn.id = 'lvResetBtn';
                resetBtn.href = "{{ route('mobilisasi.index', ['type' => $type ?? 'all']) }}";
                resetBtn.className = "text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center justify-center sm:justify-start space-x-1.5 transition whitespace-nowrap py-1 sm:py-0";
                
                const resetIcon = document.createElement('i');
                resetIcon.className = "fa-solid fa-rotate-left text-[11px]";
                
                const resetText = document.createElement('span');
                resetText.textContent = "Reset Filter";
                
                resetBtn.appendChild(resetIcon);
                resetBtn.appendChild(resetText);
                resetContainer.appendChild(resetBtn);
            }
        } else if (!hasServerFilters && resetBtn) {
            resetBtn.remove();
        }
    });
});
</script>
@endsection
