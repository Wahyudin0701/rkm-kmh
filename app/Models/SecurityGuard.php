<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SecurityGuard extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'badge_id',
        'name',
        'team',
        'gate_id',
        'user_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'team' => 'integer',
    ];

    public function gate()
    {
        return $this->belongsTo(Gate::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(VehicleActivity::class);
    }
}
