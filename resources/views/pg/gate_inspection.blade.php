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
                <!-- Tombol Fleksibel Ganti Pos Jaga -->
                <button type="button" 
                        onclick="openGateSelectionModal()" 
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-white transition shadow-2xs cursor-pointer"
                        title="Ganti pos gerbang yang sedang dijaga">
                    <i class="fa-solid fa-arrows-rotate text-[11px]"></i>
                    <span>Ganti Pos</span>
                </button>
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
            const response = await fetch("{{ route('pg.gate.live_queue', [], false) }}?gate_id={{ $gate ? $gate->id : '' }}&_t=" + Date.now(), {
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache'
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
            if (listContainer && data.html && lastQueueHtml !== data.html) {
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

    // Modal Lightbox Foto Driver & Mobil LV
    function openImageLightbox(url, title) {
        const modal = document.getElementById('imageLightboxModal');
        const img = document.getElementById('lightboxImage');
        const titleEl = document.getElementById('lightboxTitle');
        if (modal && img) {
            img.src = url;
            if (titleEl) titleEl.textContent = title || 'Pratinjau Foto';
            modal.classList.remove('hidden');
        }
    }

    function closeImageLightbox() {
        const modal = document.getElementById('imageLightboxModal');
        if (modal) modal.classList.add('hidden');
    }

    // Modal Pemilihan Pos Gerbang Bertugas
    let isGateModalForced = {{ $needsGateSelection ? 'true' : 'false' }};

    function openGateSelectionModal(isForced = false) {
        isGateModalForced = isForced;
        const modal = document.getElementById('gateSelectionModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.style.display = 'block';
        }
    }

    function closeGateSelectionModal() {
        if (isGateModalForced) {
            return;
        }
        const modal = document.getElementById('gateSelectionModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }
    }

    function onGateModalBackdropClick() {
        if (!isGateModalForced) {
            closeGateSelectionModal();
        }
    }

    @if($needsGateSelection)
    document.addEventListener('DOMContentLoaded', function() {
        openGateSelectionModal(true);
    });
    @endif

    // Modal Tolak Perjalanan Kendaraan di Pos Gerbang
    function openRejectModal(id, unit, driver, direction) {
        document.getElementById('rejectUnitDisplay').textContent = unit || '-';
        document.getElementById('rejectDriverDisplay').textContent = driver || '-';
        const dirEl = document.getElementById('rejectDirectionDisplay');
        if (direction === 'in') {
            dirEl.textContent = 'Masuk Gerbang (IN)';
            dirEl.className = 'font-bold text-xs uppercase px-2 py-0.5 rounded-md bg-blue-100 text-blue-800';
        } else {
            dirEl.textContent = 'Keluar Gerbang (OUT)';
            dirEl.className = 'font-bold text-xs uppercase px-2 py-0.5 rounded-md bg-amber-100 text-amber-800';
        }

        document.getElementById('rejectNoteInput').value = '';
        document.getElementById('rejectForm').action = `/pg/gate-inspection/${id}/reject`;
        const modal = document.getElementById('rejectModal');
        if (modal) modal.classList.remove('hidden');
    }

    function closeRejectModal() {
        const modal = document.getElementById('rejectModal');
        if (modal) modal.classList.add('hidden');
    }

    function setQuickReason(text) {
        const input = document.getElementById('rejectNoteInput');
        if (input) {
            input.value = text;
            input.focus();
        }
    }
</script>

<!-- MODAL LIGHTBOX PRATINJAU FOTO DRIVER & MOBIL LV -->
<div id="imageLightboxModal" class="fixed inset-0 z-50 hidden bg-slate-900/80 backdrop-blur-xs flex items-center justify-center p-4" onclick="closeImageLightbox()">
    <div class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl p-4 space-y-3" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <h4 id="lightboxTitle" class="text-xs font-bold text-slate-800 truncate">Pratinjau Foto</h4>
            <button type="button" onclick="closeImageLightbox()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
        <div class="rounded-xl overflow-hidden bg-slate-950 flex items-center justify-center max-h-[70vh]">
            <img id="lightboxImage" src="" alt="Pratinjau" class="max-h-[68vh] w-auto max-w-full object-contain">
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PILIH POS GERBANG BERTUGAS                                    -->
<!-- ========================================================================= -->
<div id="gateSelectionModal" 
     class="{{ $needsGateSelection ? '' : 'hidden' }} fixed inset-0 z-50 overflow-y-auto"
     style="{{ $needsGateSelection ? 'display: block;' : 'display: none;' }}">
    <!-- Backdrop Gelap -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" 
         onclick="onGateModalBackdropClick()"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-xl bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all p-6 sm:p-7 space-y-5">
            <!-- Header Modal -->
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-xl flex-shrink-0 shadow-2xs">
                        <i class="fa-solid fa-door-open"></i>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">
                            Pilih Pos Gerbang Bertugas
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Tentukan gerbang tempat Anda berjaga saat ini untuk verifikasi perlintasan.
                        </p>
                    </div>
                </div>
                @if(!$needsGateSelection)
                <button type="button" onclick="closeGateSelectionModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
                @endif
            </div>

            <!-- Form Pemilihan Pos Gerbang -->
            <form action="{{ route('pg.gate.select_gate') }}" method="POST" id="gateSelectionForm">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 max-h-[60vh] overflow-y-auto p-1">
                    @foreach($allGates as $g)
                        @php
                            $isCurrent = $gate && $gate->id === $g->id;
                            $queueCount = $gateQueueCounts[$g->id] ?? 0;
                        @endphp
                        <label class="relative flex flex-col justify-between p-4 rounded-2xl border-2 cursor-pointer transition-all hover:shadow-md {{ $isCurrent ? 'border-amber-500 bg-amber-50/50' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                            <input type="radio" 
                                   name="gate_id" 
                                   value="{{ $g->id }}" 
                                   {{ $isCurrent ? 'checked' : '' }}
                                   class="sr-only" 
                                   onchange="document.getElementById('gateSelectionForm').submit()">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold {{ $isCurrent ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-700' }}">
                                        <i class="fa-solid fa-shield-halved"></i>
                                    </span>
                                    @if($isCurrent)
                                        <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300">
                                            Pos Aktif
                                        </span>
                                    @elseif($queueCount > 0)
                                        <span class="text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-100 text-amber-900 border border-amber-300">
                                            {{ $queueCount }} Antrean
                                        </span>
                                    @else
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">
                                            Bebas
                                        </span>
                                    @endif
                                </div>
                                <h4 class="text-sm font-black text-slate-900">{{ $g->name }}</h4>
                                <p class="text-xs text-slate-500 mt-1 line-clamp-2">
                                    {{ $g->location ?: 'Pos pemeriksaan keluar/masuk kendaraan' }}
                                </p>
                            </div>
                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold {{ $isCurrent ? 'text-amber-700' : 'text-slate-600' }}">
                                <span>{{ $isCurrent ? 'Sedang Dipilih' : 'Klik untuk Memilih' }}</span>
                                <i class="fa-solid fa-arrow-right text-[11px]"></i>
                            </div>
                        </label>
                    @endforeach
                </div>

                @if(!$needsGateSelection)
                <div class="flex items-center justify-end pt-3 border-t border-slate-100 mt-4">
                    <button type="button" onclick="closeGateSelectionModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                        Batal
                    </button>
                </div>
                @endif
            </form>
        </div>
    </div>
</div>
<!-- ========================================================================= -->
<!-- MODAL POPUP: TOLAK PERJALANAN KENDARAAN (DENGAN DETAIL ALASAN)           -->
<!-- ========================================================================= -->
<div id="rejectModal" data-backdrop="static" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-7 border border-slate-100 overflow-hidden animate-scale-in space-y-4">
        <!-- Header Modal -->
        <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center text-xl flex-shrink-0 shadow-2xs">
                    <i class="fa-solid fa-ban"></i>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">
                        Tolak Perjalanan Kendaraan
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Berikan alasan penolakan secara jelas untuk catatan log audit & driver.
                    </p>
                </div>
            </div>
            <button type="button" onclick="closeRejectModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <!-- Identitas Kendaraan & Driver -->
        <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 space-y-1.5">
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500 font-bold">Kendaraan:</span>
                <span id="rejectUnitDisplay" class="font-mono font-black text-slate-900"></span>
            </div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500 font-bold">Pengemudi:</span>
                <span id="rejectDriverDisplay" class="font-bold text-slate-900"></span>
            </div>
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-500 font-bold">Arah Perlintasan:</span>
                <span id="rejectDirectionDisplay" class="font-bold text-xs uppercase px-2 py-0.5 rounded-md"></span>
            </div>
        </div>

        <!-- Form Penolakan -->
        <form id="rejectForm" method="POST" action="" class="space-y-4">
            @csrf
            <!-- Petugas yang Menolak (Otomatis Akun Login) -->
            <div class="p-3 bg-slate-50 border border-slate-200/80 rounded-2xl flex items-center justify-between text-xs">
                <span class="text-slate-500 font-medium">Petugas Pemeriksa:</span>
                <span class="font-bold text-slate-800 flex items-center space-x-1.5">
                    <i class="fa-solid fa-user-shield text-[#1b5e20]"></i>
                    <span>{{ auth()->user()?->name ?? 'Petugas Pos Gerbang' }}</span>
                </span>
            </div>

            <!-- Quick Reason Chips -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                    Pilih Cepat Alasan Penolakan:
                </label>
                <div class="flex flex-wrap gap-1.5">
                    <button type="button" onclick="setQuickReason('Masa berlaku SIMPER pengemudi telah kedaluwarsa (Expired)')" class="text-[11px] px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-rose-50 hover:border-rose-300 hover:text-rose-700 transition font-medium cursor-pointer">
                        SIMPER Expired
                    </button>
                    <button type="button" onclick="setQuickReason('Kondisi fisik kendaraan tidak layak jalan (Rem blong / Lampu mati / Ban botak)')" class="text-[11px] px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-rose-50 hover:border-rose-300 hover:text-rose-700 transition font-medium cursor-pointer">
                        Fisik Tidak Layak
                    </button>
                    <button type="button" onclick="setQuickReason('Tujuan atau keperluan perjalanan tidak jelas / Tanpa instruksi kerja')" class="text-[11px] px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-rose-50 hover:border-rose-300 hover:text-rose-700 transition font-medium cursor-pointer">
                        Tujuan Tidak Jelas
                    </button>
                    <button type="button" onclick="setQuickReason('Surat jalan / BAST belum lengkap')" class="text-[11px] px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-rose-50 hover:border-rose-300 hover:text-rose-700 transition font-medium cursor-pointer">
                        Surat Jalan Kurang
                    </button>
                    <button type="button" onclick="setQuickReason('Pengemudi bukan operator / driver yang terdaftar untuk unit ini')" class="text-[11px] px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-rose-50 hover:border-rose-300 hover:text-rose-700 transition font-medium cursor-pointer">
                        Bukan Driver Resmi
                    </button>
                </div>
            </div>

            <!-- Detail Alasan Wajib -->
            <div>
                <label for="rejectNoteInput" class="block text-xs font-bold text-slate-700 mb-1.5">
                    Detail Catatan / Alasan Penolakan <span class="text-rose-500">*</span>
                </label>
                <textarea name="rejection_note" id="rejectNoteInput" rows="3" required 
                          placeholder="Jelaskan alasan penolakan secara spesifik..."
                          class="w-full text-xs p-3 rounded-xl border border-slate-300 bg-white text-slate-800 placeholder:text-slate-400 focus:outline-none focus:border-rose-600 focus:ring-2 focus:ring-rose-500/10 transition resize-none"></textarea>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" 
                        onclick="if(!document.getElementById('rejectNoteInput').value.trim()){ alert('Alasan penolakan wajib diisi!'); return false; }"
                        class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center space-x-1.5 cursor-pointer">
                    <i class="fa-solid fa-ban text-xs"></i>
                    <span>Konfirmasi Tolak Perjalanan</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
