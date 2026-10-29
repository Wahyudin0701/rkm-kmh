@extends('layouts.app')

@section('title', 'Riwayat Aktivitas - Sistem RKM | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Halaman Bersih & Minimalis -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
            <div class="flex items-center space-x-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">
                <span>Portal Karyawan</span>
                <span>&bull;</span>
                <span class="text-[#1b5e20] font-bold">Catatan Operasional</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Riwayat Aktivitas Saya
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Rekap seluruh aktivitas pemeriksaan kelaikan P2H, timesheet alat berat, dan mobilisasi kendaraan Anda.
            </p>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-center">
            <a href="{{ route('employee.dashboard') }}" 
               class="px-3.5 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition shadow-2xs">
                &larr; Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- Ringkasan KPI Pribadi Karyawan (Tanpa Ikon Berlebih, Tipografi Bersih) -->
    <div class="grid grid-cols-1 {{ ($canP2h && $canTimesheet && $canTrips) ? 'sm:grid-cols-3' : (($canP2h || $canTimesheet) && ($canTrips || ($canP2h && $canTimesheet)) ? 'sm:grid-cols-2' : 'sm:grid-cols-1') }} gap-4">
        @if($canP2h)
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-2xs">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">P2H Heavy Equipment</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-slate-900">{{ number_format($stats['p2h_total']) }}</span>
                <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                    {{ $stats['p2h_good'] }} Layak
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-2">
                {{ $stats['p2h_defects'] }} catatan memiliki temuan kerusakan
            </p>
        </div>
        @endif

        @if($canTimesheet)
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-2xs">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Timesheet Alat Berat</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-slate-900">{{ number_format($stats['timesheet_hours'], 1, ',', '.') }} Jam</span>
                <span class="text-xs font-semibold text-slate-700 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                    {{ number_format($stats['timesheet_total']) }} Sesi
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-2">
                Total akumulasi jam operasi alat berat
            </p>
        </div>
        @endif

        @if($canTrips)
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-2xs">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Perjalanan Gerbang (LV)</p>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-2xl font-black text-slate-900">{{ number_format($stats['trip_distance'], 0, ',', '.') }} KM</span>
                <span class="text-xs font-semibold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                    {{ number_format($stats['trip_total']) }} Trip
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-2">
                Jarak kumulatif mobilisasi kendaraan dinas
            </p>
        </div>
        @endif
    </div>

    <!-- Tab Navigasi Dinamis Berdasarkan Hak Akses & Riwayat -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xs overflow-hidden">
        
        <!-- Header Tab -->
        <div class="border-b border-slate-200 px-5 pt-3 bg-slate-50/50">
            <div class="flex space-x-6 overflow-x-auto no-scrollbar">
                @if($canP2h)
                <a href="{{ route('employee.history', array_merge(request()->except(['page', 'p2h_page', 'ts_page', 'trip_page']), ['tab' => 'p2h'])) }}" 
                   class="pb-3 text-xs sm:text-sm font-bold border-b-2 whitespace-nowrap transition flex items-center gap-2 {{ $activeTab === 'p2h' ? 'border-[#1b5e20] text-[#1b5e20]' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                    <span>P2H Heavy Equipment</span>
                    <span class="px-2 py-0.5 text-[11px] rounded-full {{ $activeTab === 'p2h' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200/80 text-slate-600' }}">
                        {{ $p2hCount }}
                    </span>
                </a>
                @endif

                @if($canTimesheet)
                <a href="{{ route('employee.history', array_merge(request()->except(['page', 'p2h_page', 'ts_page', 'trip_page']), ['tab' => 'timesheet'])) }}" 
                   class="pb-3 text-xs sm:text-sm font-bold border-b-2 whitespace-nowrap transition flex items-center gap-2 {{ $activeTab === 'timesheet' ? 'border-[#1b5e20] text-[#1b5e20]' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                    <span>Timesheet Heavy Equipment</span>
                    <span class="px-2 py-0.5 text-[11px] rounded-full {{ $activeTab === 'timesheet' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200/80 text-slate-600' }}">
                        {{ $timesheetCount }}
                    </span>
                </a>
                @endif

                @if($canTrips)
                <a href="{{ route('employee.history', array_merge(request()->except(['page', 'p2h_page', 'ts_page', 'trip_page']), ['tab' => 'trips'])) }}" 
                   class="pb-3 text-xs sm:text-sm font-bold border-b-2 whitespace-nowrap transition flex items-center gap-2 {{ $activeTab === 'trips' ? 'border-[#1b5e20] text-[#1b5e20]' : 'border-transparent text-slate-500 hover:text-slate-800' }}">
                    <span>Mobilisasi & Pos Gerbang (LV)</span>
                    <span class="px-2 py-0.5 text-[11px] rounded-full {{ $activeTab === 'trips' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200/80 text-slate-600' }}">
                        {{ $tripCount }}
                    </span>
                </a>
                @endif
            </div>
        </div>

        <!-- Filter Bar Bersih -->
        <div class="p-5 border-b border-slate-100 bg-white">
            <form method="GET" action="{{ route('employee.history') }}" class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
                <input type="hidden" name="tab" value="{{ $activeTab }}">

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-2.5 flex-1">
                    <!-- Kata Kunci Pencarian -->
                    <div class="sm:col-span-2 md:col-span-1">
                        <input type="text" 
                               name="search" 
                               value="{{ $search }}" 
                               placeholder="Cari transaksi, kode unit..." 
                               class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                    </div>

                    <!-- Dari Tanggal -->
                    <div>
                        <input type="date" 
                               name="date_from" 
                               value="{{ $dateFrom }}" 
                               title="Dari Tanggal"
                               class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition text-slate-600">
                    </div>

                    <!-- Sampai Tanggal -->
                    <div>
                        <input type="date" 
                               name="date_to" 
                               value="{{ $dateTo }}" 
                               title="Sampai Tanggal"
                               class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition text-slate-600">
                    </div>

                    <!-- Filter Status Sesuai Tab Aktif -->
                    <div>
                        <select name="status" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition text-slate-600">
                            <option value="">Semua Status</option>
                            @if($activeTab === 'p2h')
                                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Menunggu Approval QMA</option>
                                <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Disetujui</option>
                                <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                                <option value="good" {{ $status === 'good' ? 'selected' : '' }}>Kondisi: Layak Jalan</option>
                                <option value="has_defect" {{ $status === 'has_defect' ? 'selected' : '' }}>Kondisi: Ada Temuan</option>
                            @elseif($activeTab === 'timesheet')
                                <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Selesai</option>
                                <option value="running" {{ $status === 'running' ? 'selected' : '' }}>Sedang Berjalan</option>
                            @elseif($activeTab === 'trips')
                                <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Disetujui Satpam</option>
                                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Menunggu Validasi</option>
                                <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Ditolak Satpam</option>
                                <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                            @endif
                        </select>
                    </div>
                </div>

                <!-- Tombol Filter & Reset -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button type="submit" 
                            class="px-4 py-2 bg-[#1b5e20] hover:bg-[#144918] text-white font-bold text-xs rounded-xl shadow-2xs transition">
                        Terapkan Filter
                    </button>
                    @if($search || $dateFrom || $dateTo || $status)
                    <a href="{{ route('employee.history', ['tab' => $activeTab]) }}" 
                       class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold rounded-xl transition">
                        Reset
                    </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- ========================================================== -->
        <!-- TAB 1: RIWAYAT P2H HEAVY EQUIPMENT                         -->
        <!-- ========================================================== -->
        @if($activeTab === 'p2h')
            @if(isset($p2hRecords) && $p2hRecords->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left min-w-[760px]">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                            <th class="py-3 px-4">Waktu Inspeksi</th>
                            <th class="py-3 px-4">No. Transaksi</th>
                            <th class="py-3 px-4">Unit Alat Berat</th>
                            <th class="py-3 px-4">Shift & Odometer</th>
                            <th class="py-3 px-4 text-center">Kelaikan Operasi</th>
                            <th class="py-3 px-4 text-center">Status QMA</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($p2hRecords as $rec)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-bold text-slate-900 block">{{ $rec->inspected_at ? $rec->inspected_at->format('d/m/Y') : '-' }}</span>
                                <span class="text-[11px] text-slate-400 block">{{ $rec->inspected_at ? $rec->inspected_at->format('H:i') . ' WIB' : '-' }}</span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap font-mono font-bold text-slate-800">
                                {{ $rec->transaction_no }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-bold text-[#1b5e20] font-mono text-xs block">{{ $rec->vehicle->code ?? '-' }}</span>
                                <span class="text-[11px] text-slate-500 block truncate max-w-[160px]">{{ $rec->vehicle->brand_type ?? '-' }}</span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-medium text-slate-800 block">Shift {{ $rec->shift }}</span>
                                <span class="text-[11px] font-mono text-slate-500 block">HM: {{ number_format($rec->start_hm, 1) }}</span>
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($rec->summary_condition === 'good')
                                    <span class="inline-block px-2.5 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Layak Jalan
                                    </span>
                                @else
                                    <span class="inline-block px-2.5 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200" title="{{ $rec->defect_count }} temuan kerusakan">
                                        Ada Temuan ({{ $rec->defect_count }})
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($rec->validation_status === 'approved')
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Disetujui oleh: {{ $rec->validator->name ?? 'Admin QMA' }}">
                                        Disetujui
                                    </span>
                                @elseif($rec->validation_status === 'rejected')
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="Alasan: {{ $rec->validation_notes }}">
                                        Ditolak
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        Menunggu QMA
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap space-x-1">
                                <button type="button" 
                                        onclick="viewP2hDetail({{ $rec->id }})" 
                                        class="px-2.5 py-1 text-[11px] font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition cursor-pointer">
                                    Detail
                                </button>
                                <a href="{{ route('employee.history.p2h_pdf', $rec->id) }}" 
                                   target="_blank" 
                                   class="px-2.5 py-1 text-[11px] font-semibold text-rose-700 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100 transition">
                                    PDF
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            <div class="p-4 border-t border-slate-100 bg-white">
                {{ $p2hRecords->links() }}
            </div>
            @else
            <div class="py-12 px-4 text-center">
                <p class="text-sm font-bold text-slate-700">Belum Ada Riwayat P2H Heavy Equipment</p>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                    Data pemeriksaan kelaikan unit yang pernah Anda kerjakan akan otomatis tampil di tabel ini.
                </p>
                @can('employee.p2h')
                <div class="mt-4">
                    <a href="{{ route('p2h.index') }}" class="px-4 py-2 bg-[#1b5e20] text-white text-xs font-bold rounded-xl hover:bg-[#144918] transition inline-block">
                        Mulai Pemeriksaan P2H Sekarang
                    </a>
                </div>
                @endcan
            </div>
            @endif

        <!-- ========================================================== -->
        <!-- TAB 2: RIWAYAT TIMESHEET ALAT BERAT                        -->
        <!-- ========================================================== -->
        @elseif($activeTab === 'timesheet')
            @if(isset($timesheetRecords) && $timesheetRecords->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left min-w-[760px]">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                            <th class="py-3 px-4">Tanggal & Shift</th>
                            <th class="py-3 px-4">No. Timesheet</th>
                            <th class="py-3 px-4">Unit Alat Berat</th>
                            <th class="py-3 px-4 text-right">Odometer (HM / KM)</th>
                            <th class="py-3 px-4 text-right">Jam Operasi</th>
                            <th class="py-3 px-4">Lokasi & Aktivitas</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($timesheetRecords as $ts)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-bold text-slate-900 block">{{ $ts->date ? $ts->date->format('d/m/Y') : '-' }}</span>
                                <span class="text-[11px] text-slate-500 block font-medium">Shift {{ $ts->shift }}</span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap font-mono font-bold text-slate-800">
                                {{ $ts->timesheet_no }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-bold text-[#1b5e20] font-mono text-xs block">{{ $ts->vehicle->code ?? '-' }}</span>
                                <span class="text-[11px] text-slate-500 block truncate max-w-[150px]">{{ $ts->vehicle->brand_type ?? '-' }}</span>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono">
                                <span class="text-slate-900 font-semibold block">{{ number_format($ts->start_meter, 1) }} &rarr; {{ number_format($ts->end_meter, 1) }}</span>
                                <span class="text-[10px] text-slate-400 block">{{ strtoupper($ts->meter_type ?? 'HM') }}</span>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono">
                                <span class="font-bold text-slate-900 block">{{ number_format($ts->operating_hours, 1) }} Jam</span>
                                @if($ts->standby_hours > 0 || $ts->breakdown_hours > 0)
                                    <span class="text-[10px] text-slate-400 block">
                                        Stb: {{ number_format($ts->standby_hours, 1) }} | BD: {{ number_format($ts->breakdown_hours, 1) }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-bold text-slate-800 block truncate max-w-[180px]">{{ $ts->location ?? '-' }}</span>
                                <span class="text-[11px] text-slate-500 block truncate max-w-[200px]">{{ $ts->task_description ?? '-' }}</span>
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($ts->status === 'completed')
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Selesai
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        Berjalan
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            <div class="p-4 border-t border-slate-100 bg-white">
                {{ $timesheetRecords->links() }}
            </div>
            @else
            <div class="py-12 px-4 text-center">
                <p class="text-sm font-bold text-slate-700">Belum Ada Riwayat Timesheet Alat Berat</p>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                    Data jam kerja dan catatan operasional operator heavy equipment akan tampil di sini setelah dicatat.
                </p>
            </div>
            @endif

        <!-- ========================================================== -->
        <!-- TAB 3: RIWAYAT MOBILISASI & POS GERBANG (LV)               -->
        <!-- ========================================================== -->
        @elseif($activeTab === 'trips')
            @if(isset($tripRecords) && $tripRecords->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left min-w-[760px]">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                            <th class="py-3 px-4">Waktu</th>
                            <th class="py-3 px-4">No. Transaksi</th>
                            <th class="py-3 px-4">Unit Kendaraan</th>
                            <th class="py-3 px-4">Pos Gerbang & Arah</th>
                            <th class="py-3 px-4 text-right">Odometer & Jarak</th>
                            <th class="py-3 px-4">Keperluan</th>
                            <th class="py-3 px-4 text-center">Status Satpam</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($tripRecords as $act)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-bold text-slate-900 block">{{ $act->recorded_at ? $act->recorded_at->format('d/m/Y') : '-' }}</span>
                                <span class="text-[11px] text-slate-400 block">{{ $act->recorded_at ? $act->recorded_at->format('H:i') . ' WIB' : '-' }}</span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap font-mono font-bold text-slate-800">
                                {{ $act->transaction_no }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-bold text-[#1b5e20] font-mono text-xs block">{{ $act->vehicle->code ?? '-' }}</span>
                                <span class="text-[11px] text-slate-500 block truncate max-w-[160px]">
                                    {{ $act->vehicle->brand_type ?? '-' }} &bull; {{ $act->vehicle->license_plate ?? 'Tanpa Plat' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-bold text-slate-800 block">{{ $act->gate->name ?? 'Pos Gerbang' }}</span>
                                @if($act->direction === 'in')
                                    <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        MASUK
                                    </span>
                                @else
                                    <span class="inline-block mt-0.5 px-1.5 py-0.2 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        KELUAR
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono">
                                <span class="font-bold text-slate-900 block">{{ number_format($act->current_km, 0, ',', '.') }} KM</span>
                                <span class="text-[11px] text-emerald-700 font-semibold block">+{{ number_format($act->distance, 0, ',', '.') }} KM</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="text-slate-700 block truncate max-w-[180px]" title="{{ $act->purpose }}">
                                    {{ $act->purpose ?: '-' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($act->validation_status === 'approved')
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Divalidasi oleh: {{ $act->securityGuard->name ?? 'Satpam' }}">
                                        Disetujui
                                    </span>
                                @elseif($act->validation_status === 'cancelled')
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200" title="{{ $act->rejection_note }}">
                                        Dibatalkan
                                    </span>
                                @elseif($act->validation_status === 'rejected')
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="Alasan: {{ $act->rejection_note }}">
                                        Ditolak
                                    </span>
                                @else
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        Menunggu Satpam
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            <div class="p-4 border-t border-slate-100 bg-white">
                {{ $tripRecords->links() }}
            </div>
            @else
            <div class="py-12 px-4 text-center">
                <p class="text-sm font-bold text-slate-700">Belum Ada Riwayat Perjalanan Pos Gerbang</p>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                    Catatan mobilisasi kendaraan ringan (LV) Anda yang melewati pos penjagaan satpam akan dicatat di sini.
                </p>
            </div>
            @endif

        @else
            <div class="py-12 px-4 text-center">
                <p class="text-sm font-bold text-slate-700">Tidak Ada Modul Riwayat yang Tersedia</p>
                <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                    Akun Anda saat ini belum memiliki hak akses operasional yang aktif.
                </p>
            </div>
        @endif

    </div>

</div>

<!-- ========================================================== -->
<!-- MODAL DETAIL INSPEKSI P2H (DESAIN BERSIH TANPA IKON BERLEBIH) -->
<!-- ========================================================== -->
<div id="p2hDetailModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/40 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h3 class="text-base font-bold text-slate-900" id="modalTrxNo">Detail Lembar P2H</h3>
                <p class="text-xs text-slate-500 mt-0.5" id="modalSubTitle">Hasil pemeriksaan kelaikan unit alat berat</p>
            </div>
            <button type="button" 
                    onclick="closeP2hDetailModal()" 
                    class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 cursor-pointer">
                &times;
            </button>
        </div>

        <!-- Modal Body Scrollable -->
        <div class="p-6 overflow-y-auto space-y-4 text-xs">
            <!-- Metadata Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                <div>
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Unit Alat Berat</span>
                    <span class="font-bold text-[#1b5e20] text-xs font-mono" id="modalUnitCode">-</span>
                    <span class="text-[11px] text-slate-600 block truncate" id="modalUnitName">-</span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Shift & Odometer</span>
                    <span class="font-bold text-slate-800 text-xs block" id="modalShift">-</span>
                    <span class="text-[11px] font-mono text-slate-600 block" id="modalHm">-</span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Waktu Inspeksi</span>
                    <span class="font-semibold text-slate-800 text-xs block" id="modalTime">-</span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 uppercase font-bold block">Status Approval</span>
                    <span class="inline-block mt-0.5 px-2 py-0.5 text-[10px] font-bold rounded" id="modalStatusBadge">-</span>
                </div>
            </div>

            <!-- Catatan Umum -->
            <div id="modalGeneralNotesContainer" class="p-3 bg-amber-50/60 rounded-xl border border-amber-200/60 hidden">
                <span class="text-[11px] font-bold text-amber-900 block mb-0.5">Catatan Operator:</span>
                <p class="text-xs text-amber-800" id="modalGeneralNotes"></p>
            </div>

            <!-- Tabel Checklist Items -->
            <div>
                <p class="font-bold text-slate-800 mb-2 uppercase text-[10px] tracking-wider text-slate-400">Daftar Pemeriksaan Komponen:</p>
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                                <th class="py-2.5 px-3">Bagian</th>
                                <th class="py-2.5 px-3">Komponen / Item</th>
                                <th class="py-2.5 px-3 text-center">Kondisi</th>
                                <th class="py-2.5 px-3">Catatan Temuan</th>
                            </tr>
                        </thead>
                        <tbody id="modalItemsTableBody" class="divide-y divide-slate-100">
                            <!-- Injected via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <a id="modalDownloadPdfBtn" href="#" target="_blank" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl transition shadow-2xs">
                Cetak Lembar PDF
            </a>
            <button type="button" 
                    onclick="closeP2hDetailModal()" 
                    class="px-4 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold rounded-xl transition cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    function viewP2hDetail(recordId) {
        const modal = document.getElementById('p2hDetailModal');
        const pdfBtn = document.getElementById('modalDownloadPdfBtn');
        const tableBody = document.getElementById('modalItemsTableBody');

        pdfBtn.href = "{{ url('/karyawan/riwayat/p2h', [], false) }}/" + recordId + "/pdf";
        tableBody.innerHTML = '<tr><td colspan="4" class="py-4 text-center text-slate-400">Memuat data...</td></tr>';
        modal.classList.remove('hidden');

        fetch("{{ url('/karyawan/riwayat/p2h', [], false) }}/" + recordId)
            .then(res => {
                if (!res.ok) throw new Error('Gagal memuat detail data.');
                return res.json();
            })
            .then(data => {
                document.getElementById('modalTrxNo').textContent = 'P2H: ' + data.transaction_no;
                document.getElementById('modalSubTitle').textContent = data.template_name;
                document.getElementById('modalUnitCode').textContent = data.vehicle_code;
                document.getElementById('modalUnitName').textContent = data.vehicle_name;
                document.getElementById('modalShift').textContent = 'Shift ' + data.shift;
                document.getElementById('modalHm').textContent = 'HM: ' + (data.start_hm || '0');
                document.getElementById('modalTime').textContent = data.inspected_at || '-';

                const badge = document.getElementById('modalStatusBadge');
                if (data.validation_status === 'approved') {
                    badge.className = 'inline-block mt-0.5 px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-50 text-emerald-700 border border-emerald-200';
                    badge.textContent = 'Disetujui';
                } else if (data.validation_status === 'rejected') {
                    badge.className = 'inline-block mt-0.5 px-2 py-0.5 text-[10px] font-bold rounded bg-rose-50 text-rose-700 border border-rose-200';
                    badge.textContent = 'Ditolak';
                } else {
                    badge.className = 'inline-block mt-0.5 px-2 py-0.5 text-[10px] font-bold rounded bg-amber-50 text-amber-800 border border-amber-200';
                    badge.textContent = 'Menunggu QMA';
                }

                const notesContainer = document.getElementById('modalGeneralNotesContainer');
                if (data.general_notes && data.general_notes.trim() !== '') {
                    notesContainer.classList.remove('hidden');
                    document.getElementById('modalGeneralNotes').textContent = data.general_notes;
                } else {
                    notesContainer.classList.add('hidden');
                }

                if (data.items && data.items.length > 0) {
                    tableBody.innerHTML = data.items.map(item => {
                        const isGood = item.condition === 'baik' || item.condition === 'good' || item.condition === 'ok';
                        const condBadge = isGood
                            ? '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Baik (OK)</span>'
                            : '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">Rusak (Defect)</span>';
                        
                        return `
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-2.5 px-3 text-slate-500 font-medium">${item.section || '-'}</td>
                                <td class="py-2.5 px-3 font-semibold text-slate-800">${item.item_name || '-'}</td>
                                <td class="py-2.5 px-3 text-center">${condBadge}</td>
                                <td class="py-2.5 px-3 text-slate-600">${item.notes || '-'}</td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    tableBody.innerHTML = '<tr><td colspan="4" class="py-3 text-center text-slate-400">Tidak ada komponen checklist tercatat.</td></tr>';
                }
            })
            .catch(err => {
                tableBody.innerHTML = '<tr><td colspan="4" class="py-4 text-center text-rose-500">' + err.message + '</td></tr>';
            });
    }

    function closeP2hDetailModal() {
        document.getElementById('p2hDetailModal').classList.add('hidden');
    }

    // Tutup modal jika klik di luar box
    document.getElementById('p2hDetailModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeP2hDetailModal();
        }
    });
</script>
@endsection
