<?php

namespace App\Exports;

use App\Models\Service;
use App\Models\Vehicle; // Pastikan import model Vehicle
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ServiceReportExport implements WithMultipleSheets
{
    protected $dari, $sampai, $workshopId;

    public function __construct($dari, $sampai, $workshopId = null)
    {
        $this->dari = $dari;
        $this->sampai = $sampai;
        $this->workshopId = $workshopId;
    }

    public function sheets(): array
    {
        $sheets = [];

        // Ambil daftar No Lambung yang memiliki transaksi di periode tersebut
        $vehicles = Vehicle::whereHas('services', function ($query) {
            if ($this->dari) $query->whereDate('tanggal_service', '>=', $this->dari);
            if ($this->sampai) $query->whereDate('tanggal_service', '<=', $this->sampai);
            if ($this->workshopId) $query->where('workshop_id', $this->workshopId);
        })->get();

        foreach ($vehicles as $vehicle) {
            // Buat satu sheet untuk setiap kendaraan
            $sheets[] = new VehicleServiceSheet(
                $vehicle, 
                $this->dari, 
                $this->sampai, 
                $this->workshopId
            );
        }

        return $sheets;
    }
}