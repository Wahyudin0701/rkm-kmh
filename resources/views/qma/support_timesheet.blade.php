@extends('layouts.app')

@php
    $isHistoryRoute = request()->routeIs('qma.activity.support_timesheet');
@endphp

@section('title', ($isHistoryRoute ? 'History Timesheet Heavy Equipment' : 'Timesheet Heavy Equipment') . ' - Admin QMA | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Judul & Tombol Aksi (Persis Format Standar HSE Employees) -->
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
                        title="Panduan Halaman Timesheet Heavy Equipment">
                    <i class="fa-solid fa-info"></i>
                </button>
            </div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                {{ $isHistoryRoute ? 'History Timesheet' : 'Timesheet Heavy Equipment' }}
            </h2>
        </div>

        <!-- Tombol Aksi Kanan -->
        <div class="flex items-center gap-2.5 sm:gap-3 w-full sm:w-auto">
            <!-- Dropdown Menu Ekspor Format it.allrole.web.id -->
            <div class="relative inline-block w-full sm:w-auto flex-1 sm:flex-none" id="exportDropdownContainer">
                <button type="button" 
                        onclick="toggleExportMenu(event)" 
                        id="btnExportTrigger"
                        class="w-full sm:w-auto border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 transition shadow-sm cursor-pointer">
                    <i class="fa-regular fa-file-excel text-emerald-700 text-sm"></i>
                    <span class="truncate">Ekspor Timesheet</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-0.5 sm:ml-1"></i>
                </button>
                <div id="exportMenu" style="display: none;" class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-[0_8px_30px_rgb(0,0,0,0.12)] border border-slate-100 z-50 overflow-hidden">
                    <a href="{{ route('qma.support_timesheet.export_pdf', request()->query()) }}" target="_blank" class="flex items-center px-4 py-3 text-xs text-slate-700 hover:bg-slate-50 hover:text-[#1b5e20] font-bold transition">
                        <i class="fa-regular fa-file-pdf text-rose-600 text-sm mr-2.5"></i>
                        <span>Format PDF (Cetak)</span>
                    </a>
                    <div class="h-px bg-slate-100 mx-2"></div>
                    <a href="{{ route('qma.support_timesheet.export_excel', request()->query()) }}" class="flex items-center px-4 py-3 text-xs text-emerald-700 hover:bg-emerald-50 font-bold transition">
                        <i class="fa-regular fa-file-excel text-emerald-600 text-sm mr-2.5"></i>
                        <span>Format Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            @if(!$isHistoryRoute)
                <!-- Tombol Tambah Timesheet -->
                @can('qma.logs.edit_odometer')
                <button type="button" 
                        onclick="openAddModal()" 
                        class="w-full sm:w-auto flex-1 sm:flex-none bg-[#1b5e20] hover:bg-[#144919] text-white px-3 sm:px-4 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 transition shadow-sm cursor-pointer whitespace-nowrap"
                        title="Catat Timesheet Harian Heavy Equipment">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span class="truncate">Catat Timesheet</span>
                </button>
                @endcan
            @endif
        </div>
    </div>

    <!-- KPI Ringkasan Data Timesheet -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- 1. Total Entri -->
        <div class="bg-white p-4 rounded-xl sm:rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Timesheet</p>
            <h3 class="text-2xl font-black text-slate-800 mt-1">{{ number_format($totalEntries, 0, ',', '.') }}</h3>
            <span class="text-[10px] text-slate-400 font-medium block mt-1">Laporan Operasi</span>
        </div>

        <!-- 2. Total Jam Operasi (HM) -->
        <div class="bg-white p-4 rounded-xl sm:rounded-2xl border border-emerald-200 shadow-sm bg-gradient-to-br from-white to-emerald-50/40">
            <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Total Jam Operasi</p>
            <h3 class="text-2xl font-black text-emerald-800 mt-1">{{ number_format($totalOperatingHours, 1, ',', '.') }} <span class="text-sm font-bold text-emerald-600">Jam</span></h3>
            <span class="text-[10px] text-emerald-600 font-semibold block mt-1">Akumulasi Jam Mesin</span>
        </div>

        <!-- 3. Unit Aktif -->
        <div class="bg-white p-4 rounded-xl sm:rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Unit Beroperasi</p>
            <h3 class="text-2xl font-black text-slate-800 mt-1">{{ number_format($activeUnitsCount, 0, ',', '.') }}</h3>
            <span class="text-[10px] text-emerald-600 font-medium block mt-1">Unit Beroperasi</span>
        </div>

        <!-- 4. Breakdown -->
        <div class="bg-white p-4 rounded-xl sm:rounded-2xl border border-slate-200 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Kendala Breakdown</p>
            <h3 class="text-2xl font-black text-rose-600 mt-1">{{ number_format($breakdownCount, 0, ',', '.') }}</h3>
            <span class="text-[10px] text-rose-500 font-medium block mt-1">Status Breakdown</span>
        </div>
    </div>

    <!-- Kotak Pencarian & Filter Status Timesheet (Tanpa Card Wrapper, Format Persis HSE Employees) -->
    <form method="GET" action="{{ url()->current() }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5 sm:gap-3">
        <!-- Pencarian Cepat di Kiri -->
        <div class="relative w-full sm:max-w-md">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" 
                   name="search"
                   id="timesheetSearchInput"
                   value="{{ request('search') }}"
                   placeholder="Cari No. TS, nama/NIK operator, atau lokasi..." 
                   class="w-full text-xs pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 shadow-sm transition">
        </div>

        <!-- Filter di Kanan (Pada Mobile: Sejajar Grid di Bawah Search Bar) -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-2 sm:gap-2.5 w-full sm:w-auto">
            <div class="grid grid-cols-2 sm:flex sm:items-center sm:gap-2.5 w-full sm:w-auto">
                <!-- Filter Rentang Tanggal Compact -->
                <div class="col-span-2 sm:col-auto flex items-center gap-1.5 bg-white border border-slate-200 rounded-xl px-2.5 py-2 shadow-sm">
                    <i class="fa-regular fa-calendar text-slate-400 text-xs flex-shrink-0"></i>
                    <input type="date" 
                           name="start_date" 
                           value="{{ request('start_date', $startDate) }}" 
                           onchange="this.form.submit()" 
                           class="text-xs bg-transparent text-slate-700 font-semibold focus:outline-none" 
                           title="Dari Tanggal">
                    <span class="text-slate-300 text-xs">-</span>
                    <input type="date" 
                           name="end_date" 
                           value="{{ request('end_date', $endDate) }}" 
                           onchange="this.form.submit()" 
                           class="text-xs bg-transparent text-slate-700 font-semibold focus:outline-none" 
                           title="Sampai Tanggal">
                </div>

                <!-- Filter Heavy Equipment -->
                <select name="vehicle_id" 
                        onchange="this.form.submit()" 
                        class="w-full sm:w-auto text-xs rounded-xl border border-slate-200 bg-white px-2.5 sm:px-3 py-2.5 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 shadow-sm transition truncate">
                    <option value="">Semua Heavy Equipment</option>
                    @foreach($unitSupports as $u)
                        <option value="{{ $u->id }}" {{ request('vehicle_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->code }} - {{ $u->brand_type }}
                        </option>
                    @endforeach
                </select>

                <!-- Filter Shift -->
                <select name="shift" 
                        onchange="this.form.submit()" 
                        class="w-full sm:w-auto text-xs rounded-xl border border-slate-200 bg-white px-2.5 sm:px-3 py-2.5 text-slate-700 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 shadow-sm transition truncate">
                    <option value="">Semua Shift</option>
                    @foreach($shifts as $s)
                        <option value="{{ $s }}" {{ request('shift') === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Tombol Reset Filter -->
            <div id="timesheetResetContainer">
                @if(request()->anyFilled(['search', 'vehicle_id', 'shift']) || (request('start_date') && request('start_date') !== now()->toDateString()) || (request('end_date') && request('end_date') !== now()->toDateString()))
                    <a href="{{ url()->current() }}" 
                       id="timesheetResetBtn"
                       class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center justify-center sm:justify-start space-x-1.5 transition whitespace-nowrap py-1 sm:py-0 sm:ml-2">
                        <i class="fa-solid fa-rotate-left text-[11px]"></i>
                        <span>Reset Filter</span>
                    </a>
                @endif
            </div>
        </div>
    </form>

    <!-- Alert Notifikasi -->
    @if(session('success'))
    <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    @endif

    @if($errors->any())
    <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <i class="fa-solid fa-triangle-exclamation text-rose-600 text-sm"></i>
            <span>Terdapat kesalahan pada isian form: {{ $errors->first() }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    @endif

    <!-- Tabel Data Timesheet Heavy Equipment dengan Header Biru Tua Navy (Pada Mobile: Format Kotak Tanpa Border Radius) -->
    <div class="bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <!-- Header Tabel Navy Gelap Solid #06213f -->
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">NO</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">NO TIMESHEET</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">TANGGAL & SHIFT</th>
                        <th class="py-3.5 px-4 whitespace-nowrap min-w-[180px]">KODE & TIPE UNIT</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">OPERATOR</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">METER AWAL</th>
                        <th class="py-3.5 px-4 whitespace-nowrap min-w-[200px]">LOKASI & AKTIVITAS</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">STATUS</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap w-24">AKSI</th>
                    </tr>
                </thead>
                <tbody id="timesheetTableBody" class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($timesheets as $idx => $ts)
                        <tr class="timesheet-row hover:bg-slate-50/70 transition">
                            <!-- No -->
                            <td class="py-3.5 px-3.5 text-center font-semibold text-slate-400">
                                {{ $timesheets->firstItem() + $idx }}
                            </td>

                            <!-- No Timesheet -->
                            <td class="py-3.5 px-3.5 whitespace-nowrap">
                                <span class="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200 text-[11px]">
                                    {{ $ts->timesheet_no }}
                                </span>
                            </td>

                            <!-- Tanggal & Shift -->
                            <td class="py-3.5 px-3.5 whitespace-nowrap">
                                <div class="font-bold text-slate-800">
                                    {{ $ts->date ? $ts->date->translatedFormat('d M Y') : '-' }}
                                </div>
                                <span class="inline-flex items-center text-[10px] font-medium text-slate-500 mt-0.5">
                                    <i class="fa-regular fa-clock mr-1 text-[9px] text-slate-400"></i>
                                    {{ $ts->shift }}
                                    @if($ts->start_time)
                                        <span class="ml-1 text-slate-700 font-bold">• {{ \Carbon\Carbon::parse($ts->start_time)->format('H:i') }}</span>
                                    @endif
                                </span>
                            </td>

                            <!-- Heavy Equipment -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono font-extrabold text-slate-900 text-xs">
                                        {{ $ts->vehicle?->code ?: '-' }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-600 line-clamp-1" title="{{ $ts->vehicle?->brand_type }}">
                                    {{ $ts->vehicle?->brand_type ?: '-' }}
                                </div>
                            </td>

                            <!-- Operator -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900 line-clamp-1">
                                    {{ $ts->operator?->name ?: '-' }}
                                </div>
                                <div class="font-mono text-[10px] text-slate-400">
                                    NIK: {{ $ts->operator?->nik ?: '-' }}
                                </div>
                            </td>

                            <!-- Odometer Awal -->
                            <td class="py-3.5 px-4 text-center font-mono whitespace-nowrap">
                                <span class="text-xs font-bold text-slate-900 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                                    {{ number_format($ts->start_meter, 1, ',', '.') }} {{ $ts->meter_type }}
                                </span>
                            </td>

                            <!-- Lokasi & Aktivitas -->
                            <td class="py-3.5 px-4">
                                <div class="flex items-center space-x-1 font-bold text-slate-900 line-clamp-1">
                                    <i class="fa-solid fa-location-dot text-[#1b5e20] text-[10px] flex-shrink-0"></i>
                                    <span>{{ $ts->location }}</span>
                                </div>
                                <div class="text-[11px] text-emerald-700 font-semibold mt-0.5 line-clamp-1">
                                    {{ $ts->activity_type }}
                                </div>
                                @if($ts->task_description)
                                    <p class="text-[10px] text-slate-500 mt-0.5 line-clamp-1 italic">
                                        "{{ $ts->task_description }}"
                                    </p>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-3.5 text-center whitespace-nowrap">
                                @if($ts->status === 'Operation')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Operation
                                    </span>
                                @elseif($ts->status === 'Standby')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Standby
                                    </span>
                                @elseif($ts->status === 'Breakdown')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Breakdown
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">
                                        {{ $ts->status }}
                                    </span>
                                @endif
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-3.5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center space-x-1.5">
                                    <!-- Tombol Detail (Bisa dilihat di History maupun Operasional) -->
                                    <button type="button" 
                                            data-timesheet="{{ json_encode([
                                                'timesheet_no' => $ts->timesheet_no,
                                                'date' => $ts->date ? $ts->date->format('d/m/Y') : '-',
                                                'start_time' => $ts->start_time ? \Carbon\Carbon::parse($ts->start_time)->format('H:i') : null,
                                                'shift' => $ts->shift,
                                                'code' => $ts->vehicle?->code ?: '-',
                                                'brand_type' => $ts->vehicle?->brand_type ?: '-',
                                                'operator_name' => $ts->operator?->name ?: '-',
                                                'operator_nik' => $ts->operator?->nik ?: '-',
                                                'start_meter' => number_format($ts->start_meter, 1, ',', '.'),
                                                'end_meter' => number_format($ts->end_meter, 1, ',', '.'),
                                                'meter_type' => $ts->meter_type,
                                                'operating_hours' => number_format($ts->operating_hours, 1, ',', '.'),
                                                'standby_hours' => number_format($ts->standby_hours, 1, ',', '.'),
                                                'breakdown_hours' => number_format($ts->breakdown_hours, 1, ',', '.'),
                                                'fuel_liters' => $ts->fuel_liters ? number_format($ts->fuel_liters, 0, ',', '.') : '-',
                                                'location' => $ts->location,
                                                'activity_type' => $ts->activity_type,
                                                'task_description' => $ts->task_description ?: '-',
                                                'status' => $ts->status,
                                                'notes' => $ts->notes ?: '-',
                                            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                                            onclick="openDetailModalFromBtn(this)"
                                            class="inline-flex items-center space-x-1 text-slate-500 hover:text-slate-800 hover:bg-slate-100 p-1.5 rounded-lg transition cursor-pointer"
                                            title="Lihat Detail Riwayat">
                                        <i class="fa-regular fa-eye text-xs"></i>
                                    </button>

                                    @can('qma.logs.edit_odometer')
                                        @if(!$isHistoryRoute)
                                        <button type="button" 
                                                data-timesheet="{{ json_encode($ts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                                                onclick="openEditModalFromBtn(this)"
                                                class="inline-flex items-center space-x-1 text-amber-600 hover:text-amber-800 hover:bg-amber-50 p-1.5 rounded-lg transition cursor-pointer"
                                                title="Edit Timesheet">
                                            <i class="fa-regular fa-pen-to-square text-xs"></i>
                                        </button>

                                        <button type="button" 
                                                onclick="openDeleteModal('{{ $ts->id }}', '{{ $ts->timesheet_no }}')"
                                                class="inline-flex items-center space-x-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 p-1.5 rounded-lg transition cursor-pointer"
                                                title="Hapus Timesheet">
                                            <i class="fa-regular fa-trash-can text-xs"></i>
                                        </button>
                                        @else
                                        <!-- Mode History: Opsi Koreksi -->
                                        <button type="button" 
                                                data-timesheet="{{ json_encode($ts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                                                onclick="openEditModalFromBtn(this)"
                                                class="inline-flex items-center space-x-1 text-amber-600 hover:text-amber-800 hover:bg-amber-50 p-1.5 rounded-lg transition cursor-pointer"
                                                title="Koreksi Data Timesheet">
                                            <i class="fa-solid fa-pen-nib text-xs"></i>
                                        </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-clipboard-list text-4xl mb-3 block text-slate-300"></i>
                                <p class="text-sm font-semibold text-slate-700">Belum ada data timesheet Heavy Equipment</p>
                                @if(!$isHistoryRoute)
                                    <p class="text-xs text-slate-400 mt-1">Gunakan tombol "Catat Timesheet" di atas untuk menambahkan laporan harian.</p>
                                @else
                                    <p class="text-xs text-slate-400 mt-1">Tidak ditemukan catatan timesheet yang sesuai dengan kriteria filter saat ini.</p>
                                @endif
                                @if(request()->anyFilled(['start_date', 'end_date', 'vehicle_id', 'shift', 'search']))
                                <div class="mt-4">
                                    <a href="{{ url()->current() }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                                        <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                        <span>Reset Filter</span>
                                    </a>
                                </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($timesheets->hasPages())
        <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <div>
                Menampilkan data <span class="font-bold text-slate-800">{{ $timesheets->firstItem() }}</span> sampai <span class="font-bold text-slate-800">{{ $timesheets->lastItem() }}</span> dari total <span class="font-bold text-slate-800">{{ $timesheets->total() }}</span> timesheet
            </div>
            <div>
                {{ $timesheets->links() }}
            </div>
        </div>
        @endif
    </div>

</div>

@if(!$isHistoryRoute)
<!-- ========================================== -->
<!-- 1. MODAL TAMBAH TIMESHEET HEAVY EQUIPMENT -->
<!-- ========================================== -->
<div id="addTimesheetModal" class="hidden fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeAddModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#1b5e20]"></span>
                        Catat Timesheet Heavy Equipment
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Input laporan kerja alat berat & penugasan operator harian.</p>
                </div>
                <button type="button" onclick="closeAddModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Form Tambah Timesheet -->
            <form id="addTimesheetForm" method="POST" action="{{ route('qma.support_timesheet.store') }}" class="p-6 space-y-4">
                @csrf

                <!-- Baris 1: Lokasi Kerja & Heavy Equipment -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Lokasi Kerja <span class="text-rose-500">*</span>
                            <span class="sr-only">Tentukan Lokasi Dulu</span>
                        </label>
                        <select id="addLocationSelect" 
                                required 
                                onchange="onTimesheetLocationChanged(this.value, 'add')"
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                            <option value="" disabled selected>-- Pilih Lokasi Kerja --</option>
                            <option value="ALL">[Semua Lokasi / Semua Unit]</option>
                            @foreach($unitLocations as $locName => $count)
                                <option value="{{ $locName }}">{{ $locName }} ({{ $count }} Unit Ready)</option>
                            @endforeach
                            @if(isset($otherLocations) && count($otherLocations))
                                <optgroup label="Lokasi Lainnya di Site">
                                    @foreach($otherLocations as $otherLoc)
                                        <option value="{{ $otherLoc }}">{{ $otherLoc }} (0 Unit)</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            <option value="__CUSTOM__">+ Ketik Lokasi Baru...</option>
                        </select>
                        <div id="addCustomLocationWrapper" class="hidden mt-2">
                            <input type="text" 
                                   id="addCustomLocationInput" 
                                   name="custom_location"
                                   placeholder="Ketik nama lokasi kerja baru..." 
                                   oninput="onCustomLocationTyped(this.value, 'add')"
                                   class="w-full text-xs px-3.5 py-2 rounded-xl border border-emerald-300 bg-emerald-50/40 text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20]">
                        </div>
                        <input type="hidden" name="location" id="addFinalLocation" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                            <span>Heavy Equipment <span class="text-rose-500">*</span></span>
                            <span id="addUnitCounterBadge" class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 hidden"></span>
                        </label>
                        <select name="vehicle_id" 
                                id="addVehicleSelect" 
                                required 
                                onchange="onVehicleSelectChanged(this, 'add')"
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                            <option value="" disabled selected>-- Pilih Lokasi Kerja Terlebih Dahulu --</option>
                        </select>
                        <div id="addVehiclePreview" class="hidden mt-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200/80 text-[11px] text-emerald-900 flex items-center justify-between">
                            <span id="addVehiclePreviewText" class="font-bold"></span>
                            <span id="addVehicleLastKmText" class="text-emerald-700 font-mono text-[10px]"></span>
                        </div>
                    </div>
                </div>

                <!-- Baris 2: Pengemudi / Operator & Kategori Pekerjaan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Pengemudi / Operator <span class="text-rose-500">*</span>
                        </label>
                        <select name="operator_id" 
                                id="addOperatorSelect" 
                                required 
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                            <option value="">-- Pilih Operator --</option>
                            @foreach($operators as $op)
                                <option value="{{ $op->id }}">
                                    {{ $op->name }} ({{ $op->nik }}) {{ $op->is_operator ? '★ Operator' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Kategori Pekerjaan <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="activity_type" 
                               id="addActivityType" 
                               list="activityPresetList" 
                               required 
                               placeholder="Contoh: Galian, Loading, Hauling..." 
                               class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                    </div>
                </div>

                <!-- Baris 3: Tanggal & Jam Mulai -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Tanggal <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" 
                               name="date" 
                               id="addDate"
                               value="{{ date('Y-m-d') }}" 
                               required 
                               class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Jam Mulai <span class="text-rose-500">*</span>
                        </label>
                        <input type="time" 
                               name="start_time" 
                               id="addStartTime" 
                               value="08:00" 
                               required 
                               class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono font-bold focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                    </div>
                </div>

                <!-- Baris 4: Meter Awal & Satuan Meteran -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Meter Awal (Start Meter) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" 
                                   step="0.01" 
                                   name="start_meter" 
                                   id="addStartMeter" 
                                   required 
                                   placeholder="0.00"
                                   class="w-full text-xs pl-3.5 pr-14 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono font-bold focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                            <span id="addMeterUnitLabel" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-extrabold text-slate-400">HM</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Satuan Meteran <span class="text-rose-500">*</span>
                        </label>
                        <select name="meter_type" id="addMeterType" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                            <option value="HM">HM (Hour Meter)</option>
                            <option value="KM">KM (Odometer)</option>
                        </select>
                    </div>
                </div>

                <!-- Baris 5: Shift Kerja & Status Unit -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Shift Kerja
                        </label>
                        <select name="shift" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                            @foreach($shifts as $s)
                                <option value="{{ $s }}" {{ $s === 'Shift Siang' ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Status Akhir Unit <span class="text-rose-500">*</span>
                        </label>
                        <select name="status" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                            <option value="Operation">Operation (Siap Jalan)</option>
                            <option value="Standby">Standby (Tersedia / Siaga)</option>
                            <option value="Breakdown">Breakdown (Kendala / Rusak)</option>
                            <option value="Maintenance">Maintenance (Servis Rutin)</option>
                        </select>
                    </div>
                </div>

                <!-- Baris 6: Rincian Tugas & Catatan -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Rincian Tugas / Catatan Pekerjaan <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <textarea name="task_description" 
                              rows="2" 
                              placeholder="Keterangan rincian tugas atau catatan foreman..." 
                              class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition resize-none"></textarea>
                </div>

                <!-- Tombol Submit Modal -->
                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeAddModal()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-800 hover:bg-slate-100 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="bg-[#1b5e20] hover:bg-[#144919] text-white px-5 py-2.5 rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                        <span>Simpan Timesheet</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- ========================================== -->
<!-- MODAL DETAIL TIMESHEET HEAVY EQUIPMENT    -->
<!-- ========================================== -->
<div id="detailTimesheetModal" class="hidden fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeDetailModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                        Detail Riwayat Timesheet
                    </h3>
                    <p id="detailTimesheetNo" class="font-mono text-xs text-emerald-700 font-bold mt-0.5"></p>
                </div>
                <button type="button" onclick="closeDetailModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Modal Body (Grid Data Rinci) -->
            <div class="p-6 space-y-3 text-xs">
                <div class="grid grid-cols-2 gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200/60">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Tanggal & Shift</span>
                        <span id="detailDateShift" class="font-bold text-slate-800"></span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Status Operasi</span>
                        <span id="detailStatus" class="font-bold text-slate-800"></span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200/60">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Heavy Equipment</span>
                        <span id="detailUnit" class="font-bold text-slate-800"></span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Operator Bertugas</span>
                        <span id="detailOperator" class="font-bold text-slate-800"></span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 p-3 bg-slate-50 rounded-xl border border-slate-200/60">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Meter Awal (Start Meter)</span>
                        <span id="detailMeter" class="font-mono font-bold text-slate-800"></span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Jam Mulai Kerja</span>
                        <span id="detailStartTime" class="font-mono font-bold text-emerald-700 text-sm"></span>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/60 space-y-2">
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Lokasi & Tipe Aktivitas</span>
                        <div class="font-bold text-slate-800 flex items-center space-x-1.5 mt-0.5">
                            <i class="fa-solid fa-location-dot text-[#1b5e20] text-xs"></i>
                            <span id="detailLocation"></span>
                            <span class="text-slate-300">&bull;</span>
                            <span id="detailActivity" class="text-emerald-700 font-semibold"></span>
                        </div>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold uppercase text-slate-400 block">Uraian Tugas / Catatan</span>
                        <p id="detailTask" class="text-slate-600 mt-0.5 italic"></p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3.5 bg-slate-50/70 border-t border-slate-100 flex justify-end">
                <button type="button" onclick="closeDetailModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-100 transition cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ========================================== -->
<!-- 2. MODAL EDIT TIMESHEET HEAVY EQUIPMENT   -->
<!-- ========================================== -->
<div id="editTimesheetModal" class="hidden fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeEditModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        Edit Timesheet Heavy Equipment
                    </h3>
                    <p id="editTimesheetSubtitle" class="text-xs text-slate-500 font-mono mt-0.5">Koreksi data jam kerja atau aktivitas operasional.</p>
                </div>
                <button type="button" onclick="closeEditModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>

            <!-- Form Edit Timesheet -->
            <form id="editTimesheetForm" method="POST" action="" class="p-6 space-y-4">
                @csrf
                @method('PUT')

                <!-- Baris 1: Lokasi Kerja & Heavy Equipment -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Lokasi Kerja <span class="text-rose-500">*</span>
                            <span class="sr-only">Tentukan Lokasi Dulu</span>
                        </label>
                        <select id="editLocationSelect" 
                                required 
                                onchange="onTimesheetLocationChanged(this.value, 'edit')"
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                            <option value="" disabled selected>-- Pilih Lokasi Kerja --</option>
                            <option value="ALL">[Semua Lokasi / Semua Unit]</option>
                            @foreach($unitLocations as $locName => $count)
                                <option value="{{ $locName }}">{{ $locName }} ({{ $count }} Unit Ready)</option>
                            @endforeach
                            @if(isset($otherLocations) && count($otherLocations))
                                <optgroup label="Lokasi Lainnya di Site">
                                    @foreach($otherLocations as $otherLoc)
                                        <option value="{{ $otherLoc }}">{{ $otherLoc }} (0 Unit)</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            <option value="__CUSTOM__">+ Ketik Lokasi Baru...</option>
                        </select>
                        <div id="editCustomLocationWrapper" class="hidden mt-2">
                            <input type="text" 
                                   id="editCustomLocationInput" 
                                   name="custom_location"
                                   placeholder="Ketik nama lokasi kerja baru..." 
                                   oninput="onCustomLocationTyped(this.value, 'edit')"
                                   class="w-full text-xs px-3.5 py-2 rounded-xl border border-emerald-300 bg-emerald-50/40 text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20]">
                        </div>
                        <input type="hidden" name="location" id="editFinalLocation" required>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                            <span>Heavy Equipment <span class="text-rose-500">*</span></span>
                            <span id="editUnitCounterBadge" class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 hidden"></span>
                        </label>
                        <select name="vehicle_id" 
                                id="editVehicleSelect" 
                                required 
                                onchange="onVehicleSelectChanged(this, 'edit')"
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                            <option value="" disabled selected>-- Pilih Lokasi Kerja Terlebih Dahulu --</option>
                        </select>
                        <div id="editVehiclePreview" class="hidden mt-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200/80 text-[11px] text-emerald-900 flex items-center justify-between">
                            <span id="editVehiclePreviewText" class="font-bold"></span>
                            <span id="editVehicleLastKmText" class="text-emerald-700 font-mono text-[10px]"></span>
                        </div>
                    </div>
                </div>

                <!-- Baris 2: Pengemudi / Operator & Kategori Pekerjaan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Pengemudi / Operator <span class="text-rose-500">*</span>
                        </label>
                        <select name="operator_id" 
                                id="editOperatorSelect" 
                                required 
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                            @foreach($operators as $op)
                                <option value="{{ $op->id }}">
                                    {{ $op->name }} ({{ $op->nik }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Kategori Pekerjaan <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="activity_type" 
                               id="editActivityType" 
                               list="activityPresetList" 
                               required 
                               placeholder="Contoh: Galian, Loading, Hauling..." 
                               class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                    </div>
                </div>

                <!-- Baris 3: Tanggal & Jam Mulai -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Tanggal <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" 
                               name="date" 
                               id="editDate"
                               required 
                               class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Jam Mulai <span class="text-rose-500">*</span>
                        </label>
                        <input type="time" 
                               name="start_time" 
                               id="editStartTime" 
                               required 
                               class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono font-bold focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                    </div>
                </div>

                <!-- Baris 4: Meter Awal & Satuan Meteran -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Meter Awal (Start Meter) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" 
                                   step="0.01" 
                                   name="start_meter" 
                                   id="editStartMeter" 
                                   required 
                                   placeholder="0.00"
                                   class="w-full text-xs pl-3.5 pr-14 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono font-bold focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                            <span id="editMeterUnitLabel" class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-extrabold text-slate-400">HM</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Satuan Meteran <span class="text-rose-500">*</span>
                        </label>
                        <select name="meter_type" id="editMeterType" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                            <option value="HM">HM (Hour Meter)</option>
                            <option value="KM">KM (Odometer)</option>
                        </select>
                    </div>
                </div>

                <!-- Baris 5: Shift Kerja & Status Unit -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Shift Kerja
                        </label>
                        <select name="shift" id="editShift" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                            @foreach($shifts as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Status Akhir Unit <span class="text-rose-500">*</span>
                        </label>
                        <select name="status" id="editStatus" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 font-semibold focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition">
                            <option value="Operation">Operation (Siap Jalan)</option>
                            <option value="Standby">Standby (Tersedia / Siaga)</option>
                            <option value="Breakdown">Breakdown (Kendala / Rusak)</option>
                            <option value="Maintenance">Maintenance (Servis Rutin)</option>
                        </select>
                    </div>
                </div>

                <!-- Baris 6: Deskripsi & Catatan -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Rincian Tugas / Catatan Pekerjaan <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <textarea name="task_description" 
                              id="editTaskDescription"
                              rows="2" 
                              placeholder="Keterangan rincian tugas atau catatan foreman..." 
                              class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] focus:ring-1 focus:ring-[#1b5e20] transition resize-none"></textarea>
                </div>

                <!-- Tombol Submit Edit -->
                <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-800 hover:bg-slate-100 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-5 py-2.5 rounded-xl text-xs font-bold transition shadow-xs flex items-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ========================================== -->
<!-- 3. MODAL HAPUS TIMESHEET                  -->
<!-- ========================================== -->
<div id="deleteTimesheetModal" class="hidden fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeDeleteModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl border border-slate-100 p-6 text-center">
            <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-xl mx-auto mb-3">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-900 mb-1">Hapus Data Timesheet?</h3>
            <p class="text-xs text-slate-500 mb-4">
                Apakah Anda yakin ingin menghapus timesheet <span id="deleteTimesheetNo" class="font-mono font-bold text-rose-600">-</span>? Tindakan ini tidak dapat dibatalkan.
            </p>

            <form id="deleteTimesheetForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="flex items-center justify-center space-x-2">
                    <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                        Batal
                    </button>
                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2 rounded-xl text-xs font-bold transition shadow-xs">
                        Ya, Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Datalist Rekomendasi Heavy Equipment -->
<datalist id="unitSupportDatalist">
    @foreach($unitSupports as $u)
        <option value="{{ $u->code }}">{{ $u->brand_type }} (Terakhir: {{ number_format($u->current_km ?? 0, 1, ',', '.') }})</option>
    @endforeach
</datalist>

<!-- Datalist Rekomendasi Kategori Pekerjaan -->
<datalist id="activityPresetList">
    @foreach($activityTypes as $act)
        <option value="{{ $act }}">
    @endforeach
</datalist>

<!-- Datalist Rekomendasi Lokasi Global -->
<datalist id="locationPresetList">
    @foreach($locationSuggestions as $loc)
        <option value="{{ $loc }}">
    @endforeach
</datalist>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PANDUAN HALAMAN (TIMESHEET HEAVY EQUIPMENT)                  -->
<!-- ========================================================================= -->
<div id="guideModal" 
     onclick="if(event.target === this) closeGuideModal()" 
     class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-7 border border-slate-100 transform transition-all my-8">
        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-[#1b5e20] flex items-center justify-center text-lg shadow-2xs">
                    <i class="fa-solid fa-book-open"></i>
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight">
                        Panduan Timesheet Heavy Equipment
                    </h3>
                    <p class="text-xs text-slate-500 font-medium">
                        Tata cara pencatatan jam kerja mesin & operasional alat berat
                    </p>
                </div>
            </div>
            <button type="button" 
                    onclick="closeGuideModal()" 
                    class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <!-- Konten Panduan -->
        <div class="py-5 space-y-4 text-xs text-slate-600 leading-relaxed max-h-[60vh] overflow-y-auto pr-1">
            <div class="p-3.5 rounded-2xl bg-emerald-50/60 border border-emerald-100/80">
                <h4 class="font-bold text-[#1b5e20] text-xs flex items-center mb-1">
                    <i class="fa-solid fa-stopwatch mr-1.5"></i>
                    1. Satuan Meter Mesin (HM vs KM)
                </h4>
                <p class="text-slate-600">
                    Sistem mendeteksi satuan secara otomatis: Dump Truck (DT) menggunakan satuan Kilometer (<strong>KM</strong>), sedangkan Excavator, Dozer, Crane, dsb. menggunakan Hour Meter (<strong>HM</strong>).
                </p>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <h4 class="font-bold text-slate-800 text-xs flex items-center mb-1">
                    <i class="fa-solid fa-filter mr-1.5 text-emerald-600"></i>
                    2. Filter Lokasi Kerja & Unit Ready
                </h4>
                <p class="text-slate-600">
                    Saat menambah timesheet, pilih lokasi kerja terlebih dahulu. Daftar Heavy Equipment akan disaring otomatis menampilkan unit yang berada pada lokasi tersebut.
                </p>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <h4 class="font-bold text-slate-800 text-xs flex items-center mb-1">
                    <i class="fa-solid fa-triangle-exclamation mr-1.5 text-amber-500"></i>
                    3. Status Operasi & Breakdown
                </h4>
                <p class="text-slate-600">
                    Pilih status <strong>Operation</strong> untuk unit yang bekerja produktif, <strong>Standby</strong> jika mesin ready namun menunggu arahan, atau <strong>Breakdown</strong> jika mengalami kerusakan.
                </p>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <h4 class="font-bold text-slate-800 text-xs flex items-center mb-1">
                    <i class="fa-solid fa-file-export mr-1.5 text-blue-600"></i>
                    4. Ekspor Data Laporan
                </h4>
                <p class="text-slate-600">
                    Gunakan tombol <strong>Ekspor Timesheet</strong> di pojok kanan atas untuk mengunduh laporan rekapitulasi dalam format PDF atau Excel (.xlsx) sesuai filter yang aktif.
                </p>
            </div>
        </div>

        <!-- Footer Modal -->
        <div class="pt-4 border-t border-slate-100 flex justify-end">
            <button type="button" 
                    onclick="closeGuideModal()" 
                    class="bg-[#1b5e20] hover:bg-[#144919] text-white px-5 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                Mengerti
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    window.unitSupportsData = @json($unitSupports);

    // 1. Dropdown Ekspor
    function toggleExportMenu(event) {
        event.stopPropagation();
        const menu = document.getElementById('exportMenu');
        menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'block' : 'none';
    }

    document.addEventListener('click', function(e) {
        const menu = document.getElementById('exportMenu');
        const trigger = document.getElementById('btnExportTrigger');
        if (menu && menu.style.display === 'block' && trigger && !trigger.contains(e.target)) {
            menu.style.display = 'none';
        }
    });

    // 2. Modal Tambah Timesheet
    function openAddModal() {
        const modal = document.getElementById('addTimesheetModal');
        if (modal) {
            // Reset form pilihan lokasi & unit
            const addLocSelect = document.getElementById('addLocationSelect');
            if (addLocSelect) addLocSelect.selectedIndex = 0;
            const customWrap = document.getElementById('addCustomLocationWrapper');
            if (customWrap) customWrap.classList.add('hidden');
            const customInput = document.getElementById('addCustomLocationInput');
            if (customInput) customInput.value = '';
            const finalLoc = document.getElementById('addFinalLocation');
            if (finalLoc) finalLoc.value = '';

            const vehSelect = document.getElementById('addVehicleSelect');
            if (vehSelect) {
                vehSelect.innerHTML = '<option value="" disabled selected>-- Pilih Lokasi Kerja Terlebih Dahulu --</option>';
            }
            const counter = document.getElementById('addUnitCounterBadge');
            if (counter) counter.classList.add('hidden');
            const preview = document.getElementById('addVehiclePreview');
            if (preview) preview.classList.add('hidden');

            const startMeter = document.getElementById('addStartMeter');
            if (startMeter) startMeter.value = '';

            const startTime = document.getElementById('addStartTime');
            if (startTime) startTime.value = '08:00';

            modal.classList.remove('hidden');
            modal.style.display = 'block';
        }
    }
    function closeAddModal() {
        const modal = document.getElementById('addTimesheetModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    }

    // Modal Detail Riwayat Timesheet
    function openDetailModalFromBtn(btn) {
        try {
            const ts = JSON.parse(btn.getAttribute('data-timesheet'));
            document.getElementById('detailTimesheetNo').textContent = ts.timesheet_no;
            document.getElementById('detailDateShift').textContent = `${ts.date} • ${ts.shift}`;
            document.getElementById('detailUnit').textContent = `${ts.code} - ${ts.brand_type}`;
            document.getElementById('detailOperator').textContent = `${ts.operator_name} (NIK: ${ts.operator_nik})`;
            document.getElementById('detailMeter').textContent = `${ts.start_meter} ${ts.meter_type}`;
            const detailStartTimeEl = document.getElementById('detailStartTime');
            if (detailStartTimeEl) {
                detailStartTimeEl.textContent = ts.start_time ? (ts.start_time.substring(0, 5) + ' WIB') : '08:00 WIB';
            }
            document.getElementById('detailLocation').textContent = ts.location;
            document.getElementById('detailActivity').textContent = ts.activity_type;
            document.getElementById('detailTask').textContent = ts.task_description !== '-' ? `"${ts.task_description}"` : '-';
            document.getElementById('detailStatus').textContent = ts.status;

            const modal = document.getElementById('detailTimesheetModal');
            modal.classList.remove('hidden');
            modal.style.display = 'block';
        } catch (err) {
            console.error('Failed to parse detail timesheet data:', err);
        }
    }

    function closeDetailModal() {
        const modal = document.getElementById('detailTimesheetModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    }

    // 3. Modal Edit Timesheet
    function openEditModalFromBtn(btn) {
        try {
            const raw = btn.getAttribute('data-timesheet');
            const ts = JSON.parse(raw);
            openEditModal(ts);
        } catch (err) {
            console.error('Failed to parse timesheet data:', err);
        }
    }

    function openEditModal(ts) {
        const form = document.getElementById('editTimesheetForm');
        form.action = `/qma/support-timesheet/${ts.id}`;

        const unitCode = ts.vehicle ? ts.vehicle.code : (ts.vehicle_code || '');
        document.getElementById('editTimesheetSubtitle').textContent = `No: ${ts.timesheet_no} (${unitCode || '-'})`;

        // 1. Sinkronisasi Lokasi Kerja
        const locSelect = document.getElementById('editLocationSelect');
        const finalLoc = document.getElementById('editFinalLocation');
        const customWrap = document.getElementById('editCustomLocationWrapper');
        const targetLoc = ts.location || '';

        finalLoc.value = targetLoc;

        let matched = false;
        if (locSelect) {
            for (let i = 0; i < locSelect.options.length; i++) {
                if (locSelect.options[i].value.toLowerCase() === targetLoc.toLowerCase()) {
                    locSelect.selectedIndex = i;
                    matched = true;
                    break;
                }
            }

            if (!matched && targetLoc) {
                // Tambahkan option lokasi baru jika belum terdaftar
                const newOpt = document.createElement('option');
                newOpt.value = targetLoc;
                newOpt.textContent = `${targetLoc} (Lokasi Riwayat)`;
                locSelect.appendChild(newOpt);
                locSelect.value = targetLoc;
            }
        }

        if (customWrap) customWrap.classList.add('hidden');

        // Filter unit pada lokasi ini dengan memilih ts.vehicle_id
        onTimesheetLocationChanged(locSelect ? locSelect.value : targetLoc, 'edit', ts.vehicle_id);

        document.getElementById('editOperatorSelect').value = ts.operator_id;
        
        // Format tanggal YYYY-MM-DD
        const d = ts.date ? ts.date.split('T')[0] : '';
        document.getElementById('editDate').value = d;
        const startTimeInput = document.getElementById('editStartTime');
        if (startTimeInput) {
            startTimeInput.value = ts.start_time ? ts.start_time.substring(0, 5) : '08:00';
        }
        document.getElementById('editShift').value = ts.shift || 'Shift Siang';
        document.getElementById('editMeterType').value = ts.meter_type || 'HM';

        document.getElementById('editStartMeter').value = ts.start_meter;

        document.getElementById('editActivityType').value = ts.activity_type;
        document.getElementById('editStatus').value = ts.status || 'Operation';
        document.getElementById('editTaskDescription').value = ts.task_description || ts.notes || '';

        updateMeterUnitLabels('edit');

        const modal = document.getElementById('editTimesheetModal');
        modal.classList.remove('hidden');
        modal.style.display = 'block';
    }

    function closeEditModal() {
        const modal = document.getElementById('editTimesheetModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    // 4. Modal Hapus Timesheet
    function openDeleteModal(id, no) {
        document.getElementById('deleteTimesheetForm').action = `/qma/support-timesheet/${id}`;
        document.getElementById('deleteTimesheetNo').textContent = no;
        const modal = document.getElementById('deleteTimesheetModal');
        modal.classList.remove('hidden');
        modal.style.display = 'block';
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteTimesheetModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    // 5. Handler saat Lokasi Kerja Dipilih (Filter Heavy Equipment yang Ready di Lokasi tersebut)
    function onTimesheetLocationChanged(selectedLoc, prefix, preselectVehicleId = null) {
        const customWrapper = document.getElementById(`${prefix}CustomLocationWrapper`);
        const customInput = document.getElementById(`${prefix}CustomLocationInput`);
        const finalLocation = document.getElementById(`${prefix}FinalLocation`);
        const vehicleSelect = document.getElementById(`${prefix}VehicleSelect`);
        const counterBadge = document.getElementById(`${prefix}UnitCounterBadge`);
        const previewEl = document.getElementById(`${prefix}VehiclePreview`);

        if (!vehicleSelect) return;

        // Tangani pilihan custom atau ALL
        if (selectedLoc === '__CUSTOM__') {
            if (customWrapper) customWrapper.classList.remove('hidden');
            if (customInput) {
                customInput.focus();
                if (finalLocation) finalLocation.value = customInput.value.trim();
            }
        } else {
            if (customWrapper) customWrapper.classList.add('hidden');
            if (finalLocation) {
                finalLocation.value = (selectedLoc === 'ALL' || !selectedLoc) ? '' : selectedLoc;
            }
        }

        const allUnits = window.unitSupportsData || [];
        let filteredUnits = [];

        if (!selectedLoc) {
            vehicleSelect.innerHTML = '<option value="" disabled selected>-- Pilih Lokasi Kerja Terlebih Dahulu --</option>';
            if (counterBadge) counterBadge.classList.add('hidden');
            if (previewEl) previewEl.classList.add('hidden');
            return;
        } else if (selectedLoc === 'ALL' || selectedLoc === '__CUSTOM__') {
            filteredUnits = allUnits;
        } else {
            const norm = selectedLoc.trim().toLowerCase();
            filteredUnits = allUnits.filter(u => {
                const uLoc = (u.location || 'Site Kerinci').trim().toLowerCase();
                return uLoc === norm;
            });

            // Jika dalam mode edit dan preselectVehicleId tidak ditemukan di lokasi ini (misal unit berpindah setelah ts dicatat), sertakan unit tersebut
            if (preselectVehicleId) {
                const foundInFiltered = filteredUnits.some(u => String(u.id) === String(preselectVehicleId));
                if (!foundInFiltered) {
                    const extra = allUnits.find(u => String(u.id) === String(preselectVehicleId));
                    if (extra) filteredUnits.unshift(extra);
                }
            }
        }

        // Update badge counter
        if (counterBadge) {
            counterBadge.classList.remove('hidden');
            if (selectedLoc === 'ALL' || selectedLoc === '__CUSTOM__') {
                counterBadge.textContent = `${filteredUnits.length} Total Unit`;
            } else {
                counterBadge.textContent = `${filteredUnits.length} Unit Ready`;
            }
        }

        // Render option heavy equipment
        vehicleSelect.innerHTML = '';
        const defaultPrompt = document.createElement('option');
        defaultPrompt.value = '';
        defaultPrompt.disabled = true;
        defaultPrompt.textContent = filteredUnits.length > 0 
            ? `-- Pilih Heavy Equipment (${filteredUnits.length} Unit Ready) --` 
            : `-- Tidak ada unit di lokasi ini (Pilih 'Semua Unit') --`;
        vehicleSelect.appendChild(defaultPrompt);

        let hasSelected = false;
        filteredUnits.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.setAttribute('data-km', u.current_km ?? 0);
            opt.setAttribute('data-code', u.code || '');
            opt.setAttribute('data-brand', u.brand_type || '');
            opt.setAttribute('data-location', u.location || 'Site Kerinci');

            const lastKm = parseFloat(u.current_km) || 0;
            const code = (u.code || '').toUpperCase();
            const meterType = code.startsWith('DT') ? 'KM' : 'HM';
            const kmFormatted = lastKm.toLocaleString('id-ID', {minimumFractionDigits: 1, maximumFractionDigits: 2});

            opt.textContent = `${u.code} - ${u.brand_type || ''} (Meter: ${kmFormatted} ${meterType})`;

            if (preselectVehicleId && String(u.id) === String(preselectVehicleId)) {
                opt.selected = true;
                hasSelected = true;
            }
            vehicleSelect.appendChild(opt);
        });

        if (hasSelected) {
            onVehicleSelectChanged(vehicleSelect, prefix);
        } else {
            defaultPrompt.selected = true;
            if (previewEl) previewEl.classList.add('hidden');
        }
    }

    // 6. Handler saat Custom Lokasi Diketik
    function onCustomLocationTyped(val, prefix) {
        const finalLocation = document.getElementById(`${prefix}FinalLocation`);
        if (finalLocation) {
            finalLocation.value = val.trim();
        }
    }

    // 7. Handler saat Heavy Equipment Dipilih
    function onVehicleSelectChanged(selectEl, prefix) {
        const opt = selectEl.options[selectEl.selectedIndex];
        if (!opt || !opt.value) return;

        const code = opt.getAttribute('data-code') || '';
        const brand = opt.getAttribute('data-brand') || '';
        const lastKm = parseFloat(opt.getAttribute('data-km')) || 0;
        const unitLocation = opt.getAttribute('data-location') || 'Site Kerinci';

        // Tampilkan info preview
        const previewEl = document.getElementById(`${prefix}VehiclePreview`);
        const previewText = document.getElementById(`${prefix}VehiclePreviewText`);
        const lastKmText = document.getElementById(`${prefix}VehicleLastKmText`);
        if (previewEl) {
            previewEl.classList.remove('hidden');
            previewText.textContent = `${code} - ${brand}`;
            lastKmText.textContent = `Meter Terakhir: ${lastKm.toLocaleString('id-ID', {minimumFractionDigits: 1, maximumFractionDigits: 2})}`;
        }

        // Auto-isi start_meter jika kosong atau 0
        const startMeterInput = document.getElementById(`${prefix}StartMeter`);
        if (startMeterInput && (!startMeterInput.value || parseFloat(startMeterInput.value) === 0)) {
            startMeterInput.value = lastKm.toFixed(2);
        }

        // Auto-deteksi tipe meteran: DT -> KM, selainnya -> HM
        const meterTypeSelect = document.getElementById(`${prefix}MeterType`);
        if (meterTypeSelect) {
            if (code.toUpperCase().startsWith('DT')) {
                meterTypeSelect.value = 'KM';
            } else {
                meterTypeSelect.value = 'HM';
            }
            updateMeterUnitLabels(prefix);
        }

        // Jika lokasi kerja dipilih 'ALL', sinkronkan finalLocation ke lokasi unit
        const locSelect = document.getElementById(`${prefix}LocationSelect`);
        const finalLoc = document.getElementById(`${prefix}FinalLocation`);
        if (locSelect && locSelect.value === 'ALL' && finalLoc && !finalLoc.value) {
            finalLoc.value = unitLocation;
        }
    }

    function updateMeterUnitLabels(prefix) {
        const typeEl = document.getElementById(`${prefix}MeterType`);
        const unit = typeEl ? typeEl.value : 'HM';
        const label = document.getElementById(`${prefix}MeterUnitLabel`);
        if (label) {
            label.textContent = unit;
        }
    }

    document.getElementById('addMeterType')?.addEventListener('change', () => updateMeterUnitLabels('add'));
    document.getElementById('editMeterType')?.addEventListener('change', () => updateMeterUnitLabels('edit'));

    // 8. Filter Pencarian Instan Real-Time (Persis Standar HSE Employees)
    const timesheetSearchInput = document.getElementById('timesheetSearchInput');
    const timesheetResetContainer = document.getElementById('timesheetResetContainer');
    const timesheetTableBody = document.getElementById('timesheetTableBody');

    timesheetSearchInput?.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#timesheetTableBody tr.timesheet-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            if (query === '' || text.includes(query)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Dynamic empty state
        const existingEmptyTr = document.getElementById('timesheetClientEmptyRow');
        if (existingEmptyTr) existingEmptyTr.remove();

        if (visibleCount === 0 && rows.length > 0) {
            const emptyTr = document.createElement('tr');
            emptyTr.id = 'timesheetClientEmptyRow';
            emptyTr.innerHTML = `
                <td colspan="9" class="py-12 text-center text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-3xl mb-3 block text-slate-300"></i>
                    <p class="text-sm font-semibold text-slate-700">Tidak ada timesheet yang cocok</p>
                    <p class="text-xs text-slate-400 mt-1">
                        Tidak ditemukan data dengan kata kunci "${escapeHtml(query)}" pada halaman ini.
                    </p>
                    <div class="mt-4">
                        <button type="button" 
                                onclick="const inp = document.getElementById('timesheetSearchInput'); if(inp){ inp.value=''; inp.dispatchEvent(new Event('input')); inp.focus(); }" 
                                class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
                            <i class="fa-solid fa-rotate-left text-[11px]"></i>
                            <span>Hapus Pencarian</span>
                        </button>
                    </div>
                </td>
            `;
            timesheetTableBody?.appendChild(emptyTr);
        }

        // Dynamic reset button
        let resetBtn = document.getElementById('timesheetResetBtn');
        if (query.length > 0) {
            if (!resetBtn && timesheetResetContainer) {
                resetBtn = document.createElement('a');
                resetBtn.id = 'timesheetResetBtn';
                resetBtn.href = window.location.pathname;
                resetBtn.className = 'text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center justify-center sm:justify-start space-x-1.5 transition whitespace-nowrap py-1 sm:py-0 sm:ml-2';
                resetBtn.innerHTML = '<i class="fa-solid fa-rotate-left text-[11px]"></i><span>Reset Filter</span>';
                timesheetResetContainer.appendChild(resetBtn);
            }
        }
    });

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // 9. Modal Panduan Halaman (Data Timesheet Heavy Equipment)
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

    // 10. Keyboard ESC Listener untuk Semua Modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGuideModal();
            closeAddModal();
            closeEditModal();
            closeDetailModal();
            closeDeleteModal();
            const exportMenu = document.getElementById('exportMenu');
            if (exportMenu) exportMenu.style.display = 'none';
        }
    });
</script>
@endsection
