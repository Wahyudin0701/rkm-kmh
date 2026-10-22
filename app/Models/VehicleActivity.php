<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleActivity extends Model
{
    use HasFactory;

    public const PENDING_TIMEOUT_HOURS = 5;

    protected $fillable = [
        'transaction_no',
        'vehicle_id',
        'driver_id',
        'gate_id',
        'direction',
        'previous_km',
        'current_km',
        'distance',
        'purpose',
        'recorded_at',
        'security_guard_id',
        'validation_status',
        'rejection_note',
        'validated_at',
        'is_corrected',
        'original_current_km',
        'correction_reason',
        'corrected_by_user_id',
        'corrected_at',
    ];

    protected $casts = [
        'previous_km' => 'decimal:2',
        'current_km' => 'decimal:2',
        'distance' => 'decimal:2',
        'original_current_km' => 'decimal:2',
        'is_corrected' => 'boolean',
        'recorded_at' => 'datetime',
        'validated_at' => 'datetime',
        'corrected_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'driver_id');
    }

    public function gate(): BelongsTo
    {
        return $this->belongsTo(Gate::class);
    }

    public function securityGuard(): BelongsTo
    {
        return $this->belongsTo(SecurityGuard::class, 'security_guard_id');
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by_user_id');
    }

    public function locationHistories(): HasMany
    {
        return $this->hasMany(VehicleLocationHistory::class, 'vehicle_activity_id');
    }

    public function isPending(): bool
    {
        return $this->validation_status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->validation_status === 'approved';
    }

    public function isCancelled(): bool
    {
        return $this->validation_status === 'cancelled';
    }

    public function isRejected(): bool
    {
        return $this->validation_status === 'rejected';
    }

    /**
     * Cek apakah tiket perjalanan pending sudah melebihi batas waktu 5 jam.
     */
    public function isOverdue(int $hours = self::PENDING_TIMEOUT_HOURS): bool
    {
        if ($this->validation_status !== 'pending' || !$this->recorded_at) {
            return false;
        }

        return $this->recorded_at->copy()->addHours($hours)->isPast();
    }

    /**
     * Waktu tepat tiket kedaluwarsa (recorded_at + 5 jam).
     */
    public function getExpiresAtAttribute(): ?Carbon
    {
        return $this->recorded_at ? $this->recorded_at->copy()->addHours(self::PENDING_TIMEOUT_HOURS) : null;
    }

    /**
     * Hitung sisa detik sebelum tiket kedaluwarsa.
     */
    public function getRemainingSecondsAttribute(): int
    {
        if ($this->validation_status !== 'pending' || !$this->recorded_at) {
            return 0;
        }

        $expireTime = $this->expires_at;
        return max(0, (int) Carbon::now()->diffInSeconds($expireTime, false));
    }

    /**
     * Batalkan otomatis seluruh tiket pending yang sudah melebihi batas waktu (default 5 jam).
     */
    public static function expireOverduePendingActivities(int $hours = self::PENDING_TIMEOUT_HOURS): int
    {
        $cutoff = Carbon::now()->subHours($hours);

        return static::where('validation_status', 'pending')
            ->where('recorded_at', '<=', $cutoff)
            ->update([
                'validation_status' => 'cancelled',
                'rejection_note' => "Dibatalkan sistem: Melebihi batas waktu verifikasi ({$hours} jam)",
            ]);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('validation_status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('validation_status', 'approved');
    }

    public function scopeForGate(Builder $query, int $gateId): Builder
    {
        return $query->where('gate_id', $gateId);
    }
}
