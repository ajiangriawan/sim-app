<?php

namespace App\Filament\Widgets;

use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\Service;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class FinancialStats extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        // 1. Ambil nilai filter
        $pihak = $this->filters['nama_pihak'] ?? null;

        // 2. Query Dasar untuk Transaksi (agar kode lebih rapi)
        $transactionQuery = Transaction::query()
            ->where('status', 'selesai')
            ->when($pihak, fn($query) => $query->where('nama_deposit_pilihan', $pihak));

        // 3. Hitung Total Deposit
        $totalDeposit = Deposit::query()
            ->when($pihak, fn($query) => $query->where('nama_pihak', $pihak))
            ->sum('jumlah_deposit');

        // 4. Hitung Pemakaian Deposit (Hanya yang pakai_deposit = true)
        $pemakaianTransaksi = (clone $transactionQuery)
            ->where('pakai_deposit', true)
            ->selectRaw('SUM(uang_jalan + bonus_tonase + COALESCE(insentif, 0)) as total')
            ->value('total') ?? 0;

        $pemakaianService = Service::query()
            ->where('pakai_deposit', true)
            ->when($pihak, fn($query) => $query->where('nama_deposit_pilihan', $pihak))
            ->sum('total_biaya');

        // 5. Hitung Metrik Tambahan (Tonase & Laba)
        // Kita gunakan clone agar query dasar tidak terganggu filter pakai_deposit
        $totalTonase = (clone $transactionQuery)->sum('tonase');
        $totalLabaKotor = (clone $transactionQuery)->sum('pendapatan_kotor');
        $totalLabaBersih = (clone $transactionQuery)->sum('pendapatan_bersih');

        $totalPemakaian = $pemakaianTransaksi + $pemakaianService;
        $sisaDeposit = $totalDeposit - $totalPemakaian;

        return [
            // Row 1: Saldo & Deposit
            Stat::make('Sisa Saldo Deposit', 'Rp ' . number_format($sisaDeposit, 0, ',', '.'))
                ->description('Total Saldo Tersedia')
                ->color($sisaDeposit >= 0 ? 'success' : 'danger')
                ->icon('heroicon-m-wallet'),

            Stat::make('Total Deposit', 'Rp ' . number_format($totalDeposit, 0, ',', '.'))
                ->description('Akumulasi Dana Masuk')
                ->color('gray'),

            Stat::make('Total Pemakaian', 'Rp ' . number_format($totalPemakaian, 0, ',', '.'))
                ->description('Transaksi + Service')
                ->color('danger'),

            // Row 2: Performa Operasional
            Stat::make('Total Tonase', number_format($totalTonase, 2, ',', '.') . ' Ton')
                ->description('Total Muatan')
                ->icon('heroicon-m-truck'),

            Stat::make('Total Laba Kotor', 'Rp ' . number_format($totalLabaKotor, 0, ',', '.'))
                ->description('Sebelum Potongan Operasional')
                ->color('primary'),

            Stat::make('Total Laba Bersih', 'Rp ' . number_format($totalLabaBersih, 0, ',', '.'))
                ->description('Setelah Potongan Operasional')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 10]) // Opsional: sekadar pemanis visual
        ];
    }
}