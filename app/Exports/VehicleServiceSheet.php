<?php

namespace App\Exports;

use App\Models\Service;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class VehicleServiceSheet implements
    FromQuery,
    WithTitle,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize,
    WithColumnFormatting,
    WithEvents
{
    protected $vehicle, $dari, $sampai, $workshopId;
    protected $rowNumber = 0;

    public function __construct($vehicle, $dari, $sampai, $workshopId)
    {
        $this->vehicle = $vehicle;
        $this->dari = $dari;
        $this->sampai = $sampai;
        $this->workshopId = $workshopId;
    }

    // Nama Sheet berdasarkan No Lambung
    public function title(): string
    {
        return $this->vehicle->no_lambung ?? 'Unit';
    }

    public function query()
    {
        $query = Service::query()
            ->with(['vehicle', 'workshop', 'items'])
            ->where('vehicle_id', $this->vehicle->id);

        if ($this->dari) $query->whereDate('tanggal_service', '>=', $this->dari);
        if ($this->sampai) $query->whereDate('tanggal_service', '<=', $this->sampai);
        if ($this->workshopId) $query->where('workshop_id', $this->workshopId);

        return $query->orderBy('tanggal_service', 'asc');
    }

    public function headings(): array
    {
        return [
            ['LAPORAN SERVICE & MAINTENANCE - UNIT ' . $this->vehicle->no_lambung],
            ['PERIODE: ' . ($this->dari ?? 'Awal') . ' s/d ' . ($this->sampai ?? 'Akhir')],
            [],
            ['NO', 'TGL INVOICE', 'NO INVOICE', 'NO LAMBUNG', 'TGL PEMAKAIAN', 'JENIS BARANG', 'QTY', 'HARGA', 'DISCONT', 'OWNER', 'JUMLAH', 'TOTAL TAGIHAN']
        ];
    }

    public function map($service): array
    {
        $rows = [];
        $totalTagihanNota = $service->items->sum(fn($item) => ($item->quantity * $item->harga_satuan) - $item->diskon_item);
        $isFirstItem = true;

        foreach ($service->items as $item) {
            $this->rowNumber++;
            $rows[] = [
                $this->rowNumber,
                $service->tanggal_service,
                $service->no_invoice,
                $this->vehicle->no_lambung,
                $service->tanggal_service,
                $item->nama_item,
                $item->quantity,
                $item->harga_satuan,
                $item->diskon_item,
                $service->workshop->nama_bengkel ?? '-',
                $item->subtotal,
                $isFirstItem ? $totalTagihanNota : 0
            ];
            $isFirstItem = false;
        }
        return $rows;
    }

    public function columnFormats(): array
    {
        return ['H' => '#,##0', 'I' => '#,##0', 'K' => '#,##0', 'L' => '#,##0'];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('A2:L2');
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A4:L4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']]
        ]);
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $highestRow = $sheet->getHighestRow();
                $totalRow = $highestRow + 1;

                // 1. Tambahkan Border untuk seluruh data dari baris 4 sampai data terakhir
                $sheet->getStyle("A4:L{$highestRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // 2. Tambahkan Baris TOTAL di paling bawah per sheet
                $sheet->mergeCells("A{$totalRow}:K{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", "TOTAL TAGIHAN UNIT " . $this->vehicle->no_lambung);

                // Rumus SUM untuk kolom Total Tagihan (L)
                // Kita menjumlahkan kolom L, karena kolom L hanya berisi angka jika itu item pertama di invoice
                $sheet->setCellValue("L{$totalRow}", "=SUM(L5:L{$highestRow})");

                // 3. Styling Baris Total
                $sheet->getStyle("A{$totalRow}:L{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00'] // Warna Kuning
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ]
                ]);

                // 4. Format Rupiah/Accounting untuk cell Total
                $sheet->getStyle("L{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');

                // 5. Bersihkan angka 0 pada kolom L agar tidak berantakan (logika lama Anda)
                for ($i = 5; $i <= $highestRow; $i++) {
                    $cellValue = $sheet->getCell("L{$i}")->getValue();
                    // Kita cek jika nilainya strictly 0 (bukan kosong atau formula)
                    if ($cellValue === 0 || $cellValue === "0") {
                        $sheet->setCellValue("L{$i}", "");
                    }
                }
            },
        ];
    }
}
