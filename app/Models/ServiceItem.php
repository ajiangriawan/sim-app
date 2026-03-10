<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceItem extends Model
{
    protected $fillable = [
        'service_id',
        'nama_item', // Diperbarui dari product_id
        'quantity',
        'harga_satuan',
        'diskon_item',
        'subtotal'
    ];

    /**
     * Relasi ke header Service
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Logika Booted (DIBERSIHKAN)
     * Logika pengurangan stok dihapus karena input sekarang manual.
     */
    protected static function booted()
    {
        // Jika di masa depan Anda butuh log atau kalkulasi tambahan 
        // saat item disimpan di database, Anda bisa menambahkannya di sini.
    }
}