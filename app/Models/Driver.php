<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    //
    protected $fillable = [
        'nik',
        'nama',
        'alamat',
        'no_telp',
        'status',
    ];
    public function vehicle(){
        return $this->hasOne(Vehicle::class);
    }
}
