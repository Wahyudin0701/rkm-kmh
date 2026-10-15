<?php

namespace App\Http\Controllers\Qma;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Gate;
use App\Models\P2hTemplate;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Halaman khusus pengelolaan Kategori Kendaraan Berat (Unit Support / Alat Berat).
     */
    public function indexSupport(Request $request): View
    {
        $query = Vehicle::unitSupportCategories()->withCount('units');

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
        $totalSupport = Vehicle::unitSupportCategories()->count();
        $totalUnits = Vehicle::unitSupportPhysical()->count();
        $totalOperation = Vehicle::unitSupportCategories()->where('status', 'Operation')->count();
        $totalStandby = Vehicle::unitSupportCategories()->where('status', 'Standby')->count();
        $totalBreakdown = Vehicle::unitSupportCategories()->where('status', 'Breakdown')->count();

        // Daftar kategori alat berat yang terdaftar untuk opsi filter
        $availableCategories = Vehicle::unitSupportCategories()
            ->whereNotNull('unit_category')
            ->distinct()
            ->pluck('unit_category');

        // Master data daftar divisi perusahaan (gabungan standar + data existing di Employee & Vehicle)
        $standardDivisions = collect([
            'Civil & Inviro',
            'GA',
            'HR',
            'INFORMATION & TECHNOLOGY',
            'K3',
            'Keuangan',
            'Logistik',
            'Mechanical',
            'Operasional',
            'PG',
            'Quality Management Asset (QMA)',
        ]);

        $availableDivisions = $standardDivisions
            ->merge(Employee::whereNotNull('division')->where('division', '!=', '')->pluck('division'))
            ->merge(Vehicle::whereNotNull('division')->where('division', '!=', '')->pluck('division'))
            ->map(fn($d) => trim($d))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Daftar semua kode unit support untuk validasi unik langsung di form
        $existingCodes = Vehicle::unitSupports()->pluck('code')->map(fn($c) => strtoupper(trim($c)))->values()->all();

        return view('qma.vehicles_support', compact(
            'vehicles',
            'totalSupport',
            'totalUnits',
            'totalOperation',
            'totalStandby',
            'totalBreakdown',
            'availableCategories',
            'availableDivisions',
            'existingCodes'
        ));
    }

    /**
     * Halaman daftar unit fisik berdasarkan kategori Unit Support yang dipilih.
     */
    public function indexSupportUnits(Request $request, int $id): View
    {
        $category = Vehicle::unitSupportCategories()->withCount('units')->findOrFail($id);

        $query = $category->units();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('license_plate', 'like', "%{$search}%")
                  ->orWhere('production_year', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('silo_document', 'like', "%{$search}%")
                  ->orWhere('kir_document', 'like', "%{$search}%")
                  ->orWhere('brand_type', 'like', "%{$search}%");
            });
        }

        $units = $query->paginate(15)->withQueryString();

        $totalUnits = $category->units()->count();
        $totalOperation = $category->units()->where('status', 'Operation')->count();
        $totalStandby = $category->units()->where('status', 'Standby')->count();
        $totalBreakdown = $category->units()->where('status', 'Breakdown')->count();
        $totalMaintenance = $category->units()->where('status', 'Maintenance')->count();

        $nextUnitNumber = $category->getNextUnitNumber();
        $nextUnitCode = $category->getNextUnitCode();

        $existingCodes = Vehicle::pluck('code')->map(fn($c) => strtoupper(trim($c)))->values()->all();

        // Master data lokasi penempatan (gabungan pos gerbang aktif & lokasi operasional proyek)
        $masterLocations = Gate::where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->merge(
                Vehicle::whereNotNull('location')
                    ->where('location', '!=', '')
                    ->pluck('location')
            )
            ->map(fn($loc) => trim($loc))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        return view('qma.vehicles_support_units', compact(
            'category',
            'units',
            'totalUnits',
            'totalOperation',
            'totalStandby',
            'totalBreakdown',
            'totalMaintenance',
            'nextUnitNumber',
            'nextUnitCode',
            'existingCodes',
            'masterLocations'
        ));
    }

    /**
     * Simpan penambahan unit fisik baru di bawah kategori Unit Support tertentu.
     */
    public function storeSupportUnit(Request $request, int $id): RedirectResponse
    {
        $category = Vehicle::unitSupportCategories()->findOrFail($id);

        $request->validate([
            'unit_number' => ['nullable', 'string', 'max:20'],
            'code' => ['nullable', 'string', 'max:50'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'license_plate' => ['nullable', 'string', 'max:30'],
            'production_year' => ['nullable', 'integer', 'min:1980', 'max:' . (date('Y') + 1)],
            'status' => ['required', 'string', 'in:Operation,Standby,Breakdown,Maintenance'],
            'current_km' => ['nullable', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:150'],
            'silo_document' => ['nullable', 'string', 'max:150'],
            'kir_document' => ['nullable', 'string', 'max:150'],
            'ownership' => ['nullable', 'string', 'max:150'],
            'division' => ['nullable', 'string', 'max:100'],
        ]);

        return DB::transaction(function () use ($request, $category) {
            // Tentukan nomor unit dan kode unit
            $rawUnitNumber = trim((string)$request->input('unit_number'));
            if ($rawUnitNumber !== '') {
                if (ctype_digit($rawUnitNumber)) {
                    $unitNumber = str_pad($rawUnitNumber, 3, '0', STR_PAD_LEFT);
                } else {
                    $unitNumber = $rawUnitNumber;
                }
                $code = strtoupper(trim($category->code . '-' . $unitNumber));
            } elseif ($request->filled('code')) {
                $code = strtoupper(trim($request->input('code')));
            } else {
                $code = $category->getNextUnitCode();
            }

            // Validasi unik dengan lock for update
            if (Vehicle::where('code', $code)->lockForUpdate()->exists()) {
                return back()->withInput()->with('error', "Kode unit [{$code}] sudah terdaftar di sistem. Silakan gunakan nomor urut lain.");
            }

            $unit = Vehicle::create([
                'code' => $code,
                'category' => 'unit_support',
                'parent_id' => $category->id,
                'p2h_template_id' => $category->p2h_template_id,
                'brand_type' => $category->brand_type,
                'unit_category' => $category->unit_category,
                'unit_type' => $category->unit_type,
                'brand' => $category->brand,
                'model_number' => $category->model_number,
                'license_plate' => $request->input('license_plate'),
                'serial_number' => $request->input('serial_number'),
                'production_year' => $request->input('production_year'),
                'status' => $request->input('status', 'Operation'),
                'current_km' => $request->input('current_km', 0),
                'location' => $request->input('location', $category->location),
                'ownership' => $request->input('ownership', $category->ownership),
                'division' => $request->input('division', $category->division),
                'fuel_consumption' => $category->fuel_consumption,
                'silo_document' => $request->input('silo_document'),
                'kir_document' => $request->input('kir_document'),
            ]);

            return back()->with('success', "Unit fisik [{$unit->code}] berhasil ditambahkan ke kategori {$category->unit_category}.");
        });
    }

    /**
     * Simpan data aset kendaraan baru (Kendaraan Ringan atau Kategori Kendaraan Berat).
     */
    public function store(Request $request): RedirectResponse
    {
        $category = $request->input('category', 'light_vehicle');

        $rules = [
            'code' => ['required', 'string', 'max:50', 'unique:vehicles,code'],
            'category' => ['required', 'in:light_vehicle,unit_support'],
            'brand_type' => ['required', 'string', 'max:150'],
            'license_plate' => ['nullable', 'string', 'max:30'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'ownership' => ['nullable', 'string', 'max:150'],
            'division' => ['nullable', 'string', 'max:100'],
            'status' => [$category === 'unit_support' ? 'nullable' : 'required', 'string', 'in:Operation,Standby,Breakdown,Maintenance'],
            'current_km' => [$category === 'unit_support' ? 'nullable' : 'required', 'numeric', 'min:0'],
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

        if ($category === 'unit_support') {
            $validated['status'] = $validated['status'] ?? 'Operation';
            $validated['current_km'] = $validated['current_km'] ?? 0;
        }

        $vehicle = Vehicle::create($validated);

        if ($vehicle->isSupportCategory()) {
            // Otomatis buatkan Master Template P2H baru (default kosong tanpa butir pertanyaan)
            $template = P2hTemplate::firstOrCreate(
                ['code' => $vehicle->code],
                [
                    'name' => $vehicle->unit_category ?: $vehicle->brand_type,
                    'description' => "Template form inspeksi P2H resmi untuk kategori {$vehicle->brand_type} (Kode: {$vehicle->code}).",
                    'is_active' => true,
                ]
            );
            $vehicle->update(['p2h_template_id' => $template->id]);
        }

        $categoryLabel = $vehicle->isLightVehicle() ? 'Light Vehicle (LV)' : 'Kategori Unit Support';

        return back()->with('success', "Data {$categoryLabel} [{$vehicle->code}] berhasil ditambahkan ke sistem.");
    }

    /**
     * Perbarui data aset kendaraan (LV atau Unit Support).
     * Jika kode Kategori Unit Support diubah, otomatis sinkronisasikan kode seluruh unit fisik di bawahnya serta Master Template P2H.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $vehicle = Vehicle::findOrFail($id);
        $oldCode = $vehicle->code;
        $newCode = strtoupper(trim($request->input('code')));

        $rules = [
            'code' => ['required', 'string', 'max:50', Rule::unique('vehicles', 'code')->ignore($vehicle->id)],
            'category' => ['required', 'in:light_vehicle,unit_support'],
            'brand_type' => ['required', 'string', 'max:150'],
            'license_plate' => ['nullable', 'string', 'max:30'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'ownership' => ['nullable', 'string', 'max:150'],
            'division' => ['nullable', 'string', 'max:100'],
            'status' => [$vehicle->isSupportCategory() ? 'nullable' : 'required', 'string', 'in:Operation,Standby,Breakdown,Maintenance'],
            'current_km' => [$vehicle->isSupportCategory() ? 'nullable' : 'required', 'numeric', 'min:0'],
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

        DB::transaction(function () use ($vehicle, $validated, $oldCode, $newCode) {
            $vehicle->update($validated);

            // Jika ini kategori Unit Support, sinkronkan Master Template P2H dan unit-unit fisik di bawahnya
            if ($vehicle->isSupportCategory()) {
                $template = $vehicle->p2hTemplate ?: P2hTemplate::where('code', $oldCode)->first();
                if ($template) {
                    $template->update([
                        'code' => $newCode,
                        'name' => $vehicle->unit_category ?: $vehicle->brand_type,
                        'description' => "Template form inspeksi P2H resmi untuk kategori {$vehicle->brand_type} (Kode: {$newCode}).",
                    ]);
                    if ($vehicle->p2h_template_id !== $template->id) {
                        $vehicle->update(['p2h_template_id' => $template->id]);
                    }
                } else {
                    $template = P2hTemplate::firstOrCreate(
                        ['code' => $newCode],
                        [
                            'name' => $vehicle->unit_category ?: $vehicle->brand_type,
                            'description' => "Template form inspeksi P2H resmi untuk kategori {$vehicle->brand_type} (Kode: {$newCode}).",
                            'is_active' => true,
                        ]
                    );
                    $vehicle->update(['p2h_template_id' => $template->id]);
                }

                if ($oldCode !== $newCode) {
                    $childUnits = $vehicle->units()->lockForUpdate()->get();
                    foreach ($childUnits as $child) {
                        if (str_starts_with($child->code, $oldCode . '-')) {
                            $suffix = substr($child->code, strlen($oldCode . '-'));
                            $child->code = $newCode . '-' . $suffix;
                        } else {
                            $child->code = $newCode . '-' . $child->code;
                        }
                        $child->p2h_template_id = $template->id;
                        $child->save();
                    }
                }
            }
        });

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

        // Cegah jika memiliki catatan P2H
        if ($vehicle->p2hRecords()->exists()) {
            return back()->with('error', "Aset {$vehicle->code} tidak dapat dihapus karena telah memiliki riwayat inspeksi P2H.");
        }

        // Jika kategori memiliki unit fisik yang punya P2H atau riwayat aktivitas
        if ($vehicle->isSupportCategory()) {
            foreach ($vehicle->units as $u) {
                if ($u->activities()->exists() || $u->p2hRecords()->exists()) {
                    return back()->with('error', "Kategori {$vehicle->code} tidak dapat dihapus karena unit fisik [{$u->code}] di bawahnya telah memiliki riwayat data operasional.");
                }
            }

            // Hapus juga master template jika belum ada riwayat inspeksi
            $template = $vehicle->p2hTemplate ?: P2hTemplate::where('code', $vehicle->code)->first();
            if ($template && !$template->records()->exists()) {
                $template->items()->delete();
                $template->delete();
            }
        }

        $code = $vehicle->code;
        $vehicle->delete();

        return back()->with('success', "Unit/Kategori kendaraan {$code} berhasil dihapus dari sistem.");
    }
}
