@extends('layouts.app')

@section('title', 'Dashboard Admin - Super Admin | PT Kerinci Merangin Hidro')

@section('content')
<div class="w-full space-y-6">

    <!-- Header Judul Halaman -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                Dashboard Admin
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Pusat ikhtisar operasional dan analitik sistem terintegrasi PT Kerinci Merangin Hidro.
            </p>
        </div>
    </div>

    <!-- Konten Dashboard Kosong (Placeholder Persiapan Awal) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-12 sm:p-16 text-center shadow-xs">
        <div class="w-16 h-16 mx-auto rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mb-4">
            <i class="fa-solid fa-chart-pie"></i>
        </div>
        <h3 class="text-base sm:text-lg font-bold text-slate-800">
            Halaman Dashboard Sedang Disiapkan
        </h3>
        <p class="text-xs sm:text-sm text-slate-400 max-w-md mx-auto mt-1 mb-6">
            Komponen analitik, grafik metrik aktivitas, dan rekapitulasi data sistem saat ini masih dalam tahap perancangan awal.
        </p>
        <a href="{{ route('superadmin.roles.index') }}" 
           class="inline-flex items-center space-x-2 px-4 py-2.5 rounded-xl bg-[#1b5e20] hover:bg-[#144919] text-white text-xs font-bold transition shadow-sm">
            <i class="fa-solid fa-users-gear text-xs"></i>
            <span>Buka Manajemen Role</span>
        </a>
    </div>

</div>
@endsection
