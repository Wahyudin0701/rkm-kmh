<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function (User $user) {
            if ($user->role && \Illuminate\Support\Facades\Schema::hasTable('roles')) {
                $role = \Spatie\Permission\Models\Role::where('name', $user->role)->first();
                if ($role && !$user->hasRole($user->role)) {
                    $user->assignRole($role);
                }
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'gate_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function gate(): BelongsTo
    {
        return $this->belongsTo(Gate::class);
    }

    public function securityGuard(): HasOne
    {
        return $this->hasOne(SecurityGuard::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin' || $this->hasRole('super_admin');
    }

    public function isGateOfficer(): bool
    {
        return $this->role === 'gate_officer' || $this->hasRole('gate_officer');
    }

    public function isHseAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->role === 'hse_admin' || $this->hasRole('hse_admin');
    }

    public function isQmaAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->role === 'qma_admin' || $this->hasRole('qma_admin');
    }

    public function isPgAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->role === 'pg_admin' || $this->hasRole('pg_admin');
    }

    public function isAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->role === 'admin' || $this->hasRole('admin');
    }
}
