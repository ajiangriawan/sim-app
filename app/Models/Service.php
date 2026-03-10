<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Service extends Model
{
    protected $fillable = [
        'vehicle_id',
        'is_vendor',
        'workshop_id',
        'no_invoice',
        'tanggal_service',
        'total_biaya',
        'pakai_deposit',
        'nama_deposit_pilihan',
    ];

    /* ================= RELATION ================= */

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function workshop()
    {
        return $this->belongsTo(Workshop::class);
    }

    public function deposit()
    {
        return $this->belongsTo(Deposit::class);
    }

    public function items()
    {
        return $this->hasMany(ServiceItem::class);
    }

    protected static function booted()
    {
        static::deleting(function ($service) {
            // Kita harus mengambil item satu per satu dan menghapusnya
            // agar event 'deleted' di ServiceItem terpicu untuk mengembalikan stok
            $service->items->each(function ($item) {
                $item->delete();
            });
        });
    }
}
