<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use App\Models\Transaction;
use App\Models\Deposit;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        // 1. Ambil Filter dari Dashboard
        $vehicleIds = $this->filters['vehicle_ids'] ?? [];
        $dateFrom = $this->filters['date_from'] ?? null;
        $dateTo = $this->filters['date_to'] ?? null;

        // 2. Query Dasar Transaksi
        $transactionQuery = Transaction::query()
            ->where('status', 'selesai')
            ->when($vehicleIds, fn($q) => $q->whereIn('vehicle_id', $vehicleIds))
            ->when($dateFrom, fn($q) => $q->where('tanggal', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('tanggal', '<=', $dateTo));

        // 3. Query Dasar Service
        $serviceQuery = Service::query()
            ->when($vehicleIds, fn($q) => $q->whereIn('vehicle_id', $vehicleIds))
            ->when($dateFrom, fn($q) => $q->where('tanggal_service', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('tanggal_service', '<=', $dateTo));

        // 4. Logika Saldo
        $saldoTersedia = 0;
        if (empty($vehicleIds)) {
            // Menghitung sisa saldo seluruh pihak
            $totalDeposit = Deposit::sum('jumlah_deposit');
            $totalUJ = Transaction::where('pakai_deposit', true)->sum('uang_jalan');
            $saldoTersedia = $totalDeposit - $totalUJ;
        } else {
            $saldoTersedia = Deposit::getSaldoTersedia(); 
        }

        return [
            Stat::make('Total Pengiriman', $transactionQuery->count() . ' Rit')
                ->description($dateFrom ? 'Periode filter aktif' : 'Seluruh riwayat')
                ->descriptionIcon('heroicon-m-check-circle')
                ->icon('heroicon-o-truck')
                ->color('primary'),

            Stat::make('Total Tonase', number_format($transactionQuery->sum('tonase'), 0, ',', '.') . ' Ton')
                ->description('Muatan terangkut')
                ->descriptionIcon('heroicon-m-scale')
                ->color('info'),

            // TAMBAHAN: LABA KOTOR (OMSET)
            Stat::make('Laba Kotor (Omset)', 'Rp ' . number_format($transactionQuery->sum('pendapatan_kotor'), 0, ',', '.'))
                ->description('Total pendapatan sebelum biaya')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Laba Bersih', 'Rp ' . number_format($transactionQuery->sum('pendapatan_bersih'), 0, ',', '.'))
                ->description('Net income setelah potongan')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Biaya Service', 'Rp ' . number_format($serviceQuery->sum('total_biaya'), 0, ',', '.'))
                ->description('Maintenance unit')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('danger'),

            Stat::make('Saldo Kas Deposit', 'Rp ' . number_format($saldoTersedia, 0, ',', '.'))
                ->description('Sisa saldo tersedia')
                ->icon('heroicon-o-wallet')
                ->color($saldoTersedia < 0 ? 'danger' : 'success'),
        ];
    }
}