@extends('layouts.app')

@section('title', 'Form P2H Heavy Equipment - ' . $vehicle->code)

@section('content')
<div class="max-w-3xl mx-auto space-y-4">
    <!-- Navigasi Kembali & Logo -->
    <div class="flex items-center justify-between pb-1">
        <a href="{{ route('p2h.index') }}" class="inline-flex items-center space-x-1.5 text-xs font-bold text-slate-600 hover:text-[#1b5e20] transition bg-white px-3 py-1.5 rounded-xl border border-slate-200 shadow-sm">
            <i class="fa-solid fa-arrow-left text-[11px]"></i>
            <span>Kembali ke Katalog Unit</span>
        </a>
        <img src="{{ asset('images/logo-kerinci.png') }}" alt="Kerinci Hydropower" class="h-7 w-auto object-contain">
    </div>

    <!-- Header Info Heavy Equipment (Readonly Card - Selaras dengan Form LV) -->
    <div class="bg-gradient-to-r from-slate-800 to-slate-900 text-white p-5 rounded-2xl shadow-sm border border-slate-700">
        <div class="flex items-center justify-between mb-3">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                Heavy Equipment
            </span>
            <span class="text-xs text-slate-400 flex items-center">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse mr-1.5"></span>
                Checksheet: <span class="text-emerald-300 font-bold ml-1">{{ $template->code ?? 'P2H' }}</span>
            </span>
        </div>
        
        <h2 class="text-xl font-bold tracking-tight text-white mb-3">Pemeriksaan Pra-Harian (P2H) Heavy Equipment</h2>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-300 bg-slate-800/80 p-3 rounded-xl border border-slate-700/80">
            <div><span class="text-slate-400 font-medium">Kode Unit:</span> <span class="font-black text-white text-sm ml-1">{{ $vehicle->code }}</span></div>
            <div><span class="text-slate-400 font-medium">Kategori Alat:</span> <span class="font-bold text-amber-300 text-sm ml-1">{{ $vehicle->unit_category ?? ($template->name ?? '-') }}</span></div>
            <div class="col-span-1 sm:col-span-2"><span class="text-slate-400 font-medium">Merek / Tipe Model:</span> <span class="text-white ml-1 font-semibold">{{ $vehicle->brand_type }}</span></div>
            <div><span class="text-slate-400 font-medium">Lokasi Operasi:</span> <span class="text-slate-200 ml-1">{{ $vehicle->location ?? '-' }}</span></div>
            <div><span class="text-slate-400 font-medium">HM Terakhir:</span> <span class="font-bold text-emerald-400 text-sm ml-1">{{ number_format($vehicle->current_km, 1, ',', '.') }} HM</span></div>
        </div>
    </div>

    <!-- Alert Jika Terjadi Masalah SIMPER atau Autentikasi -->
    @if($simperError)
        @if(auth()->user()->isQmaAdmin() || auth()->user()->isSuperAdmin())
            <div class="p-4 sm:p-5 bg-blue-50 border border-blue-200 rounded-2xl text-blue-900 text-xs flex items-start space-x-3.5 shadow-sm">
                <div class="w-9 h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-base flex-shrink-0">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div class="flex-1">
                    <span class="font-bold block text-sm mb-1 text-blue-950">Wewenang Role: Admin QMA (Validator / Verifikator)</span>
                    <p class="leading-relaxed text-blue-800">{{ $simperError }}</p>
                    <div class="mt-3 flex items-center gap-2">
                        <a href="{{ route('qma.p2h.index') }}" 
                           class="inline-flex items-center justify-center space-x-1.5 px-3 py-2 sm:px-3.5 sm:py-1.5 rounded-xl bg-[#1b5e20] text-white font-bold text-xs hover:bg-[#144919] transition shadow-xs flex-shrink-0"
                           title="Buka Verifikasi & Approval P2H di Menu QMA">
                            <i class="fa-solid fa-clipboard-check text-xs"></i>
                            <span class="hidden sm:inline">Buka Verifikasi & Approval P2H di Menu QMA</span>
                        </a>
                        <a href="{{ route('p2h.index') }}" class="inline-flex items-center justify-center space-x-1 px-3 py-2 sm:py-1.5 rounded-xl bg-white border border-blue-200 text-blue-700 font-semibold text-xs hover:bg-blue-100/50 transition">
                            <span>Kembali ke Katalog Unit</span>
                        </a>
                    </div>
                </div>
            </div>
        @else
            <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs flex items-start space-x-3 shadow-sm">
                <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center font-bold text-base flex-shrink-0">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="flex-1">
                    <span class="font-bold block text-sm mb-0.5 text-rose-900">Akses Pengoperasian Tidak Diizinkan</span>
                    <p class="leading-relaxed">{{ $simperError }}</p>
                    <p class="mt-2 text-[11px] font-semibold text-rose-700">Hubungi Divisi HSE untuk pendaftaran atau perpanjangan lisensi SIMPER Anda.</p>
                </div>
            </div>
        @endif
    @endif

    <!-- Card Operator Terverifikasi (Otomatis Terisi dari Akun Login) -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 sm:p-5 flex items-center justify-between gap-3">
        <div class="flex items-center space-x-3 min-w-0">
            @if(auth()->user()->isQmaAdmin() || auth()->user()->isSuperAdmin())
                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-base flex-shrink-0">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center space-x-2 flex-wrap">
                        <span class="text-sm font-bold text-slate-900">{{ auth()->user()->name }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-800">
                            <i class="fa-solid fa-shield-halved mr-1"></i> Admin QMA
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">
                            <i class="fa-solid fa-lock text-slate-400 mr-1"></i> Akun Login Terverifikasi
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5 truncate">
                        <span>Username: {{ auth()->user()->username }}</span> &bull; 
                        <span>Departemen QMA</span> &bull; 
                        <span class="text-blue-700 font-medium">Wewenang: Verifikator / Validator</span>
                    </p>
                </div>
            @else
                <div class="w-10 h-10 rounded-full {{ $employee && $employee->hasValidSimper() ? 'bg-emerald-100 text-[#1b5e20]' : 'bg-rose-100 text-rose-700' }} flex items-center justify-center font-bold text-base flex-shrink-0">
                    <i class="fa-solid {{ $employee && $employee->hasValidSimper() ? 'fa-user-check' : 'fa-user-xmark' }}"></i>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center space-x-2 flex-wrap">
                        <span class="text-sm font-bold text-slate-900">{{ $employee->name ?? auth()->user()->name }}</span>
                        @if($employee && $employee->hasValidSimper())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                <i class="fa-solid fa-shield-check mr-1"></i> SIMPER Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 text-rose-800">
                                <i class="fa-solid fa-circle-xmark mr-1"></i> SIMPER Tidak Aktif
                            </span>
                        @endif
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">
                            <i class="fa-solid fa-lock text-slate-400 mr-1"></i> Akun Login Terverifikasi
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5 truncate">
                        <span>NIK: {{ $employee->nik ?? auth()->user()->username }}</span> &bull; 
                        <span>{{ $employee->division ?? 'Operasional' }}</span> &bull; 
                        <span class="{{ $employee && $employee->hasValidSimper() ? 'text-emerald-700 font-medium' : 'text-rose-700 font-medium' }}">
                            Masa Berlaku: {{ $employee && $employee->simper_expired_at ? $employee->simper_expired_at->format('d/m/Y') : 'Tidak Ada' }}
                        </span>
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- Form Utama P2H Heavy Equipment -->
    <form action="{{ route('p2h.store', ['code' => $vehicle->code]) }}" method="POST" id="p2hForm" class="space-y-4">
        @csrf
        <input type="hidden" name="template_id" value="{{ $template->id }}">

        <!-- Card Input Operasional (Hour Meter) -->
        <input type="hidden" name="shift" value="Shift 1 (Siang)">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 sm:p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <label for="start_hm" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Hour Meter (HM) Saat Ini <span class="text-rose-500">*</span>
                    </label>
                    <p class="text-xs text-slate-400 mt-0.5">HM sebelumnya: <span class="font-semibold text-slate-600">{{ number_format($vehicle->current_km, 1, ',', '.') }} HM</span></p>
                </div>
                <div class="relative w-full sm:w-64">
                    <input type="number" step="0.1" name="start_hm" id="start_hm" 
                           value="{{ old('start_hm', $vehicle->current_km) }}" 
                           min="{{ $vehicle->current_km }}"
                           required 
                           placeholder="0,00"
                           class="w-full rounded-xl border border-slate-300 bg-white pl-4 pr-12 py-2.5 text-sm text-slate-800 font-bold focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition">
                    <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-xs font-extrabold text-slate-400">
                        HM
                    </div>
                </div>
            </div>
        </div>

        @php
            $sectionKeys = [];
            foreach ($groupedItems as $sName => $sItems) {
                $sectionKeys[] = 'sec_' . md5($sName);
            }
        @endphp

        <!-- Section Kuesioner P2H dengan Format Tab Horizontal Kesamping -->
        <div class="space-y-3">
            <!-- Header Judul & Tombol Tandai Semua Baik -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 px-1">
                <div>
                    <h3 class="font-extrabold text-slate-900 text-base">Checklist Pemeriksaan Kelaikan (P2H)</h3>
                    <p class="text-xs text-slate-500">Pilih opsi <strong>Baik</strong> jika kondisi normal, atau <strong>Rusak</strong> jika ada abnormalitas.</p>
                </div>
                <button type="button" onclick="setAllBaik()" class="text-xs font-bold text-[#1b5e20] hover:text-[#144918] bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-3 py-1.5 rounded-xl transition flex items-center justify-center space-x-1.5 shadow-sm self-start sm:self-auto cursor-pointer">
                    <i class="fa-solid fa-check-double text-[11px]"></i>
                    <span>Tandai Semua Baik</span>
                </button>
            </div>

            <!-- 1. Baris Section Horizontal (Dari Kiri ke Kanan Sesuai Format Master Template) -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-thin" id="p2hTabsContainer">
                @foreach($groupedItems as $sectionName => $itemsInSection)
                    @php
                        $secKey = 'sec_' . md5($sectionName);
                        $isFirst = $loop->first;
                    @endphp
                    <button type="button" 
                            onclick="switchP2hSection('{{ $secKey }}')"
                            id="p2h-tab-{{ $secKey }}"
                            data-section-key="{{ $secKey }}"
                            class="p2h-section-tab group inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer shrink-0 {{ $isFirst ? 'bg-[#1b5e20] text-white shadow-xs border border-[#1b5e20]' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300' }}">
                        <span>{{ $sectionName }}</span>
                    </button>
                @endforeach
            </div>

            <!-- 2. Panel Konten List Pertanyaan Berdasarkan Section yang Dipilih -->
            <div id="p2hPanelsContainer" class="space-y-4">
                @foreach($groupedItems as $sectionName => $itemsInSection)
                    @php
                        $secKey = 'sec_' . md5($sectionName);
                        $isFirst = $loop->first;
                        $prevKey = isset($sectionKeys[$loop->index - 1]) ? $sectionKeys[$loop->index - 1] : null;
                        $nextKey = isset($sectionKeys[$loop->index + 1]) ? $sectionKeys[$loop->index + 1] : null;
                    @endphp
                    <div class="p2h-section-content bg-white rounded-none sm:rounded-2xl border border-slate-200 shadow-sm overflow-hidden transition-all duration-200 {{ $isFirst ? '' : 'hidden' }}"
                         id="p2h-content-{{ $secKey }}">
                        
                        <!-- Header Panel Section Aktif Sesuai Referensi Master Template -->
                        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between gap-3 bg-white">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                                    <h3 class="font-bold text-slate-900 text-base sm:text-lg tracking-tight truncate">
                                        {{ $sectionName }}
                                    </h3>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-[#1b5e20] border border-emerald-200/80">
                                        {{ count($itemsInSection) }} Pertanyaan
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5 truncate hidden sm:block">
                                    Daftar butir pertanyaan inspeksi pra-harian pada bagian ini.
                                </p>
                            </div>
                        </div>

                        <!-- List Pertanyaan di Bagian Ini -->
                        <div class="divide-y divide-slate-100 p-2 sm:p-4 space-y-1">
                            @foreach($itemsInSection as $item)
                                <div class="p-3 rounded-xl hover:bg-slate-50/70 transition space-y-2">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                        <div class="flex-1 pr-2">
                                            <div class="flex items-start space-x-2">
                                                <span class="text-xs font-bold text-slate-400 mt-0.5">{{ $item->sequence ?? $loop->iteration }}.</span>
                                                <span class="text-xs font-semibold text-slate-800 leading-snug">{{ $item->title }}</span>
                                            </div>
                                        </div>

                                        @if(in_array($item->question_type, ['choice', 'condition', 'Multiple choice: only one answer', 'Matrix']))
                                            <!-- Opsi Jawaban Radio: Baik & Rusak -->
                                            <div class="flex items-center space-x-2 flex-shrink-0">
                                                <label class="cursor-pointer">
                                                    <input type="radio" 
                                                           name="items[{{ $item->id }}][condition]" 
                                                           value="baik" 
                                                           checked
                                                           onchange="handleConditionChange({{ $item->id }}, 'baik')"
                                                           class="peer sr-only">
                                                    <span class="inline-flex items-center justify-center min-w-[62px] px-3.5 py-1.5 rounded-xl text-xs font-bold border border-slate-300 text-slate-600 bg-white peer-checked:bg-[#1b5e20] peer-checked:text-white peer-checked:border-[#1b5e20] peer-checked:shadow-sm transition">
                                                        <span>Baik</span>
                                                    </span>
                                                </label>

                                                <label class="cursor-pointer">
                                                    <input type="radio" 
                                                           name="items[{{ $item->id }}][condition]" 
                                                           value="rusak" 
                                                           onchange="handleConditionChange({{ $item->id }}, 'rusak')"
                                                           class="peer sr-only">
                                                    <span class="inline-flex items-center justify-center space-x-1.5 min-w-[68px] px-3.5 py-1.5 rounded-xl text-xs font-bold border border-slate-300 text-slate-600 bg-white peer-checked:bg-rose-600 peer-checked:text-white peer-checked:border-rose-600 peer-checked:shadow-sm transition">
                                                        <i class="fa-solid fa-triangle-exclamation text-[11px]"></i>
                                                        <span>Rusak</span>
                                                    </span>
                                                </label>
                                            </div>
                                        @elseif(in_array($item->question_type, ['multiple_choice', 'Multiple choice: multiple answers allowed']))
                                            <!-- Checkbox Pilihan Ganda -->
                                            <div class="flex items-center space-x-2 flex-shrink-0">
                                                <label class="cursor-pointer inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl text-xs font-bold border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 transition">
                                                    <input type="checkbox" 
                                                           name="items[{{ $item->id }}][answer]" 
                                                           value="Sesuai"
                                                           class="rounded text-[#1b5e20] focus:ring-[#1b5e20]">
                                                    <span>Sesuai Standar</span>
                                                </label>
                                            </div>
                                        @elseif(in_array($item->question_type, ['number', 'Numerical Value']))
                                            <div class="sm:w-48 flex-shrink-0">
                                                <input type="number" step="0.1" name="items[{{ $item->id }}][answer]" placeholder="Nilai angka..." class="w-full text-xs font-semibold px-3 py-1.5 rounded-xl border border-slate-300 focus:border-emerald-500">
                                            </div>
                                        @elseif(in_array($item->question_type, ['date', 'Date']))
                                            <div class="sm:w-48 flex-shrink-0">
                                                <input type="date" name="items[{{ $item->id }}][answer]" value="{{ date('Y-m-d') }}" class="w-full text-xs font-semibold px-3 py-1.5 rounded-xl border border-slate-300 focus:border-emerald-500">
                                            </div>
                                        @elseif(in_array($item->question_type, ['datetime', 'Datetime']))
                                            <div class="sm:w-48 flex-shrink-0">
                                                <input type="datetime-local" name="items[{{ $item->id }}][answer]" value="{{ date('Y-m-d\TH:i') }}" class="w-full text-xs font-semibold px-3 py-1.5 rounded-xl border border-slate-300 focus:border-emerald-500">
                                            </div>
                                        @elseif(in_array($item->question_type, ['textarea', 'Multiple Lines Text Box']))
                                            <div class="sm:w-72 flex-shrink-0">
                                                <textarea rows="2" name="items[{{ $item->id }}][answer]" placeholder="Catatan lengkap..." class="w-full text-xs px-3 py-1.5 rounded-xl border border-slate-300 focus:border-emerald-500"></textarea>
                                            </div>
                                        @else
                                            <div class="sm:w-64 flex-shrink-0">
                                                <input type="text" name="items[{{ $item->id }}][answer]" placeholder="Catatan singkat..." class="w-full text-xs px-3 py-1.5 rounded-xl border border-slate-300 focus:border-emerald-500">
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Box Keterangan Tambahan Jika Rusak -->
                                    <div id="notes_box_{{ $item->id }}" class="hidden mt-2 pt-2 border-t border-rose-100/80 transition-all">
                                        <div class="relative flex items-center">
                                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-rose-500 text-xs">
                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                            </span>
                                            <input type="text" 
                                                   name="items[{{ $item->id }}][notes]" 
                                                   placeholder="Jelaskan detail kerusakan pada bagian ini..." 
                                                   class="w-full text-xs pl-10 pr-3.5 py-2 rounded-xl border border-rose-300 bg-rose-50/50 text-rose-900 placeholder:text-rose-400 focus:outline-none focus:border-rose-600 focus:ring-2 focus:ring-rose-500/20 shadow-2xs transition">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Navigasi Cepat Antar Section (Sebelumnya & Selanjutnya) -->
                        @if(count($groupedItems) > 1)
                            <div class="px-4 py-3 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between">
                                @if($prevKey)
                                    <button type="button" 
                                            onclick="switchP2hSection('{{ $prevKey }}')"
                                            class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 hover:bg-slate-100 transition shadow-2xs cursor-pointer">
                                        <i class="fa-solid fa-chevron-left text-[10px]"></i>
                                        <span>Bagian Sebelumnya</span>
                                    </button>
                                @else
                                    <div></div>
                                @endif

                                @if($nextKey)
                                    <button type="button" 
                                            onclick="switchP2hSection('{{ $nextKey }}')"
                                            class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition shadow-2xs cursor-pointer">
                                        <span>Bagian Selanjutnya</span>
                                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                    </button>
                                @else
                                    <span class="text-[11px] font-semibold text-slate-400 flex items-center">
                                        <i class="fa-solid fa-flag-checkered mr-1.5 text-emerald-600"></i> Bagian Terakhir
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Card Catatan Umum Operator -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-3">
            <label for="general_notes" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                Catatan Umum / Keterangan Tambahan Operator
            </label>
            <textarea name="general_notes" id="general_notes" rows="3" placeholder="Tuliskan temuan atau catatan umum lainnya mengenai kondisi Heavy Equipment hari ini..." class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition resize-none">{{ old('general_notes') }}</textarea>
        </div>

        <!-- Tombol Aksi Submit -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 sm:p-5 flex items-center justify-between gap-3">
            <a href="{{ route('p2h.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold py-2.5 px-4 rounded-xl transition text-xs">
                Batal
            </a>

            <button type="submit" 
                    id="btnSubmitP2h" 
                    {{ $simperError ? 'disabled' : '' }}
                    class="bg-[#1b5e20] hover:bg-[#144918] active:scale-[0.99] text-white font-bold py-2.5 px-6 rounded-xl shadow-sm hover:shadow transition flex items-center space-x-2 text-xs sm:text-sm disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed cursor-pointer">
                <i class="fa-solid fa-paper-plane text-xs"></i>
                <span>Kirim Formulir P2H ke QMA</span>
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    function switchP2hSection(secKey) {
        // 1. Update tab styling
        const allTabs = document.querySelectorAll('.p2h-section-tab');
        allTabs.forEach(tab => {
            const key = tab.dataset.sectionKey;
            if (key === secKey) {
                tab.className = 'p2h-section-tab group inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer shrink-0 bg-[#1b5e20] text-white shadow-xs border border-[#1b5e20]';
                tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            } else {
                tab.className = 'p2h-section-tab group inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer shrink-0 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:border-slate-300';
            }
        });

        // 2. Show active panel, hide others
        const allPanels = document.querySelectorAll('.p2h-section-content');
        allPanels.forEach(panel => {
            if (panel.id === 'p2h-content-' + secKey) {
                panel.classList.remove('hidden');
            } else {
                panel.classList.add('hidden');
            }
        });
    }

    function handleConditionChange(itemId, condition) {
        const notesBox = document.getElementById('notes_box_' + itemId);
        if (notesBox) {
            if (condition === 'rusak') {
                notesBox.classList.remove('hidden');
                const input = notesBox.querySelector('input');
                if (input) input.focus();
            } else {
                notesBox.classList.add('hidden');
            }
        }
    }

    function setAllBaik() {
        const radiosBaik = document.querySelectorAll('input[type="radio"][value="baik"]');
        radiosBaik.forEach(r => {
            r.checked = true;
            const match = r.name.match(/items\[(\d+)\]/);
            if (match && match[1]) {
                handleConditionChange(match[1], 'baik');
            }
        });
    }
</script>
@endsection
