<?php

namespace App\Http\Controllers\Qma;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehicleController extends Controller
{
    /**
     * Tampilkan halaman dashboard utama Admin QMA (Quality Management Asset).
     */
    public function dashboard(): View
    {
        $totalLv = Vehicle::lightVehicles()->count();
        $totalSupport = Vehicle::unitSupports()->count();
        $totalVehicles = Vehicle::count();

        return view('qma.dashboard', compact('totalLv', 'totalSupport', 'totalVehicles'));
    }

    /**
     * Fallback atau redirect dari route index umum ke halaman Light Vehicle.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('qma.vehicles.lv');
    }

    /**
     * Halaman khusus pengelolaan armada Kendaraan Ringan (Light Vehicle / LV).
     */
    public function indexLv(Request $request): View
    {
        $query = Vehicle::lightVehicles();

        // Filter Status Operasional
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter Pencarian Keyword LV
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('brand_type', 'like', "%{$search}%")
                  ->orWhere('license_plate', 'like', "%{$search}%")
                  ->orWhere('ownership', 'like', "%{$search}%")
                  ->orWhere('division', 'like', "%{$search}%");
            });
        }

        $vehicles = $query->orderBy('code')->paginate(15)->withQueryString();

        // Statistik Ringkasan Khusus LV
        $totalLv = Vehicle::lightVehicles()->count();
        $totalOperation = Vehicle::lightVehicles()->where('status', 'Operation')->count();
        $totalStandby = Vehicle::lightVehicles()->where('status', 'Standby')->count();
        $totalBreakdown = Vehicle::lightVehicles()->where('status', 'Breakdown')->count();

        // Daftar semua kode kendaraan LV untuk validasi unik langsung di form
        $existingCodes = Vehicle::lightVehicles()->pluck('code')->map(fn($c) => strtoupper(trim($c)))->values()->all();

        return view('qma.vehicles_lv', compact(
            'vehicles',
            'totalLv',
            'totalOperation',
            'totalStandby',
            'totalBreakdown',
            'existingCodes'
        ));
    }

    /**
     * Halaman khusus pengelolaan armada Kendaraan Berat (Unit Support / Alat Berat).
     */
    public function indexSupport(Request $request): View
    {
        $query = Vehicle::unitSupports();

        // Filter Status Operasional
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter Kategori Unit Support (Excavator, Crane, Bulldozer, dll)
        if ($request->filled('unit_category')) {
            $query->where('unit_category', $request->input('unit_category'));
        }

        // Filter Pencarian Keyword Unit Support
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('brand_type', 'like', "%{$search}%")
                  ->orWhere('unit_category', 'like', "%{$search}%")
                  ->orWhere('unit_type', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model_number', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('ownership', 'like', "%{$search}%")
                  ->orWhere('division', 'like', "%{$search}%");
            });
        }

        $vehicles = $query->orderBy('code')->paginate(15)->withQueryString();

        // Statistik Ringkasan Khusus Unit Support
        $totalSupport = Vehicle::unitSupports()->count();
        $totalOperation = Vehicle::unitSupports()->where('status', 'Operation')->count();
        $totalStandby = Vehicle::unitSupports()->where('status', 'Standby')->count();
        $totalBreakdown = Vehicle::unitSupports()->where('status', 'Breakdown')->count();

        // Daftar kategori alat berat yang terdaftar untuk opsi filter
        $availableCategories = Vehicle::unitSupports()
            ->whereNotNull('unit_category')
            ->distinct()
            ->pluck('unit_category');

        // Daftar semua kode unit support untuk validasi unik langsung di form
        $existingCodes = Vehicle::unitSupports()->pluck('code')->map(fn($c) => strtoupper(trim($c)))->values()->all();

        return view('qma.vehicles_support', compact(
            'vehicles',
            'totalSupport',
            'totalOperation',
            'totalStandby',
            'totalBreakdown',
            'availableCategories',
            'existingCodes'
        ));
    }

    /**
     * Simpan data aset kendaraan baru (Kendaraan Ringan atau Kendaraan Berat).
     */
    public function store(Request $request): RedirectResponse
    {
        $category = $request->input('category', 'light_vehicle');

        $rules = [
            'code' => ['required', 'string', 'max:50', 'unique:vehicles,code'],
            'category' => ['required', 'in:light_vehicle,unit_support'],
            'brand_type' => ['required', 'string', 'max:150'],
            'license_plate' => ['nullable', 'string', 'max:30'],
            'ownership' => ['nullable', 'string', 'max:150'],
            'division' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:Operation,Standby,Breakdown,Maintenance'],
            'current_km' => ['required', 'numeric', 'min:0'],
        ];

        // Atribut opsional spesifik Unit Support (Kendaraan Berat)
        if ($category === 'unit_support') {
            $rules = array_merge($rules, [
                'unit_category' => ['nullable', 'string', 'max:100'],
                'unit_type' => ['nullable', 'string', 'max:100'],
                'brand' => ['nullable', 'string', 'max:100'],
                'model_number' => ['nullable', 'string', 'max:100'],
                'production_year' => ['nullable', 'integer', 'min:1980', 'max:' . (date('Y') + 1)],
                'location' => ['nullable', 'string', 'max:150'],
                'fuel_consumption' => ['nullable', 'numeric', 'min:0'],
                'silo_document' => ['nullable', 'string', 'max:150'],
                'stnk_document' => ['nullable', 'string', 'max:150'],
                'pajak_document' => ['nullable', 'string', 'max:150'],
                'kir_document' => ['nullable', 'string', 'max:150'],
            ]);
        }

        $validated = $request->validate($rules, [
            'code.required' => 'Kode kendaraan wajib diisi.',
            'code.unique' => 'Kode sudah ada, silakan gunakan kode lain.',
            'brand_type.required' => 'Merek dan spesifikasi/tipe wajib diisi.',
            'current_km.required' => 'Nilai KM / HM awal wajib diisi.',
            'status.required' => 'Status operasional wajib dipilih.',
        ]);

        $vehicle = Vehicle::create($validated);

        $categoryLabel = $vehicle->isLightVehicle() ? 'Light Vehicle (LV)' : 'Unit Support';

        return back()->with('success', "Data {$categoryLabel} [{$vehicle->code}] berhasil ditambahkan ke sistem.");
    }

    /**
     * Perbarui data aset kendaraan (LV atau Unit Support).
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $vehicle = Vehicle::findOrFail($id);

        $rules = [
            'code' => ['required', 'string', 'max:50', Rule::unique('vehicles', 'code')->ignore($vehicle->id)],
            'category' => ['required', 'in:light_vehicle,unit_support'],
            'brand_type' => ['required', 'string', 'max:150'],
            'license_plate' => ['nullable', 'string', 'max:30'],
            'ownership' => ['nullable', 'string', 'max:150'],
            'division' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:Operation,Standby,Breakdown,Maintenance'],
            'current_km' => ['required', 'numeric', 'min:0'],
            'unit_category' => ['nullable', 'string', 'max:100'],
            'unit_type' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model_number' => ['nullable', 'string', 'max:100'],
            'production_year' => ['nullable', 'integer', 'min:1980', 'max:' . (date('Y') + 1)],
            'location' => ['nullable', 'string', 'max:150'],
            'fuel_consumption' => ['nullable', 'numeric', 'min:0'],
            'silo_document' => ['nullable', 'string', 'max:150'],
            'stnk_document' => ['nullable', 'string', 'max:150'],
            'pajak_document' => ['nullable', 'string', 'max:150'],
            'kir_document' => ['nullable', 'string', 'max:150'],
        ];

        $validated = $request->validate($rules, [
            'code.required' => 'Kode kendaraan wajib diisi.',
            'code.unique' => 'Kode sudah ada, silakan gunakan kode lain.',
            'brand_type.required' => 'Merek dan tipe wajib diisi.',
            'current_km.required' => 'Nilai KM / HM wajib diisi.',
        ]);

        $vehicle->update($validated);

        return back()->with('success', "Data unit kendaraan [{$vehicle->code}] berhasil diperbarui.");
    }

    /**
     * Hapus data aset kendaraan dari sistem.
     */
    public function destroy(int $id): RedirectResponse
    {
        $vehicle = Vehicle::findOrFail($id);

        // Cegah penghapusan jika unit sudah tercatat di transaksi pergerakan gerbang
        if ($vehicle->activities()->exists()) {
            return back()->with('error', "Unit {$vehicle->code} tidak dapat dihapus karena telah memiliki catatan riwayat pergerakan di pos gerbang.");
        }

        $code = $vehicle->code;
        $vehicle->delete();

        return back()->with('success', "Unit kendaraan {$code} berhasil dihapus dari sistem.");
    }
}
