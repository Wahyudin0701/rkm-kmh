@extends('layouts.app')

@section('title', 'Summary Aktivitas Kendaraan QMA')

@section('content')
<div class="space-y-6">
    <!-- Header Halaman -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20] block mb-1">
                MANAJEMEN SISTEM
            </span>
            <div class="flex items-center space-x-3">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Summary Aktivitas Kendaraan
                </h2>
                <span class="w-6 h-6 rounded-full bg-slate-200/90 text-slate-500 text-xs inline-flex items-center justify-center font-bold shadow-sm" title="Ringkasan analitik dan rekapitulasi pergerakan armada kendaraan">
                    <i class="fa-solid fa-chart-pie"></i>
                </span>
            </div>
        </div>

        <div class="flex items-center space-x-3 w-full sm:w-auto">
            <a href="{{ route('qma.activity.log') }}" class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 shadow-sm transition group">
                <i class="fa-solid fa-clipboard-list mr-2 text-slate-500 group-hover:text-slate-800"></i>
                <span>Lihat Log Aktivitas Detail</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar Periode & Parameter -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <form method="GET" action="{{ route('qma.activity.summary') }}" class="space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2 text-slate-800 font-bold text-xs uppercase tracking-wider">
                    <i class="fa-solid fa-sliders text-[#1b5e20]"></i>
                    <span>Filter Periode & Kriteria Summary</span>
                </div>
                @if(request()->hasAny(['start_date', 'end_date', 'type', 'gate_id']))
                    <a href="{{ route('qma.activity.summary') }}" class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center space-x-1 transition">
                        <i class="fa-solid fa-rotate-left"></i>
                        <span>Reset Filter</span>
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                <!-- Mulai Tanggal -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-regular fa-calendar text-slate-400"></i>
                        <span>Mulai Tanggal</span>
                    </label>
                    <input type="date" 
                           name="start_date" 
                           value="{{ request('start_date') }}" 
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>

                <!-- Sampai Tanggal -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-regular fa-calendar-check text-slate-400"></i>
                        <span>Sampai Tanggal</span>
                    </label>
                    <input type="date" 
                           name="end_date" 
                           value="{{ request('end_date') }}" 
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>

                <!-- Tipe Kendaraan -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-car-side text-slate-400"></i>
                        <span>Kategori Unit</span>
                    </label>
                    <select name="type" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Kategori</option>
                        <option value="lv" {{ request('type') === 'lv' ? 'selected' : '' }}>Light Vehicle (LV)</option>
                        <option value="support" {{ request('type') === 'support' ? 'selected' : '' }}>Unit Support</option>
                    </select>
                </div>

                <!-- Pos Gerbang -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-torii-gate text-slate-400"></i>
                        <span>Pos Gerbang</span>
                    </label>
                    <select name="gate_id" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Gerbang</option>
                        @foreach($gates as $g)
                            <option value="{{ $g->id }}" {{ request('gate_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Tombol Submit & Reset -->
            <div class="flex items-center justify-end space-x-2 pt-2">
                <a href="{{ route('qma.activity.summary') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition">
                    Reset
                </a>
                <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-[#06213f] hover:bg-[#0b315b] shadow-sm transition flex items-center space-x-2">
                    <i class="fa-solid fa-filter text-[11px]"></i>
                    <span>Terapkan Filter</span>
                </button>
            </div>
        </form>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Pergerakan / Trips -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Pergerakan</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalActivities, 0, ',', '.') }}</h3>
                <div class="flex items-center space-x-2 mt-1 text-[11px]">
                    <span class="text-emerald-700 font-semibold"><i class="fa-solid fa-arrow-down mr-0.5"></i> {{ $inCount }} Masuk</span>
                    <span class="text-slate-300">•</span>
                    <span class="text-blue-700 font-semibold"><i class="fa-solid fa-arrow-up mr-0.5"></i> {{ $outCount }} Keluar</span>
                </div>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-[#06213f] text-xl">
                <i class="fa-solid fa-arrows-up-down"></i>
            </div>
        </div>

        <!-- Akumulasi Jarak Tempuh (KM) -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Jarak Tempuh</p>
                <h3 class="text-2xl font-black text-emerald-700 font-mono mt-1">
                    {{ number_format($totalDistance, 0, ',', '.') }} <span class="text-xs font-sans font-bold text-slate-500">KM</span>
                </h3>
                <p class="text-[11px] text-slate-500 mt-1">
                    Rata-rata: <span class="font-bold font-mono text-slate-700">{{ number_format($avgDistancePerTrip, 1, ',', '.') }} KM</span>/trip
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 text-xl">
                <i class="fa-solid fa-gauge-high"></i>
            </div>
        </div>

        <!-- Armada Beroperasi -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Armada Beroperasi</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $activeVehiclesCount }} <span class="text-xs font-bold text-slate-500 font-sans">Unit</span></h3>
                <p class="text-[11px] text-slate-500 mt-1">
                    Unit aktif dalam periode ini
                </p>
            </div>
            <div class="w-12 h-12 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 text-xl">
                <i class="fa-solid fa-truck-pickup"></i>
            </div>
        </div>

        <!-- Status Validasi PG -->
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status Validasi PG</p>
                <div class="flex items-center space-x-2 mt-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ $approvedCount }} Disetujui
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        {{ $pendingCount }} Menunggu
                    </span>
                </div>
                @if($rejectedCount > 0)
                    <p class="text-[10px] text-rose-600 font-semibold mt-1">
                        {{ $rejectedCount }} Ditolak
                    </p>
                @endif
            </div>
            <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-600 text-xl">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
        </div>
    </div>

    <!-- Rekapitulasi per Kendaraan (Tabel Summary Armada) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-base font-bold text-slate-900">Rekapitulasi Aktivitas per Armada Kendaraan</h3>
                <p class="text-xs text-slate-500">Akumulasi pergerakan, jarak tempuh, dan frekuensi per unit kendaraan</p>
            </div>
            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-xl">
                {{ $vehicleSummaries->count() }} Kendaraan Aktif
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">Kode Unit</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">Unit / Merek</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">Kategori</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">Penanggung Jawab</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">Trip Masuk</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">Trip Keluar</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">Total Trip</th>
                        <th class="py-3.5 px-4 text-right whitespace-nowrap">Total Jarak</th>
                        <th class="py-3.5 px-4 text-right whitespace-nowrap">Odometer Terkini</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($vehicleSummaries as $vSummary)
                        @php
                            $veh = $vSummary->vehicle;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 text-center whitespace-nowrap font-mono">
                                <span class="inline-block bg-emerald-50 text-emerald-800 font-bold px-2.5 py-1 rounded border border-emerald-200">
                                    {{ $veh->code ?? '-' }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">{{ $veh->brand_type ?? '-' }}</div>
                                @if($veh && $veh->license_plate)
                                    <span class="inline-block text-[10px] font-mono font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 mt-0.5">
                                        {{ $veh->license_plate }}
                                    </span>
                                @else
                                    <span class="text-[10px] text-slate-400 italic">-</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($veh && $veh->isLightVehicle())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                        Light Vehicle
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800">
                                        Unit Support
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-slate-600 font-medium">
                                {{ $veh->ownership ?? '-' }}
                                <div class="text-[10px] text-slate-400">{{ $veh->division ?? '-' }}</div>
                            </td>

                            <td class="py-3.5 px-4 text-center font-bold text-emerald-700 whitespace-nowrap">
                                {{ $vSummary->in_trips }}
                            </td>

                            <td class="py-3.5 px-4 text-center font-bold text-blue-700 whitespace-nowrap">
                                {{ $vSummary->out_trips }}
                            </td>

                            <td class="py-3.5 px-4 text-center font-black text-slate-900 whitespace-nowrap">
                                {{ $vSummary->total_trips }}
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono font-bold text-emerald-700 whitespace-nowrap">
                                {{ number_format($vSummary->total_km, 0, ',', '.') }}
                                <span class="text-[10px] text-slate-400 font-sans font-normal">KM</span>
                            </td>

                            <td class="py-3.5 px-4 text-right font-mono font-medium text-slate-600 whitespace-nowrap">
                                {{ number_format($veh->current_km ?? 0, 0, ',', '.') }}
                                <span class="text-[10px] text-slate-400 font-sans font-normal">KM</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-slate-400">
                                <i class="fa-solid fa-chart-simple text-3xl mb-2 block"></i>
                                Belum ada data aktivitas kendaraan untuk periode yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Grid: Top Driver & Rekapitulasi Pos Gerbang -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top 10 Driver Pengemudi Teraktif -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Pengemudi (Driver) Teraktif</h3>
                    <p class="text-xs text-slate-500">Berdasarkan total jarak tempuh & frekuensi trip</p>
                </div>
                <i class="fa-solid fa-id-card text-slate-400 text-lg"></i>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs text-left border-collapse">
                    <thead class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Driver</th>
                            <th class="py-3 px-4">Divisi</th>
                            <th class="py-3 px-4 text-center">Total Trip</th>
                            <th class="py-3 px-4 text-right">Total KM</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($driverSummaries as $dSummary)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    {{ $dSummary->driver->name ?? 'Pengemudi Tidak Terdaftar' }}
                                    <div class="text-[10px] text-slate-400 font-mono font-normal">{{ $dSummary->driver->nik ?? '-' }}</div>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    {{ $dSummary->driver->division ?? '-' }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-slate-900">
                                    {{ $dSummary->total_trips }}
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">
                                    {{ number_format($dSummary->total_km, 0, ',', '.') }} KM
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400">
                                    Belum ada data driver pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rekapitulasi Pos Gerbang -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Trafik Pos Gerbang</h3>
                    <p class="text-xs text-slate-500">Distribusi pergerakan armada per pos inspeksi</p>
                </div>
                <i class="fa-solid fa-torii-gate text-slate-400 text-lg"></i>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-xs text-left border-collapse">
                    <thead class="bg-slate-50 text-slate-700 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="py-3 px-4">Pos Gerbang</th>
                            <th class="py-3 px-4 text-center">Masuk</th>
                            <th class="py-3 px-4 text-center">Keluar</th>
                            <th class="py-3 px-4 text-right">Total Pergerakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($gateSummaries as $gSummary)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 font-bold text-slate-900">
                                    {{ $gSummary->gate->name ?? 'Gerbang' }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-emerald-700">
                                    {{ $gSummary->in_trips }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-blue-700">
                                    {{ $gSummary->out_trips }}
                                </td>
                                <td class="py-3 px-4 text-right font-black text-slate-900">
                                    {{ $gSummary->total_trips }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-slate-400">
                                    Belum ada data trafik gerbang pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
