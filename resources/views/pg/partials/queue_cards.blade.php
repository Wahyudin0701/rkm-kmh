@forelse($pendingActivities as $act)
    <div class="queue-card bg-white rounded-2xl border-2 border-amber-200/80 hover:border-amber-400 p-4 sm:p-5 shadow-xs hover:shadow-sm transition-all"
         data-search="{{ strtolower($act->vehicle->code . ' ' . $act->vehicle->license_plate . ' ' . $act->driver->name . ' ' . $act->vehicle->brand_type . ' ' . $act->purpose) }}">
        
        <!-- Baris 1: Identitas Utama Kendaraan & Arah -->
        <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100">
            <div class="flex items-center flex-wrap gap-2">
                <!-- Plat Nomor -->
                <span class="bg-amber-50 text-amber-900 border border-amber-300 font-mono font-black text-xs sm:text-sm px-2.5 py-0.5 rounded-xl">
                    {{ $act->vehicle->license_plate ?? 'Tanpa Plat' }}
                </span>

                <!-- Badge Arah (Tanpa Icon) -->
                @if(strtolower($act->direction) === 'in')
                    <span class="bg-blue-100 text-blue-800 border border-blue-300 text-xs font-black px-2.5 py-0.5 rounded-xl shadow-2xs">
                        MASUK (IN)
                    </span>
                @else
                    <span class="bg-amber-100 text-amber-800 border border-amber-300 text-xs font-black px-2.5 py-0.5 rounded-xl shadow-2xs">
                        KELUAR (OUT)
                    </span>
                @endif
            </div>

            <!-- Waktu Pengajuan Driver -->
            <div class="text-[11px] font-mono text-slate-400 flex items-center gap-1">
                <i class="fa-regular fa-clock text-slate-400"></i>
                <span>Diinput: {{ $act->recorded_at->format('H:i') }} WIB</span>
            </div>
        </div>

        <!-- Baris 2: Rincian Visual Driver, Kendaraan LV, dan Odometer -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3">
            <!-- Pengemudi (Driver) -->
            <div class="flex items-center space-x-3 bg-slate-50/80 p-2.5 rounded-xl border border-slate-200/80">
                @if($act->driver && $act->driver->photo_url)
                    <div class="relative group/pic flex-shrink-0">
                        <img src="{{ $act->driver->photo_url }}" 
                             alt="{{ $act->driver->name }}" 
                             onclick="openImageLightbox('{{ $act->driver->photo_url }}', 'Foto Driver: {{ addslashes($act->driver->name) }} ({{ $act->driver->nik ?? '' }})')"
                             class="w-12 h-12 rounded-xl object-cover ring-2 ring-emerald-500/30 shadow-2xs cursor-pointer hover:opacity-90 transition"
                             title="Klik untuk memperbesar foto driver">
                        <span class="absolute -bottom-1 -right-1 bg-emerald-600 text-white text-[9px] w-4 h-4 rounded-full flex items-center justify-center shadow-xs pointer-events-none" title="Foto Driver">
                            <i class="fa-solid fa-id-badge"></i>
                        </span>
                    </div>
                @else
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-[#1b5e20] flex items-center justify-center font-black text-sm flex-shrink-0 ring-1 ring-emerald-200">
                        {{ strtoupper(substr($act->driver->name ?? 'D', 0, 1)) }}
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Pengemudi:</span>
                    <span class="text-xs font-bold text-slate-900 truncate block">{{ $act->driver->name }}</span>
                    <span class="text-[10px] text-slate-500 block truncate">{{ $act->driver->division ?? 'Umum' }} @if($act->driver->nik) &bull; {{ $act->driver->nik }} @endif</span>
                </div>
            </div>

            <!-- Tipe Unit Mobil (LV) -->
            <div class="flex items-center space-x-3 bg-slate-50/80 p-2.5 rounded-xl border border-slate-200/80">
                @if($act->vehicle && $act->vehicle->photo_url)
                    <div class="relative group/pic flex-shrink-0">
                        <img src="{{ $act->vehicle->photo_url }}" 
                             alt="{{ $act->vehicle->brand_type }}" 
                             onclick="openImageLightbox('{{ $act->vehicle->photo_url }}', 'Foto Unit Kendaraan: {{ addslashes($act->vehicle->brand_type . ' (' . ($act->vehicle->license_plate ?? $act->vehicle->code) . ')') }}')"
                             class="w-12 h-12 rounded-xl object-cover ring-2 ring-blue-500/30 shadow-2xs cursor-pointer hover:opacity-90 transition"
                             title="Klik untuk memperbesar foto unit kendaraan">
                        <span class="absolute -bottom-1 -right-1 bg-blue-600 text-white text-[9px] w-4 h-4 rounded-full flex items-center justify-center shadow-xs pointer-events-none" title="Foto LV">
                            <i class="fa-solid fa-car"></i>
                        </span>
                    </div>
                @else
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-base flex-shrink-0 ring-1 ring-blue-200">
                        <i class="fa-solid fa-car-side"></i>
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Kendaraan (LV):</span>
                    <span class="text-xs font-bold text-slate-900 truncate block">{{ $act->vehicle->brand_type }}</span>
                    <span class="text-[10px] text-slate-500 block truncate">{{ $act->vehicle->code }} @if($act->vehicle->ownership) &bull; {{ $act->vehicle->ownership }} @endif</span>
                </div>
            </div>

            <!-- Odometer / Spedometer KM (Tanpa Highlight) -->
            <div class="flex items-center space-x-3 bg-slate-50/80 p-2.5 rounded-xl border border-slate-200/80">
                <div class="w-12 h-12 rounded-xl bg-slate-200/80 text-slate-700 flex items-center justify-center text-base flex-shrink-0 ring-1 ring-slate-300">
                    <i class="fa-solid fa-gauge-high"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Spedometer / KM:</span>
                    <span class="text-xs font-bold font-mono text-slate-900 truncate block">
                        {{ number_format($act->current_km, 0, ',', '.') }} KM
                    </span>
                    <span class="text-[10px] text-slate-500 block truncate">
                        @if($act->distance > 0)
                            +{{ number_format($act->distance, 0, ',', '.') }} KM sesi ini
                        @else
                            Angka lapor driver
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- Baris 3: Keperluan Perjalanan -->
        @if($act->purpose)
            <div class="mt-2.5 text-xs text-slate-600 bg-slate-50/50 p-2.5 rounded-xl border border-slate-100 flex items-start gap-2">
                <i class="fa-solid fa-file-lines text-slate-400 text-xs mt-0.5"></i>
                <div class="min-w-0 flex-1">
                    <strong class="text-slate-800">Tujuan / Keperluan:</strong>
                    <span class="text-slate-600 ml-1">{{ $act->purpose }}</span>
                </div>
            </div>
        @endif

        <!-- Baris 4: Tombol Verifikasi Langsung di Card (Tanpa Popup & Tanpa Fitur Tolak) -->
        <div class="mt-3.5 pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2.5">
            <span class="text-[11px] text-slate-400 font-medium hidden sm:inline flex items-center gap-1.5">
                <i class="fa-solid fa-shield-check text-[#1b5e20]"></i>
                <span>Cek fisik kendaraan lalu klik verifikasi untuk membuka portal</span>
            </span>

            @can('gate.approve')
            <form action="{{ route('pg.gate.approve', $act->id) }}" method="POST" class="w-full sm:w-auto m-0">
                @csrf
                @if($currentGuard)
                    <input type="hidden" name="security_guard_id" value="{{ $currentGuard->id }}">
                @elseif($guards->isNotEmpty())
                    <input type="hidden" name="security_guard_id" value="{{ $guards->first()->id }}">
                @endif

                <button type="submit" 
                        onclick="this.disabled=true; this.innerHTML='<i class=\'fa-solid fa-spinner fa-spin text-xs\'></i> Memverifikasi...'; this.form.submit();"
                        class="w-full sm:w-auto px-6 py-2.5 bg-[#1b5e20] hover:bg-[#144919] active:bg-[#0f3613] text-white font-bold text-xs sm:text-sm rounded-xl shadow-xs hover:shadow transition flex items-center justify-center space-x-2 cursor-pointer">
                    <i class="fa-solid fa-clipboard-check text-sm"></i>
                    <span>Verifikasi</span>
                </button>
            </form>
            @else
            <span class="text-xs text-slate-400 italic">Tidak memiliki izin verifikasi</span>
            @endcan
        </div>

    </div>
@empty
    <!-- Tampilan Bersih & Ramah Saat Antrean Kosong -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-8 sm:p-12 text-center shadow-xs">
        <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-[#1b5e20] border border-emerald-200 mx-auto flex items-center justify-center text-3xl mb-3 shadow-2xs">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <h3 class="text-base font-extrabold text-slate-900 tracking-tight">
            Portal Pos Gerbang Siap
        </h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 leading-relaxed">
            Tidak ada kendaraan yang menunggu verifikasi saat ini. Saat pengemudi memindai QR mobil dan mengirim form timesheet, data akan otomatis muncul di sini secara realtime.
        </p>
        <div class="mt-4 inline-flex items-center space-x-2 text-xs font-semibold text-emerald-800 bg-emerald-50 px-3 py-1.5 rounded-full border border-emerald-200/80">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
            <span>Sistem memantau antrean otomatis secara realtime</span>
        </div>
    </div>
@endforelse
