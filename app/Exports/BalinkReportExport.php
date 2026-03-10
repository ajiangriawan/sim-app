<?php

namespace App\Exports;

use App\Models\Vehicle;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BalinkReportExport implements WithMultipleSheets
{
    use Exportable;

    protected $startDate;
    protected $endDate;
    protected $isVendor; // 1 untuk Harga Vendor, 0 untuk Harga Pusat

    public function __construct($startDate, $endDate, $isVendor)
    {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->isVendor  = $isVendor;
    }

    public function sheets(): array
    {
        $sheets = [];

        // PERBAIKAN: Hapus filter where('is_vendor'), ambil SEMUA unit
        $vehicles = Vehicle::query()
            ->orderBy('no_lambung')
            ->get();

        foreach ($vehicles as $vehicle) {
            $sheets[] = new VehicleReportExport(
                $vehicle->id,
                $vehicle->no_lambung,
                $this->startDate,
                $this->endDate,
                $this->isVendor
            );
        }

        return $sheets;
    }
}