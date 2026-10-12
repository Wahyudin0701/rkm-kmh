@extends('layouts.app')

@section('title', 'Input Timesheet Unit - ' . $vehicle->code)

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- Card Utama Form Input Timesheet -->
    <div class="bg-white rounded-xl shadow-md border border-slate-200 overflow-hidden">
        <!-- Header Info Kendaraan (Readonly Card) -->
        <div class="bg-gradient-to-r from-slate-800 to-slate-900 text-white p-5 border-b border-slate-700">
            <div class="flex items-center justify-between mb-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    <i class="fa-solid fa-car mr-1"></i> Kendaraan Ringan (LV)
                </span>
                <span class="text-xs text-slate-400">Scan QR Code Aktif</span>
            </div>
            
            <h2 class="text-xl font-bold tracking-tight text-white mb-3">Input Timesheet Unit</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-300 bg-slate-800/80 p-3 rounded-lg border border-slate-700">
                <div><span class="text-slate-400 font-medium">Kode Unit:</span> <span class="font-bold text-white text-sm">{{ $vehicle->code }}</span></div>
                <div><span class="text-slate-400 font-medium">Nopol:</span> <span class="font-bold text-amber-300 text-sm">{{ $vehicle->license_plate ?? '-' }}</span></div>
                <div class="col-span-1 sm:col-span-2"><span class="text-slate-400 font-medium">Merk / Tipe:</span> <span class="text-white">{{ $vehicle->brand_type }}</span></div>
                <div><span class="text-slate-400 font-medium">Penanggung Jawab:</span> <span class="text-slate-200">{{ $vehicle->ownership ?? '-' }}</span></div>
                <div><span class="text-slate-400 font-medium">KM Sebelumnya:</span> <span class="font-bold text-emerald-400 text-sm">{{ number_format($vehicle->current_km, 0, ',', '.') }} KM</span></div>
            </div>
        </div>

        <!-- Form Body -->
        <form action="{{ route('vehicle.activity.store', ['code' => $vehicle->code]) }}" method="POST" id="timesheetForm" class="p-6 space-y-5">
            @csrf

            <!-- 1. Pilihan Pengemudi (Driver) dengan Validasi SIMPER Real-time -->
            <div>
                <label for="driver_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                    Nama Pemakai Unit (Driver) <span class="text-rose-500">*</span>
                </label>
                <select name="driver_id" id="driver_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
                    <option value="" disabled selected>-- Pilih Driver / Pemakai Unit --</option>
                    @foreach($drivers as $d)
                        @php
                            $isValid = $d->hasValidSimper();
                            $expiredDate = $d->simper_expired_at ? $d->simper_expired_at->format('d/m/Y') : 'Tidak Ada Data';
                        @endphp
                        <option value="{{ $d->id }}" 
                                data-is-valid="{{ $isValid ? '1' : '0' }}"
                                data-expired="{{ $expiredDate }}"
                                {{ old('driver_id') == $d->id ? 'selected' : '' }}>
                            {{ $d->name }} ({{ $d->division ?? 'Umum' }}) — SIMPER: {{ $expiredDate }} {{ !$isValid ? '🔴 [KEDALUWARSA]' : '🟢' }}
                        </option>
                    @endforeach
                </select>

                <!-- Warning Banner SIMPER Kedaluwarsa (BR-01 & BR-02) -->
                <div id="simperWarningBanner" class="hidden mt-3 p-3.5 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 text-xs flex items-start space-x-2.5 animate-pulse">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base mt-0.5"></i>
                    <div>
                        <span class="font-bold block text-sm">Peringatan: Masa Berlaku SIMPER Telah Kedaluwarsa!</span>
                        <p class="mt-0.5" id="simperWarningText">Karyawan ini tidak dapat mengoperasikan unit. Silakan lakukan perpanjangan SIMPER melalui Divisi HSE.</p>
                    </div>
                </div>
            </div>

            <!-- 2. Pilihan Gate / Gerbang -->
            <div>
                <label for="gate_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                    Lokasi Gate / Gerbang <span class="text-rose-500">*</span>
                </label>
                <select name="gate_id" id="gate_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
                    <option value="" disabled selected>-- Pilih Pos Gerbang --</option>
                    @foreach($gates as $g)
                        <option value="{{ $g->id }}" {{ old('gate_id') == $g->id ? 'selected' : '' }}>
                            {{ $g->name }} ({{ $g->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 3. Arah Gerbang (In/Out) dengan Logika Berpasangan -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="direction" class="block text-xs font-semibold uppercase tracking-wider text-slate-700">
                        Arah Akses Gerbang (In / Out) <span class="text-rose-500">*</span>
                    </label>
                    @if($allowedDirection === 'ANY')
                        <span class="text-xs text-emerald-600 font-medium"><i class="fa-solid fa-sparkles"></i> Transaksi Perdana (Bebas Pilih)</span>
                    @else
                        <span class="text-xs text-amber-600 font-medium"><i class="fa-solid fa-lock"></i> Terkunci Berpasangan ({{ strtoupper($allowedDirection) }})</span>
                    @endif
                </div>

                @if($allowedDirection === 'ANY')
                    <select name="direction" id="direction" required class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
                        <option value="" disabled selected>-- Pilih Arah Akses --</option>
                        <option value="in" {{ old('direction') === 'in' ? 'selected' : '' }}>MASUK (In)</option>
                        <option value="out" {{ old('direction') === 'out' ? 'selected' : '' }}>KELUAR (Out)</option>
                    </select>
                @else
                    <input type="hidden" name="direction" value="{{ $allowedDirection }}">
                    <div class="p-3 bg-slate-100 border border-slate-300 rounded-lg text-sm font-semibold flex items-center justify-between {{ $allowedDirection === 'in' ? 'text-emerald-700 bg-emerald-50 border-emerald-300' : 'text-blue-700 bg-blue-50 border-blue-300' }}">
                        <span>
                            <i class="fa-solid {{ $allowedDirection === 'in' ? 'fa-arrow-right-to-bracket' : 'fa-arrow-right-from-bracket' }} mr-2"></i>
                            Arah Otomatis: {{ $allowedDirection === 'in' ? 'MASUK (In)' : 'KELUAR (Out)' }}
                        </span>
                        <span class="text-xs font-normal text-slate-500">(Berdasarkan status terakhir)</span>
                    </div>
                @endif
            </div>

            <!-- 4. Input Odometer KM -->
            <div>
                <label for="current_km" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                    Odometer Saat Ini (KM) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" step="0.1" name="current_km" id="current_km" 
                           value="{{ old('current_km', $vehicle->current_km) }}" 
                           min="{{ $vehicle->current_km }}"
                           required 
                           placeholder="Masukkan angka odometer di speedometer"
                           class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 font-semibold focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs font-bold text-slate-400">
                        KM
                    </div>
                </div>
                <div class="flex items-center justify-between mt-1 text-xs text-slate-500">
                    <span>Minimal: {{ number_format($vehicle->current_km, 0, ',', '.') }} KM</span>
                    <span id="distancePreview" class="font-medium text-emerald-600">Estimasi Jarak: 0 KM</span>
                </div>
            </div>

            <!-- 5. Rencana Pekerjaan / Aktivitas -->
            <div>
                <label for="purpose" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                    Rencana Pekerjaan / Aktivitas <span class="text-rose-500">*</span>
                </label>
                <textarea name="purpose" id="purpose" rows="3" required placeholder="Jelaskan keperluan operasional penggunaan unit..." class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">{{ old('purpose') }}</textarea>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-3 border-t border-slate-200 flex items-center space-x-3">
                <button type="submit" id="btnSubmit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-4 rounded-lg shadow-sm transition flex items-center justify-center space-x-2 disabled:bg-slate-300 disabled:text-slate-500 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-check"></i>
                    <span>Simpan Timesheet</span>
                </button>
                <button type="reset" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 px-4 rounded-lg transition border border-slate-300">
                    <i class="fa-solid fa-rotate-left mr-1"></i> Reset
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const driverSelect = document.getElementById('driver_id');
        const submitBtn = document.getElementById('btnSubmit');
        const warningBanner = document.getElementById('simperWarningBanner');
        const warningText = document.getElementById('simperWarningText');
        const currentKmInput = document.getElementById('current_km');
        const distancePreview = document.getElementById('distancePreview');
        const previousKm = parseFloat("{{ $vehicle->current_km }}") || 0;

        // Validasi SIMPER saat driver dipilih
        function checkSimper() {
            const selectedOption = driverSelect.options[driverSelect.selectedIndex];
            if (!selectedOption || !selectedOption.value) {
                warningBanner.classList.add('hidden');
                submitBtn.disabled = false;
                return;
            }

            const isValid = selectedOption.getAttribute('data-is-valid') === '1';
            const expiredDate = selectedOption.getAttribute('data-expired');

            if (!isValid) {
                // SIMPER Kedaluwarsa -> Blokir Tombol Submit (BR-01 & BR-02)
                warningText.textContent = `SIMPER pengemudi telah kedaluwarsa pada ${expiredDate}. Tombol simpan dinonaktifkan. Pengemudi wajib melakukan perpanjangan SIMPER melalui Divisi HSE.`;
                warningBanner.classList.remove('hidden');
                submitBtn.disabled = true;
            } else {
                warningBanner.classList.add('hidden');
                submitBtn.disabled = false;
            }
        }

        // Kalkulasi jarak real-time
        function calculateDistance() {
            const inputKm = parseFloat(currentKmInput.value) || 0;
            const diff = Math.max(0, inputKm - previousKm);
            distancePreview.textContent = `Estimasi Jarak: ${diff.toLocaleString('id-ID')} KM`;
        }

        driverSelect.addEventListener('change', checkSimper);
        currentKmInput.addEventListener('input', calculateDistance);

        // Run check on load
        checkSimper();
        calculateDistance();
    });
</script>
@endsection
