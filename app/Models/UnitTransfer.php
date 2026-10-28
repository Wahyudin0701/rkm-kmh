<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class UnitTransfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transfer_no',
        'vehicle_id',
        'transfer_date',
        'from_location',
        'from_site_status',
        'to_location',
        'to_site_status',
        'description',
        'reported_by',
        'user_id',
        'transporter_vehicle_id',
        'transporter_unit_name',
        'transporter_driver_id',
        'transporter_driver_name',
        'transporter_description',
        'document_number',
        'document_title',
        'document_file_path',
        'status',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    protected $appends = [
        'document_file_url',
        'to_site_status_label',
        'from_site_status_label',
    ];

    /**
     * Unit Alat Berat / Support Unit yang dipindahkan.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    /**
     * Kendaraan pengangkut (Transporter) dari daftar unit internal jika dipilih.
     */
    public function transporterVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'transporter_vehicle_id');
    }

    /**
     * Driver pengangkut dari data karyawan/operator jika dipilih.
     */
    public function transporterDriver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'transporter_driver_id');
    }

    /**
     * Akun user penginput transfer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isToInsideSite(): bool
    {
        return ($this->to_site_status ?? 'inside_site') === 'inside_site';
    }

    public function isToOutsideSite(): bool
    {
        return ($this->to_site_status ?? 'inside_site') === 'outside_site';
    }

    public function getToSiteStatusLabelAttribute(): string
    {
        return $this->isToOutsideSite() ? 'Di Luar Site' : 'Di Dalam Site';
    }

    public function getFromSiteStatusLabelAttribute(): string
    {
        return ($this->from_site_status ?? 'inside_site') === 'outside_site' ? 'Di Luar Site' : 'Di Dalam Site';
    }

    public function getDocumentFileUrlAttribute(): ?string
    {
        if (!$this->document_file_path) {
            return null;
        }

        if (str_starts_with($this->document_file_path, 'http://') || str_starts_with($this->document_file_path, 'https://')) {
            return $this->document_file_path;
        }

        return asset('storage/' . ltrim($this->document_file_path, '/'));
    }

    /**
     * Generate nomor transaksi transfer unik otomatis.
     * Format: TRF-YYYYMMDD-XXXX
     */
    public static function generateTransferNo(?Carbon $date = null): string
    {
        $targetDate = $date ?? Carbon::now();
        $datePrefix = 'TRF-' . $targetDate->format('Ymd') . '-';

        $lastTransfer = self::where('transfer_no', 'like', $datePrefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastTransfer) {
            $lastSequence = (int) substr($lastTransfer->transfer_no, -4);
            $newSequence = str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT);
        } else {
            $newSequence = '0001';
        }

        return $datePrefix . $newSequence;
    }
}
