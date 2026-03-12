<?php

namespace App\Filament\Pages;

use App\Exports\ServiceReportExport;
use App\Models\Workshop;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;

class LaporanService extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Hauling';
    protected static ?string $navigationLabel = 'Export Laporan Service';
    protected static ?string $title = 'Laporan Service';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.laporan-service';

    // Properti untuk menampung data form
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'kategori' => 'inti',
            'dari' => now()->startOfMonth()->format('Y-m-d'),
            'sampai' => now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Laporan')
                    ->description('Tentukan periode dan parameter laporan yang ingin diunduh.')
                    ->schema([
                        Select::make('workshop_id')
                            ->label('Bengkel')
                            ->options(Workshop::pluck('nama_bengkel', 'id'))
                            ->placeholder('Semua Bengkel'),
                        DatePicker::make('dari')
                            ->label('Tanggal Mulai')
                            ->required(),
                        DatePicker::make('sampai')
                            ->label('Tanggal Selesai')
                            ->required(),
                        
                    ])->columns(3),
            ])
            ->statePath('data');
    }

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Action::make('export')
    //             ->label('Unduh Laporan (Excel)')
    //             ->icon('heroicon-o-arrow-down-tray')
    //             ->color('success')
    //             ->action('exportExcel'),
    //     ];
    // }

    public function exportExcel()
    {
        $formData = $this->form->getState();

        $filename = 'Laporan_Service_' . $formData['dari'] . '_sd_' . $formData['sampai'] . '.xlsx';

        return Excel::download(
            new ServiceReportExport(
                $formData['dari'],
                $formData['sampai'],
                $formData['workshop_id']
            ),
            $filename
        );
    }
}
