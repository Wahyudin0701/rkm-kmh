@extends('layouts.app')

@section('title', 'Katalog Kendaraan Ringan (LV) - Mobilisasi Operasional')

@section('content')
<div class="w-full space-y-6">
    <!-- Header Halaman Minimalis (Clean Typography, Tanpa Ikon Berlebihan) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200/80">
        <div class="min-w-0">
            <div class="flex items-center flex-wrap gap-2">
                <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                    Timesheet Kendaraan Ringan & Armada Mobile
                </h1>
                <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                    Katalog Mobilisasi
                </span>
            </div>
            <div class="text-xs sm:text-sm text-slate-600 font-medium truncate mt-1 flex items-center flex-wrap gap-2">
                <span>Pengemudi: <strong class="text-slate-900 font-bold">{{ $employee ? $employee->name : auth()->user()->name }}</strong></span>
                @if($employee && $employee->nik)
                    <span class="text-slate-400 font-mono text-xs">({{ $employee->nik }})</span>
                @endif

                @if(!empty($activeSimpers) && $activeSimpers->isNotEmpty())
                    <span class="text-slate-300">|</span>
                    <span class="text-slate-500 text-xs">SIMPER:</span>
                    @foreach($activeSimpers as $as)
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            {{ $as->simperType ? $as->simperType->name : 'SIMPER Aktif' }}
                        </span>
                    @endforeach
                @elseif($employee && $employee->hasValidSimper())
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        SIMPER Aktif
                    </span>
                @elseif($employee)
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                        Belum Memiliki SIMPER
                    </span>
                @endif
            </div>
        </div>
    </div>

    @php
        $showAllTab = !($isRestrictedEmployee ?? false) || ($hasUniversalScope ?? false) || (($canDriveLv ?? false) && !empty($heavyScopes ?? []));
        $showLvTab = !($isRestrictedEmployee ?? false) || ($hasUniversalScope ?? false) || ($canDriveLv ?? false);
        $showHeavyTab = !($isRestrictedEmployee ?? false) || ($hasUniversalScope ?? false) || !empty($heavyScopes ?? []);
    @endphp

    @if($showAllTab || ($showLvTab && $showHeavyTab))
        <!-- Segmented Filter Tab: Semua Armada / LV / Heavy Mobile (Minimalis Tanpa Ikon) -->
        <div class="flex items-center space-x-1 sm:space-x-2 border-b border-slate-200/80 overflow-x-auto pb-px">
            @if($showAllTab)
                <a href="{{ route('mobilisasi.index', array_filter(['search' => request('search'), 'type' => 'all'])) }}" 
                   class="px-4 py-2 text-xs font-bold rounded-t-xl transition whitespace-nowrap {{ ($type ?? 'all') === 'all' ? 'bg-[#1b5e20] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Semua Armada
                </a>
            @endif
            @if($showLvTab)
                <a href="{{ route('mobilisasi.index', array_filter(['search' => request('search'), 'type' => 'light_vehicle'])) }}" 
                   class="px-4 py-2 text-xs font-bold rounded-t-xl transition whitespace-nowrap {{ ($type ?? '') === 'light_vehicle' ? 'bg-[#1b5e20] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Light Vehicle (LV)
                </a>
            @endif
            @if($showHeavyTab)
                <a href="{{ route('mobilisasi.index', array_filter(['search' => request('search'), 'type' => 'unit_support'])) }}" 
                   class="px-4 py-2 text-xs font-bold rounded-t-xl transition whitespace-nowrap {{ ($type ?? '') === 'unit_support' ? 'bg-[#1b5e20] text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                    Heavy Equipment Mobile
                </a>
            @endif
        </div>
    @endif

    <!-- Filter & Search Bar (Format Bersih Sesuai Standar) -->
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
                    <!-- Badge Kategori & Status (Minimalis) -->
                    <div class="flex items-center justify-between mb-3">
                        @if($v->isLightVehicle())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                LV
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                Heavy Mobile
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
            @if($noActiveSimper ?? false)
                <!-- Kartu Peringatan Bersih Jika Karyawan Belum Punya SIMPER Aktif -->
                <div class="col-span-full bg-amber-50/80 border border-amber-200 rounded-2xl p-8 sm:p-10 text-center max-w-lg mx-auto my-6 space-y-2 shadow-xs">
                    <h3 class="text-base font-bold text-amber-900">Akses Kendaraan Belum Tersedia</h3>
                    <p class="text-xs text-amber-800 leading-relaxed">
                        Anda belum memiliki SIMPER aktif yang terdaftar di HSE untuk mengemudikan kendaraan.
                    </p>
                    <p class="text-[11px] text-amber-700 font-medium pt-1">
                        Silakan hubungi bagian HSE untuk pendaftaran atau pembaruan masa berlaku SIMPER Anda.
                    </p>
                </div>
            @else
                <div id="lvServerEmptyCard" class="col-span-full bg-white rounded-2xl border border-slate-200 p-10 text-center shadow-xs">
                    @if(request('search'))
                        <h3 class="text-base font-bold text-slate-800">
                            Data "<span class="text-rose-600 font-bold">{{ request('search') }}</span>" tidak ditemukan di katalog
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">Pastikan kode unit atau nopol kendaraan yang dicari sudah benar.</p>
                        <div class="mt-4">
                            <a href="{{ route('mobilisasi.index', ['type' => $type ?? 'all']) }}" class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                <span>Hapus Pencarian</span>
                            </a>
                        </div>
                    @else
                        <h3 class="text-base font-bold text-slate-800">Tidak Ada Kendaraan Tersedia</h3>
                        <p class="text-xs text-slate-500 mt-1">Tidak ada unit kendaraan yang sesuai dengan kategori atau izin mengemudi Anda.</p>
                    @endif
                </div>
            @endif
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
    // Search Server-Side dengan Debounce Pintar & Submit Cepat
    const searchInput = document.getElementById('lvSearchInput');
    const filterForm = document.getElementById('lvFilterForm');
    const hasServerSearch = {{ request()->filled('search') ? 'true' : 'false' }};
    let debounceTimer = null;

    // Otomatis cari ke seluruh database server setelah jeda mengetik 550ms
    searchInput?.addEventListener('input', function(e) {
        clearTimeout(debounceTimer);
        const val = e.target.value.trim();

        // Jika input dikosongkan setelah sebelumnya ada pencarian, langsung reload reset
        if (val === '' && hasServerSearch) {
            debounceTimer = setTimeout(() => {
                filterForm.submit();
            }, 300);
            return;
        }

        // Auto submit setelah berhenti mengetik minimal 2 karakter
        if (val.length >= 2) {
            debounceTimer = setTimeout(() => {
                filterForm.submit();
            }, 550);
        }
    });

    // Submit langsung seketika saat tombol Enter ditekan di keyboard
    searchInput?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            clearTimeout(debounceTimer);
            filterForm.submit();
        }
    });
});
</script>
@endsection
