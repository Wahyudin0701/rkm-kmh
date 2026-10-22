@extends('layouts.app')

@section('title', 'Input Timesheet Unit - ' . $vehicle->code)

@section('content')
<div class="max-w-2xl sm:max-w-3xl mx-auto space-y-4">
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
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-white">Input Timesheet Unit</h2>
                <p class="text-xs text-slate-400 mt-0.5">Pemeriksaan & Mobilisasi Kendaraan Pos Gerbang</p>
            </div>
            @if($vehicle->photo_url)
                <img src="{{ $vehicle->photo_url }}" alt="{{ $vehicle->brand_type }}" class="w-16 h-16 rounded-xl object-cover ring-2 ring-emerald-500/30 shadow-sm flex-shrink-0">
            @endif
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-300 bg-slate-800/80 p-3 rounded-xl border border-slate-700/80">
            <div><span class="text-slate-400 font-medium">Kode Unit:</span> <span class="font-bold text-white text-sm ml-1">{{ $vehicle->code }}</span></div>
            <div><span class="text-slate-400 font-medium">Nopol:</span> <span class="font-bold text-amber-300 text-sm ml-1">{{ $vehicle->license_plate ?? '-' }}</span></div>
            <div class="col-span-1 sm:col-span-2"><span class="text-slate-400 font-medium">Merk / Tipe:</span> <span class="text-white ml-1">{{ $vehicle->brand_type }}</span></div>
            <div><span class="text-slate-400 font-medium">Penanggung Jawab:</span> <span class="text-slate-200 ml-1">{{ $vehicle->ownership ?? '-' }}</span></div>
            <div><span class="text-slate-400 font-medium">KM Sebelumnya:</span> <span class="font-bold text-emerald-400 text-sm ml-1">{{ number_format($vehicle->current_km, 0, ',', '.') }} KM</span></div>
        </div>
    </div>

    @if(!$verifiedDriver)
        <!-- Kartu Peringatan Izin Mengemudi (SIMPER / Non-Driver) -->
        <div class="bg-white rounded-2xl shadow-sm border border-rose-200 p-6 space-y-4">
            <div class="flex items-center space-x-3.5 pb-3 border-b border-rose-100">
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-base flex-shrink-0">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-800 text-base">{{ $nikErrorTitle ?? 'Izin Mengemudi Tidak Valid' }}</h3>
                    <p class="text-xs text-slate-500">Pemeriksaan lisensi SIMPER di database Divisi HSE.</p>
                </div>
            </div>

            <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-xs flex items-start space-x-3">
                <i class="fa-solid fa-circle-exclamation text-rose-600 text-base mt-0.5 flex-shrink-0"></i>
                <div class="flex-1">
                    <p class="leading-relaxed font-medium">{{ $nikError ?? 'Akun Anda tidak memiliki lisensi mengemudi aktif untuk mengoperasikan unit ini.' }}</p>
                </div>
            </div>

            <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-3">
                <a href="{{ route('employee.dashboard') }}" class="w-full sm:w-auto px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition text-center">
                    <i class="fa-solid fa-arrow-left mr-1.5"></i> Kembali ke Dashboard
                </a>
                <form action="{{ route('logout') }}" method="POST" class="w-full sm:w-auto">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-xl text-xs border border-rose-200 transition text-center cursor-pointer">
                        <i class="fa-solid fa-arrow-right-from-bracket mr-1.5"></i> Ganti Akun Pengemudi Lain
                    </button>
                </form>
            </div>
        </div>
    @else
        <!-- FORM UTAMA INPUT TIMESHEET UNIT (DRIVER TERVERIFIKASI) -->
        <div id="timesheetFormCard" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            
            <!-- Header Driver Terverifikasi (Kompak, Bersih & Profesional) -->
            <div class="p-4 sm:p-5 bg-gradient-to-r from-emerald-50/70 via-slate-50 to-white border-b border-slate-100 flex items-center justify-between gap-3">
                <div class="flex items-center space-x-3 min-w-0">
                    @if($verifiedDriver && $verifiedDriver->photo_url)
                        <img src="{{ $verifiedDriver->photo_url }}" alt="{{ $verifiedDriver->name }}" class="w-11 h-11 rounded-full object-cover ring-2 ring-emerald-500/40 shadow-xs flex-shrink-0">
                    @else
                        <div class="w-11 h-11 rounded-full bg-emerald-100 text-[#1b5e20] flex items-center justify-center font-bold text-base flex-shrink-0 ring-1 ring-emerald-200">
                            {{ strtoupper(substr($verifiedDriver->name ?? 'D', 0, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <div class="flex items-center space-x-2 flex-wrap">
                            <span class="text-sm font-bold text-slate-900" id="verifiedDriverName">{{ $verifiedDriver->name ?? '' }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                <i class="fa-solid fa-shield-check mr-1"></i> SIMPER Aktif
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-200/70 text-slate-600">
                                <i class="fa-solid fa-circle-check text-emerald-600 mr-1"></i> Terverifikasi Akun
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5 truncate">
                            <span id="verifiedDriverNik">NIK: {{ $verifiedDriver->nik ?? '' }}</span> &bull; 
                            <span id="verifiedDriverDiv">{{ $verifiedDriver->division ?? 'Umum' }}</span> &bull; 
                            <span class="text-emerald-700 font-medium" id="verifiedDriverExp">Berlaku s.d. {{ $verifiedDriver && $verifiedDriver->simper_expired_at ? $verifiedDriver->simper_expired_at->format('d/m/Y') : '' }}</span>
                        </p>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-slate-600 hover:text-rose-600 bg-white hover:bg-rose-50 px-3 py-1.5 rounded-lg border border-slate-200 hover:border-rose-200 shadow-xs transition flex items-center space-x-1.5 flex-shrink-0 cursor-pointer" title="Keluar dari akun ini untuk berganti akun driver lain">
                        <i class="fa-solid fa-arrow-right-from-bracket text-[10px] text-slate-400"></i>
                        <span>Bukan Anda? Ganti Akun</span>
                    </button>
                </form>
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
                {{-- Peringatan jika selisih jarak sangat jauh (misal > 200 KM) --}}
                <div id="highDistanceWarning" class="hidden mt-2 p-2.5 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 flex items-start space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5 flex-shrink-0"></i>
                    <span><strong>Perhatian:</strong> Estimasi jarak tempuh tercatat sangat jauh (<span id="highDistanceValue" class="font-bold font-mono">0</span> KM). Pastikan angka yang Anda ketik sesuai dengan total odometer speedometer mobil (bukan trip meter dan tidak kelebihan digit 0).</span>
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
    @endif

    <!-- RIWAYAT PERJALANAN UNIT (CLEAN, MINIMAL, TANPA WATERMARK BACKGROUND) -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6 space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-sm font-bold flex-shrink-0">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <h3 class="text-sm font-black text-slate-900 tracking-tight">Riwayat Perjalanan Unit {{ $vehicle->code }}</h3>
                    <p class="text-[11px] text-slate-500">Catatan pergerakan dan mutasi gerbang terakhir kendaraan ini.</p>
                </div>
            </div>
            <span class="text-[11px] font-semibold text-slate-400 self-start sm:self-center">
                {{ isset($recentActivities) ? $recentActivities->count() : 0 }} Aktivitas Terakhir
            </span>
        </div>

        @if(isset($recentActivities) && $recentActivities->isNotEmpty())
        <div class="overflow-x-auto -mx-5 sm:mx-0">
            <table class="w-full text-xs text-left min-w-[560px]">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                        <th class="py-2.5 px-4">Waktu</th>
                        <th class="py-2.5 px-4">Pengemudi</th>
                        <th class="py-2.5 px-4">Pos Gerbang</th>
                        <th class="py-2.5 px-4 text-center">Arah</th>
                        <th class="py-2.5 px-4 text-right">Odometer</th>
                        <th class="py-2.5 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @foreach($recentActivities as $act)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-bold text-slate-900 block">{{ $act->recorded_at->locale('id')->translatedFormat('d M Y') }}</span>
                            <span class="text-[11px] text-slate-400 block">{{ $act->recorded_at->format('H:i') }} WIB</span>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-bold text-slate-800 text-xs block">{{ $act->driver->name ?? 'Pengemudi' }}</span>
                            <span class="text-[11px] text-slate-500 font-mono block">NIK: {{ $act->driver->nik ?? '-' }}</span>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap text-slate-600 font-medium">
                            {{ $act->gate->name ?? '-' }}
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            @if($act->direction === 'in')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    MASUK
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    KELUAR
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right whitespace-nowrap font-mono">
                            <div class="font-bold text-slate-900">{{ number_format($act->current_km, 0, ',', '.') }} KM</div>
                            <div class="text-[11px] text-emerald-600 font-semibold">+{{ number_format($act->distance, 0, ',', '.') }} KM</div>
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            @if($act->validation_status === 'approved')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" title="Divalidasi oleh: {{ $act->securityGuard->name ?? 'Satpam' }}">
                                    <i class="fa-solid fa-circle-check mr-1 text-emerald-600"></i> Disetujui
                                </span>
                            @elseif($act->validation_status === 'cancelled')
                                @if($act->isCancelledBySystem())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200" title="{{ $act->rejection_note }}">
                                        <i class="fa-solid fa-robot mr-1 text-amber-600"></i> Batal Sistem
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200" title="{{ $act->rejection_note }}">
                                        <i class="fa-solid fa-user-xmark mr-1 text-slate-500"></i> Batal Sopir
                                    </span>
                                @endif
                            @elseif($act->validation_status === 'rejected')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200" title="Alasan: {{ $act->rejection_note }}">
                                    <i class="fa-solid fa-circle-xmark mr-1 text-rose-600"></i> Ditolak
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200 animate-pulse">
                                    <i class="fa-solid fa-clock mr-1 text-amber-600"></i> Menunggu
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="py-5 text-center text-slate-400 text-xs">
            <p>Belum ada riwayat pergerakan gerbang yang tercatat untuk unit {{ $vehicle->code }}.</p>
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const currentKmInput = document.getElementById('current_km');
        const distancePreview = document.getElementById('distancePreview');
        const previousKm = parseFloat("{{ $vehicle->current_km }}") || 0;

        // Kalkulasi jarak real-time & deteksi anomali jarak tinggi
        function calculateDistance() {
            if (!currentKmInput) return;
            const inputKm = parseFloat(currentKmInput.value) || 0;
            const diff = Math.max(0, inputKm - previousKm);
            if (distancePreview) {
                distancePreview.textContent = `Estimasi Jarak: ${diff.toLocaleString('id-ID')} KM`;
            }

            const highWarning = document.getElementById('highDistanceWarning');
            const highVal = document.getElementById('highDistanceValue');
            if (highWarning && highVal) {
                if (diff >= 200) {
                    highVal.textContent = '+' + diff.toLocaleString('id-ID');
                    highWarning.classList.remove('hidden');
                } else {
                    highWarning.classList.add('hidden');
                }
            }
        }

        if (currentKmInput) {
            currentKmInput.addEventListener('input', calculateDistance);
            calculateDistance();
        }
    });
</script>
@endsection
