<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleLocationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'site_status',
        'location',
        'note',
        'source',
        'vehicle_activity_id',
        'user_id',
        'user_name',
        'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(VehicleActivity::class, 'vehicle_activity_id');
    }

    public function isInsideSite(): bool
    {
        return $this->site_status === 'inside_site';
    }

    public function isOutsideSite(): bool
    {
        return $this->site_status === 'outside_site';
    }

    public function getSiteStatusLabelAttribute(): string
    {
        return $this->site_status === 'outside_site' ? 'Di Luar Site' : 'Di Dalam Site';
    }

    public function getSourceLabelAttribute(): string
    {
        return $this->source === 'gate_crossing' ? 'Pos Gerbang (Otomatis)' : 'Manual QMA';
    }
}
