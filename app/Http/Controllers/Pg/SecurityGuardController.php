<?php

namespace App\Http\Controllers\Pg;

use App\Http\Controllers\Controller;
use App\Models\SecurityGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityGuardController extends Controller
{
    /**
     * Tampilkan daftar petugas Satpam.
     */
    public function index(): View
    {
        $guards = SecurityGuard::orderBy('name')->get();
        return view('pg.guards_index', compact('guards'));
    }

    /**
     * Simpan data petugas Satpam baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ], [
            'name.required' => 'Nama petugas Satpam wajib diisi.',
        ]);

        SecurityGuard::create([
            'name' => $request->input('name'),
            'is_active' => true,
        ]);

        return back()->with('success', 'Petugas Satpam berhasil ditambahkan ke dalam sistem.');
    }

    /**
     * Ubah status aktif/nonaktif Satpam.
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        $guard = SecurityGuard::findOrFail($id);
        $guard->update([
            'is_active' => !$guard->is_active,
        ]);

        $status = $guard->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Status petugas Satpam ({$guard->name}) berhasil {$status}.");
    }
}
