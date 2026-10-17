@extends('layouts.app')

@section('title', 'Verifikasi Pos Gerbang - Private Guard (PG) | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-4 sm:space-y-5 relative">

    <!-- ========================================================================= -->
    <!-- FLOATING TOAST NOTIFIKASI KENDARAAN BARU (KEDIP & MUNCUL DARI ATAS)        -->
    <!-- ========================================================================= -->
    <div id="incomingVehicleToast" 
         class="fixed top-5 right-5 z-50 max-w-sm w-[92vw] sm:w-full bg-[#06213f] text-white rounded-2xl p-4 shadow-2xl border-2 border-amber-400/80 transition-all duration-300 pointer-events-none opacity-0 -translate-y-10 scale-95 flex items-start gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-amber-400 text-slate-950 flex items-center justify-center text-xl flex-shrink-0 font-bold shadow-md animate-bounce">
            <i class="fa-solid fa-bell"></i>
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-1 mb-1">
                <span class="text-[10px] font-black uppercase tracking-wider text-amber-300">KENDARAAN BARU DI PORTAL</span>
                <span id="toastDirectionBadge" class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-amber-500/30 text-amber-300 border border-amber-400/50">MASUK</span>
            </div>
            <p id="toastVehicleUnit" class="text-sm font-extrabold text-white truncate">Toyota Hilux (LV 01)</p>
            <p id="toastDriverName" class="text-xs text-slate-300 truncate mt-0.5">Driver: Hendra Saputra</p>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 1. STATUS BAR POS JAGA, JAM KERJA & KONTROL BEL SUARA                      -->
    <!-- ========================================================================= -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3 border-b border-slate-200/80">
        <!-- Pos Jaga Aktif, Regu, dan Nama Petugas -->
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

        <!-- Bagian Kanan: Kontrol Bel Notifikasi & Jam Digital WIB -->
        <div class="flex items-center justify-start md:justify-end flex-wrap gap-2 pt-2 md:pt-0 border-t md:border-t-0 border-slate-100 flex-shrink-0">
            <!-- Kontrol Bel Suara: Mode & Tombol Tes -->
            <div class="inline-flex items-center bg-white border border-slate-200/90 rounded-xl p-1 shadow-2xs gap-1">
                <button type="button" 
                        id="btnSoundToggle"
                        onclick="cycleSoundMode()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-bold text-xs transition cursor-pointer bg-emerald-50 text-emerald-800 hover:bg-emerald-100 border border-emerald-200/80"
                        title="Klik untuk mengganti mode bel suara">
                    <i id="soundModeIcon" class="fa-solid fa-volume-high text-xs text-emerald-600"></i>
                    <span id="soundModeLabel">Bel & Suara</span>
                </button>
                <button type="button" 
                        onclick="testSoundAlert()" 
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg font-bold text-xs text-slate-700 bg-slate-50 hover:bg-slate-100 hover:text-slate-900 border border-slate-200/70 transition cursor-pointer"
                        title="Uji coba nada bel Ting-Tung & suara asisten">
                    <i class="fa-solid fa-play text-[10px] text-slate-500"></i>
                    <span>Tes Bel</span>
                </button>
            </div>

            <!-- Jam Digital Realtime WIB -->
            <div class="inline-flex items-center space-x-1.5 bg-white border border-slate-200/80 px-3 py-2 rounded-xl font-mono text-xs font-bold text-slate-700 shadow-2xs">
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
    <!-- JAVASCRIPT LOGIC: JAM DIGITAL, BEL SUARA TING-TUNG & ASISTEN BICARA        -->
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

    // =========================================================================
    // 2. SISTEM NOTIFIKASI AUDIO: BEL TING-TUNG & SPEECH SYNTHESIS (ASISTEN)
    // =========================================================================
    // Mode suara yang didukung:
    // - 'both'   : Bel Ting-Tung + Suara Asisten Bicara (Default)
    // - 'chime'  : Hanya Bel Ting-Tung
    // - 'voice'  : Hanya Suara Asisten Bicara
    // - 'mute'   : Senyap (Hanya Toast Visual)
    let currentSoundMode = localStorage.getItem('pg_gate_sound_mode') || 'both';

    function updateSoundUI() {
        const iconEl = document.getElementById('soundModeIcon');
        const labelEl = document.getElementById('soundModeLabel');
        const btnEl = document.getElementById('btnSoundToggle');
        if (!iconEl || !labelEl || !btnEl) return;

        btnEl.classList.remove('bg-emerald-50', 'text-emerald-800', 'border-emerald-200/80',
                               'bg-blue-50', 'text-blue-800', 'border-blue-200/80',
                               'bg-amber-50', 'text-amber-800', 'border-amber-200/80',
                               'bg-slate-100', 'text-slate-600', 'border-slate-200');

        if (currentSoundMode === 'both') {
            btnEl.classList.add('bg-emerald-50', 'text-emerald-800', 'border-emerald-200/80');
            iconEl.className = 'fa-solid fa-volume-high text-xs text-emerald-600';
            labelEl.textContent = 'Bel & Suara';
            btnEl.title = 'Mode: Bel Ting-Tung + Asisten Bicara aktif (Klik untuk ganti)';
        } else if (currentSoundMode === 'chime') {
            btnEl.classList.add('bg-blue-50', 'text-blue-800', 'border-blue-200/80');
            iconEl.className = 'fa-solid fa-bell text-xs text-blue-600';
            labelEl.textContent = 'Hanya Bel';
            btnEl.title = 'Mode: Hanya Bel Ting-Tung (Klik untuk ganti)';
        } else if (currentSoundMode === 'voice') {
            btnEl.classList.add('bg-amber-50', 'text-amber-800', 'border-amber-200/80');
            iconEl.className = 'fa-solid fa-bullhorn text-xs text-amber-600';
            labelEl.textContent = 'Hanya Suara';
            btnEl.title = 'Mode: Hanya Suara Asisten Bicara (Klik untuk ganti)';
        } else {
            btnEl.classList.add('bg-slate-100', 'text-slate-600', 'border-slate-200');
            iconEl.className = 'fa-solid fa-volume-xmark text-xs text-slate-400';
            labelEl.textContent = 'Senyap';
            btnEl.title = 'Mode: Senyap / Tanpa Suara (Klik untuk ganti)';
        }
    }

    function cycleSoundMode() {
        if (currentSoundMode === 'both') currentSoundMode = 'chime';
        else if (currentSoundMode === 'chime') currentSoundMode = 'voice';
        else if (currentSoundMode === 'voice') currentSoundMode = 'mute';
        else currentSoundMode = 'both';

        localStorage.setItem('pg_gate_sound_mode', currentSoundMode);
        updateSoundUI();
        requestDesktopNotificationPermission();
    }

    // A. Web Audio API Synthesizer: Nada Bel Dua Ketuk "Ting - Tung"
    function playChime(callback) {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) {
                if (callback) callback();
                return;
            }
            const ctx = new AudioContext();
            const now = ctx.currentTime;

            // Nada 1: "Ting" (Frekuensi 880 Hz - A5 jernih dengan desah lembut)
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(880, now);
            gain1.gain.setValueAtTime(0.3, now);
            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.45);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(now);
            osc1.stop(now + 0.45);

            // Nada 2: "Tung" (Frekuensi 659.25 Hz - E5 nada bel resepsionis/bandara)
            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(659.25, now + 0.22);
            gain2.gain.setValueAtTime(0.35, now + 0.22);
            gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.85);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(now + 0.22);
            osc2.stop(now + 0.85);

            if (callback) {
                setTimeout(callback, 800);
            }
        } catch (e) {
            console.warn('Web Audio error:', e);
            if (callback) callback();
        }
    }

    // B. Web Speech Synthesis: Suara Asisten Bicara Bahasa Indonesia
    function speakAnnouncement(text) {
        if (!('speechSynthesis' in window)) return;
        try {
            window.speechSynthesis.cancel(); // Hentikan suara sebelumnya jika masih bicara
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'id-ID';
            utterance.rate = 0.95; // Kecepatan bicara normal dan jelas
            utterance.pitch = 1.0;

            // Cari suara bahasa Indonesia bawaan komputer jika ada
            const voices = window.speechSynthesis.getVoices();
            const idVoice = voices.find(v => (v.lang && (v.lang.startsWith('id') || v.lang.includes('ID'))) || 
                                             (v.name && v.name.toLowerCase().includes('indonesia')));
            if (idVoice) {
                utterance.voice = idVoice;
            }

            window.speechSynthesis.speak(utterance);
        } catch (e) {
            console.warn('Speech error:', e);
        }
    }

    // C. Pemicu Pengumuman Kendaraan Sesuai Mode Aktif
    function announceIncomingVehicle(announcementText) {
        if (currentSoundMode === 'mute') return;

        if (currentSoundMode === 'chime') {
            playChime();
        } else if (currentSoundMode === 'voice') {
            speakAnnouncement(announcementText);
        } else {
            // Default 'both': Bunyi Bel Ting-Tung dulu, lalu disambung Asisten Bicara
            playChime(() => {
                speakAnnouncement(announcementText);
            });
        }
    }

    // D. Pop-up Toast Visual di Layar
    let toastTimeout = null;
    function showVehicleToast(vehicleUnit, driverName, direction) {
        const toast = document.getElementById('incomingVehicleToast');
        const unitEl = document.getElementById('toastVehicleUnit');
        const driverEl = document.getElementById('toastDriverName');
        const badgeEl = document.getElementById('toastDirectionBadge');
        if (!toast || !unitEl || !driverEl || !badgeEl) return;

        unitEl.textContent = vehicleUnit || 'Kendaraan Operasional';
        driverEl.textContent = 'Driver: ' + (driverName || 'Karyawan');
        
        const isMasuk = direction === 'in' || direction === 'masuk';
        badgeEl.textContent = isMasuk ? 'MASUK (IN)' : 'KELUAR (OUT)';
        badgeEl.className = isMasuk
            ? 'text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-blue-500/30 text-blue-300 border border-blue-400/50'
            : 'text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-amber-500/30 text-amber-300 border border-amber-400/50';

        // Tampilkan toast
        toast.classList.remove('opacity-0', '-translate-y-10', 'scale-95');
        toast.classList.add('opacity-100', 'translate-y-0', 'scale-100');

        clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => {
            toast.classList.remove('opacity-100', 'translate-y-0', 'scale-100');
            toast.classList.add('opacity-0', '-translate-y-10', 'scale-95');
        }, 5000);
    }

    // E. Izin Notifikasi Desktop Windows
    function requestDesktopNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }

    function showDesktopNotification(title, body) {
        if ('Notification' in window && Notification.permission === 'granted') {
            try {
                new Notification(title, {
                    body: body,
                    icon: '/images/logo-kerinci.png',
                    silent: true // Suara sudah ditangani oleh Bel & Asisten Bicara
                });
            } catch (e) {}
        }
    }

    // F. Tombol "Tes Bel" untuk Uji Coba Langsung di Layar
    function testSoundAlert() {
        requestDesktopNotificationPermission();
        const contohTeks = "Perhatian, Unit Toyota Hilux LV 62, pengemudi Hendra Saputra, akan masuk gerbang.";
        showVehicleToast("Toyota Hilux (LV 62)", "Hendra Saputra", "in");
        announceIncomingVehicle(contohTeks);
        showDesktopNotification("🔔 Tes Notifikasi Portal Pos Gerbang", "Unit Toyota Hilux LV 62 - Hendra Saputra akan masuk gerbang.");
    }

    // Inisialisasi Tampilan Suara saat halaman pertama dimuat
    updateSoundUI();

    // =========================================================================
    // 3. REALTIME LIVE SYNC ANTREAN PORTAL (SETIAP 3 DETIK)
    // =========================================================================
    let knownActivityIds = new Set(@json($pendingActivities->pluck('id')));
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

            // 1. Deteksi apakah ada KENDARAAN BARU yang baru saja masuk antrean
            if (Array.isArray(data.items)) {
                const currentIds = new Set(data.items.map(item => item.id));
                const newArrivals = data.items.filter(item => !knownActivityIds.has(item.id));

                if (newArrivals.length > 0) {
                    // Ambil kendaraan yang paling baru masuk antrean
                    const newest = newArrivals[newArrivals.length - 1];
                    const unitName = (newest.brand_type || 'Kendaraan') + (newest.code ? ' (' + newest.code + ')' : '');

                    // Bunyikan Bel Ting-Tung & Asisten Suara!
                    announceIncomingVehicle(newest.announcement);

                    // Tampilkan Pop-up Visual di Layar Pos
                    showVehicleToast(unitName, newest.driver_name, newest.direction);

                    // Kirim Notifikasi Desktop Windows
                    showDesktopNotification("🔔 Kendaraan Baru di Portal!", `${unitName} - Driver: ${newest.driver_name} (${newest.direction === 'in' ? 'Masuk' : 'Keluar'})`);
                }

                // Perbarui daftar ID antrean yang diketahui
                knownActivityIds = currentIds;
            }

            // 2. Update Badge Jumlah Antrean
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

            // 3. Update Daftar Kartu Antrean Secara Halus (Hanya Jika Ada Perubahan)
            const listContainer = document.getElementById('queueListContainer');
            if (listContainer && lastQueueHtml !== null && lastQueueHtml !== data.html) {
                listContainer.innerHTML = data.html;
            }

            lastQueueHtml = data.html;
        } catch (err) {
            // Abaikan kegagalan sementara jaringan
        } finally {
            isSyncing = false;
        }
    }

    // Jalankan sync antrean realtime setiap 3 detik secara senyap di background
    setInterval(syncLiveQueue, 3000);
</script>
@endsection
