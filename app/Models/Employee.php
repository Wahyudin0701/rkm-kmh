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
