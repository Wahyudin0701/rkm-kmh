<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            if ($user->employee) {
                if ($user->employee->activities()->exists() || $user->employee->p2hRecords()->exists()) {
                    throw new \Exception("Tidak dapat menghapus user: Karyawan '{$user->name}' memiliki data audit riwayat operasional kendaraan atau inspeksi P2H.");
                }
                $user->employee->delete();
            }
            if ($user->securityGuard) {
                if ($user->securityGuard->activities()->exists()) {
                    throw new \Exception("Tidak dapat menghapus user: Petugas Satpam '{$user->name}' memiliki riwayat data validasi pos gerbang.");
                }
                $user->securityGuard->delete();
            }
        });
    }

    public function gate(): BelongsTo
    {
        return $this->belongsTo(Gate::class);
    }

    public function securityGuard(): HasOne
    {
        return $this->hasOne(SecurityGuard::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function isGateOfficer(): bool
    {
        return $this->role === 'gate_officer';
    }

    public function isHseAdmin(): bool
    {
        return $this->role === 'hse_admin';
    }

    public function isQmaAdmin(): bool
    {
        return $this->role === 'qma_admin';
    }

    public function isPgAdmin(): bool
    {
        return $this->role === 'pg_admin';
    }

    public function isSuperAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'superadmin']);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin', 'superadmin']);
    }
}
