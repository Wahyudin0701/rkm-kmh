@extends('layouts.app')

@section('title', 'Dashboard - Admin QMA | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Admin QMA (Persis Gaya Referensi IT KMH - Bebas Card Wrapper) -->
    <div>
        <div class="flex items-center space-x-1.5 mb-1">
            <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20]">
                SISTEM ADMINISTRATOR
            </span>
            <!-- Tombol Info Panduan Halaman Sejajar Kicker -->
            <button type="button" 
                    onclick="openGuideModal()" 
                    class="w-3.5 h-3.5 rounded-full bg-slate-200/90 hover:bg-slate-300 text-slate-500 hover:text-slate-800 text-[8px] inline-flex items-center justify-center font-bold transition shadow-2xs cursor-pointer focus:outline-none" 
                    title="Panduan Halaman Dashboard QMA">
                <i class="fa-solid fa-info"></i>
            </button>
        </div>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
            Selamat datang, {{ auth()->user()->name }}
        </h2>
        <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Berikut ringkasan operasional aset kendaraan dan pencatatan log aktivitas armada PT Kerinci Merangin Hidro secara keseluruhan.
        </p>
    </div>

    <!-- 4 Kartu Metrik Ringkasan (Persis Gaya Kotak Warna Pastel Referensi HSE) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Armada LV (Pastel Hijau) -->
        <div>
            <div class="bg-[#eef7ee] border border-[#d6ebd7] rounded-3xl h-28 flex items-center justify-center shadow-xs">
                <span class="text-4xl sm:text-5xl font-black text-[#2e7d32]">{{ $totalLv }}</span>
            </div>
            <p class="mt-2.5 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">ARMADA LIGHT VEHICLE</p>
        </div>

        <!-- 2. Heavy Equipment (Pastel Biru Muda) -->
        <div>
            <div class="bg-[#edf5fd] border border-[#d2e4fb] rounded-3xl h-28 flex items-center justify-center shadow-xs">
                <span class="text-4xl sm:text-5xl font-black text-[#1976d2]">{{ $totalSupport }}</span>
            </div>
            <p class="mt-2.5 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">HEAVY EQUIPMENT</p>
        </div>

        <!-- 3. Log Timesheet Hari Ini (Pastel Soft Blue) -->
        <div>
            <div class="bg-[#f0f4fe] border border-[#dae5fd] rounded-3xl h-28 flex items-center justify-center shadow-xs">
                <span class="text-4xl sm:text-5xl font-black text-[#2563eb]">{{ $todayActivitiesCount }}</span>
            </div>
            <p class="mt-2.5 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">TIMESHEET HARI INI</p>
        </div>

        <!-- 4. Inspeksi P2H Hari Ini (Pastel Amber / Yellow) -->
        <div>
            <div class="bg-[#fef9e8] border border-[#feefc7] rounded-3xl h-28 flex items-center justify-center shadow-xs">
                <span class="text-4xl sm:text-5xl font-black text-[#f59e0b]">{{ $todayP2hCount }}</span>
            </div>
            <p class="mt-2.5 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">INSPEKSI P2H HARI INI</p>
        </div>
    </div>

    <!-- Layout 2 Kolom Bawah: Log Pergerakan & Status Heavy Equipment (Persis Layout 2 Kolom HSE) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        
        <!-- Kolom Kiri: Log Aktivitas Kendaraan Terkini (Gerbang Pos) -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Log Aktivitas Kendaraan Terkini</h3>
                    <p class="text-[11px] text-slate-500">Catatan pergerakan armada Light Vehicle di pos gerbang</p>
                </div>
                @can('qma.logs.view')
                <a href="{{ route('qma.activity.log') }}" class="text-xs font-bold text-[#1b5e20] hover:underline flex items-center gap-1">
                    <span>Kelola Data</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
                @endcan
            </div>

            <div class="space-y-3.5">
                @forelse($recentActivities as $act)
                    <div class="flex items-start space-x-3.5 py-2.5 border-b border-slate-50 last:border-b-0 hover:bg-slate-50/60 rounded-xl px-2.5 transition">
                        <!-- Icon Arah Gerbang Pastel -->
                        @if($act->direction === 'in')
                            <div class="w-10 h-10 rounded-2xl bg-[#eef7ee] text-[#1b5e20] font-bold text-xs flex items-center justify-center flex-shrink-0 mt-0.5 border border-[#d6ebd7]" title="Kendaraan Masuk (IN)">
                                <i class="fa-solid fa-arrow-right-to-bracket text-sm"></i>
                            </div>
                        @else
                            <div class="w-10 h-10 rounded-2xl bg-[#edf5fd] text-[#1976d2] font-bold text-xs flex items-center justify-center flex-shrink-0 mt-0.5 border border-[#d2e4fb]" title="Kendaraan Keluar (OUT)">
                                <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-bold text-slate-900 leading-tight">
                                    {{ $act->vehicle->code ?? 'LV' }}
                                </span>
                                @if(!empty($act->vehicle->license_plate))
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ $act->vehicle->license_plate }}
                                    </span>
                                @endif
                                <span class="text-xs text-slate-700 font-semibold truncate max-w-[130px]">
                                    &bull; {{ $act->driver->name ?? 'Driver' }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-600 mt-0.5">
                                {{ $act->gate->name ?? 'Pos Gerbang' }} &bull; Keperluan: <span class="text-slate-800 font-medium">{{ Str::limit($act->purpose, 35) }}</span>
                            </p>
                            <p class="text-[10px] text-slate-500 mt-1 flex items-center gap-2 font-mono">
                                <span><i class="fa-regular fa-clock text-slate-400 mr-1"></i>{{ $act->recorded_at->locale('id')->translatedFormat('H:i') }} WIB ({{ $act->recorded_at->locale('id')->diffForHumans() }})</span>
                                <span>&bull;</span>
                                <span class="text-slate-700 font-bold">{{ number_format($act->current_km, 0, ',', '.') }} KM</span>
                                @if($act->distance > 0)
                                    <span class="text-[#1b5e20] font-semibold">(+{{ number_format($act->distance, 1, ',', '.') }} KM)</span>
                                @endif
                            </p>
                        </div>

                        <!-- Badge Status Validasi -->
                        <div class="flex-shrink-0">
                            @if($act->validation_status === 'approved')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <i class="fa-solid fa-circle-check text-[9px]"></i>
                                    <span>Approved</span>
                                </span>
                            @elseif($act->validation_status === 'rejected')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                    <i class="fa-solid fa-circle-xmark text-[9px]"></i>
                                    <span>Rejected</span>
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    <i class="fa-solid fa-hourglass-half text-[9px]"></i>
                                    <span>Pending</span>
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-6">Belum ada catatan aktivitas pergerakan kendaraan hari ini.</p>
                @endforelse
            </div>
        </div>

        <!-- Kolom Kanan: 2 Kartu Stacked (Monitoring Kelayakan P2H & Status Heavy Equipment) -->
        <div class="space-y-6">
            
            <!-- Kartu Atas: Monitoring Kelayakan P2H Terkini -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Monitoring Kelayakan P2H</h3>
                        <p class="text-[11px] text-slate-500">Prioritas kondisi lembar periksa pra-harian Heavy Equipment</p>
                    </div>
                    @can('qma.p2h.view')
                    <a href="{{ route('qma.p2h.index') }}" class="text-xs font-bold text-[#1b5e20] hover:underline flex items-center gap-1">
                        <span>Filter Data</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                    @endcan
                </div>

                <div class="space-y-3.5">
                    @forelse($recentP2hRecords as $p2h)
                        @php
                            $hasDefect = $p2h->summary_condition === 'has_defect' || $p2h->defect_count > 0;
                        @endphp
                        <div class="flex items-start space-x-3.5 py-2.5 border-b border-slate-50 last:border-b-0 hover:bg-slate-50/60 rounded-xl px-2.5 transition">
                            <!-- Icon Status P2H Berwarna -->
                            @if($hasDefect)
                                <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0 mt-0.5 border border-rose-100" title="Ada Kerusakan / Temuan">
                                    <i class="fa-solid fa-triangle-exclamation text-sm"></i>
                                </div>
                            @else
                                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0 mt-0.5 border border-emerald-100" title="Kondisi Prima / Baik">
                                    <i class="fa-solid fa-shield-halved text-sm"></i>
                                </div>
                            @endif

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-bold text-slate-900 leading-tight">
                                        {{ $p2h->vehicle->code ?? 'Unit' }}
                                    </span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        {{ $p2h->vehicle->unit_category ?? ($p2h->template->name ?? 'Heavy Equipment') }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-600 mt-0.5">
                                    Operator: <strong class="text-slate-800">{{ $p2h->employee->name ?? 'Operator' }}</strong> &bull; <span class="font-mono">{{ number_format($p2h->start_hm, 1, ',', '.') }} HM</span>
                                </p>

                                @if($hasDefect)
                                    <p class="text-[10px] font-bold text-rose-600 mt-1 flex items-center gap-1 font-mono">
                                        <i class="fa-solid fa-circle-xmark text-[9px]"></i>
                                        <span>Terdeteksi {{ $p2h->defect_count }} butir temuan abnormalitas</span>
                                    </p>
                                @else
                                    <p class="text-[10px] font-medium text-emerald-700 mt-1 flex items-center gap-1 font-mono">
                                        <i class="fa-solid fa-circle-check text-[9px] text-emerald-600"></i>
                                        <span>Seluruh butir periksa dalam kondisi normal</span>
                                    </p>
                                @endif
                            </div>

                            <!-- Badge Kanan -->
                            <div class="flex-shrink-0">
                                @if($hasDefect)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        Ada Temuan ({{ $p2h->defect_count }})
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Kondisi Baik
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-4">Belum ada data riwayat inspeksi P2H Heavy Equipment.</p>
                    @endforelse
                </div>
            </div>

            <!-- Kartu Bawah: Status Kesiapan Heavy Equipment (Format Rapi Persis HSE) -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Kesiapan Heavy Equipment</h3>
                        <p class="text-[11px] text-slate-500">Kesiapan operasional armada alat berat & unit bantu</p>
                    </div>
                    <a href="{{ route('qma.vehicles.support') }}" class="text-xs font-bold text-[#1b5e20] hover:underline flex items-center gap-1">
                        <span>Lihat Semua</span>
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>

                <!-- 3 Mini Metric Indicator -->
                <div class="grid grid-cols-3 gap-2 mb-4 pb-3 border-b border-slate-100 text-center">
                    <div class="p-2 rounded-xl bg-emerald-50/80 border border-emerald-200/60">
                        <span class="text-xs text-emerald-700 font-bold block">{{ $supportOperation }}</span>
                        <span class="text-[9px] font-bold text-emerald-600 uppercase">Operation</span>
                    </div>
                    <div class="p-2 rounded-xl bg-amber-50/80 border border-amber-200/60">
                        <span class="text-xs text-amber-700 font-bold block">{{ $supportStandby }}</span>
                        <span class="text-[9px] font-bold text-amber-600 uppercase">Standby</span>
                    </div>
                    <div class="p-2 rounded-xl bg-rose-50/80 border border-rose-200/60">
                        <span class="text-xs text-rose-700 font-bold block">{{ $supportBreakdown }}</span>
                        <span class="text-[9px] font-bold text-rose-600 uppercase">Breakdown</span>
                    </div>
                </div>

                <div class="space-y-3.5">
                    @forelse($recentUnits as $unit)
                        <div class="flex items-start space-x-3.5 py-2 border-b border-slate-50 last:border-b-0 hover:bg-slate-50/60 rounded-xl px-2.5 transition">
                            <!-- Icon Bulat Netral -->
                            <div class="w-10 h-10 rounded-2xl bg-slate-100 text-slate-600 font-bold text-xs flex items-center justify-center flex-shrink-0 mt-0.5 border border-slate-200">
                                {{ strtoupper(substr($unit->code, 0, 2)) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-bold text-slate-900 leading-tight">
                                        {{ $unit->code }}
                                    </span>
                                    <span class="text-[11px] text-slate-600 font-medium truncate max-w-[150px]">
                                        {{ $unit->brand_type }}
                                    </span>
                                </div>
                                <p class="text-[10px] text-slate-500 mt-0.5">
                                    Lokasi: {{ $unit->location ?? 'Area Proyek' }} &bull; <span class="font-mono font-bold text-slate-700">{{ number_format($unit->current_km, 1, ',', '.') }} HM</span>
                                </p>
                            </div>

                            <div class="flex-shrink-0">
                                @if($unit->status === 'Operation')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Operation
                                    </span>
                                @elseif($unit->status === 'Standby')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Standby
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        Breakdown
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 text-center py-4">Belum ada data Heavy Equipment terdaftar.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PANDUAN HALAMAN (DASHBOARD QMA)                              -->
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
                    <p class="text-xs text-slate-500">Dashboard Divisi QMA</p>
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
                Selamat datang di <strong>Dashboard QMA (Quality Management Asset)</strong>! Halaman ini adalah pusat navigasi aset armada dan tata kelola kendaraan di PT Kerinci Merangin Hidro. Di halaman ini Anda dapat:
            </p>

            <div class="space-y-3">
                <!-- Fitur 1 (Hijau) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-emerald-200/50">
                        <i class="fa-solid fa-car"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Master Data Light Vehicle (LV)</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Mengelola data kendaraan operasional ringan (LV), nomor lambung, kepemilikan, dan riwayat mutasi armada.
                        </p>
                    </div>
                </div>

                <!-- Fitur 2 (Biru) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-100/70 text-blue-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-blue-200/50">
                        <i class="fa-solid fa-tractor"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Master Data Heavy Equipment</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Memantau armada alat berat, spesifikasi teknis, serta masa berlaku izin kelaikan operasional SILO dan KIR.
                        </p>
                    </div>
                </div>

                <!-- Fitur 3 (Amber) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-amber-200/50">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Log Aktivitas Kendaraan & Monitoring Timesheet</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Melihat log pergerakan kendaraan riil yang terverifikasi di pos gerbang dan rekapitulasi analitik kilometer perjalanan.
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

@section('scripts')
<script>
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

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGuideModal();
        }
    });
</script>
@endsection
