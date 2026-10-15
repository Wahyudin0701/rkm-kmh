@extends('layouts.app')

@section('title', 'Dashboard - Admin QMA | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">
    <!-- Header Admin QMA (Gaya Elegan Sistem IT All Role, Selaras dengan Admin HSE) -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center space-x-1.5 mb-1">
                <span class="text-xs font-bold uppercase tracking-wider text-[#1b5e20]">
                    SISTEM ADMINISTRATOR
                </span>
                <!-- Tombol Info Panduan Halaman Sejajar Kicker -->
                <button type="button" 
                        onclick="openGuideModal()" 
                        class="w-3.5 h-3.5 rounded-full bg-slate-200/90 hover:bg-slate-300 text-slate-500 hover:text-slate-800 text-[8px] inline-flex items-center justify-center font-bold transition shadow-2xs cursor-pointer focus:outline-none" 
                        title="Panduan Halaman Dashboard QMA">
                    <i class="fa-solid fa-info"></i>
                </button>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Selamat datang, {{ auth()->user()->name }}
            </h2>
            <p class="text-xs text-slate-500 mt-1">
                Berikut ringkasan operasional aset kendaraan dan pencatatan log aktivitas armada PT Kerinci Merangin Hidro.
            </p>
        </div>
    </div>

    <!-- Area Konten Dashboard Sementara Dikosongkan (Placeholder Bersih - Sama seperti Admin HSE) -->
    <div class="bg-white rounded-2xl border border-dashed border-slate-300 p-12 text-center shadow-sm">
        <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center mx-auto mb-4 border border-emerald-100 shadow-sm">
            <i class="fa-solid fa-layer-group text-2xl"></i>
        </div>
        <h3 class="text-base font-bold text-slate-800">Konten Dashboard Sedang Disiapkan</h3>
        <p class="text-xs text-slate-400 max-w-lg mx-auto mt-1.5 leading-relaxed">
            Halaman dashboard utama admin QMA untuk sementara dikosongkan sesuai instruksi. Seluruh pengelolaan master kendaraan dialihkan ke menu <strong class="text-slate-600 font-semibold">Data Aset Kendaraan</strong> dan pemantauan pergerakan pos gerbang ke menu <strong class="text-slate-600 font-semibold">Log Aktivitas Kendaraan</strong>.
        </p>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('qma.vehicles.lv') }}" 
               class="inline-flex items-center space-x-2 bg-[#1b5e20] hover:bg-[#144919] text-white font-bold text-xs py-3 px-5 rounded-xl shadow-md hover:shadow-lg transition">
                <i class="fa-solid fa-car"></i>
                <span>Buka Light Vehicle</span>
                <i class="fa-solid fa-arrow-right text-[10px] ml-1"></i>
            </a>

            <a href="{{ route('qma.vehicles.support') }}" 
               class="inline-flex items-center space-x-2 bg-blue-700 hover:bg-blue-800 text-white font-bold text-xs py-3 px-5 rounded-xl shadow-md hover:shadow-lg transition">
                <i class="fa-solid fa-tractor"></i>
                <span>Buka Unit Support</span>
                <i class="fa-solid fa-arrow-right text-[10px] ml-1"></i>
            </a>

            <a href="{{ route('qma.activity.log') }}" 
               class="inline-flex items-center space-x-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs py-3 px-5 rounded-xl shadow-md hover:shadow-lg transition">
                <i class="fa-solid fa-clipboard-list"></i>
                <span>Log Timesheet LV</span>
                <i class="fa-solid fa-arrow-right text-[10px] ml-1"></i>
            </a>

            <a href="{{ route('qma.p2h.index') }}" 
               class="inline-flex items-center space-x-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs py-3 px-5 rounded-xl shadow-md hover:shadow-lg transition">
                <i class="fa-solid fa-clipboard-check"></i>
                <span>Log P2H Unit Support</span>
                <i class="fa-solid fa-arrow-right text-[10px] ml-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL POPUP: PANDUAN HALAMAN (DASHBOARD QMA)                              -->
<!-- ========================================================================= -->
<div id="guideModal" 
     onclick="if(event.target === this) closeGuideModal()" 
     class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
    <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-7 border border-slate-100 transform transition-all my-8">
        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-sm border border-slate-200">
                    <i class="fa-solid fa-info"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 tracking-tight leading-tight">Panduan Halaman</h3>
                    <p class="text-xs text-slate-500">Dashboard Divisi QMA</p>
                </div>
            </div>
            <button type="button" 
                    onclick="closeGuideModal()" 
                    class="text-slate-400 hover:text-slate-600 transition p-1.5 rounded-lg hover:bg-slate-100 cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="mt-4 space-y-4">
            <p class="text-xs text-slate-600 leading-relaxed">
                Selamat datang di <strong>Dashboard QMA (Quality Management Asset)</strong>! Halaman ini adalah pusat navigasi aset armada dan tata kelola kendaraan di PT Kerinci Merangin Hidro. Di halaman ini Anda dapat:
            </p>

            <div class="space-y-3">
                <!-- Fitur 1 (Hijau) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-emerald-200/50">
                        <i class="fa-solid fa-car"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Master Data Light Vehicle (LV)</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Mengelola data kendaraan operasional ringan (LV), nomor lambung, kepemilikan, dan riwayat mutasi armada.
                        </p>
                    </div>
                </div>

                <!-- Fitur 2 (Biru) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-blue-100/70 text-blue-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-blue-200/50">
                        <i class="fa-solid fa-tractor"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Master Data Unit Support</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Memantau armada alat berat, spesifikasi teknis, serta masa berlaku izin kelaikan operasional SILO dan KIR.
                        </p>
                    </div>
                </div>

                <!-- Fitur 3 (Amber) -->
                <div class="bg-slate-50/70 border border-slate-100 rounded-2xl p-3.5 flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-amber-100/70 text-amber-700 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm border border-amber-200/50">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-xs font-bold text-slate-900">Monitoring Log & Summary Aktivitas</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                            Melihat log pergerakan kendaraan riil yang terverifikasi di pos gerbang dan rekapitulasi analitik kilometer perjalanan.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Tombol -->
        <div class="pt-4 mt-5 border-t border-slate-100 flex items-center justify-end">
            <button type="button" 
                    onclick="closeGuideModal()" 
                    class="px-6 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition cursor-pointer shadow-2xs">
                Saya Mengerti
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function openGuideModal() {
        const modal = document.getElementById('guideModal');
        if (modal) {
            modal.classList.remove('hidden');
            document.documentElement.classList.add('modal-open');
            document.body.classList.add('modal-open');
        }
    }

    function closeGuideModal() {
        const modal = document.getElementById('guideModal');
        if (modal) {
            modal.classList.add('hidden');
            document.documentElement.classList.remove('modal-open');
            document.body.classList.remove('modal-open');
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGuideModal();
        }
    });
</script>
@endsection
