@extends('layouts.app')

@section('title', 'Dashboard - Admin QMA | PT Kerinci Merangin Hidro')

@section('content')
<div class="space-y-6">
    <!-- Header Admin QMA (Gaya Elegan Sistem IT All Role, Selaras dengan Admin HSE) -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">
                    <i class="fa-solid fa-car mr-1.5"></i> Divisi QMA (Quality Management Asset)
                </span>
                <span class="text-xs text-slate-400">Sistem Administrator</span>
            </div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight mt-1.5">
                Selamat datang, {{ auth()->user()->name }}
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
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
                <span>Buka Log Aktivitas Kendaraan</span>
                <i class="fa-solid fa-arrow-right text-[10px] ml-1"></i>
            </a>
        </div>
    </div>
</div>
@endsection
