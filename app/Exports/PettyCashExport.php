<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PettyCashExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting, ShouldAutoSize, WithEvents
{
    protected $namaPihak;
    protected $from;
    protected $to;
    protected $runningBalance = 0;
    protected $rowNumber = 0;

    public function __construct($namaPihak, $from = null, $to = null)
    {
        $this->namaPihak = $namaPihak;
        $this->from = $from;
        $this->to = $to;
    }

    public function collection()
    {
        // 1. Data Deposit (Debit)
        $deposits = DB::table('deposits')
            ->select(
                'tanggal_deposit as tgl_uj',
                'tanggal_deposit as tgl_do',
                DB::raw('NULL as no_lambung'),
                DB::raw('NULL as no_sjb'),
                'keterangan as rute',
                DB::raw('0 as tonase'),
                'jumlah_deposit as debit',
                DB::raw('0 as kredit_uj'),
                DB::raw('0 as kredit_bonus'),
                DB::raw('0 as tambahan_uj'),
                DB::raw('NULL as nama_perbaikan'),
                DB::raw('0 as biaya_perbaikan'),
                'created_at'
            )
            ->where('nama_pihak', $this->namaPihak);

        // 2. Data Transaksi (Kredit UJ/Bonus)
        $transactions = DB::table('transactions')
            ->join('vehicles', 'transactions.vehicle_id', '=', 'vehicles.id')
            ->join('rutes', 'transactions.rute_id', '=', 'rutes.id')
            ->select(
                'transactions.tanggal as tgl_uj',
                'transactions.tanggal as tgl_do',
                'vehicles.no_lambung',
                'transactions.no_sjb',
                'rutes.nama_rute as rute',
                'transactions.tonase',
                DB::raw('0 as debit'),
                'transactions.uang_jalan as kredit_uj',
                'transactions.bonus_tonase as kredit_bonus',
                'transactions.insentif as tambahan_uj',
                DB::raw('NULL as nama_perbaikan'),
                DB::raw('0 as biaya_perbaikan'),
                'transactions.created_at'
            )
            ->where('transactions.nama_deposit_pilihan', $this->namaPihak)
            ->where('transactions.pakai_deposit', true)
            ->where('transactions.status', 'selesai');

        // 3. Data Service (Kredit Perbaikan)
        $services = DB::table('services')
            ->join('vehicles', 'services.vehicle_id', '=', 'vehicles.id')
            ->leftJoin('service_items', 'services.id', '=', 'service_items.service_id')
            ->select(
                'services.tanggal_service as tgl_uj',
                'services.tanggal_service as tgl_do',
                'vehicles.no_lambung',
                'services.no_invoice as no_sjb',
                DB::raw('"PERBAIKAN" as rute'),
                DB::raw('0 as tonase'),
                DB::raw('0 as debit'),
                DB::raw('0 as kredit_uj'),
                DB::raw('0 as kredit_bonus'),
                DB::raw('0 as tambahan_uj'),
                DB::raw('GROUP_CONCAT(service_items.nama_item SEPARATOR ", ") as nama_perbaikan'),
                'services.total_biaya as biaya_perbaikan',
                'services.created_at'
            )
            ->where('services.nama_deposit_pilihan', $this->namaPihak)
            ->where('services.pakai_deposit', true)
            ->groupBy('services.id', 'vehicles.no_lambung', 'services.tanggal_service', 'services.no_invoice', 'services.total_biaya', 'services.created_at');

        if ($this->from && $this->to) {
            $deposits->whereBetween('tanggal_deposit', [$this->from, $this->to]);
            $transactions->whereBetween('transactions.tanggal', [$this->from, $this->to]);
            $services->whereBetween('services.tanggal_service', [$this->from, $this->to]);
        }

        return $deposits->unionAll($transactions)->unionAll($services)
            ->orderBy('tgl_uj', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function map($row): array
    {
        $this->rowNumber++;
        $totalKeluar = (float)$row->kredit_uj + (float)$row->kredit_bonus + (float)$row->tambahan_uj + (float)$row->biaya_perbaikan;
        $this->runningBalance += ((float)$row->debit - $totalKeluar);

        return [
            $row->tgl_uj,
            $row->tgl_do,
            $row->no_lambung ?? '-',
            $row->no_sjb ?? '-',
            $row->rute,
            $row->tonase ?: 0,
            $row->debit ?: 0,
            $row->kredit_uj ?: 0,
            $row->kredit_bonus ?: 0,
            $row->tambahan_uj ?: 0,
            $row->nama_perbaikan ?: '-',
            $row->biaya_perbaikan ?: 0,
            $this->runningBalance,
        ];
    }

    public function headings(): array
    {
        return [
            ['UANG JALAN VENDOR ' . strtoupper($this->namaPihak)],
            ['PERIODE: ' . ($this->from ?? 'AWAL') . ' s/d ' . ($this->to ?? 'SEKARANG')],
            [],
            ['TANGGAL UJ', 'TGL DO', 'NO LAMBUNG', 'SJB', 'RUTE', 'TONASE', 'DEBIT', 'KREDIT', '', '', 'PERBAIKAN/JASA', '', 'JUMLAH'],
            ['', '', '', '', '', '', 'DEPOSIT', 'UANG JALAN', 'BONUS TONASE', 'TAMBAHAN UJ', 'NAMA BARANG', 'BIAYA', 'SALDO'],
        ];
    }

    public function columnFormats(): array
    {
        // Format angka ribuan untuk kolom nominal (G sampai M)
        return [
            'F' => '#,##0.00',
            'G' => '#,##0',
            'H' => '#,##0',
            'I' => '#,##0',
            'J' => '#,##0',
            'L' => '#,##0',
            'M' => '#,##0',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Merge judul utama
        $sheet->mergeCells('A1:M1');
        $sheet->mergeCells('A2:M2');
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        // Header bertingkat
        $sheet->mergeCells('A4:A5'); 
        $sheet->mergeCells('B4:B5'); 
        $sheet->mergeCells('C4:C5'); 
        $sheet->mergeCells('D4:D5'); 
        $sheet->mergeCells('E4:E5'); 
        $sheet->mergeCells('F4:F5'); 
        $sheet->mergeCells('G4:G5'); // Kolom Deposit
        $sheet->mergeCells('H4:J4'); // Group Kredit
        $sheet->mergeCells('K4:L4'); // Group Perbaikan
        $sheet->mergeCells('M4:M5'); // Kolom Saldo
        
        $sheet->getStyle('A4:M5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD']
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;
                $lastRow = $this->rowNumber + 5; // Baris data terakhir
                $totalRow = $lastRow + 1;       // Baris total

                // Border untuk seluruh data
                $sheet->getStyle("A6:M{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                // Baris TOTAL KESELURUHAN
                $sheet->mergeCells("A{$totalRow}:F{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", "TOTAL KESELURUHAN");
                
                // Rumus SUM Excel untuk tiap kolom nominal
                $columnsToSum = ['G', 'H', 'I', 'J', 'L'];
                foreach ($columnsToSum as $col) {
                    $sheet->setCellValue("{$col}{$totalRow}", "=SUM({$col}6:{$col}{$lastRow})");
                }
                
                // Saldo Akhir (ambil baris terakhir kolom M)
                $sheet->setCellValue("M{$totalRow}", "=M{$lastRow}");

                // Styling Baris Total & Format Rupiah
                $sheet->getStyle("G{$totalRow}:M{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("A{$totalRow}:M{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFF00'] // Kuning
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN]
                    ]
                ]);

                // Judul Total rata kanan agar dekat dengan angka
                $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal(Alignment::VERTICAL_CENTER);

                // Auto filter & Alignment data
                $sheet->setAutoFilter("A5:M5");
                $sheet->getStyle("A6:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("F6:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("K6:K{$lastRow}")->getAlignment()->setWrapText(true);
            },
        ];
    }
}