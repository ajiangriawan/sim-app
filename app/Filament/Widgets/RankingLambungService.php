<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RankingLambungService extends BaseWidget
{
    protected static ?int $sort = 2; // Mengatur urutan tampilan widget
    
    protected static ?string $heading = 'Top 10 Kendaraan Paling Sering Service';

    protected int | string | array $columnSpan = 'full';

    protected function getTableQuery(): Builder
    {
        return Vehicle::query()
            ->withCount('services')
            // Menggunakan total_biaya sesuai dengan migration services
            ->withSum('services', 'total_biaya') 
            ->orderByDesc('services_count')
            ->limit(10); // Mengambil 10 peringkat teratas
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('no_lambung')
                ->label('No Lambung')
                ->description(fn (Vehicle $record): string => $record->no_plat)
                ->searchable(),

            Tables\Columns\TextColumn::make('services_count')
                ->label('Frekuensi Service')
                ->badge()
                ->color(fn (int $state): string => $state > 5 ? 'danger' : 'warning')
                ->sortable()
                ->suffix(' Kali'),

            Tables\Columns\TextColumn::make('services_sum_total_biaya')
                ->label('Total Biaya Maintenance')
                ->money('IDR')
                ->sortable()
                ->color('danger')
                ->weight('bold'),

            Tables\Columns\TextColumn::make('status')
                ->label('Status Unit')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'aktif' => 'success',
                    'perbaikan' => 'danger',
                    'nonaktif' => 'gray',
                }),
        ];
    }

    protected function isTablePaginationEnabled(): bool
    {
        // Mematikan pagination karena ini adalah widget ringkasan/ranking
        return false;
    }
}