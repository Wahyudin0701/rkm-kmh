@extends('layouts.app')

@section('title', 'Verifikasi Pos Gerbang - Private Guard (PG)')

@section('content')
<div class="space-y-6">
    <!-- Header Pos Gerbang -->
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white p-5 rounded-2xl shadow-md border border-slate-700 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30">
                    <i class="fa-solid fa-shield-halved mr-1.5 text-blue-400"></i> Pos Gerbang Aktif
                </span>
                
                @if($currentGuard)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        <i class="fa-solid fa-user-check mr-1.5 text-emerald-400"></i>
                        Petugas: {{ $currentGuard->name }} (ID: {{ $currentGuard->badge_id }}) &bull; Regu {{ $currentGuard->team }}
                    </span>
                @else
                    <span class="text-xs text-slate-400">Akun Pengawas / Bersama</span>
                @endif
            </div>

            <h2 class="text-2xl font-black text-white tracking-tight mt-1.5">
                {{ $gate ? $gate->name : 'Pos Gerbang Belum Dipilih' }}
            </h2>
            <p class="text-xs text-slate-300 mt-1">
                Petugas Satpam yang sedang piket wajib memverifikasi kondisi fisik kendaraan dan angka odometer riil sebelum membuka portal.
            </p>
        </div>

        <div>
            <span class="bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold px-3.5 py-2 rounded-xl text-xs flex items-center shadow-sm">
                <i class="fa-solid fa-clock-rotate-left mr-2 animate-spin text-amber-400"></i>
                Antrean: {{ $pendingActivities->count() }} Kendaraan
            </span>
        </div>
    </div>

    <!-- Antrean Validasi Kendaraan -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center">
                <i class="fa-solid fa-car-side text-emerald-600 mr-2"></i> Kendaraan Menunggu Verifikasi Fisik
            </h3>
            <span class="text-xs text-slate-500">Auto-refresh saat halaman dimuat ulang</span>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($pendingActivities as $act)
                <div class="p-5 hover:bg-slate-50/70 transition flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                    <div class="space-y-2 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-black font-mono bg-slate-900 text-white px-2.5 py-1 rounded-lg">
                                {{ $act->vehicle->code }}
                            </span>
                            <span class="text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded font-mono">
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

                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs text-slate-600 pt-1">
                            <div><span class="text-slate-400 block text-[11px]">Unit:</span> <strong class="text-slate-800">{{ $act->vehicle->brand_type }}</strong></div>
                            <div><span class="text-slate-400 block text-[11px]">Pengemudi:</span> <strong class="text-slate-800">{{ $act->driver->name }}</strong> ({{ $act->driver->division ?? '-' }})</div>
                            <div><span class="text-slate-400 block text-[11px]">KM Odometer Input:</span> <strong class="text-emerald-700 text-sm font-mono font-bold">{{ number_format($act->current_km, 0, ',', '.') }} KM</strong></div>
                            <div><span class="text-slate-400 block text-[11px]">Jarak Sesi Ini:</span> <strong class="text-slate-800 font-mono">+{{ number_format($act->distance, 0, ',', '.') }} KM</strong></div>
                        </div>

                        <div class="text-xs bg-slate-50 p-2.5 rounded-xl border border-slate-200 text-slate-700">
                            <span class="font-bold text-slate-800">Keperluan Kerja:</span> {{ $act->purpose }}
                        </div>
                    </div>

                    <!-- Tombol Aksi Verifikasi Satpam -->
                    <div class="flex items-center space-x-2 self-end lg:self-center">
                        <button onclick="openVerificationModal({{ $act->id }}, '{{ $act->vehicle->code }}', '{{ $act->current_km }}', '{{ $act->driver->name }}')" 
                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2.5 px-4 rounded-xl shadow-sm transition flex items-center space-x-1.5">
                            <i class="fa-solid fa-clipboard-check"></i>
                            <span>Verifikasi Satpam</span>
                        </button>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-slate-400">
                    <i class="fa-solid fa-circle-check text-4xl text-emerald-400 mb-2 block"></i>
                    <p class="text-sm font-bold text-slate-700">Tidak ada kendaraan yang menunggu verifikasi saat ini.</p>
                    <span class="text-xs text-slate-400">Semua aktivitas kendaraan di pos gerbang ini telah diperiksa.</span>
                </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Modal Dialog Verifikasi Satpam -->
<div id="verificationModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200 animate-in fade-in zoom-in duration-150">
        <div class="bg-[#06213f] text-white p-4 flex justify-between items-center">
            <h3 class="text-sm font-bold flex items-center">
                <i class="fa-solid fa-user-shield text-emerald-400 mr-2"></i> Pemeriksaan Fisik Satpam
            </h3>
            <button onclick="closeVerificationModal()" class="text-slate-400 hover:text-white transition">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <div class="p-5 space-y-4">
            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs space-y-1.5">
                <div><span class="text-slate-500">Unit:</span> <strong id="modalUnitCode" class="text-slate-800 font-mono font-bold"></strong></div>
                <div><span class="text-slate-500">Driver:</span> <strong id="modalDriverName" class="text-slate-800 font-bold"></strong></div>
                <div><span class="text-slate-500">KM Odometer Input:</span> <strong id="modalKmInput" class="text-emerald-700 font-mono text-sm font-bold"></strong> KM</div>
            </div>

            <!-- Identitas Petugas Satpam Pemeriksa -->
            @if($currentGuard)
                <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200 text-xs flex items-center justify-between">
                    <div>
                        <span class="text-slate-500 block text-[10px] font-semibold uppercase tracking-wider">Petugas Pemeriksa:</span>
                        <strong class="text-emerald-800 font-bold text-sm">{{ $currentGuard->name }}</strong>
                        <div class="text-slate-600 text-[11px] font-mono mt-0.5">
                            ID: {{ $currentGuard->badge_id }} &bull; Regu {{ $currentGuard->team }}
                        </div>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                        <i class="fa-solid fa-circle-check text-base"></i>
                    </div>
                </div>
            @endif

            <!-- Form Approve -->
            <form id="approveForm" method="POST" action="">
                @csrf
                
                @if(!$currentGuard)
                    <div class="mb-4">
                        <label for="security_guard_id" class="block text-xs font-bold text-slate-700 mb-1">
                            Pilih Nama Satpam Pemeriksa <span class="text-rose-500">*</span>
                        </label>
                        <select name="security_guard_id" id="security_guard_id" required class="w-full text-xs rounded-xl border-slate-200 border p-2.5 bg-slate-50/50 hover:bg-white focus:bg-white">
                            <option value="" disabled selected>-- Pilih Petugas Satpam --</option>
                            @foreach($guards as $guard)
                                <option value="{{ $guard->id }}">{{ $guard->name }} (Regu {{ $guard->team }})</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="flex items-center space-x-2 pt-2 border-t border-slate-100">
                    <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2.5 px-4 rounded-xl transition flex items-center justify-center space-x-1.5 shadow-sm">
                        <i class="fa-solid fa-check"></i>
                        <span>Setujui (Buka Portal)</span>
                    </button>
                    <button type="button" onclick="showRejectSection()" class="bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs py-2.5 px-3.5 rounded-xl transition">
                        Tolak
                    </button>
                </div>
            </form>

            <!-- Section Form Reject (Hidden by default) -->
            <form id="rejectForm" method="POST" action="" class="hidden border-t border-rose-100 pt-3">
                @csrf
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-rose-800 mb-1">Alasan Penolakan <span class="text-rose-500">*</span></label>
                        <textarea name="rejection_note" required rows="2" placeholder="Contoh: Angka KM di dashboard tidak cocok, driver tidak memiliki SIMPER aktif..." class="w-full text-xs rounded-xl border-rose-300 border p-2.5 focus:ring-rose-500 focus:border-rose-500 bg-rose-50/20"></textarea>
                    </div>
                    @if(!$currentGuard)
                        <input type="hidden" name="security_guard_id" id="rejectGuardId">
                    @endif
                    <button type="submit" class="w-full bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs py-2.5 px-3 rounded-xl transition shadow-sm">
                        Konfirmasi Tolak Kendaraan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let currentActivityId = null;

    function openVerificationModal(activityId, unitCode, kmInput, driverName) {
        currentActivityId = activityId;
        document.getElementById('modalUnitCode').textContent = unitCode;
        document.getElementById('modalKmInput').textContent = Number(kmInput).toLocaleString('id-ID');
        document.getElementById('modalDriverName').textContent = driverName;

        document.getElementById('approveForm').action = `/pg/gate-inspection/${activityId}/approve`;
        document.getElementById('rejectForm').action = `/pg/gate-inspection/${activityId}/reject`;

        document.getElementById('rejectForm').classList.add('hidden');
        document.getElementById('verificationModal').classList.remove('hidden');
    }

    function closeVerificationModal() {
        document.getElementById('verificationModal').classList.add('hidden');
    }

    function showRejectSection() {
        const guardSelect = document.getElementById('security_guard_id');
        const rejectGuardInput = document.getElementById('rejectGuardId');
        
        if (guardSelect && rejectGuardInput) {
            if (!guardSelect.value) {
                alert('Silakan pilih nama petugas Satpam terlebih dahulu sebelum menolak!');
                guardSelect.focus();
                return;
            }
            rejectGuardInput.value = guardSelect.value;
        }

        document.getElementById('rejectForm').classList.toggle('hidden');
    }
</script>
@endsection
