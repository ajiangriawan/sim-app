<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
    protected $fillable = ['workshop_id', 'nama_barang', 'harga_barang',  'stok'];
    
    public function workshop()
    {
        return $this->belongsTo(Workshop::class, );
    }

    public function service()
    {
        return $this->hasMany(Service::class);
    }
}
