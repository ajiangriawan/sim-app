<?php

namespace App\Filament\Pages;

use App\Exports\BalinkReportExport;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;

class ExportLaporan extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';
    protected static ?string $navigationGroup = 'Hauling';
    protected static ?string $navigationLabel = 'Export Laporan Omset';
    protected static ?string $title = 'Export Laporan Bulanan';
    protected static ?int $navigationSort = 9;

    protected static string $view = 'filament.pages.export-laporan';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'kategori' => 'inti',
            'start_date' => now()->startOfMonth()->format('Y-m-d'),
            'end_date' => now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Parameter Laporan Omset')
                    ->schema([
                        Select::make('kategori')
                            ->options([
                                'inti'   => 'Pusat',
                                'vendor' => 'Vendor',
                            ])
                            ->required(),

                        DatePicker::make('start_date')
                            ->required(),

                        DatePicker::make('end_date')
                            ->afterOrEqual('start_date')
                            ->required(),
                    ])->columns(3),
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $formData = $this->form->getState();

        $kategori = $formData['kategori'];
        $start    = $formData['start_date'];
        $end      = $formData['end_date'];

        $isVendor = ($kategori === 'vendor');

        $fileName = "LAPORAN_OMSET_{$kategori}_{$start}_sd_{$end}.xlsx";

        return Excel::download(
            new BalinkReportExport($start, $end, $isVendor),
            $fileName
        );
    }
}
