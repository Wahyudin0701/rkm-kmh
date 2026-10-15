@extends('layouts.app')

@section('title', 'Input Timesheet Unit - ' . $vehicle->code)

@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    <!-- Navigasi Kembali & Logo Perusahaan KMH -->
    <div class="flex items-center justify-between pb-1">
        @if(auth()->check())
            <a href="{{ route('mobilisasi.index') }}" class="inline-flex items-center space-x-1.5 text-xs font-bold text-slate-600 hover:text-[#1b5e20] transition bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-sm">
                <i class="fa-solid fa-arrow-left text-[11px]"></i>
                <span>Kembali ke Katalog LV</span>
            </a>
        @else
            <div></div>
        @endif
        <img src="{{ asset('images/logo-kerinci.png') }}" alt="Kerinci Hydropower" class="h-8 w-auto object-contain">
    </div>

    <!-- Header Info Kendaraan (Readonly Card) -->
    <div class="bg-gradient-to-r from-slate-800 to-slate-900 text-white p-5 rounded-2xl shadow-sm border border-slate-700">
        <div class="flex items-center justify-between mb-3">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                <i class="fa-solid fa-car mr-1.5"></i> Kendaraan Ringan (LV)
            </span>
            <span class="text-xs text-slate-400 flex items-center">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse mr-1.5"></span>
                Scan QR Code Aktif
            </span>
        </div>
        
        <h2 class="text-xl font-bold tracking-tight text-white mb-3">Input Timesheet Unit</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-300 bg-slate-800/80 p-3 rounded-xl border border-slate-700/80">
            <div><span class="text-slate-400 font-medium">Kode Unit:</span> <span class="font-bold text-white text-sm ml-1">{{ $vehicle->code }}</span></div>
            <div><span class="text-slate-400 font-medium">Nopol:</span> <span class="font-bold text-amber-300 text-sm ml-1">{{ $vehicle->license_plate ?? '-' }}</span></div>
            <div class="col-span-1 sm:col-span-2"><span class="text-slate-400 font-medium">Merk / Tipe:</span> <span class="text-white ml-1">{{ $vehicle->brand_type }}</span></div>
            <div><span class="text-slate-400 font-medium">Penanggung Jawab:</span> <span class="text-slate-200 ml-1">{{ $vehicle->ownership ?? '-' }}</span></div>
            <div><span class="text-slate-400 font-medium">KM Sebelumnya:</span> <span class="font-bold text-emerald-400 text-sm ml-1">{{ number_format($vehicle->current_km, 0, ',', '.') }} KM</span></div>
        </div>
    </div>

    <!-- LANGKAH 1: FORM VERIFIKASI NIK PENGEMUDI (Tampil Saat Belum Terverifikasi) -->
    <div id="nikVerificationCard" class="{{ $verifiedDriver ? 'hidden' : '' }} bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
        <div class="flex items-center space-x-3.5 pb-3 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#1b5e20] flex items-center justify-center font-bold text-base flex-shrink-0">
                <i class="fa-solid fa-id-card"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-800 text-base">Verifikasi Identitas Pengemudi</h3>
                <p class="text-xs text-slate-500">Masukkan NIK Anda untuk verifikasi SIMPER sebelum mengisi timesheet unit.</p>
            </div>
        </div>

        <!-- Banner Pesan Error Verifikasi NIK (Default Hidden) -->
        <div id="nikErrorBox" class="{{ $nikError ? '' : 'hidden' }} p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-start space-x-3">
            <i class="fa-solid fa-circle-exclamation text-rose-600 text-base mt-0.5 flex-shrink-0"></i>
            <div class="flex-1">
                <span class="font-bold block text-sm mb-0.5" id="nikErrorTitle">
                    {{ $nikErrorTitle ?? 'Verifikasi Gagal' }}
                </span>
                <p id="nikErrorMessage" class="leading-relaxed">{{ $nikError }}</p>
            </div>
        </div>

        <!-- Form Input NIK -->
        <form id="nikForm" method="GET" action="{{ route('vehicle.activity.form', ['code' => $vehicle->code]) }}" onsubmit="handleCheckNik(event)" class="space-y-4">
            <div>
                <label for="input_nik" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Nomor Induk Karyawan (NIK) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-user-tag text-sm"></i>
                    </div>
                    <input type="text" id="input_nik" name="nik" value="{{ old('nik', $inputNik ?? '') }}" required placeholder="Contoh: 0001" autocomplete="off" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 uppercase placeholder:normal-case transition">
                </div>
                <p class="mt-1.5 text-[11px] text-slate-500 flex items-center">
                    <i class="fa-solid fa-circle-info mr-1.5 text-slate-400"></i> NIK akan dicocokkan dengan data SIMPER di Divisi HSE.
                </p>
            </div>

            <button type="submit" id="btnCheckNik" class="w-full bg-[#1b5e20] hover:bg-[#144918] active:scale-[0.99] text-white font-bold py-2.5 px-4 rounded-xl shadow-sm transition flex items-center justify-center space-x-2 text-sm">
                <span id="btnCheckNikText">Verifikasi NIK & Buka Form</span>
                <i id="btnCheckNikIcon" class="fa-solid fa-arrow-right text-xs"></i>
            </button>
        </form>
    </div>

    <!-- LANGKAH 2: FORM UTAMA INPUT TIMESHEET UNIT (DITAMPILKAN SETELAH NIK VALID) -->
    <div id="timesheetFormCard" class="{{ $verifiedDriver ? '' : 'hidden' }} bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        
        <!-- Header Driver Terverifikasi (Kompak, Bersih & Profesional) -->
        <div class="p-4 sm:p-5 bg-gradient-to-r from-emerald-50/70 via-slate-50 to-white border-b border-slate-100 flex items-center justify-between gap-3">
            <div class="flex items-center space-x-3 min-w-0">
                <div class="w-10 h-10 rounded-full bg-emerald-100 text-[#1b5e20] flex items-center justify-center font-bold text-base flex-shrink-0">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center space-x-2 flex-wrap">
                        <span class="text-sm font-bold text-slate-900" id="verifiedDriverName">{{ $verifiedDriver->name ?? '' }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                            <i class="fa-solid fa-shield-check mr-1"></i> SIMPER Aktif
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-200/70 text-slate-600">
                            <i class="fa-solid fa-circle-check text-emerald-600 mr-1"></i> Otomatis Terisi
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5 truncate">
                        <span id="verifiedDriverNik">NIK: {{ $verifiedDriver->nik ?? '' }}</span> &bull; 
                        <span id="verifiedDriverDiv">{{ $verifiedDriver->division ?? 'Umum' }}</span> &bull; 
                        <span class="text-emerald-700 font-medium" id="verifiedDriverExp">Berlaku s.d. {{ $verifiedDriver && $verifiedDriver->simper_expired_at ? $verifiedDriver->simper_expired_at->format('d/m/Y') : '' }}</span>
                    </p>
                </div>
            </div>
            <button type="button" onclick="changeDriver()" class="text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200 shadow-sm transition flex items-center space-x-1.5 flex-shrink-0" title="Ganti NIK Pengemudi">
                <i class="fa-solid fa-rotate-left text-[10px] text-slate-400"></i>
                <span>Ganti NIK</span>
            </button>
        </div>

        <!-- Form Body -->
        <form action="{{ route('vehicle.activity.store', ['code' => $vehicle->code]) }}" method="POST" id="timesheetForm" class="p-5 sm:p-6 space-y-4">
            @csrf

            <!-- Hidden input driver_id (otomatis terisi dari hasil verifikasi NIK) -->
            <input type="hidden" name="driver_id" id="driver_id" value="{{ $verifiedDriver->id ?? '' }}">

            <!-- 1. Pilihan Gate / Gerbang -->
            <div>
                <label for="gate_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Lokasi Gate / Gerbang <span class="text-rose-500">*</span>
                </label>
                <select name="gate_id" id="gate_id" required class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 font-medium focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition">
                    <option value="" disabled {{ !old('gate_id') ? 'selected' : '' }}>-- Pilih Pos Gerbang --</option>
                    @foreach($gates as $g)
                        <option value="{{ $g->id }}" {{ old('gate_id') == $g->id ? 'selected' : '' }}>
                            {{ $g->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 2. Arah Gerbang (In/Out) dengan Logika Berpasangan -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="direction" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Arah Akses Gerbang (In / Out) <span class="text-rose-500">*</span>
                    </label>
                    @if($allowedDirection === 'ANY')
                        <span class="text-xs text-emerald-600 font-semibold">Transaksi Perdana</span>
                    @else
                        <span class="text-xs text-amber-600 font-semibold">Terkunci Berpasangan</span>
                    @endif
                </div>

                @if($allowedDirection === 'ANY')
                    <select name="direction" id="direction" required class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 font-medium focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition">
                        <option value="" disabled {{ !old('direction') ? 'selected' : '' }}>-- Pilih Arah Akses --</option>
                        <option value="in" {{ old('direction') === 'in' ? 'selected' : '' }}>MASUK (In)</option>
                        <option value="out" {{ old('direction') === 'out' ? 'selected' : '' }}>KELUAR (Out)</option>
                    </select>
                @else
                    <input type="hidden" name="direction" value="{{ $allowedDirection }}">
                    <div class="flex items-center justify-between p-3 rounded-xl border {{ $allowedDirection === 'in' ? 'bg-emerald-50/60 border-emerald-200 text-emerald-800' : 'bg-blue-50/60 border-blue-200 text-blue-800' }}">
                        <div class="text-sm font-semibold">
                            <span>{{ $allowedDirection === 'in' ? 'MASUK (In)' : 'KELUAR (Out)' }}</span>
                        </div>
                        <span class="text-[11px] text-slate-500 font-normal">Berdasarkan riwayat terakhir (Otomatis)</span>
                    </div>
                @endif
            </div>

            <!-- 3. Input Odometer KM -->
            <div>
                <label for="current_km" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Odometer Saat Ini (KM) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" step="0.1" name="current_km" id="current_km" 
                           value="{{ old('current_km', $vehicle->current_km) }}" 
                           min="{{ $vehicle->current_km }}"
                           required 
                           placeholder="Masukkan angka odometer"
                           class="w-full rounded-xl border border-slate-300 bg-white pl-4 pr-12 py-2.5 text-sm text-slate-800 font-bold focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition">
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-xs font-extrabold text-slate-400">
                        KM
                    </div>
                </div>
                <div class="flex items-center justify-between mt-1.5 text-xs">
                    <span class="text-slate-400 font-medium">Minimal: {{ number_format($vehicle->current_km, 0, ',', '.') }} KM</span>
                    <span id="distancePreview" class="font-bold text-emerald-600">Estimasi Jarak: 0 KM</span>
                </div>
            </div>

            <!-- 4. Rencana Pekerjaan / Aktivitas -->
            <div>
                <label for="purpose" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                    Rencana Pekerjaan / Aktivitas <span class="text-rose-500">*</span>
                </label>
                <textarea name="purpose" id="purpose" rows="3" required placeholder="Jelaskan keperluan operasional penggunaan unit..." class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition resize-none">{{ old('purpose') }}</textarea>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-3 border-t border-slate-100 flex items-center space-x-3">
                <button type="submit" id="btnSubmit" class="flex-1 bg-[#1b5e20] hover:bg-[#144918] active:scale-[0.99] text-white font-bold py-2.5 px-4 rounded-xl shadow-sm hover:shadow transition flex items-center justify-center space-x-2 text-sm disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-check"></i>
                    <span>Simpan Timesheet</span>
                </button>
                <button type="reset" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold py-2.5 px-4 rounded-xl transition text-sm">
                    <i class="fa-solid fa-rotate-left mr-1"></i> Reset
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    async function handleCheckNik(e) {
        e.preventDefault();
        const nikInput = document.getElementById('input_nik');
        const nik = (nikInput.value || '').trim();
        if (!nik) return;

        const btn = document.getElementById('btnCheckNik');
        const btnText = document.getElementById('btnCheckNikText');
        const btnIcon = document.getElementById('btnCheckNikIcon');
        const errorBox = document.getElementById('nikErrorBox');
        const errorTitle = document.getElementById('nikErrorTitle');
        const errorMessage = document.getElementById('nikErrorMessage');

        // Loading state
        btn.disabled = true;
        btnText.textContent = 'Memeriksa NIK...';
        btnIcon.className = 'fa-solid fa-spinner fa-spin text-xs';
        errorBox.classList.add('hidden');

        try {
            const response = await fetch("{{ route('vehicle.activity.check.nik', ['code' => $vehicle->code]) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ nik: nik })
            });

            const data = await response.json();

            if (response.ok && data.status === 'valid') {
                // Sukses: Tampilkan kartu terintegrasi & buka form
                setupVerifiedDriver(data.employee);
            } else {
                // Tampilkan pesan error sesuai status
                errorTitle.textContent = data.title || (data.status === 'expired' ? 'Peringatan: SIMPER Kedaluwarsa' : (data.status === 'not_driver' ? 'Bukan Driver Terdaftar' : 'Verifikasi Gagal'));
                errorMessage.textContent = data.message || 'Terjadi kesalahan saat memeriksa NIK.';
                errorBox.classList.remove('hidden');
            }
        } catch (err) {
            errorTitle.textContent = 'Gagal Memeriksa';
            errorMessage.textContent = 'Terjadi gangguan jaringan atau server. Silakan coba kembali.';
            errorBox.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btnText.textContent = 'Verifikasi NIK & Buka Form';
            btnIcon.className = 'fa-solid fa-arrow-right text-xs';
        }
    }

    function setupVerifiedDriver(emp) {
        document.getElementById('driver_id').value = emp.id;
        document.getElementById('verifiedDriverName').textContent = emp.name;
        document.getElementById('verifiedDriverNik').textContent = 'NIK: ' + emp.nik;
        document.getElementById('verifiedDriverDiv').textContent = emp.division;
        document.getElementById('verifiedDriverExp').textContent = 'Berlaku s.d. ' + emp.simper_expired_at;

        document.getElementById('nikVerificationCard').classList.add('hidden');
        document.getElementById('timesheetFormCard').classList.remove('hidden');

        setTimeout(() => {
            document.getElementById('timesheetFormCard').scrollIntoView({ behavior: 'smooth' });
        }, 100);
    }

    function changeDriver() {
        document.getElementById('driver_id').value = '';
        document.getElementById('timesheetFormCard').classList.add('hidden');
        document.getElementById('nikVerificationCard').classList.remove('hidden');
        document.getElementById('nikErrorBox').classList.add('hidden');
        const input = document.getElementById('input_nik');
        input.focus();
        input.select();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const currentKmInput = document.getElementById('current_km');
        const distancePreview = document.getElementById('distancePreview');
        const previousKm = parseFloat("{{ $vehicle->current_km }}") || 0;

        // Kalkulasi jarak real-time
        function calculateDistance() {
            const inputKm = parseFloat(currentKmInput.value) || 0;
            const diff = Math.max(0, inputKm - previousKm);
            if (distancePreview) {
                distancePreview.textContent = `Estimasi Jarak: ${diff.toLocaleString('id-ID')} KM`;
            }
        }

        if (currentKmInput) {
            currentKmInput.addEventListener('input', calculateDistance);
            calculateDistance();
        }
    });
</script>
@endsection
