@extends('layouts.app')

@section('title', 'Riwayat Posisi ' . $vehicle->code . ' - Admin QMA | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- ========================================================================= -->
    <!-- 1. HEADER & TOMBOL AKSI UTAMA                                             -->
    <!-- ========================================================================= -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center space-x-2 mb-1.5">
                <a href="{{ route('qma.unit_management.index') }}" 
                   class="inline-flex items-center space-x-1.5 text-xs font-bold text-slate-500 hover:text-[#1b5e20] transition group">
                    <i class="fa-solid fa-arrow-left text-[11px] group-hover:-translate-x-0.5 transition-transform"></i>
                    <span>Kembali ke Posisi Unit</span>
                </a>
                <span class="text-slate-300">&bull;</span>
                <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20]">
                    RIWAYAT POSISI & AUDIT TRAIL
                </span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center space-x-3">
                <span>Riwayat Posisi: {{ $vehicle->code }}</span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $vehicle->isLightVehicle() ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                    {{ $vehicle->isLightVehicle() ? 'Light Vehicle' : 'Heavy Equipment' }}
                </span>
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Jejak pergerakan lokasi unit, riwayat mutasi gerbang, update manual QMA, dan mobilisasi transfer alat berat.
            </p>
        </div>

        <!-- Tombol Aksi Kanan: Ekspor & Pemindahan -->
        <div class="flex items-center flex-wrap gap-2.5 w-full sm:w-auto">
            <!-- Dropdown Menu Ekspor -->
            <div class="relative inline-block" id="exportDropdownContainer">
                <button type="button" 
                        onclick="toggleExportMenu(event)" 
                        id="btnExportTrigger"
                        class="flex items-center justify-center space-x-2 border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3.5 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                    <i class="fa-solid fa-file-export text-emerald-700 text-sm"></i>
                    <span>Ekspor Riwayat</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
                </button>
                <div id="exportMenu" style="display: none;" class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-slate-100 z-50 overflow-hidden">
                    <a href="{{ route('qma.unit_management.history_export_pdf', array_merge(['id' => $vehicle->id], request()->query())) }}" target="_blank" class="flex items-center px-4 py-3 text-xs text-slate-700 hover:bg-slate-50 hover:text-[#1b5e20] font-bold transition">
                        <i class="fa-regular fa-file-pdf text-rose-600 text-sm mr-2.5"></i>
                        <span>Format PDF (Cetak)</span>
                    </a>
                    <div class="h-px bg-slate-100 mx-2"></div>
                    <a href="{{ route('qma.unit_management.history_export_excel', array_merge(['id' => $vehicle->id], request()->query())) }}" class="flex items-center px-4 py-3 text-xs text-emerald-700 hover:bg-emerald-50 font-bold transition">
                        <i class="fa-regular fa-file-excel text-emerald-600 text-sm mr-2.5"></i>
                        <span>Format Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            @if(!$vehicle->isLightVehicle())
            <!-- Tombol Pindah Unit Alat Berat -->
            <a href="{{ route('qma.unit_transfers.create', ['vehicle_id' => $vehicle->id]) }}" 
               class="flex items-center justify-center space-x-2 bg-[#1b5e20] hover:bg-[#154a19] text-white px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                <i class="fa-solid fa-truck-moving text-sm"></i>
                <span>Pindah Unit Ini (BAST)</span>
            </a>
            @endif
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. KARTU IDENTITAS UNIT & POSISI TERKINI                                   -->
    <!-- ========================================================================= -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <!-- Sisi Kiri: Foto & Identitas Detail Unit -->
            <div class="flex items-start sm:items-center space-x-4">
                <!-- Foto Unit -->
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-slate-100 border border-slate-200 flex-shrink-0 overflow-hidden flex items-center justify-center text-slate-400 relative group cursor-pointer"
                     onclick="openPhotoPreview('{{ $vehicle->photo_url }}', '{{ $vehicle->code }}')">
                    @if($vehicle->photo_url)
                        <img src="{{ $vehicle->photo_url }}" alt="{{ $vehicle->code }}" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-white text-xs">
                            <i class="fa-solid fa-magnifying-glass-plus"></i>
                        </div>
                    @else
                        <i class="fa-solid {{ $vehicle->isLightVehicle() ? 'fa-car' : 'fa-truck-monster' }} text-2xl"></i>
                    @endif
                </div>

                <!-- Detail Teks -->
                <div class="space-y-1">
                    <div class="flex items-center flex-wrap gap-2">
                        <span class="font-mono text-base sm:text-lg font-black text-slate-900 bg-slate-100 px-2.5 py-0.5 rounded-lg border border-slate-200">
                            {{ $vehicle->code }}
                        </span>
                        <span class="font-mono text-xs font-bold text-slate-700 bg-slate-50 px-2 py-0.5 rounded border border-slate-200">
                            {{ $vehicle->license_plate ?: ($vehicle->serial_number ?: 'Tanpa Plat/Seri') }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $vehicle->status === 'Operation' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : ($vehicle->status === 'Maintenance' ? 'bg-rose-50 text-rose-800 border border-rose-200' : 'bg-slate-100 text-slate-700') }}">
                            {{ $vehicle->status ?: 'Standby' }}
                        </span>
                    </div>
                    <h3 class="text-sm sm:text-base font-bold text-slate-900">
                        {{ $vehicle->brand_type ?: 'Unit Tanpa Merek' }}
                    </h3>
                    <p class="text-xs text-slate-500 flex items-center space-x-2">
                        <span>Kepemilikan: <strong class="text-slate-700">{{ $vehicle->ownership ?: 'PT Kerinci Merangin Hidro' }}</strong></span>
                        @if($vehicle->parent)
                        <span>&bull;</span>
                        <span>Tipe Induk: <strong class="text-slate-700">{{ $vehicle->parent->brand_type }}</strong></span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Sisi Kanan: Posisi Terkini Highlight Card -->
            <div class="w-full lg:w-auto bg-slate-50 border border-slate-200/90 rounded-xl p-4 sm:min-w-[320px]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 flex items-center">
                        <i class="fa-solid fa-location-dot text-rose-500 mr-1.5"></i> Posisi Saat Ini
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ $vehicle->site_status === 'outside_site' ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-emerald-100 text-emerald-900 border border-emerald-300' }}">
                        <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $vehicle->site_status === 'outside_site' ? 'bg-amber-600' : 'bg-emerald-600' }}"></span>
                        {{ $vehicle->site_status_label }}
                    </span>
                </div>
                <div class="text-base sm:text-lg font-black text-slate-900 truncate">
                    {{ $vehicle->location ?: 'Belum diisi' }}
                </div>
                <div class="mt-2 pt-2 border-t border-slate-200 flex items-center justify-between text-[11px] text-slate-500">
                    <span>Diperbarui: <strong>{{ $vehicle->location_updated_at ? $vehicle->location_updated_at->translatedFormat('d M Y, H:i') : '-' }}</strong></span>
                    <span>Oleh: <strong>{{ $vehicle->location_updated_by ?: '-' }}</strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. KARTU METRIK STATISTIK MUTASI LOKASI                                   -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- Total Mutasi -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-3.5 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Total Mutasi</span>
            <div class="text-xl font-black text-slate-900">{{ number_format($totalHistories) }}</div>
            <span class="text-[10px] text-slate-500 font-medium">Rekam jejak</span>
        </div>

        <!-- Di Dalam Site -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-3.5 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 block mb-1">Di Dalam Site</span>
            <div class="text-xl font-black text-emerald-900">{{ number_format($insideCount) }}</div>
            <span class="text-[10px] text-emerald-700 font-medium">Area Proyek KMH</span>
        </div>

        <!-- Di Luar Site -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-3.5 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 block mb-1">Di Luar Site</span>
            <div class="text-xl font-black text-amber-900">{{ number_format($outsideCount) }}</div>
            <span class="text-[10px] text-amber-700 font-medium">Luar Area Kerja</span>
        </div>

        <!-- Transfer Unit BAST -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-3.5 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-teal-700 block mb-1">Transfer (BAST)</span>
            <div class="text-xl font-black text-teal-900">{{ number_format($transferCount) }}</div>
            <span class="text-[10px] text-teal-700 font-medium">Relokasi Alat Berat</span>
        </div>

        <!-- Gerbang Otomatis -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-3.5 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700 block mb-1">Pos Gerbang</span>
            <div class="text-xl font-black text-blue-900">{{ number_format($gateCount) }}</div>
            <span class="text-[10px] text-blue-700 font-medium">Aktivitas Satpam</span>
        </div>

        <!-- Manual QMA -->
        <div class="bg-white rounded-xl border border-slate-200/80 p-3.5 shadow-2xs">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-600 block mb-1">Manual QMA</span>
            <div class="text-xl font-black text-slate-800">{{ number_format($manualCount) }}</div>
            <span class="text-[10px] text-slate-500 font-medium">Input Admin</span>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. FILTER PENCARIAN & PARAMETER                                          -->
    <!-- ========================================================================= -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 sm:p-5">
        <form method="GET" action="{{ route('qma.unit_management.history_view', $vehicle->id) }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                <!-- Tanggal Mulai -->
                <div>
                    <label for="date_from" class="block text-[11px] font-bold text-slate-600 mb-1">Dari Tanggal</label>
                    <input type="date" 
                           name="date_from" 
                           id="date_from" 
                           value="{{ request('date_from') }}" 
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800">
                </div>

                <!-- Tanggal Selesai -->
                <div>
                    <label for="date_to" class="block text-[11px] font-bold text-slate-600 mb-1">Sampai Tanggal</label>
                    <input type="date" 
                           name="date_to" 
                           id="date_to" 
                           value="{{ request('date_to') }}" 
                           class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800">
                </div>

                <!-- Status Area -->
                <div>
                    <label for="site_status" class="block text-[11px] font-bold text-slate-600 mb-1">Status Area</label>
                    <select name="site_status" 
                            id="site_status" 
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800">
                        <option value="all" {{ request('site_status') === 'all' || !request('site_status') ? 'selected' : '' }}>Semua Status</option>
                        <option value="inside_site" {{ request('site_status') === 'inside_site' ? 'selected' : '' }}>Di Dalam Site</option>
                        <option value="outside_site" {{ request('site_status') === 'outside_site' ? 'selected' : '' }}>Di Luar Site</option>
                    </select>
                </div>

                <!-- Sumber Mutasi -->
                <div>
                    <label for="source" class="block text-[11px] font-bold text-slate-600 mb-1">Sumber Pembaruan</label>
                    <select name="source" 
                            id="source" 
                            class="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800">
                        <option value="all" {{ request('source') === 'all' || !request('source') ? 'selected' : '' }}>Semua Sumber</option>
                        <option value="gate_crossing" {{ request('source') === 'gate_crossing' ? 'selected' : '' }}>Pos Gerbang (Otomatis)</option>
                        <option value="transfer" {{ request('source') === 'transfer' ? 'selected' : '' }}>Transfer Unit (BAST)</option>
                        <option value="manual" {{ request('source') === 'manual' ? 'selected' : '' }}>Manual QMA</option>
                    </select>
                </div>

                <!-- Cari Lokasi / Catatan / User -->
                <div>
                    <label for="search" class="block text-[11px] font-bold text-slate-600 mb-1">Cari Keterangan / Lokasi</label>
                    <div class="relative">
                        <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" 
                               name="search" 
                               id="search" 
                               value="{{ request('search') }}" 
                               placeholder="Ketik lokasi / catatan..." 
                               class="w-full pl-8 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition bg-white text-slate-800">
                    </div>
                </div>
            </div>

            <!-- Tombol Submit & Reset -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                <span class="text-slate-500 font-medium">
                    Menampilkan <strong>{{ $histories->total() }}</strong> riwayat mutasi posisi
                </span>
                <div class="flex items-center space-x-2">
                    @if(request()->hasAny(['date_from', 'date_to', 'site_status', 'source', 'search']))
                    <a href="{{ route('qma.unit_management.history_view', $vehicle->id) }}" 
                       class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 font-semibold transition text-xs">
                        <i class="fa-solid fa-rotate-left mr-1"></i> Reset
                    </a>
                    @endif
                    <button type="submit" 
                            class="px-4 py-1.5 rounded-lg bg-[#1b5e20] hover:bg-[#154a19] text-white font-bold transition text-xs shadow-2xs cursor-pointer">
                        <i class="fa-solid fa-filter mr-1"></i> Terapkan Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. TABEL RIWAYAT LENGKAP & LEGA                                           -->
    <!-- ========================================================================= -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-extrabold text-slate-900 flex items-center space-x-2">
                <i class="fa-solid fa-clock-rotate-left text-[#1b5e20]"></i>
                <span>Tabel Rinci Riwayat Posisi</span>
            </h3>
            <span class="text-xs text-slate-400">Diurutkan dari mutasi paling terbaru</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[900px]">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                        <th class="py-3.5 px-4 text-center w-12">No</th>
                        <th class="py-3.5 px-5 w-48">Waktu & Tanggal</th>
                        <th class="py-3.5 px-4 text-center w-36">Status Area</th>
                        <th class="py-3.5 px-5 w-52">Titik Lokasi</th>
                        <th class="py-3.5 px-4 text-center w-44">Sumber Pembaruan</th>
                        <th class="py-3.5 px-4 w-40">Petugas / Pencatat</th>
                        <th class="py-3.5 px-5">Catatan / Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-800">
                    @forelse($histories as $idx => $history)
                        <tr class="hover:bg-slate-50/70 transition">
                            <!-- Nomor -->
                            <td class="py-4 px-4 text-center text-slate-400 font-mono text-xs">
                                {{ $histories->firstItem() + $idx }}
                            </td>

                            <!-- Waktu & Tanggal -->
                            <td class="py-4 px-5">
                                <div class="font-extrabold text-slate-900 text-xs">
                                    {{ $history->recorded_at ? $history->recorded_at->translatedFormat('d M Y, H:i') : '-' }} WIB
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    {{ $history->recorded_at ? $history->recorded_at->diffForHumans() : '-' }}
                                </div>
                            </td>

                            <!-- Status Area -->
                            <td class="py-4 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase {{ $history->site_status === 'outside_site' ? 'bg-amber-50 text-amber-900 border border-amber-300' : 'bg-emerald-50 text-emerald-900 border border-emerald-300' }}">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $history->site_status === 'outside_site' ? 'bg-amber-600' : 'bg-emerald-600' }}"></span>
                                    {{ $history->site_status_label }}
                                </span>
                            </td>

                            <!-- Lokasi Terdata -->
                            <td class="py-4 px-5">
                                <div class="font-bold text-slate-900 flex items-center space-x-1.5">
                                    <i class="fa-solid fa-map-pin text-[#1b5e20] text-xs"></i>
                                    <span>{{ $history->location ?: '-' }}</span>
                                </div>
                            </td>

                            <!-- Sumber Pembaruan -->
                            <td class="py-4 px-4 text-center">
                                @if($history->source === 'gate_crossing')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                        <i class="fa-solid fa-torii-gate mr-1 text-[10px]"></i>
                                        Pos Gerbang
                                    </span>
                                @elseif($history->source === 'transfer')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-900 border border-emerald-300">
                                        <i class="fa-solid fa-truck-moving mr-1 text-[10px] text-emerald-700"></i>
                                        Transfer BAST
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        <i class="fa-solid fa-user-pen mr-1 text-[10px] text-slate-500"></i>
                                        Manual QMA
                                    </span>
                                @endif
                            </td>

                            <!-- Petugas / Pencatat -->
                            <td class="py-4 px-4">
                                <div class="font-semibold text-slate-800 truncate" title="{{ $history->user_name ?: ($history->user?->name ?? 'Sistem') }}">
                                    {{ $history->user_name ?: ($history->user?->name ?? 'Sistem RKM') }}
                                </div>
                                @if($history->activity)
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5">
                                    Trx: {{ $history->activity->transaction_no }}
                                </div>
                                @endif
                            </td>

                            <!-- Catatan / Audit Note -->
                            <td class="py-4 px-5">
                                <p class="text-xs text-slate-600 leading-relaxed font-normal">
                                    {{ $history->note ?: '-' }}
                                </p>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center text-slate-400">
                                <div class="max-w-md mx-auto space-y-3">
                                    <div class="w-14 h-14 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 text-2xl">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </div>
                                    <h4 class="text-base font-extrabold text-slate-800">Belum Ada Riwayat Tercatat</h4>
                                    <p class="text-xs text-slate-500">
                                        Unit ini belum memiliki riwayat mutasi posisi atau pencarian filter tidak menemukan data yang cocok.
                                    </p>
                                    @if(request()->hasAny(['date_from', 'date_to', 'site_status', 'source', 'search']))
                                    <a href="{{ route('qma.unit_management.history_view', $vehicle->id) }}" 
                                       class="inline-block mt-2 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                                        Reset Semua Filter
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($histories->hasPages())
        <div class="px-5 py-4 border-t border-slate-100 bg-slate-50/50">
            {{ $histories->links() }}
        </div>
        @endif
    </div>

</div>

<!-- ========================================================================= -->
<!-- MODAL PREVIEW FOTO                                                        -->
<!-- ========================================================================= -->
<div id="photoPreviewModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/70 backdrop-blur-xs transition-opacity" onclick="closePhotoPreview()"></div>
    <div class="min-h-full flex items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-200 p-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                <h4 id="photoPreviewTitle" class="text-sm font-extrabold text-slate-900">Preview Foto Unit</h4>
                <button type="button" onclick="closePhotoPreview()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>
            <div class="w-full aspect-video rounded-xl bg-slate-100 overflow-hidden flex items-center justify-center">
                <img id="photoPreviewImg" src="" alt="Foto" class="w-full h-full object-contain">
            </div>
        </div>
    </div>
</div>

<script>
    function toggleExportMenu(e) {
        e.stopPropagation();
        const menu = document.getElementById('exportMenu');
        menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
    }

    document.addEventListener('click', function(e) {
        const container = document.getElementById('exportDropdownContainer');
        const menu = document.getElementById('exportMenu');
        if (container && !container.contains(e.target) && menu) {
            menu.style.display = 'none';
        }
    });

    function openPhotoPreview(url, code) {
        if (!url) return;
        document.getElementById('photoPreviewImg').src = url;
        document.getElementById('photoPreviewTitle').textContent = `Foto Unit: ${code}`;
        const modal = document.getElementById('photoPreviewModal');
        modal.style.display = 'block';
    }

    function closePhotoPreview() {
        const modal = document.getElementById('photoPreviewModal');
        modal.style.display = 'none';
    }
</script>
@endsection
