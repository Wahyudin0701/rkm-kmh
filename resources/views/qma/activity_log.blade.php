@extends('layouts.app')

@section('title', 'Log Aktivitas Kendaraan QMA')

@section('content')
<div class="space-y-6">
    <!-- Header Judul & Total Catatan -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20] block mb-1">
                MANAJEMEN SISTEM
            </span>
            <div class="flex items-center space-x-3">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Log Aktivitas Kendaraan
                </h2>
                <span class="w-6 h-6 rounded-full bg-slate-200/90 text-slate-500 text-xs inline-flex items-center justify-center font-bold shadow-sm" title="Catatan pergerakan armada kendaraan dengan validasi pos gerbang">
                    <i class="fa-solid fa-info"></i>
                </span>
            </div>
        </div>

        <div class="flex items-center space-x-3 w-full sm:w-auto">
            <a href="{{ route('qma.activity.summary') }}" class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#06213f] hover:bg-[#0b315b] shadow-sm transition group">
                <i class="fa-solid fa-chart-pie mr-2 text-emerald-400 group-hover:scale-110 transition-transform"></i>
                <span>Summary Aktifitas</span>
            </a>
        </div>
    </div>

    <!-- Filter Card Modern & Rapi -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <form method="GET" action="{{ route('qma.activity.log') }}" class="space-y-4">
            <!-- Header Filter -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <span class="text-slate-800 font-bold text-xs uppercase tracking-wider">
                    Filter & Pencarian Log
                </span>
                @if(request()->hasAny(['search', 'start_date', 'end_date', 'gate_id', 'direction', 'status']))
                    <a href="{{ route('qma.activity.log') }}" class="text-xs text-rose-600 hover:text-rose-700 font-semibold transition">
                        <span>Reset Semua Filter</span>
                    </a>
                @endif
            </div>

            <!-- Baris Input Filter (Grid Responsif 12 Kolom) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5">
                <!-- Pencarian Cepat -->
                <div class="lg:col-span-4">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Pencarian Cepat
                    </label>
                    <div class="relative">
                        <input type="text" 
                               name="search" 
                               value="{{ request('search') }}" 
                               placeholder="Cari Kode LV, Driver, Plat, Keperluan..." 
                               class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                    </div>
                </div>

                <!-- Rentang Mulai Tanggal -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Mulai Tanggal
                    </label>
                    <input type="date" 
                           name="start_date" 
                           value="{{ request('start_date') }}" 
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>

                <!-- Rentang Sampai Tanggal -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Sampai Tanggal
                    </label>
                    <input type="date" 
                           name="end_date" 
                           value="{{ request('end_date') }}" 
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>

                <!-- Pos Gerbang -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Pos Gerbang
                    </label>
                    <select name="gate_id" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
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
                    <select name="direction" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Arah</option>
                        <option value="in" {{ request('direction') === 'in' ? 'selected' : '' }}>MASUK (In)</option>
                        <option value="out" {{ request('direction') === 'out' ? 'selected' : '' }}>KELUAR (Out)</option>
                    </select>
                </div>
            </div>

            <!-- Baris Aksi Bawah: Filter Status PG & Tombol -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-3 border-t border-slate-100">
                <!-- Status Validasi Pos Gerbang -->
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-600 whitespace-nowrap">
                        Status PG:
                    </span>
                    <select name="status" class="text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-1.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Status</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu PG</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>

                <!-- Tombol Submit & Reset -->
                <div class="flex items-center space-x-2 justify-end">
                    <a href="{{ route('qma.activity.log') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition">
                        <span>Reset</span>
                    </a>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-[#06213f] hover:bg-[#0b315b] shadow-sm transition">
                        <span>Filter</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabel Log Aktivitas (Header Navy Solid #06213f Tanpa Nomor Kolom) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">Tanggal</th>
                        <th class="py-3.5 px-4 text-center whitespace-nowrap">Kode LV</th>
                        <th class="py-3.5 px-4 whitespace-nowrap">Unit</th>
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
                <tbody class="divide-y divide-slate-100 text-slate-700 bg-white">
                    @forelse($activities as $act)
                        <tr class="hover:bg-slate-50/70 transition">
                            <!-- Tanggal & Waktu -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="font-bold text-slate-900">{{ $act->recorded_at->format('d/m/Y') }}</div>
                                <div class="text-[10px] font-mono text-slate-400 font-medium">{{ $act->recorded_at->format('H:i') }} WIB</div>
                            </td>

                            <!-- Kode LV -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap font-mono">
                                <span class="inline-block bg-emerald-50 text-emerald-800 font-bold px-2.5 py-1 rounded border border-emerald-200">
                                    {{ $act->vehicle->code }}
                                </span>
                            </td>

                            <!-- Unit & Plat -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900">{{ $act->vehicle->brand_type }}</div>
                                @if($act->vehicle->license_plate)
                                    <span class="inline-block text-[10px] font-mono font-bold text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 mt-0.5">
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
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-arrow-right-to-bracket mr-1.5 text-emerald-600"></i> MASUK
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        <i class="fa-solid fa-arrow-right-from-bracket mr-1.5 text-blue-600"></i> KELUAR
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
                        <tr>
                            <td colspan="11" class="py-12 px-4 text-center">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-2xl">
                                        <i class="fa-solid fa-route"></i>
                                    </div>
                                    <div class="text-sm font-bold text-slate-700">Belum Ada Data Log Aktivitas</div>
                                    <p class="text-xs text-slate-400 max-w-sm">
                                        Tidak ditemukan catatan aktivitas kendaraan yang sesuai dengan kriteria filter saat ini.
                                    </p>
                                    @if(request()->hasAny(['search', 'start_date', 'end_date', 'gate_id', 'direction', 'status']))
                                        <a href="{{ route('qma.activity.log') }}" class="mt-2 inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-[#1b5e20] bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition">
                                            <i class="fa-solid fa-rotate-left text-[11px]"></i>
                                            <span>Reset Filter</span>
                                        </a>
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
            <div class="p-4 bg-slate-50 border-t border-slate-100">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
