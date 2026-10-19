<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleActivity extends Model
{
    use HasFactory;

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
