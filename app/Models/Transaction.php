<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    //
    protected $fillable = [
        'vehicle_id',
        'rute_id',
        'no_sjb',
        'tanggal',
        'tonase',
        'harga_tonase_pusat',
        'harga_tonase_vendor',
        'bonus_tonase',
        'pendapatan_kotor',
        'pendapatan_bersih',
        'pakai_deposit',
        'uang_jalan',
        'uang_makan',
        'nama_deposit_pilihan',
        'insentif',
        'status',
        'is_vendor'
    ];

    protected $casts = [
        'pakai_deposit' => 'boolean',
        'tanggal' => 'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function rute()
    {
        return $this->belongsTo(Rute::class);
    }

    public function deposit()
    {
        return $this->belongsTo(Deposit::class);
    }
}
