<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\{
    FromCollection,
    WithHeadings,
    WithStyles,
    WithMapping,
    ShouldAutoSize,
    WithEvents,
    WithTitle
};
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class VehicleReportExport implements
    FromCollection,
    WithMapping,
    WithHeadings,
    WithStyles,
    ShouldAutoSize,
    WithEvents,
    WithTitle
{
    protected $vehicleId, $noLambung, $start, $end, $isVendor;
    private $rowNumber = 1;

    public function __construct($vehicleId, $noLambung, $start, $end, $isVendor)
    {
        $this->vehicleId = $vehicleId;
        $this->noLambung = $noLambung;
        $this->start = $start;
        $this->end   = $end;
        $this->isVendor  = $isVendor;
    }

    public function title(): string
    {
        return substr($this->noLambung, 0, 30);
    }

    public function collection()
    {
        // 1. Subquery untuk meringkas biaya service per kendaraan per hari
        // Ini mencegah data double jika ada 2 atau lebih entry service di hari yang sama
        $serviceSubquery = DB::table('services')
            ->select('vehicle_id', 'tanggal_service', DB::raw('SUM(total_biaya) as total_service_harian'))
            ->groupBy('vehicle_id', 'tanggal_service');

        // 2. Query Utama
        $query = DB::table('transactions as t')
            ->join('vehicles as v', 'v.id', '=', 't.vehicle_id')
            ->join('drivers as d', 'd.id', '=', 'v.driver_id')
            ->join('rutes as r', 'r.id', '=', 't.rute_id')
            // Join ke hasil subquery yang sudah di-SUM
            ->leftJoinSub($serviceSubquery, 's', function ($join) {
                $join->on('s.vehicle_id', '=', 't.vehicle_id')
                     ->on('s.tanggal_service', '=', 't.tanggal');
            })
            ->whereBetween('t.tanggal', [$this->start, $this->end]);

        if ($this->vehicleId !== 'all') {
            $query->where('t.vehicle_id', $this->vehicleId);
        }

        return $query->select([
            't.tanggal',
            't.no_sjb',
            'v.no_lambung as kendaraan',
            'd.nama as driver',
            'r.nama_rute',
            't.tonase',
            't.uang_jalan',
            't.bonus_tonase',
            't.insentif',
            't.harga_tonase_pusat',
            't.harga_tonase_vendor',
            'r.jarak as master_jarak',
            'r.uang_jalan as master_uang_jalan',
            DB::raw('IFNULL(s.total_service_harian, 0) as service'),
        ])
        ->orderBy('t.tanggal')
        ->get();
    }

    public function map($row): array
    {
        // Logika pemilihan harga berdasarkan kategori (Pusat/Vendor)
        $hargaSatuan = $this->isVendor 
            ? ($row->harga_tonase_vendor ?? 0) 
            : ($row->harga_tonase_pusat ?? 0);

        // Rumus Omset: Tonase * Harga * Jarak
        $pendapatanKotor = (float)$row->tonase * (int)$hargaSatuan * (float)$row->master_jarak;

        // Gunakan uang jalan dari transaksi jika ada, jika tidak pakai dari master rute
        $uangJalan = $row->uang_jalan > 0 ? $row->uang_jalan : $row->master_uang_jalan;
        
        $bonus = $row->bonus_tonase ?? 0;
        $insentif = $row->insentif ?? 0;
        $biayaService = $row->service ?? 0;

        // Total Pengeluaran & Net
        $totalPengeluaran = $biayaService + $uangJalan + $bonus + $insentif;
        $pendapatanBersih = $pendapatanKotor - $totalPengeluaran;

        return [
            $this->rowNumber++,
            $row->tanggal,
            $row->no_sjb,
            $row->kendaraan,
            $row->driver,
            $row->nama_rute,
            $row->tonase,
            $hargaSatuan,
            round($pendapatanKotor),
            $uangJalan,
            $bonus,
            $insentif,
            round($pendapatanBersih)
        ];
    }

    public function headings(): array
    {
        $kategori = $this->isVendor ? 'HARGA VENDOR' : 'HARGA PUSAT';
        return [
            ['REKAP OPERASIONAL - ' . $kategori],
            ['Unit: ' . ($this->noLambung ?? 'SEMUA') . ' | Periode: ' . $this->start . ' s/d ' . $this->end],
            [],
            [
                'No', 'Tanggal', 'No. SJB', 'Kendaraan', 'Driver', 'Rute', 
                'Tonase', 'Harga Satuan', 'Pendapatan (Omset)', 
                'Uang Jalan', 'Bonus Tonase', 'Insentif', 'Pendapatan Bersih'
            ]
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F81BD']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $highestRow = $sheet->getHighestRow();
                $totalRow = $highestRow + 1;

                // Merge Header
                $sheet->mergeCells('A1:M1');
                $sheet->mergeCells('A2:M2');
                $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Baris TOTAL
                $sheet->mergeCells("A{$totalRow}:F{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", 'TOTAL KESELURUHAN');

                // Rumus SUM Otomatis (G, I, J, K, L, M)
                $columns = ['G', 'I', 'J', 'K', 'L', 'M'];
                foreach ($columns as $col) {
                    $sheet->setCellValue("{$col}{$totalRow}", "=SUM({$col}5:{$col}{$highestRow})");
                }

                // Styling Baris Total
                $sheet->getStyle("A{$totalRow}:M{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                ]);

                // Format Rupiah (Kolom H sampai M)
                $sheet->getStyle("H5:M{$totalRow}")
                    ->getNumberFormat()
                    ->setFormatCode('_("Rp"* #,##0_);_("Rp"* (#,##0);_("Rp"* "-"??_);_(@_)');

                // Format Tonase (Kolom G)
                $sheet->getStyle("G5:G{$totalRow}")->getNumberFormat()->setFormatCode('#,##0.00');

                // Border Seluruh Data
                $sheet->getStyle("A4:M{$totalRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            }
        ];
    }
}