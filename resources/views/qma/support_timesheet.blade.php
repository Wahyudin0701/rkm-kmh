@extends('layouts.app')

@section('title', 'Timesheet Unit Support - Admin QMA | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Judul & Tombol Aksi -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center space-x-1.5 mb-1">
                <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20]">
                    MANAJEMEN QMA
                </span>
                <span class="text-slate-300">&bull;</span>
                <span class="text-xs font-semibold text-slate-500">
                    Log Aktifitas
                </span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Timesheet Unit Support
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                Pencatatan jam kerja (HM/KM), penugasan operator, utilisasi shift, serta pemakaian solar alat berat.
            </p>
        </div>

        <!-- Tombol Aksi Kanan -->
        <div class="flex items-center space-x-2.5 w-full sm:w-auto">
            <!-- Dropdown Menu Ekspor -->
            <div class="relative inline-block w-full sm:w-auto" id="exportDropdownContainer">
                <button type="button" 
                        onclick="toggleExportMenu(event)" 
                        id="btnExportTrigger"
                        class="w-full sm:w-auto flex items-center justify-center space-x-2 border border-emerald-600/30 text-emerald-900 bg-white hover:bg-emerald-50/50 px-3.5 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer">
                    <i class="fa-solid fa-file-export text-emerald-700 text-sm"></i>
                    <span class="truncate">Ekspor Timesheet</span>
                    <i class="fa-solid fa-chevron-down text-[9px] text-slate-400 ml-1"></i>
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

            <!-- Tombol Tambah Timesheet -->
            @can('qma.logs.edit_odometer')
            <button type="button" 
                    onclick="openAddModal()" 
                    class="w-full sm:w-auto flex items-center justify-center space-x-2 bg-[#1b5e20] hover:bg-[#144919] text-white px-4 py-2.5 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer whitespace-nowrap">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>Catat Timesheet</span>
            </button>
            @endcan
        </div>
    </div>

    <!-- KPI Ringkasan Data Timesheet -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
        <!-- 1. Total Entri -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Timesheet</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1">{{ number_format($totalEntries, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-slate-400 font-medium">Laporan Operasi</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 text-lg">
                <i class="fa-solid fa-clipboard-check"></i>
            </div>
        </div>

        <!-- 2. Total Jam Operasi (HM) -->
        <div class="bg-white p-4 rounded-2xl border border-emerald-200/80 shadow-xs flex items-center justify-between bg-gradient-to-br from-white to-emerald-50/40">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700">Total Jam Operasi</p>
                <h3 class="text-2xl font-black text-emerald-800 mt-1">{{ number_format($totalOperatingHours, 1, ',', '.') }} <span class="text-sm font-bold text-emerald-600">Jam</span></h3>
                <span class="text-[10px] text-emerald-600 font-semibold">Akumulasi Jam Mesin</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-100/80 flex items-center justify-center text-[#1b5e20] text-lg">
                <i class="fa-solid fa-stopwatch"></i>
            </div>
        </div>

        <!-- 3. Total Solar (Liter) -->
        <div class="bg-white p-4 rounded-2xl border border-blue-200/80 shadow-xs flex items-center justify-between bg-gradient-to-br from-white to-blue-50/40">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-blue-700">Konsumsi Solar</p>
                <h3 class="text-2xl font-black text-blue-800 mt-1">{{ number_format($totalFuelLiters, 0, ',', '.') }} <span class="text-sm font-bold text-blue-600">L</span></h3>
                <span class="text-[10px] text-blue-600 font-semibold">Pengisian Bahan Bakar</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-100/80 flex items-center justify-center text-blue-700 text-lg">
                <i class="fa-solid fa-gas-pump"></i>
            </div>
        </div>

        <!-- 4. Unit Aktif -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Unit Beroperasi</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1">{{ number_format($activeUnitsCount, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-emerald-600 font-medium">Unit Beroperasi</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 text-lg">
                <i class="fa-solid fa-truck-monster"></i>
            </div>
        </div>

        <!-- 5. Breakdown -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between col-span-2 sm:col-span-1">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Kendala Breakdown</p>
                <h3 class="text-2xl font-black text-rose-600 mt-1">{{ number_format($breakdownCount, 0, ',', '.') }}</h3>
                <span class="text-[10px] text-rose-500 font-medium">Status Breakdown</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600 text-lg">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs space-y-3.5">
        <form method="GET" action="{{ route('qma.support_timesheet.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-2.5 items-center">
            
            <!-- Tanggal Mulai -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Dari Tanggal</label>
                <input type="date" 
                       name="start_date" 
                       value="{{ request('start_date', $startDate) }}" 
                       class="w-full text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-medium focus:outline-none focus:border-[#1b5e20]">
            </div>

            <!-- Tanggal Selesai -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Sampai Tanggal</label>
                <input type="date" 
                       name="end_date" 
                       value="{{ request('end_date', $endDate) }}" 
                       class="w-full text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-medium focus:outline-none focus:border-[#1b5e20]">
            </div>

            <!-- Filter Unit -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Pilih Unit</label>
                <select name="vehicle_id" class="w-full text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-medium focus:outline-none focus:border-[#1b5e20]">
                    <option value="">Semua Unit Support</option>
                    @foreach($unitSupports as $u)
                        <option value="{{ $u->id }}" {{ request('vehicle_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->code }} - {{ $u->brand_type }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Shift -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Pilih Shift</label>
                <select name="shift" class="w-full text-xs rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700 font-medium focus:outline-none focus:border-[#1b5e20]">
                    <option value="">Semua Shift</option>
                    @foreach($shifts as $s)
                        <option value="{{ $s }}" {{ request('shift') === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Pencarian Kata Kunci -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Cari Kata Kunci</label>
                <div class="relative w-full">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="No TS, lokasi, operator..." 
                           class="w-full text-xs pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20]">
                </div>
            </div>

            <!-- Tombol Submit & Reset -->
            <div class="flex items-end space-x-2 pt-4">
                <button type="submit" class="flex-1 bg-[#1b5e20] hover:bg-[#144919] text-white px-3.5 py-2 rounded-xl text-xs font-bold transition shadow-xs flex items-center justify-center space-x-1.5 cursor-pointer">
                    <i class="fa-solid fa-filter text-[11px]"></i>
                    <span>Filter</span>
                </button>

                @if(request()->anyFilled(['start_date', 'end_date', 'vehicle_id', 'operator_id', 'shift', 'status', 'search']))
                    <a href="{{ route('qma.support_timesheet.index') }}" class="px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition whitespace-nowrap" title="Reset Semua Filter">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

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

    <!-- Tabel Data Timesheet Unit Support -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 uppercase tracking-wider text-[10px] font-bold">
                        <th class="py-3 px-3.5 text-center w-10">NO</th>
                        <th class="py-3 px-3.5">NO TIMESHEET</th>
                        <th class="py-3 px-3.5">TANGGAL & SHIFT</th>
                        <th class="py-3 px-4">KODE & TIPE UNIT</th>
                        <th class="py-3 px-4">OPERATOR</th>
                        <th class="py-3 px-3.5 text-right">METERAN (HM/KM)</th>
                        <th class="py-3 px-3 text-center">JAM KERJA</th>
                        <th class="py-3 px-4">LOKASI & AKTIVITAS</th>
                        <th class="py-3 px-3 text-right">SOLAR</th>
                        <th class="py-3 px-3.5 text-center">STATUS</th>
                        <th class="py-3 px-3.5 text-center w-24">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($timesheets as $idx => $ts)
                        <tr class="hover:bg-slate-50/70 transition">
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
                                </span>
                            </td>

                            <!-- Unit Support -->
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

                            <!-- Meteran Start - End -->
                            <td class="py-3.5 px-3.5 text-right font-mono whitespace-nowrap">
                                <div class="text-[11px] text-slate-600">
                                    <span class="text-slate-400 text-[10px]">Awal:</span> {{ number_format($ts->start_meter, 1, ',', '.') }}
                                </div>
                                <div class="text-[11px] font-bold text-slate-900">
                                    <span class="text-slate-400 text-[10px]">Akhir:</span> {{ number_format($ts->end_meter, 1, ',', '.') }}
                                </div>
                            </td>

                            <!-- Jam Kerja (Operating Hours) -->
                            <td class="py-3.5 px-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    {{ number_format($ts->operating_hours, 1, ',', '.') }} {{ $ts->meter_type }}
                                </span>
                                @if($ts->standby_hours > 0 || $ts->breakdown_hours > 0)
                                    <div class="text-[9px] text-slate-400 mt-0.5">
                                        @if($ts->standby_hours > 0) STB: {{ number_format($ts->standby_hours, 1) }}h @endif
                                        @if($ts->breakdown_hours > 0) BD: {{ number_format($ts->breakdown_hours, 1) }}h @endif
                                    </div>
                                @endif
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

                            <!-- Solar (Liter) -->
                            <td class="py-3.5 px-3 text-right font-mono whitespace-nowrap">
                                @if($ts->fuel_liters)
                                    <span class="font-bold text-blue-700">{{ number_format($ts->fuel_liters, 0, ',', '.') }}</span>
                                    <span class="text-[10px] text-slate-400">L</span>
                                @else
                                    <span class="text-slate-400">-</span>
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
                                    @can('qma.logs.edit_odometer')
                                    <button type="button" 
                                            data-timesheet="{{ json_encode($ts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) }}"
                                            onclick="openEditModalFromBtn(this)"
                                            class="inline-flex items-center space-x-1 text-amber-600 hover:text-amber-800 hover:bg-amber-50 p-1.5 rounded-lg transition"
                                            title="Edit Timesheet">
                                        <i class="fa-regular fa-pen-to-square text-xs"></i>
                                    </button>

                                    <button type="button" 
                                            onclick="openDeleteModal('{{ $ts->id }}', '{{ $ts->timesheet_no }}')"
                                            class="inline-flex items-center space-x-1 text-rose-600 hover:text-rose-800 hover:bg-rose-50 p-1.5 rounded-lg transition"
                                            title="Hapus Timesheet">
                                        <i class="fa-regular fa-trash-can text-xs"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="py-12 text-center text-slate-400">
                                <i class="fa-solid fa-clipboard-list text-4xl mb-3 block text-slate-300"></i>
                                <p class="text-sm font-semibold text-slate-700">Belum ada data timesheet unit support</p>
                                <p class="text-xs text-slate-400 mt-1">Gunakan tombol "Catat Timesheet" di atas untuk menambahkan laporan harian.</p>
                                @if(request()->anyFilled(['start_date', 'end_date', 'vehicle_id', 'shift', 'search']))
                                <div class="mt-4">
                                    <a href="{{ route('qma.support_timesheet.index') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition cursor-pointer">
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

<!-- ========================================== -->
<!-- 1. MODAL TAMBAH TIMESHEET UNIT SUPPORT    -->
<!-- ========================================== -->
<div id="addTimesheetModal" class="hidden fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeAddModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/70">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100/80 text-[#1b5e20] flex items-center justify-center text-sm">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Catat Timesheet Unit Support</h3>
                        <p class="text-[11px] text-slate-500">Input laporan kerja alat berat & penugasan operator harian.</p>
                    </div>
                </div>
                <button type="button" onclick="closeAddModal()" class="text-slate-400 hover:text-slate-600 text-sm p-1 rounded-lg hover:bg-slate-100 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Form Tambah Timesheet -->
            <form id="addTimesheetForm" method="POST" action="{{ route('qma.support_timesheet.store') }}" class="p-6 space-y-4">
                @csrf

                <!-- Baris 1: Unit Support & Operator -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Pilih Unit Support <span class="text-rose-500">*</span>
                        </label>
                        <select name="vehicle_id" 
                                id="addVehicleSelect" 
                                required 
                                onchange="onVehicleSelected(this, 'add')"
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                            <option value="">-- Pilih Unit Support --</option>
                            @foreach($unitSupports as $u)
                                <option value="{{ $u->id }}" 
                                        data-km="{{ $u->current_km ?? 0 }}"
                                        data-brand="{{ $u->brand_type }}"
                                        data-code="{{ $u->code }}">
                                    {{ $u->code }} - {{ $u->brand_type }} (Terakhir: {{ number_format($u->current_km, 1) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Pengemudi / Operator <span class="text-rose-500">*</span>
                        </label>
                        <select name="operator_id" 
                                id="addOperatorSelect" 
                                required 
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] focus:ring-2 focus:ring-[#1b5e20]/10 transition">
                            <option value="">-- Pilih Operator --</option>
                            @foreach($operators as $op)
                                <option value="{{ $op->id }}">
                                    {{ $op->name }} ({{ $op->nik }}) {{ $op->is_operator ? '★ Operator' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Baris 2: Tanggal, Shift, Satuan Meteran -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Tanggal <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" 
                               name="date" 
                               value="{{ date('Y-m-d') }}" 
                               required 
                               class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Shift Kerja <span class="text-rose-500">*</span>
                        </label>
                        <select name="shift" required class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20]">
                            @foreach($shifts as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Satuan Meteran <span class="text-rose-500">*</span>
                        </label>
                        <select name="meter_type" id="addMeterType" required class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20]">
                            <option value="HM">HM (Hour Meter)</option>
                            <option value="KM">KM (Odometer)</option>
                        </select>
                    </div>
                </div>

                <!-- Baris 3: Start Meter, End Meter, Total Jam Kerja (Auto Calc) -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/90 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-1">
                                Meter Awal (Start) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   name="start_meter" 
                                   id="addStartMeter" 
                                   required 
                                   placeholder="0.00"
                                   oninput="calculateOperatingHours('add')"
                                   class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono focus:outline-none focus:border-[#1b5e20]">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-1">
                                Meter Akhir (End) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   name="end_meter" 
                                   id="addEndMeter" 
                                   required 
                                   placeholder="0.00"
                                   oninput="calculateOperatingHours('add')"
                                   class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono focus:outline-none focus:border-[#1b5e20]">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-emerald-800 mb-1">
                                Total Jam Operasi (Auto)
                            </label>
                            <div class="flex items-center space-x-2">
                                <input type="text" 
                                       id="addOperatingHoursDisplay" 
                                       readonly 
                                       value="0.00"
                                       class="w-full text-xs px-3.5 py-2 rounded-xl border border-emerald-300 bg-emerald-50 text-emerald-800 font-bold font-mono">
                                <span id="addMeterUnitLabel" class="text-xs font-bold text-emerald-700">HM</span>
                            </div>
                        </div>
                    </div>

                    <!-- Input Opsional Jam Standby & Breakdown -->
                    <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-200/60">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                                Jam Standby <span class="text-slate-400 font-normal">(Jam Tunggu/Hujan)</span>
                            </label>
                            <input type="number" 
                                   step="0.1" 
                                   name="standby_hours" 
                                   value="0" 
                                   placeholder="0.0"
                                   class="w-full text-xs px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                                Jam Breakdown <span class="text-slate-400 font-normal">(Jam Kerusakan Alat)</span>
                            </label>
                            <input type="number" 
                                   step="0.1" 
                                   name="breakdown_hours" 
                                   value="0" 
                                   placeholder="0.0"
                                   class="w-full text-xs px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-mono">
                        </div>
                    </div>
                </div>

                <!-- Baris 4: Lokasi Kerja & Aktivitas Pekerjaan -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Lokasi Kerja <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="location" 
                               id="addLocationInput"
                               list="locationPresetList"
                               required 
                               placeholder="Contoh: Main Dam, Adit 1, Powerhouse..." 
                               class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Kategori Pekerjaan <span class="text-rose-500">*</span>
                        </label>
                        <select name="activity_type" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] transition">
                            <option value="">-- Pilih Aktivitas --</option>
                            @foreach($activityTypes as $act)
                                <option value="{{ $act }}">{{ $act }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Baris 5: Solar & Status Akhir Unit -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Konsumsi / Pengisian Solar <span class="text-slate-400 font-normal">(Liter, Opsional)</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-gas-pump text-xs"></i>
                            </span>
                            <input type="number" 
                                   step="0.01" 
                                   name="fuel_liters" 
                                   placeholder="Contoh: 150" 
                                   class="w-full text-xs pl-8 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20]">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Status Akhir Unit <span class="text-rose-500">*</span>
                        </label>
                        <select name="status" required class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20]">
                            <option value="Operation">Operation (Siap Jalan)</option>
                            <option value="Standby">Standby (Tersedia / Siaga)</option>
                            <option value="Breakdown">Breakdown (Kendala / Rusak)</option>
                            <option value="Maintenance">Maintenance (Servis Rutin)</option>
                        </select>
                    </div>
                </div>

                <!-- Baris 6: Rincian Tugas & Catatan -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">
                        Rincian Tugas / Catatan Pekerjaan <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <textarea name="task_description" 
                              rows="2" 
                              placeholder="Keterangan rincian galian, muatan ritase, kendala lapangan, atau catatan foreman..." 
                              class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:border-[#1b5e20] transition resize-none"></textarea>
                </div>

                <!-- Tombol Submit Modal -->
                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeAddModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-800 hover:bg-slate-100 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="bg-[#1b5e20] hover:bg-[#144919] text-white px-5 py-2 rounded-xl text-xs font-bold transition shadow-xs flex items-center space-x-1.5 cursor-pointer">
                        <i class="fa-solid fa-floppy-disk text-xs"></i>
                        <span>Simpan Timesheet</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ========================================== -->
<!-- 2. MODAL EDIT TIMESHEET UNIT SUPPORT      -->
<!-- ========================================== -->
<div id="editTimesheetModal" class="hidden fixed inset-0 z-50 overflow-y-auto" style="display: none;">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeEditModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/70">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100/80 text-amber-700 flex items-center justify-center text-sm">
                        <i class="fa-regular fa-pen-to-square"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Edit Timesheet Unit Support</h3>
                        <p id="editTimesheetSubtitle" class="text-[11px] text-slate-500">Koreksi data jam kerja atau aktivitas operasional.</p>
                    </div>
                </div>
                <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 text-sm p-1 rounded-lg hover:bg-slate-100 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Form Edit Timesheet -->
            <form id="editTimesheetForm" method="POST" action="" class="p-6 space-y-4">
                @csrf
                @method('PUT')

                <!-- Baris 1: Unit Support & Operator -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Pilih Unit Support <span class="text-rose-500">*</span>
                        </label>
                        <select name="vehicle_id" 
                                id="editVehicleSelect" 
                                required 
                                onchange="onVehicleSelected(this, 'edit')"
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] transition">
                            @foreach($unitSupports as $u)
                                <option value="{{ $u->id }}" 
                                        data-km="{{ $u->current_km ?? 0 }}"
                                        data-brand="{{ $u->brand_type }}"
                                        data-code="{{ $u->code }}">
                                    {{ $u->code }} - {{ $u->brand_type }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Pengemudi / Operator <span class="text-rose-500">*</span>
                        </label>
                        <select name="operator_id" 
                                id="editOperatorSelect" 
                                required 
                                class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] transition">
                            @foreach($operators as $op)
                                <option value="{{ $op->id }}">
                                    {{ $op->name }} ({{ $op->nik }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Baris 2: Tanggal, Shift, Satuan Meteran -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Tanggal <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" 
                               name="date" 
                               id="editDate"
                               required 
                               class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Shift Kerja <span class="text-rose-500">*</span>
                        </label>
                        <select name="shift" id="editShift" required class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20]">
                            @foreach($shifts as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Satuan Meteran <span class="text-rose-500">*</span>
                        </label>
                        <select name="meter_type" id="editMeterType" required class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20]">
                            <option value="HM">HM (Hour Meter)</option>
                            <option value="KM">KM (Odometer)</option>
                        </select>
                    </div>
                </div>

                <!-- Baris 3: Start Meter, End Meter, Total Jam Kerja -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/90 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-center">
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-1">
                                Meter Awal (Start) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   name="start_meter" 
                                   id="editStartMeter" 
                                   required 
                                   oninput="calculateOperatingHours('edit')"
                                   class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono focus:outline-none focus:border-[#1b5e20]">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-1">
                                Meter Akhir (End) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" 
                                   step="0.01" 
                                   name="end_meter" 
                                   id="editEndMeter" 
                                   required 
                                   oninput="calculateOperatingHours('edit')"
                                   class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 font-mono focus:outline-none focus:border-[#1b5e20]">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-emerald-800 mb-1">
                                Total Jam Operasi
                            </label>
                            <div class="flex items-center space-x-2">
                                <input type="text" 
                                       id="editOperatingHoursDisplay" 
                                       readonly 
                                       class="w-full text-xs px-3.5 py-2 rounded-xl border border-emerald-300 bg-emerald-50 text-emerald-800 font-bold font-mono">
                                <span id="editMeterUnitLabel" class="text-xs font-bold text-emerald-700">HM</span>
                            </div>
                        </div>
                    </div>

                    <!-- Jam Standby & Breakdown -->
                    <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-200/60">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                                Jam Standby (Jam)
                            </label>
                            <input type="number" 
                                   step="0.1" 
                                   name="standby_hours" 
                                   id="editStandbyHours"
                                   class="w-full text-xs px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                                Jam Breakdown (Jam)
                            </label>
                            <input type="number" 
                                   step="0.1" 
                                   name="breakdown_hours" 
                                   id="editBreakdownHours"
                                   class="w-full text-xs px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 font-mono">
                        </div>
                    </div>
                </div>

                <!-- Baris 4: Lokasi Kerja & Aktivitas -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Lokasi Kerja <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="location" 
                               id="editLocationInput"
                               list="locationPresetList"
                               required 
                               class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] transition">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Kategori Pekerjaan <span class="text-rose-500">*</span>
                        </label>
                        <select name="activity_type" id="editActivityType" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] transition">
                            @foreach($activityTypes as $act)
                                <option value="{{ $act }}">{{ $act }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Baris 5: Solar & Status -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Konsumsi / Pengisian Solar (Liter)
                        </label>
                        <input type="number" 
                               step="0.01" 
                               name="fuel_liters" 
                               id="editFuelLiters"
                               class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20]">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">
                            Status Akhir Unit <span class="text-rose-500">*</span>
                        </label>
                        <select name="status" id="editStatus" required class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20]">
                            <option value="Operation">Operation (Siap Jalan)</option>
                            <option value="Standby">Standby (Tersedia / Siaga)</option>
                            <option value="Breakdown">Breakdown (Kendala / Rusak)</option>
                            <option value="Maintenance">Maintenance (Servis Rutin)</option>
                        </select>
                    </div>
                </div>

                <!-- Baris 6: Deskripsi & Catatan -->
                <div>
                    <label class="block text-xs font-bold text-slate-800 mb-1.5">
                        Rincian Tugas / Catatan
                    </label>
                    <textarea name="task_description" 
                              id="editTaskDescription"
                              rows="2" 
                              class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-slate-800 focus:outline-none focus:border-[#1b5e20] transition resize-none"></textarea>
                </div>

                <!-- Tombol Submit Edit -->
                <div class="flex items-center justify-end space-x-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-800 hover:bg-slate-100 transition cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white px-5 py-2 rounded-xl text-xs font-bold transition shadow-xs flex items-center space-x-1.5 cursor-pointer">
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

<!-- Datalist Rekomendasi Lokasi Global -->
<datalist id="locationPresetList">
    @foreach($locationSuggestions as $loc)
        <option value="{{ $loc }}">
    @endforeach
</datalist>

@endsection

@section('scripts')
<script>
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
        modal.classList.remove('hidden');
        modal.style.display = 'block';
    }
    function closeAddModal() {
        const modal = document.getElementById('addTimesheetModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
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

        document.getElementById('editTimesheetSubtitle').textContent = `No: ${ts.timesheet_no} (${ts.vehicle?.code || '-'})`;
        document.getElementById('editVehicleSelect').value = ts.vehicle_id;
        document.getElementById('editOperatorSelect').value = ts.operator_id;
        
        // Format tanggal YYYY-MM-DD
        const d = ts.date ? ts.date.split('T')[0] : '';
        document.getElementById('editDate').value = d;
        document.getElementById('editShift').value = ts.shift;
        document.getElementById('editMeterType').value = ts.meter_type || 'HM';

        document.getElementById('editStartMeter').value = ts.start_meter;
        document.getElementById('editEndMeter').value = ts.end_meter;
        document.getElementById('editStandbyHours').value = ts.standby_hours || 0;
        document.getElementById('editBreakdownHours').value = ts.breakdown_hours || 0;

        document.getElementById('editLocationInput').value = ts.location;
        document.getElementById('editActivityType').value = ts.activity_type;
        document.getElementById('editFuelLiters').value = ts.fuel_liters || '';
        document.getElementById('editStatus').value = ts.status || 'Operation';
        document.getElementById('editTaskDescription').value = ts.task_description || ts.notes || '';

        calculateOperatingHours('edit');

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

    // 5. Handler saat Unit Dipilih (Auto-isi meter awal)
    function onVehicleSelected(selectEl, prefix) {
        const selectedOpt = selectEl.options[selectEl.selectedIndex];
        if (!selectedOpt || !selectedOpt.value) return;

        const lastKm = parseFloat(selectedOpt.getAttribute('data-km')) || 0;
        const startMeterInput = document.getElementById(`${prefix}StartMeter`);
        if (startMeterInput && (!startMeterInput.value || parseFloat(startMeterInput.value) === 0)) {
            startMeterInput.value = lastKm.toFixed(2);
        }

        const code = (selectedOpt.getAttribute('data-code') || '').toUpperCase();
        const meterTypeSelect = document.getElementById(`${prefix}MeterType`);
        if (meterTypeSelect) {
            // Jika kode diawali DT (Dump Truck), defaultkan KM, selain itu HM
            if (code.startsWith('DT')) {
                meterTypeSelect.value = 'KM';
            } else {
                meterTypeSelect.value = 'HM';
            }
            updateMeterUnitLabels(prefix);
        }

        calculateOperatingHours(prefix);
    }

    // 6. Hitung Jam Operasi Otomatis (End - Start)
    function calculateOperatingHours(prefix) {
        const start = parseFloat(document.getElementById(`${prefix}StartMeter`).value) || 0;
        const end = parseFloat(document.getElementById(`${prefix}EndMeter`).value) || 0;
        const diff = Math.max(0, end - start);

        const display = document.getElementById(`${prefix}OperatingHoursDisplay`);
        if (display) {
            display.value = diff.toFixed(2);
        }
        updateMeterUnitLabels(prefix);
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
</script>
@endsection
