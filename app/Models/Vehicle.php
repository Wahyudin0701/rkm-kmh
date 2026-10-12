<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'category',
        'brand_type',
        'license_plate',
        'ownership',
        'division',
        'status',
        'current_km',
        'photo_path',
        'unit_category',
        'unit_type',
        'brand',
        'model_number',
        'production_year',
        'location',
        'fuel_consumption',
        'silo_document',
        'stnk_document',
        'pajak_document',
        'kir_document',
    ];

    protected $casts = [
        'current_km' => 'decimal:2',
        'fuel_consumption' => 'decimal:2',
        'production_year' => 'integer',
    ];

    public function isLightVehicle(): bool
    {
        return $this->category === 'light_vehicle';
    }

    public function isUnitSupport(): bool
    {
        return $this->category === 'unit_support';
    }

    public function scopeLightVehicles(Builder $query): Builder
    {
        return $query->where('category', 'light_vehicle');
    }

    public function scopeUnitSupports(Builder $query): Builder
    {
        return $query->where('category', 'unit_support');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(VehicleActivity::class);
    }

    /**
     * Aktivitas gerbang terakhir dari kendaraan ini.
     */
    public function latestActivity(): HasOne
    {
        return $this->hasOne(VehicleActivity::class)->latestOfMany('recorded_at');
    }

    /**
     * Tentukan arah gerbang berikutnya yang sah:
     * - 'ANY' jika belum pernah ada riwayat (kendaraan baru)
     * - 'in' jika aktivitas terakhir adalah 'out'
     * - 'out' jika aktivitas terakhir adalah 'in'
     */
    public function determineNextAllowedDirection(): string
    {
        $last = $this->latestActivity;

        if (!$last) {
            return 'ANY';
        }

        return ($last->direction === 'out') ? 'in' : 'out';
    }
}
