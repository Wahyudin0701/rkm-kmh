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
        'photo_path',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'photo_url',
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

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Dapatkan URL foto profil pengguna (mendukung delegasi otomatis ke foto master Employee).
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (!empty($this->photo_path)) {
            if (str_starts_with($this->photo_path, 'http://') || str_starts_with($this->photo_path, 'https://')) {
                return $this->photo_path;
            }
            return asset('storage/' . ltrim($this->photo_path, '/'));
        }

        // Delegasi ke profil employee tertaut
        if ($this->relationLoaded('employee') && $this->employee) {
            return $this->employee->photo_url;
        }

        $employee = $this->employee()->first();
        if ($employee && $employee->photo_url) {
            return $employee->photo_url;
        }

        // Fallback cari employee dengan NIK sama dengan username
        if (!empty($this->username)) {
            $employeeByNik = Employee::where('nik', $this->username)->first();
            if ($employeeByNik) {
                if (empty($employeeByNik->user_id)) {
                    $employeeByNik->update(['user_id' => $this->id]);
                }
                return $employeeByNik->photo_url;
            }
        }

        return null;
    }

    /**
     * Dapatkan profil Employee yang tertaut, atau cari berdasarkan NIK (username),
     * atau buat profil Employee default jika akun berstatus employee / super admin penguji.
     */
    public function getOrCreateEmployee(): ?Employee
    {
        if ($this->relationLoaded('employee') && $this->employee) {
            return $this->employee;
        }

        $emp = $this->employee()->first();
        if ($emp) {
            return $emp;
        }

        // Cek jika sudah ada master Employee dengan NIK sama dengan username
        $emp = Employee::where('nik', $this->username)->first();
        if ($emp) {
            $emp->update(['user_id' => $this->id]);
            return $emp;
        }

        // Jika user memiliki role employee, wewenang operasional karyawan, atau admin
        if ($this->isEmployee() || $this->can('employee.dashboard') || $this->can('employee.timesheet') || $this->can('employee.p2h') || $this->isSuperAdmin()) {
            return Employee::create([
                'user_id' => $this->id,
                'nik' => $this->username,
                'name' => $this->name,
                'unit_kerja' => 'Umum',
                'division' => 'Operasional',
                'position' => 'Driver Operasional',
                'is_operator' => true,
                'simper_expired_at' => now()->addYears(2),
            ]);
        }

        return null;
    }

    public function locationHistories(): HasMany
    {
        return $this->hasMany(VehicleLocationHistory::class);
    }

    public function isEmployee(): bool
    {
        if ($this->isSuperAdmin()) {
            return false;
        }

        return $this->role === 'employee' 
            || $this->hasRole('employee') 
            || $this->can('employee.dashboard') 
            || $this->can('employee.timesheet') 
            || $this->can('employee.p2h');
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

    public function isSuperAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'superadmin']) || $this->hasRole('super_admin');
    }

    public function isAdmin(): bool
    {
        return $this->isSuperAdmin() || in_array($this->role, ['admin', 'super_admin', 'superadmin']) || $this->hasRole('admin');
    }
}
