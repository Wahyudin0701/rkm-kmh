@extends('layouts.app')

@section('title', 'Log Riwayat Verifikasi Gerbang - Admin Private Guard | PT Kerinci Merangin Hidro')

@section('content')
<div class="space-y-6">

    <!-- Header Judul & Tombol Aksi Cepat -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20] block mb-1">
                MANAJEMEN SISTEM
            </span>
            <div class="flex items-center space-x-3">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Log Riwayat Verifikasi Gerbang
                </h2>
                <!-- Info Icon Badge -->
                <span class="w-6 h-6 rounded-full bg-slate-200/90 text-slate-500 text-xs inline-flex items-center justify-center font-bold shadow-sm" title="Rekapitulasi lengkap riwayat pemeriksaan fisik kendaraan dan validasi pos gerbang oleh satpam">
                    <i class="fa-solid fa-info"></i>
                </span>
            </div>
        </div>

        <div class="flex items-center space-x-3 w-full sm:w-auto">
            <a href="{{ route('pg.guards.index') }}" class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1b5e20] hover:bg-[#144919] shadow-sm transition group">
                <i class="fa-solid fa-user-shield mr-2 group-hover:scale-110 transition-transform"></i>
                <span>Kelola Data Satpam</span>
            </a>
        </div>
    </div>

    <!-- 4 Kartu Statistik Ringkas Audit Satpam -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Kartu 1: Total Disetujui (Approved) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex items-center space-x-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-[#1b5e20] flex items-center justify-center flex-shrink-0 text-lg font-bold">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Disetujui</p>
                <h3 class="text-xl font-extrabold text-slate-800">{{ number_format($totalApproved, 0, ',', '.') }}</h3>
            </div>
        </div>

        <!-- Kartu 2: Total Ditolak (Rejected) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex items-center space-x-3.5">
            <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0 text-lg font-bold">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Ditolak</p>
                <h3 class="text-xl font-extrabold text-slate-800">{{ number_format($totalRejected, 0, ',', '.') }}</h3>
            </div>
        </div>

        <!-- Kartu 3: Total Antrean (Pending) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex items-center space-x-3.5">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0 text-lg font-bold">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Menunggu</p>
                <h3 class="text-xl font-extrabold text-slate-800">{{ number_format($totalPending, 0, ',', '.') }}</h3>
            </div>
        </div>

        <!-- Kartu 4: Pemeriksaan Hari Ini -->
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex items-center space-x-3.5">
            <div class="w-11 h-11 rounded-xl bg-[#06213f]/10 text-[#06213f] flex items-center justify-center flex-shrink-0 text-lg font-bold">
                <i class="fa-solid fa-calendar-day"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Hari Ini</p>
                <h3 class="text-xl font-extrabold text-slate-800">{{ number_format($todayInspections, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>

    <!-- Filter Bar Card Komprehensif -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <form method="GET" action="{{ route('pg.logs.index') }}" class="space-y-4">
            <!-- Header Filter & Tombol Reset -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2 text-slate-800 font-bold text-xs uppercase tracking-wider">
                    <i class="fa-solid fa-sliders text-[#1b5e20]"></i>
                    <span>Filter & Pencarian Log Audit</span>
                </div>
                @if(request()->hasAny(['search', 'team', 'security_guard_id', 'gate_id', 'direction', 'status', 'start_date', 'end_date']))
                    <a href="{{ route('pg.logs.index') }}" class="text-xs text-rose-600 hover:text-rose-700 font-semibold flex items-center space-x-1 transition">
                        <i class="fa-solid fa-rotate-left"></i>
                        <span>Reset Semua Filter</span>
                    </a>
                @endif
            </div>

            <!-- Baris Input Filter (Grid 12 Kolom) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3.5">
                <!-- Pencarian Kata Kunci -->
                <div class="lg:col-span-4">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-magnifying-glass text-slate-400"></i>
                        <span>Pencarian Cepat</span>
                    </label>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Cari TRX, Plat, Driver, Satpam, Catatan..." 
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>

                <!-- Filter Regu Satpam (Regu 1, 2, 3) -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-users text-slate-400"></i>
                        <span>Regu Satpam</span>
                    </label>
                    <select name="team" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Regu</option>
                        <option value="1" {{ request('team') == '1' ? 'selected' : '' }}>Regu 1</option>
                        <option value="2" {{ request('team') == '2' ? 'selected' : '' }}>Regu 2</option>
                        <option value="3" {{ request('team') == '3' ? 'selected' : '' }}>Regu 3</option>
                    </select>
                </div>

                <!-- Filter Petugas Satpam Spesifik -->
                <div class="lg:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-user-shield text-slate-400"></i>
                        <span>Petugas Satpam</span>
                    </label>
                    <select name="security_guard_id" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Petugas Satpam</option>
                        @foreach($guards as $guard)
                            <option value="{{ $guard->id }}" {{ request('security_guard_id') == $guard->id ? 'selected' : '' }}>
                                {{ $guard->badge_id }} - {{ $guard->name }} (Regu {{ $guard->team }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Pos Gerbang -->
                <div class="lg:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-torii-gate text-slate-400"></i>
                        <span>Pos Gerbang</span>
                    </label>
                    <select name="gate_id" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Gerbang</option>
                        @foreach($gates as $g)
                            <option value="{{ $g->id }}" {{ request('gate_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Arah Gerbang -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-arrows-left-right text-slate-400"></i>
                        <span>Arah Gerbang</span>
                    </label>
                    <select name="direction" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Arah</option>
                        <option value="in" {{ request('direction') === 'in' ? 'selected' : '' }}>Masuk (In)</option>
                        <option value="out" {{ request('direction') === 'out' ? 'selected' : '' }}>Keluar (Out)</option>
                    </select>
                </div>

                <!-- Filter Status Verifikasi -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-solid fa-circle-check text-slate-400"></i>
                        <span>Status</span>
                    </label>
                    <select name="status" class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                        <option value="">Semua Status</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Disetujui (Approved)</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Ditolak (Rejected)</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Menunggu (Pending)</option>
                    </select>
                </div>

                <!-- Rentang Mulai Tanggal -->
                <div class="lg:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-regular fa-calendar text-slate-400"></i>
                        <span>Mulai Tanggal</span>
                    </label>
                    <input type="date" 
                           name="start_date" 
                           value="{{ request('start_date') }}" 
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>

                <!-- Rentang Sampai Tanggal -->
                <div class="lg:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                        <i class="fa-regular fa-calendar-check text-slate-400"></i>
                        <span>Sampai Tanggal</span>
                    </label>
                    <input type="date" 
                           name="end_date" 
                           value="{{ request('end_date') }}" 
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/50 hover:bg-white focus:bg-white px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>

                <!-- Tombol Submit Terapkan Filter -->
                <div class="lg:col-span-2 flex items-end">
                    <button type="submit" class="w-full bg-[#1b5e20] hover:bg-[#144919] text-white py-2.5 px-4 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 shadow-sm transition">
                        <i class="fa-solid fa-filter text-xs"></i>
                        <span>Terapkan</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabel Lengkap Log Verifikasi Gerbang (Navy Header #06213f) -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs text-left border-collapse">
                <!-- Header Navy Solid #06213f -->
                <thead class="bg-[#06213f] text-white font-bold text-[11px] uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 text-center w-12">NO</th>
                        <th class="py-3.5 px-4">WAKTU VERIFIKASI</th>
                        <th class="py-3.5 px-4">NO. TRANSAKSI</th>
                        <th class="py-3.5 px-4">PETUGAS SATPAM</th>
                        <th class="py-3.5 px-4">POS GERBANG</th>
                        <th class="py-3.5 px-4">KENDARAAN</th>
                        <th class="py-3.5 px-4">DRIVER / PENGEMUDI</th>
                        <th class="py-3.5 px-4 text-center">ARAH</th>
                        <th class="py-3.5 px-4 text-right">ODOMETER</th>
                        <th class="py-3.5 px-4 text-center">STATUS & CATATAN</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($activities as $index => $item)
                        @php
                            $rowNum = $activities->firstItem() ? ($activities->firstItem() + $index) : ($index + 1);
                            $validatedTime = $item->validated_at ?? $item->recorded_at;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors {{ $loop->even ? 'bg-slate-50/30' : 'bg-white' }}">
                            <!-- No -->
                            <td class="py-3 px-4 text-center text-slate-400 font-bold">
                                {{ $rowNum }}
                            </td>

                            <!-- Waktu Verifikasi -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-900">
                                    {{ $validatedTime ? $validatedTime->format('d/m/Y') : '-' }}
                                </div>
                                <div class="text-[11px] text-slate-500 flex items-center gap-1 font-mono">
                                    <i class="fa-regular fa-clock text-[10px]"></i>
                                    {{ $validatedTime ? $validatedTime->format('H:i') . ' WIB' : '-' }}
                                </div>
                            </td>

                            <!-- No Transaksi -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-800 font-mono font-bold text-[11px] border border-slate-200">
                                    <i class="fa-solid fa-qrcode text-slate-400 text-[10px]"></i>
                                    {{ $item->transaction_no }}
                                </span>
                            </td>

                            <!-- Petugas Satpam Pemeriksa -->
                            <td class="py-3 px-4">
                                @if($item->securityGuard)
                                    <div class="flex items-center space-x-2">
                                        <div>
                                            <p class="font-bold text-slate-900 leading-tight">
                                                {{ $item->securityGuard->name }}
                                            </p>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="text-[10px] text-slate-500 font-mono">
                                                    {{ $item->securityGuard->badge_id }}
                                                </span>
                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-bold {{ $item->securityGuard->team == 1 ? 'bg-blue-100 text-blue-700' : ($item->securityGuard->team == 2 ? 'bg-amber-100 text-amber-700' : 'bg-purple-100 text-purple-700') }}">
                                                    Regu {{ $item->securityGuard->team }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">- Belum ada -</span>
                                @endif
                            </td>

                            <!-- Pos Gerbang -->
                            <td class="py-3 px-4 whitespace-nowrap font-bold text-slate-800">
                                <div class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-torii-gate text-slate-400 text-xs"></i>
                                    <span>{{ $item->gate ? $item->gate->name : '-' }}</span>
                                </div>
                            </td>

                            <!-- Kendaraan -->
                            <td class="py-3 px-4">
                                @if($item->vehicle)
                                    <div class="font-bold text-slate-900">
                                        {{ $item->vehicle->license_plate }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 flex items-center gap-1">
                                        <span class="font-bold text-emerald-700">{{ $item->vehicle->code }}</span>
                                        <span>•</span>
                                        <span>{{ $item->vehicle->brand_type }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- Driver / Pengemudi -->
                            <td class="py-3 px-4">
                                @if($item->driver)
                                    <div class="font-bold text-slate-900">
                                        {{ $item->driver->name }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-mono">
                                        {{ $item->driver->nik }}
                                    </div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>

                            <!-- Arah Gerbang -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($item->direction === 'in')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-arrow-down-left text-[10px]"></i>
                                        <span>Masuk</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fa-solid fa-arrow-up-right text-[10px]"></i>
                                        <span>Keluar</span>
                                    </span>
                                @endif
                            </td>

                            <!-- Odometer -->
                            <td class="py-3 px-4 text-right whitespace-nowrap font-mono">
                                <div class="font-bold text-slate-900">
                                    {{ number_format($item->current_km, 0, ',', '.') }} KM
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    Awal: {{ number_format($item->previous_km, 0, ',', '.') }} KM
                                    @if($item->distance > 0)
                                        <span class="text-emerald-600 font-bold">(+{{ number_format($item->distance, 0, ',', '.') }})</span>
                                    @endif
                                </div>
                            </td>

                            <!-- Status & Catatan -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($item->validation_status === 'approved')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                        <i class="fa-solid fa-check text-[10px]"></i>
                                        <span>Disetujui</span>
                                    </span>
                                @elseif($item->validation_status === 'rejected')
                                    <div>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800" title="{{ $item->rejection_note }}">
                                            <i class="fa-solid fa-xmark text-[10px]"></i>
                                            <span>Ditolak</span>
                                        </span>
                                        @if($item->rejection_note)
                                            <p class="text-[10px] text-rose-600 max-w-xs truncate mx-auto mt-0.5" title="{{ $item->rejection_note }}">
                                                {{ $item->rejection_note }}
                                            </p>
                                        @endif
                                    </div>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                                        <i class="fa-regular fa-clock text-[10px]"></i>
                                        <span>Menunggu</span>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-lg">
                                        <i class="fa-solid fa-clipboard-question"></i>
                                    </div>
                                    <p class="text-sm font-bold text-slate-600">Belum ada catatan log riwayat verifikasi gerbang</p>
                                    <p class="text-xs text-slate-400">Aktivitas kendaraan yang diverifikasi oleh petugas Satpam akan tercatat otomatis di sini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        @if($activities->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $activities->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
