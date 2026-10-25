<?php

namespace App\Http\Controllers\Hse;

use App\Http\Controllers\Controller;
use App\Models\SimperType;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SimperTypeController extends Controller
{
    /**
     * Tampilkan daftar master jenis SIMPER.
     */
    public function index(Request $request): View
    {
        $today = Carbon::today()->toDateString();

        $simperTypes = SimperType::withCount([
            'employeeSimpers as active_holders_count' => function ($q) use ($today) {
                $q->where('status', 'active')
                  ->whereNotNull('expired_at')
                  ->where('expired_at', '>=', $today);
            },
            'employeeSimpers as total_holders_count',
        ])
        ->orderBy('name')
        ->get();

        $categoryScopes = SimperType::getAvailableScopes();

        $heavyCategories = Vehicle::unitSupportCategories()
            ->orderBy('unit_category')
            ->get(['id', 'code', 'brand_type', 'unit_category']);

        return view('hse.simper_types_index', compact('simperTypes', 'categoryScopes', 'heavyCategories'));
    }

    /**
     * Tambah jenis SIMPER baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validScopes = array_keys(SimperType::getAvailableScopes());

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:simper_types,code'],
            'name' => ['required', 'string', 'max:100'],
            'category_scope' => ['required', 'string', Rule::in($validScopes)],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $code = strtoupper(trim($validated['code']));

        SimperType::create([
            'code' => $code,
            'name' => trim($validated['name']),
            'category_scope' => $validated['category_scope'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Jenis SIMPER '{$validated['name']}' ({$code}) berhasil ditambahkan.");
    }

    /**
     * Perbarui data jenis SIMPER.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $simperType = SimperType::findOrFail($id);
        $validScopes = array_keys(SimperType::getAvailableScopes());

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('simper_types', 'code')->ignore($simperType->id)],
            'name' => ['required', 'string', 'max:100'],
            'category_scope' => ['required', 'string', Rule::in($validScopes)],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $simperType->update([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'category_scope' => $validated['category_scope'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Jenis SIMPER '{$simperType->name}' berhasil diperbarui.");
    }

    /**
     * Hapus jenis SIMPER.
     */
    public function destroy(int $id): RedirectResponse
    {
        $simperType = SimperType::withCount('employeeSimpers')->findOrFail($id);

        if ($simperType->employee_simpers_count > 0) {
            return back()->with('error', "Jenis SIMPER '{$simperType->name}' tidak dapat dihapus karena masih digunakan oleh {$simperType->employee_simpers_count} personil. Anda dapat menonaktifkan statusnya.");
        }

        $name = $simperType->name;
        $simperType->delete();

        return back()->with('success', "Jenis SIMPER '{$name}' berhasil dihapus.");
    }
}
