<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deposit extends Model
{
    //
    protected $fillable = [
        'nama_pihak',
        'tanggal_deposit',
        'jumlah_deposit',
        'keterangan',
        'user_id',
    ];

    public function transaction()
    {
        return $this->hasMany(Transaction::class);
    }

    public static function getSaldoTersedia(): float
    {
        // 1. Hitung total deposit yang pernah masuk
        $totalDeposit = self::sum('jumlah_deposit');

        // 2. Hitung total penggunaan (saldo keluar) dari tabel Service
        // Kita kurangi dengan total bayar di Service yang sudah tersimpan
        $totalPakaiService = \App\Models\Service::sum('total_biaya');

        // 3. Hitung penggunaan dari tabel Transaction (Uang Jalan)
        $totalPakaiHauling = \App\Models\Transaction::sum('uang_jalan');

        return (float) ($totalDeposit - $totalPakaiService - $totalPakaiHauling);
    }
    public static function getSaldoPerNama($nama)
    {
        // Cek jika nama kosong
        if (empty($nama)) {
            return 0;
        }

        // 1. Total Uang Masuk (Deposit) dari orang tersebut
        $totalDeposit = self::where('nama_pihak', $nama)
            ->sum('jumlah_deposit');

        // 2. Total Uang Keluar (Dipakai di Transaksi)
        // Mencari transaksi yang menggunakan saldo atas nama orang ini
        $terpakaiDiTransaksi = \App\Models\Transaction::query()
            ->where('nama_deposit_pilihan', $nama) // Pastikan kolom ini ada di tabel transactions
            ->where('pakai_deposit', true)
            ->where('status', 'selesai') // Hanya hitung yang sudah selesai
            ->sum('uang_jalan');

        // 3. (Opsional) Total Uang Keluar di Service/Maintenance
        // Jika Anda punya fitur service yang memotong saldo, tambahkan di sini:
        /*
        $terpakaiDiService = \App\Models\Service::query()
            ->where('nama_deposit_pilihan', $nama)
            ->sum('total_biaya');
        */
        $terpakaiDiService = 0;

        // 4. Hitung Sisa
        return $totalDeposit - ($terpakaiDiTransaksi + $terpakaiDiService);
    }
}
