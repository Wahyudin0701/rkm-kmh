<?php

namespace App\Http\Requests;

use App\Models\Employee;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreVehicleActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Akses form terbuka untuk pengguna/driver via scan QR
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'exists:employees,id'],
            'gate_id' => ['required', 'exists:gates,id'],
            'direction' => ['required', 'in:in,out'],
            'current_km' => ['required', 'numeric', 'min:0'],
            'purpose' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'driver_id.required' => 'Silakan pilih nama pemakai/pengemudi kendaraan.',
            'driver_id.exists' => 'Data pengemudi tidak ditemukan dalam sistem.',
            'gate_id.required' => 'Silakan pilih lokasi pos gerbang.',
            'direction.required' => 'Pilih opsi arah pergerakan gerbang (Masuk / Keluar).',
            'direction.in' => 'Arah gerbang harus berupa Masuk (in) atau Keluar (out).',
            'current_km.required' => 'Nilai odometer KM saat ini wajib diisi.',
            'current_km.numeric' => 'Nilai odometer harus berupa angka.',
            'purpose.required' => 'Rencana pekerjaan / aktivitas kendaraan wajib diisi.',
        ];
    }

    /**
     * Validasi bisnis lanjutan: SIMPER kedaluwarsa, Odometer konsisten, dan Arah In/Out berpasangan.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $vehicleCode = $this->route('code');
            $vehicle = Vehicle::timesheetEligible()
                ->where('code', $vehicleCode)
                ->first();

            if (!$vehicle) {
                $validator->errors()->add('vehicle', 'Kendaraan tidak valid atau tidak diizinkan untuk timesheet mobilisasi.');
                return;
            }

            // 1. Validasi SIMPER Pengemudi (BR-01 & BR-02)
            $driver = Employee::find($this->input('driver_id'));
            if ($driver) {
                if (!$driver->is_operator) {
                    $validator->errors()->add(
                        'driver_id',
                        "Karyawan ({$driver->name}) terdaftar sebagai Bukan Driver dan tidak memiliki izin mengemudi (SIMPER)."
                    );
                } elseif (!$driver->hasValidSimperForVehicle($vehicle)) {
                    $requiredSimper = $vehicle->requiredSimperType ? $vehicle->requiredSimperType->name : ($vehicle->isLightVehicle() ? 'SIMPER LV' : 'SIMPER Dump Truck / Heavy Mobile');
                    $expiredText = $driver->simper_expired_at ? $driver->simper_expired_at->format('d/m/Y') : 'Belum Terdaftar';
                    $validator->errors()->add(
                        'driver_id',
                        "Pengemudi ({$driver->name}) tidak memiliki izin SIMPER aktif yang sesuai untuk mengoperasikan unit {$vehicle->code} (Wajib: {$requiredSimper}). Tanggal SIMPER terakhir: {$expiredText}. Silakan hubungi Divisi HSE."
                    );
                }
            }

            // 2. Validasi Konsistensi Odometer Maju (BR-03)
            $previousKm = (float) $vehicle->current_km;
            $currentKm = (float) $this->input('current_km');

            if ($currentKm < $previousKm) {
                $validator->errors()->add(
                    'current_km',
                    "Nilai kilometer input ({$currentKm} KM) tidak boleh lebih kecil dari kilometer sebelumnya ({$previousKm} KM)."
                );
            }

            // 3. Validasi Urutan Arah Gerbang Berpasangan (BR-04 & BR-05)
            $allowedDirection = $vehicle->determineNextAllowedDirection();
            $inputDirection = $this->input('direction');

            if ($allowedDirection !== 'ANY' && $inputDirection !== $allowedDirection) {
                $expectedText = ($allowedDirection === 'in') ? 'MASUK (In)' : 'KELUAR (Out)';
                $validator->errors()->add(
                    'direction',
                    "Arah pergerakan tidak sesuai riwayat terakhir. Kendaraan ini harus tercatat {$expectedText} terlebih dahulu."
                );
            }
        });
    }
}
