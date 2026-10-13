@extends('layouts.app')

@section('title', 'Verifikasi Pos Gerbang - Private Guard (PG) | PT Kerinci Merangin Hidro')

@section('content')
<div class="space-y-6">

    <!-- Header Judul & Tindakan Cepat -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20] block mb-1">
                OPERASIONAL POS KEAMANAN
            </span>
            <div class="flex items-center space-x-3">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                    Verifikasi Pos Gerbang
                </h2>
                <span class="w-6 h-6 rounded-full bg-slate-200/90 text-slate-500 text-xs inline-flex items-center justify-center font-bold shadow-sm" title="Pemeriksaan fisik armada dan validasi angka odometer sebelum portal dibuka">
                    <i class="fa-solid fa-info text-[10px]"></i>
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Petugas Satpam yang sedang piket wajib memverifikasi kondisi fisik kendaraan dan angka odometer riil sebelum membuka portal gerbang.
            </p>
        </div>

        <!-- Tombol Aksi Cepat: Segarkan Antrean & Status Auto-Refresh -->
        <div class="flex flex-wrap items-center gap-2.5">
            <button onclick="window.location.reload()" 
                    class="inline-flex items-center space-x-2 px-3.5 py-2.5 bg-white border border-slate-200 hover:border-slate-300 text-slate-700 hover:text-slate-900 rounded-xl text-xs font-bold shadow-xs hover:bg-slate-50 transition cursor-pointer"
                    title="Segarkan data antrean kendaraan">
                <i class="fa-solid fa-arrows-rotate text-[#1b5e20] transition-transform duration-300 active:rotate-180"></i>
                <span>Segarkan Antrean</span>
            </button>

            <!-- Indikator Auto Refresh -->
            <div class="inline-flex items-center space-x-2 px-3.5 py-2 bg-emerald-50 border border-emerald-200/80 rounded-xl text-xs font-semibold text-emerald-800 shadow-xs">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span>Auto-refresh: <strong id="refreshCountdown" class="font-mono">30s</strong></span>
            </div>
        </div>
    </div>

    <!-- 3 Kartu Ringkasan Operasional (Gaya Pastel Sederhana & Bersih) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- 1. Antrean Menunggu -->
        <div>
            <div class="bg-[#fef9e8] border border-[#feefc7] rounded-3xl h-24 sm:h-28 flex items-center justify-center shadow-xs">
                <span class="text-3xl sm:text-4xl font-black text-[#d97706]">{{ $pendingActivities->count() }}</span>
            </div>
            <p class="mt-2 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">ANTREAN MENUNGGU</p>
        </div>

        <!-- 2. Divalidasi Hari Ini -->
        <div>
            <div class="bg-[#eef7ee] border border-[#d6ebd7] rounded-3xl h-24 sm:h-28 flex items-center justify-center shadow-xs">
                <span class="text-3xl sm:text-4xl font-black text-[#2e7d32]">{{ $todayApprovedCount }}</span>
            </div>
            <p class="mt-2 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">DIVALIDASI HARI INI</p>
        </div>

        <!-- 3. Pos Jaga & Regu Piket -->
        <div>
            <div class="bg-[#edf5fd] border border-[#d2e4fb] rounded-3xl h-24 sm:h-28 flex flex-col items-center justify-center px-3 text-center shadow-xs">
                <span class="text-sm sm:text-base font-black text-[#1976d2] truncate max-w-full">
                    {{ $gate ? $gate->name : 'Pos Gerbang' }}
                </span>
                <span class="text-[11px] font-bold text-slate-600 mt-0.5">
                    Regu {{ $currentGuard->team ?? '1' }}
                </span>
            </div>
            <p class="mt-2 text-center text-[11px] font-bold text-slate-500 uppercase tracking-wider">POS JAGA AKTIF</p>
        </div>
    </div>

    <!-- Informasi Pos Jaga & Petugas Piket (Clean White Card) -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center space-x-4">
            <!-- Avatar Bulat Hijau Khas KMH -->
            <div class="w-12 h-12 rounded-2xl bg-[#1b5e20] text-white font-black text-lg flex items-center justify-center shadow-sm flex-shrink-0">
                {{ strtoupper(substr($currentGuard->name ?? auth()->user()->name, 0, 1)) }}
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                        Pos Aktif: {{ $gate ? $gate->name : 'Gerbang' }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                        Regu {{ $currentGuard->team ?? '1' }}
                    </span>
                </div>
                <h3 class="text-base font-extrabold text-slate-800 tracking-tight mt-1">
                    {{ $currentGuard ? $currentGuard->name : auth()->user()->name }}
                    <span class="text-xs font-mono font-medium text-slate-500 ml-1">
                        (ID: {{ $currentGuard->badge_id ?? auth()->user()->username }})
                    </span>
                </h3>
            </div>
        </div>

        <!-- Jam Digital Real-time Pos Jaga -->
        <div class="bg-slate-50 border border-slate-200/80 rounded-xl px-4 py-2 text-left sm:text-right w-full sm:w-auto">
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Waktu Pos Jaga</span>
            <span id="posDigitalClock" class="text-xs font-mono font-black text-slate-800">
                --:--:-- WIB
            </span>
        </div>
    </div>

    <!-- Antrean Validasi Kendaraan -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Header Card Antrean & Pencarian Cepat -->
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white">
            <div class="flex items-center space-x-2.5">
                <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 tracking-tight uppercase">
                    Kendaraan Menunggu Verifikasi Fisik
                </h3>
                <span class="bg-amber-100 text-amber-800 text-xs font-extrabold px-2.5 py-0.5 rounded-full font-mono">
                    {{ $pendingActivities->count() }}
                </span>
            </div>

            <!-- Input Filter Cepat Client-Side -->
            @if($pendingActivities->count() > 0)
                <div class="w-full sm:w-72">
                    <input type="text" 
                           id="searchQueueInput" 
                           placeholder="Cari kode unit, plat, driver..." 
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white px-3.5 py-2 text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                </div>
            @endif
        </div>

        <!-- List Antrean Kendaraan -->
        <div id="queueListContainer" class="divide-y divide-slate-100">
            @forelse($pendingActivities as $act)
                <div class="queue-card p-5 hover:bg-slate-50/70 transition flex flex-col lg:flex-row justify-between items-start lg:items-center gap-5"
                     data-search="{{ strtolower($act->vehicle->code . ' ' . $act->vehicle->license_plate . ' ' . $act->driver->name . ' ' . $act->vehicle->brand_type . ' ' . $act->purpose) }}">
                    
                    <div class="space-y-3 flex-1 w-full">
                        <!-- Baris 1: Badge Kode Unit, Plat Nomor, Arah, & Waktu Input -->
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-black font-mono bg-slate-900 text-white px-2.5 py-1 rounded-lg shadow-xs">
                                {{ $act->vehicle->code }}
                            </span>
                            <span class="text-xs font-bold text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded font-mono">
                                {{ $act->vehicle->license_plate ?? 'Tanpa Plat' }}
                            </span>
                            @if($act->direction === 'in')
                                <span class="text-xs font-bold bg-emerald-50 text-emerald-700 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                    <i class="fa-solid fa-arrow-right-to-bracket mr-1 text-emerald-600"></i> MASUK (In)
                                </span>
                            @else
                                <span class="text-xs font-bold bg-blue-50 text-blue-700 px-2.5 py-0.5 rounded-full border border-blue-200">
                                    <i class="fa-solid fa-arrow-right-from-bracket mr-1 text-blue-600"></i> KELUAR (Out)
                                </span>
                            @endif
                            <span class="text-xs text-slate-400 font-mono">
                                Diinput: {{ $act->recorded_at->format('H:i:s d/m/Y') }}
                            </span>
                        </div>

                        <!-- Baris 2: Informasi Detail Unit, Driver, dan KM Odometer -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs text-slate-600 pt-1">
                            <div>
                                <span class="text-slate-400 block text-[11px]">Unit:</span>
                                <strong class="text-slate-800">{{ $act->vehicle->brand_type }}</strong>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Pengemudi:</span>
                                <strong class="text-slate-800">{{ $act->driver->name }}</strong> 
                                <span class="text-slate-500">({{ $act->driver->division ?? '-' }})</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">KM Odometer Input:</span>
                                <strong class="text-[#1b5e20] text-sm font-mono font-bold">{{ number_format($act->current_km, 0, ',', '.') }} KM</strong>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[11px]">Jarak Sesi Ini:</span>
                                <strong class="text-slate-800 font-mono">+{{ number_format($act->distance, 0, ',', '.') }} KM</strong>
                            </div>
                        </div>

                        <!-- Baris 3: Keperluan Kerja -->
                        <div class="text-xs bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-slate-700">
                            <span class="font-bold text-slate-800">Keperluan Kerja:</span> {{ $act->purpose }}
                        </div>
                    </div>

                    <!-- Tombol Aksi Verifikasi Satpam -->
                    <div class="flex items-center space-x-2 self-end lg:self-center flex-shrink-0">
                        <button onclick="openVerificationModal({{ $act->id }}, '{{ $act->vehicle->code }}', '{{ $act->current_km }}', '{{ addslashes($act->driver->name) }}', '{{ $act->vehicle->license_plate ?? 'Tanpa Plat' }}', '{{ addslashes($act->vehicle->brand_type) }}', '{{ $act->direction }}')" 
                                class="bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-xs py-2.5 px-4 rounded-xl shadow-sm transition flex items-center space-x-2 cursor-pointer">
                            <i class="fa-solid fa-clipboard-check"></i>
                            <span>Verifikasi Satpam</span>
                        </button>
                    </div>
                </div>
            @empty
                <!-- Tampilan Bersih & Ramah Saat Antrean Kosong -->
                <div class="py-16 px-4 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-[#eef7ee] text-[#2e7d32] mx-auto flex items-center justify-center text-2xl mb-3 shadow-xs">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <h4 class="text-sm font-extrabold text-slate-800">
                        Tidak Ada Kendaraan Menunggu Verifikasi
                    </h4>
                    <p class="text-xs text-slate-500 max-w-md mx-auto mt-1">
                        Semua aktivitas kendaraan di pos gerbang ini telah diperiksa. Sistem siap memantau kedatangan armada berikutnya.
                    </p>
                    <button onclick="window.location.reload()" 
                            class="mt-4 inline-flex items-center space-x-2 px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer">
                        <i class="fa-solid fa-arrows-rotate text-[#1b5e20]"></i>
                        <span>Cek Antrean Sekarang</span>
                    </button>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Modal Dialog Verifikasi Satpam (Friendly, Simple & Professional) -->
<div id="verificationModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in duration-150">
        <!-- Header Modal Bersih -->
        <div class="bg-white px-5 py-4 border-b border-slate-100 flex justify-between items-center">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-[#1b5e20] flex items-center justify-center text-sm">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">
                        Pemeriksaan Fisik Satpam
                    </h3>
                    <p class="text-[11px] text-slate-500">
                        Pos Gerbang: {{ $gate ? $gate->name : 'Pos Jaga' }}
                    </p>
                </div>
            </div>
            <button onclick="closeVerificationModal()" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="p-5 space-y-4">
            <!-- Box Ringkasan Unit & Driver -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs space-y-2">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200/60">
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Unit & Plat Nomor:</span>
                        <div class="flex items-center space-x-2 mt-0.5">
                            <span id="modalUnitCode" class="text-slate-900 font-mono font-black text-sm"></span>
                            <span id="modalPlate" class="text-amber-800 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded text-[11px] font-mono font-bold"></span>
                            <span id="modalDirectionBadge"></span>
                        </div>
                        <div id="modalBrandType" class="text-slate-500 text-[11px] mt-0.5"></div>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Pengemudi:</span>
                        <strong id="modalDriverName" class="text-slate-800 text-xs block mt-0.5"></strong>
                    </div>
                </div>

                <!-- Highlight Angka Odometer KM -->
                <div class="pt-1 flex items-center justify-between bg-white p-3 rounded-lg border border-slate-200/80">
                    <div>
                        <span class="text-slate-500 text-[11px] font-semibold block">Angka Odometer Input Driver:</span>
                        <span class="text-[10px] text-slate-400">Cocokkan dengan speedometer fisik di dashboard kendaraan</span>
                    </div>
                    <div class="text-right">
                        <span id="modalKmInput" class="text-emerald-700 font-mono text-lg font-black block"></span>
                    </div>
                </div>
            </div>

            <!-- Petugas Pemeriksa -->
            @if($currentGuard)
                <div class="p-3 bg-emerald-50/70 rounded-xl border border-emerald-200/70 text-xs flex items-center justify-between">
                    <div>
                        <span class="text-slate-500 block text-[10px] font-semibold uppercase tracking-wider">Petugas Piket:</span>
                        <strong class="text-emerald-900 font-bold text-xs">{{ $currentGuard->name }}</strong>
                        <span class="text-slate-500 text-[11px] font-mono ml-1">
                            (ID: {{ $currentGuard->badge_id }} &bull; Regu {{ $currentGuard->team }})
                        </span>
                    </div>
                    <span class="text-emerald-600 text-xs font-bold flex items-center">
                        <i class="fa-solid fa-circle-check mr-1"></i> Terverifikasi
                    </span>
                </div>
            @endif

            <!-- Form Approve -->
            <form id="approveForm" method="POST" action="">
                @csrf
                
                @if(!$currentGuard)
                    <div class="mb-4">
                        <label for="security_guard_id" class="block text-xs font-bold text-slate-700 mb-1.5">
                            Pilih Nama Satpam Pemeriksa <span class="text-rose-500">*</span>
                        </label>
                        <select name="security_guard_id" id="security_guard_id" required class="w-full text-xs rounded-xl border-slate-200 border p-2.5 bg-slate-50 hover:bg-white focus:bg-white focus:ring-2 focus:ring-[#1b5e20]/20 focus:border-[#1b5e20] transition">
                            <option value="" disabled selected>-- Pilih Petugas Satpam --</option>
                            @foreach($guards as $guard)
                                <option value="{{ $guard->id }}">{{ $guard->name }} (Regu {{ $guard->team }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="pt-2 border-t border-slate-100">
                    <button type="submit" class="w-full bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-xs py-2.5 px-4 rounded-xl transition flex items-center justify-center space-x-2 shadow-sm cursor-pointer">
                        <i class="fa-solid fa-check"></i>
                        <span>Validasi (Kendaraan Lewat)</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let currentActivityId = null;
    let autoRefreshTimer = null;
    let countdownSeconds = 30;
    let isModalOpen = false;

    // Jam Digital Pos Gerbang
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const clockEl = document.getElementById('posDigitalClock');
        if (clockEl) {
            clockEl.textContent = `${hours}:${minutes}:${seconds} WIB`;
        }
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Auto Refresh Countdown (30 Detik)
    function startAutoRefresh() {
        const countdownEl = document.getElementById('refreshCountdown');
        autoRefreshTimer = setInterval(() => {
            if (!isModalOpen) {
                countdownSeconds--;
                if (countdownEl) countdownEl.textContent = `${countdownSeconds}s`;
                if (countdownSeconds <= 0) {
                    window.location.reload();
                }
            }
        }, 1000);
    }
    startAutoRefresh();

    // Filter Pencarian Cepat Client-Side
    const searchInput = document.getElementById('searchQueueInput');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.queue-card');
            cards.forEach(card => {
                const searchData = card.getAttribute('data-search') || '';
                if (searchData.includes(term)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // Modal Functions
    function openVerificationModal(activityId, unitCode, kmInput, driverName, plate = '', brandType = '', direction = 'in') {
        currentActivityId = activityId;
        isModalOpen = true;

        document.getElementById('modalUnitCode').textContent = unitCode;
        document.getElementById('modalKmInput').textContent = Number(kmInput).toLocaleString('id-ID') + ' KM';
        document.getElementById('modalDriverName').textContent = driverName;

        const plateEl = document.getElementById('modalPlate');
        if (plateEl) plateEl.textContent = plate;

        const brandEl = document.getElementById('modalBrandType');
        if (brandEl) brandEl.textContent = brandType;

        const dirEl = document.getElementById('modalDirectionBadge');
        if (dirEl) {
            if (direction === 'in') {
                dirEl.innerHTML = '<span class="text-xs font-bold bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full border border-emerald-200">MASUK</span>';
            } else {
                dirEl.innerHTML = '<span class="text-xs font-bold bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full border border-blue-200">KELUAR</span>';
            }
        }

        document.getElementById('approveForm').action = `/pg/gate-inspection/${activityId}/approve`;
        document.getElementById('verificationModal').classList.remove('hidden');
    }

    function closeVerificationModal() {
        document.getElementById('verificationModal').classList.add('hidden');
        isModalOpen = false;
    }

    // Tutup modal jika tombol Escape ditekan
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && isModalOpen) {
            closeVerificationModal();
        }
    });
</script>
@endsection
