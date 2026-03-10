<?php

namespace App\Exports;

use App\Models\Service;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ServiceReportExport implements 
    FromQuery, 
    WithHeadings, 
    WithMapping, 
    WithStyles, 
    ShouldAutoSize, 
    WithColumnFormatting,
    WithEvents
{
    protected $dari, $sampai, $workshopId;
    protected $rowNumber = 0;

    public function __construct($dari, $sampai, $workshopId = null)
    {
        $this->dari = $dari;
        $this->sampai = $sampai;
        $this->workshopId = $workshopId;
    }

    public function query()
    {
        $query = Service::query()->with([ 'vehicle', 'workshop']);

        if ($this->dari) $query->whereDate('tanggal_service', '>=', $this->dari);
        if ($this->sampai) $query->whereDate('tanggal_service', '<=', $this->sampai);
        if ($this->workshopId) $query->where('workshop_id', $this->workshopId);

        return $query->orderBy('tanggal_service', 'asc');
    }

    public function headings(): array
    {
        return [
            ['LAPORAN SERVICE & MAINTENANCE'], // Baris Judul 1
            ['PERIODE: ' . ($this->dari ?? 'Awal') . ' s/d ' . ($this->sampai ?? 'Akhir')], // Baris Judul 2
            [], // Baris Kosong
            [
                'NO',
                'TGL INVOICE',
                'NO INVOICE',
                'NO LAMBUNG',
                'TGL PEMAKAIAN',
                'JENIS BARANG',
                'QTY',
                'HARGA',
                'DISCONT',
                'OWNER',
                'JUMLAH',
                'TOTAL TAGIHAN',
            ]
        ];
    }

    public function map($service): array
    {
        $rows = [];
        $totalTagihanNota = $service->items->sum(function ($item) {
            return ($item->quantity * $item->harga_satuan) - $item->diskon_item;
        });

        $isFirstItem = true;

        foreach ($service->items as $item) {
            $this->rowNumber++;

            $rows[] = [
                $this->rowNumber,
                $service->tanggal_service,
                $service->no_invoice,
                $service->vehicle->no_lambung ?? '-',
                $service->tanggal_service,
                $item->nama_item ?? '-',
                $item->quantity,
                $item->harga_satuan,
                $item->diskon_item,
                $service->workshop->nama_bengkel ?? '-',
                $item->subtotal,
                $isFirstItem ? $totalTagihanNota : 0 // Gunakan 0 agar bisa diformat accounting (nanti dihilangkan via styling jika perlu)
            ];

            $isFirstItem = false;
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'H' => '#,##0', // Harga
            'I' => '#,##0', // Discont
            'K' => '#,##0', // Jumlah
            'L' => '#,##0', // Total Tagihan
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Styling Judul
        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('A2:L2');
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Styling Header Tabel (Baris ke-4)
        $styleHeader = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD']
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ];

        $sheet->getStyle('A4:L4')->applyFromArray($styleHeader);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $highestRow = $sheet->getHighestRow();
                $totalRow = $highestRow + 1;

                // Border untuk seluruh data
                $sheet->getStyle("A4:L{$highestRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Tambahkan Baris TOTAL KESELURUHAN di paling bawah
                $sheet->mergeCells("A{$totalRow}:J{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", "GRAND TOTAL SELURUH SERVICE");
                
                // Rumus SUM untuk kolom Jumlah (K)
                $sheet->setCellValue("K{$totalRow}", "=SUM(K5:K{$highestRow})");
                
                // Styling Grand Total
                $sheet->getStyle("A{$totalRow}:L{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00'] // Warna Kuning
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ]
                ]);

                // Format Rupiah untuk Grand Total
                $sheet->getStyle("K{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');

                // Hilangkan angka 0 pada kolom L yang bukan baris pertama (agar bersih)
                for ($i = 5; $i <= $highestRow; $i++) {
                    $cellValue = $sheet->getCell("L{$i}")->getValue();
                    if ($cellValue == 0) {
                        $sheet->setCellValue("L{$i}", "");
                    }
                }
            },
        ];
    }
}