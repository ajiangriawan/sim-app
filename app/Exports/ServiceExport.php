<?php

namespace App\Exports;

use App\Models\Service;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ServiceExport implements FromCollection, WithHeadings, WithMapping
{
    protected $records;

    public function __construct($records)
    {
        $this->records = $records;
    }

    public function collection()
    {
        return $this->records;
    }

    public function headings(): array
    {
        return [
            'No. Invoice',
            'Tanggal Service',
            'Unit Kendaraan',
            'Bengkel',
            'Total Bayar',
            'Status Deposit',
        ];
    }

    public function map($service): array
    {
        return [
            $service->no_invoice,
            $service->tanggal_service,
            $service->vehicle->no_lambung,
            $service->workshop->nama_bengkel,
            $service->total_service,
            $service->pakai_deposit ? 'Ya' : 'Tidak',
        ];
    }
}
