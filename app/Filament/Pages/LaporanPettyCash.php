<?php

namespace App\Filament\Pages;

use App\Exports\PettyCashExport;
use App\Models\Deposit;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;

class LaporanPettyCash extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Export Laporan Petty Cash';
    protected static ?string $title = 'Filter Laporan Petty Cash';
    protected static ?string $navigationGroup = 'Hauling';
    protected static ?int $navigationSort = 11;

    protected static string $view = 'filament.pages.laporan-petty-cash';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Parameter Laporan')
                    ->description('Tentukan rentang tanggal dan pihak untuk mengunduh laporan Excel.')
                    ->schema([
                        Select::make('nama_pihak')
                            ->label('Pilih Pihak/Vendor')
                            ->options(Deposit::distinct()->pluck('nama_pihak', 'nama_pihak'))
                            ->searchable()
                            ->required(),
                        DatePicker::make('dari')
                            ->label('Dari Tanggal'),
                        DatePicker::make('sampai')
                            ->label('Sampai Tanggal'),
                    ])->columns(3)
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('export')
                ->label('Download Excel')
                ->submit('export')
                ->color('success')
                ->icon('heroicon-o-arrow-down-tray'),
        ];
    }

    public function export()
    {
        $input = $this->form->getState();
        
        return Excel::download(
            new PettyCashExport(
                $input['nama_pihak'], 
                $input['dari'], 
                $input['sampai']
            ), 
            "Laporan_PC_{$input['nama_pihak']}.xlsx"
        );
    }
}