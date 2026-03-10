<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rute extends Model
{
    //
    protected $fillable = [
        'nama_rute',
        'jarak',
        'gaji_pokok',
        'harga_tonase_pusat',
        'harga_tonase_vendor',
        'uang_jalan',
        'uang_makan',
        'uang_jalan',
        'insentif',
        'bahan_bakar',
        'pungli',
    ];

    public function transaction(){
        return $this->hasMany(Transaction::class);
    }
}
