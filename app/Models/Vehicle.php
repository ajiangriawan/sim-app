<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    //
    protected $fillable = [
        'driver_id',
        'partai',
        'no_lambung',
        'no_plat',
        'kapasitas',
        'tahun',
        'is_vendor',
        'status'
    ];

    protected $casts = [
        'is_vendor' => 'boolean',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function transaction()
    {
        return $this->hasMany(Transaction::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'vehicle_id');
    }
}
