<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    public const TYPE_KARYAWAN = 'Karyawan';
    public const TYPE_SUKON = 'Sukon';

    protected $fillable = [
        'user_id',
        'nik',
        'name',
        'employee_type',
        'unit_kerja',
        'division',
        'position',
        'is_operator',
        'simper_expired_at',
        'photo_path',
    ];

    protected $casts = [
        'is_operator' => 'boolean',
        'simper_expired_at' => 'date',
    ];

    protected $appends = [
        'photo_url',
    ];

    public function isSukon(): bool
    {
        return strcasecmp($this->employee_type ?? '', self::TYPE_SUKON) === 0;
    }

    public function isKaryawan(): bool
    {
        return empty($this->employee_type) || strcasecmp($this->employee_type, self::TYPE_KARYAWAN) === 0;
    }

    public function scopeSukon(Builder $query): Builder
    {
        return $query->where('employee_type', self::TYPE_SUKON);
    }

    public function scopeKaryawan(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('employee_type', self::TYPE_KARYAWAN)
              ->orWhereNull('employee_type');
        });
    }

    /**
     * Cek apakah SIMPER masih aktif hari ini.
     */
    public function hasValidSimper(): bool
    {
        if (!$this->simper_expired_at) {
            return false;
        }

        return $this->simper_expired_at->isFuture() || $this->simper_expired_at->isToday();
    }

    public function simpers(): HasMany
    {
        return $this->hasMany(EmployeeSimper::class)->with('simperType');
    }

    public function simperTypes(): BelongsToMany
    {
        return $this->belongsToMany(SimperType::class, 'employee_simpers')
            ->withPivot(['simper_number', 'expired_at', 'status', 'notes'])
            ->withTimestamps();
    }

    /**
     * Cek apakah personil memiliki SIMPER aktif untuk kode jenis SIMPER tertentu.
     */
    public function hasValidSimperForType(string $code): bool
    {
        $hasAnySimper = $this->relationLoaded('simpers') ? $this->simpers->isNotEmpty() : $this->simpers()->exists();

        // Jika belum ada record di employee_simpers, gunakan fallback kolom simper_expired_at
        if (! $hasAnySimper) {
            return $this->hasValidSimper();
        }

        $simpers = $this->relationLoaded('simpers') ? $this->simpers : $this->simpers()->with('simperType')->get();

        foreach ($simpers as $s) {
            if ($s->simperType && $s->simperType->code === $code && $s->isValid()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek apakah personil memiliki SIMPER dengan cakupan scope tertentu.
     */
    public function hasValidSimperForScope(string $scope): bool
    {
        $simpers = $this->relationLoaded('simpers') ? $this->simpers : $this->simpers()->with('simperType')->get();
        foreach ($simpers as $s) {
            if ($s->simperType && $s->simperType->category_scope === $scope && $s->isValid()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Cek apakah personil memiliki izin SIMPER yang sah untuk kendaraan tertentu.
     */
    public function hasValidSimperForVehicle(Vehicle $vehicle): bool
    {
        if (! $this->is_operator) {
            return false;
        }

        $hasAnySimper = $this->relationLoaded('simpers') ? $this->simpers->isNotEmpty() : $this->simpers()->exists();

        // Fallback jika belum dimigrasikan ke employee_simpers
        if (! $hasAnySimper) {
            return $this->hasValidSimper();
        }

        // 1. Jika kendaraan mensyaratkan spesifik required_simper_type_id
        if ($vehicle->required_simper_type_id) {
            $simpers = $this->relationLoaded('simpers') ? $this->simpers : $this->simpers()->get();
            foreach ($simpers as $s) {
                if ($s->simper_type_id === $vehicle->required_simper_type_id && $s->isValid()) {
                    return true;
                }
            }
            return false;
        }

        // 2. Berdasarkan kategori kendaraan
        if ($vehicle->isLightVehicle()) {
            return $this->hasValidSimperForType('SIMPER-LV') 
                || $this->hasValidSimperForScope('light_vehicle') 
                || $this->hasValidSimperForScope(SimperType::SCOPE_ALL);
        }

        if ($vehicle->isUnitSupport()) {
            // Cek apakah personil memiliki izin SIMPER dengan scope kategori alat berat ini (misal: 'Dump Truck', 'Excavator', dll.)
            if ($vehicle->unit_category && $this->hasValidSimperForScope($vehicle->unit_category)) {
                return true;
            }
            if ($vehicle->brand_type && $this->hasValidSimperForScope($vehicle->brand_type)) {
                return true;
            }

            if ($vehicle->is_timesheet_mobile) {
                return $this->hasValidSimperForType('SIMPER-DT')
                    || $this->hasValidSimperForType('SIMPER-HEAVY')
                    || $this->hasValidSimperForScope('unit_support_mobile')
                    || $this->hasValidSimperForScope(SimperType::SCOPE_ALL);
            }

            return $this->hasValidSimperForType('SIMPER-HEAVY')
                || $this->hasValidSimperForType('SIMPER-DT')
                || $this->hasValidSimperForScope('unit_support_heavy')
                || $this->hasValidSimperForScope(SimperType::SCOPE_ALL);
        }

        return $this->hasValidSimper();
    }

    public function getPhotoUrlAttribute(): ?string
    {
        $path = $this->photo_path;
        if (empty($path) && $this->user_id) {
            $path = $this->user?->photo_path;
        }

        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    /**
     * Scope untuk mengambil hanya karyawan yang berstatus operator/driver.
     */
    public function scopeOperators(Builder $query): Builder
    {
        return $query->where('is_operator', true);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(VehicleActivity::class, 'driver_id');
    }

    public function p2hRecords(): HasMany
    {
        return $this->hasMany(P2hRecord::class, 'employee_id')->orderByDesc('inspected_at');
    }

    public function supportTimesheets(): HasMany
    {
        return $this->hasMany(UnitSupportTimesheet::class, 'operator_id')->orderByDesc('date');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
