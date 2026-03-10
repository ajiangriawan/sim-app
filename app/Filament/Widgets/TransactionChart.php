<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use App\Models\Vehicle;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;

class TransactionChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Statistik Pengiriman Kendaraan';
    protected int | string | array $columnSpan = 'full';
    public ?string $filter = 'month';

    protected function getFilters(): ?array
    {
        return [
            'week' => '7 Hari Terakhir',
            'month' => '30 Hari Terakhir',
            'year' => '1 Tahun Terakhir',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;
        $vehicleIds = $this->filters['vehicle_ids'] ?? [];
        $dateFrom = $this->filters['date_from'] ?? null;
        $dateTo = $this->filters['date_to'] ?? null;

        $start = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : match ($activeFilter) {
            'week' => now()->subDays(6)->startOfDay(),
            'year' => now()->subYear()->startOfMonth(),
            default => now()->subDays(29)->startOfDay(),
        };
        $end = $dateTo ? Carbon::parse($dateTo)->endOfDay() : now();

        // Query Referensi untuk Label Sumbu X
        $trendRef = Trend::model(Transaction::class)
            ->dateColumn('tanggal')
            ->between(start: $start, end: $end);

        $periodData = match ($activeFilter) {
            'year' => $trendRef->perMonth()->count(),
            default => $trendRef->perDay()->count(),
        };

        $labels = $periodData->map(fn(TrendValue $value) => match ($activeFilter) {
            'year' => Carbon::parse($value->date)->format('M Y'),
            default => Carbon::parse($value->date)->format('d M'),
        });

        $datasets = [];

        // PERBAIKAN: Palette warna yang lebih banyak (20 warna unik)
        $colors = [
            '#3b82f6',
            '#ef4444',
            '#10b981',
            '#f59e0b',
            '#8b5cf6',
            '#ec4899',
            '#06b6d4',
            '#1e293b',
            '#d946ef',
            '#f43f5e',
            '#84cc16',
            '#0ea5e9',
            '#6366f1',
            '#fb923c',
            '#4ade80',
            '#2dd4bf',
            '#a855f7',
            '#f87171',
            '#475569',
            '#166534'
        ];

        if (empty($vehicleIds)) {
            $datasets[] = [
                'label' => 'Total Pengiriman (Semua Unit)',
                'data' => $periodData->map(fn(TrendValue $value) => $value->aggregate),
                'borderColor' => '#64748b',
                'backgroundColor' => '#94a3b844',
                'fill' => true,
            ];
        } else {
            // PERBAIKAN PERFORMA: Eager Load data kendaraan sekaligus
            $vehicles = Vehicle::whereIn('id', $vehicleIds)->get()->keyBy('id');

            foreach ($vehicleIds as $index => $id) {
                $vehicle = $vehicles->get($id);
                if (!$vehicle) continue;

                $query = Transaction::query()
                    ->where('vehicle_id', $id)
                    ->where('status', 'selesai');

                $vTrend = Trend::query($query)
                    ->dateColumn('tanggal')
                    ->between(start: $start, end: $end);

                $result = match ($activeFilter) {
                    'year' => $vTrend->perMonth()->count(),
                    default => $vTrend->perDay()->count(),
                };

                $color = $colors[$index % count($colors)];

                $datasets[] = [
                    'label' => $vehicle->no_lambung,
                    'data' => $result->map(fn(TrendValue $value) => $value->aggregate),
                    'borderColor' => $color,
                    'backgroundColor' => 'transparent', // Agar grafik tidak "tumpuk-tumpukan" jika banyak unit
                    'fill' => false,
                    'tension' => 0.3,
                    'pointRadius' => 2, // Mengecilkan titik agar tidak semrawut jika banyak garis
                ];
            }
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
