<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nik',
        'name',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
