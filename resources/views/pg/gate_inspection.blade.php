@extends('layouts.app')

@section('title', 'Verifikasi Pos Gerbang - Private Guard (PG) | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-4 sm:space-y-5">

    <!-- ========================================================================= -->
    <!-- 1. STATUS BAR POS JAGA & JAM KERJA SATPAM (SEAMLESS / TANPA KARTU)        -->
    <!-- ========================================================================= -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-200/80">
        <!-- Pos Jaga Aktif, Regu, dan Nama Petugas (Tanpa Icon Perisai) -->
        <div class="min-w-0">
            <div class="flex items-center flex-wrap gap-2.5">
                <h1 class="text-lg sm:text-xl font-black text-slate-900 tracking-tight">
                    Pos {{ $gate ? $gate->name : 'Gerbang' }}
                </h1>
                <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-blue-50 text-blue-700 border border-blue-200">
                    Regu {{ $currentGuard->team ?? '1' }}
                </span>
            </div>
            <div class="text-sm sm:text-base text-slate-600 font-medium truncate mt-1 flex items-center flex-wrap gap-1.5">
                <span>Petugas: <strong class="text-slate-900 font-bold">{{ $currentGuard ? $currentGuard->name : auth()->user()->name }}</strong></span>
                @if($currentGuard && $currentGuard->badge_id)
                    <span class="text-slate-500 font-mono text-xs sm:text-sm font-semibold">({{ $currentGuard->badge_id }})</span>
                @endif
            </div>
        </div>

        <!-- Jam Digital WIB (Di Atas Kanan) -->
        <div class="flex items-center justify-end pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 flex-shrink-0">
            <!-- Jam Digital Realtime WIB -->
            <div class="inline-flex items-center space-x-1.5 bg-white border border-slate-200/80 px-3 py-1.5 rounded-xl font-mono text-xs font-bold text-slate-700 shadow-2xs">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span id="posDigitalClock">--:--:-- WIB</span>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. HEADER ANTREAN KENDARAAN (INFO MENUNGGU DI PALING KANAN)               -->
    <!-- ========================================================================= -->
    <div class="flex items-center justify-between gap-3 pt-2">
        <h2 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">
            Antrean Kendaraan di Portal
        </h2>

        <!-- Info Menunggu / Portal Bebas di Paling Kanan -->
        <div id="queueBadgeContainer">
            @if($pendingActivities->count() > 0)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-100 text-amber-900 border border-amber-300 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                    <span>{{ $pendingActivities->count() }} Menunggu</span>
                </span>
            @else
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                    <i class="fa-solid fa-check text-emerald-600 text-[10px]"></i>
                    <span>Portal Bebas</span>
                </span>
            @endif
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. DAFTAR KARTU KENDARAAN (DIOPTIMASI UNTUK PEMERIKSAAN CEPAT LAPANGAN)    -->
    <!-- ========================================================================= -->
    <div id="queueListContainer" class="space-y-3.5">
        @include('pg.partials.queue_cards')
    </div>

</div>





<!-- ========================================================================= -->
<!-- JAVASCRIPT LOGIC UNTUK JAM DIGITAL, REALTIME LIVE SYNC & PANDUAN           -->
<!-- ========================================================================= -->
<script>
    // 1. Jam Digital Pos Gerbang (WIB)
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

    // 2. Realtime Live Sync Antrean Tanpa Reload Halaman (Setiap 3 Detik)
    let lastQueueHtml = null;
    let isSyncing = false;

    async function syncLiveQueue() {
        if (isSyncing) return;
        isSyncing = true;

        try {
            const response = await fetch("{{ route('pg.gate.live_queue') }}", {
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                isSyncing = false;
                return;
            }

            const data = await response.json();

            // 1. Update Badge Jumlah Antrean
            const badgeContainer = document.getElementById('queueBadgeContainer');
            if (badgeContainer) {
                if (data.count > 0) {
                    badgeContainer.innerHTML = `
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-black bg-amber-100 text-amber-900 border border-amber-300 shadow-2xs">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                            <span>${data.count} Menunggu</span>
                        </span>
                    `;
                } else {
                    badgeContainer.innerHTML = `
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                            <i class="fa-solid fa-check text-emerald-600 text-[10px]"></i>
                            <span>Portal Bebas</span>
                        </span>
                    `;
                }
            }

            // 2. Update Daftar Kartu Antrean Secara Halus (Hanya Jika Ada Perubahan)
            const listContainer = document.getElementById('queueListContainer');
            if (listContainer && lastQueueHtml !== null && lastQueueHtml !== data.html) {
                listContainer.innerHTML = data.html;
            }

            lastQueueHtml = data.html;
        } catch (err) {
            // Abaikan kegagalan sementara koneksi jaringan
        } finally {
            isSyncing = false;
        }
    }

    // Jalankan sync antrean realtime setiap 3 detik secara senyap di background
    setInterval(syncLiveQueue, 3000);
</script>
@endsection
