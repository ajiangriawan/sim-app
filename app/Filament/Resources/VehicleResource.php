<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleResource\Pages;
use App\Models\Deposit;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Hauling';
    protected static ?string $pluralLabel = 'Input Data Kendaraan';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Kendaraan')
                    ->schema([
                        Forms\Components\Select::make('partai')
                            ->label('Partai')
                            ->options(
                                Vehicle::query()
                                    ->distinct()
                                    ->pluck('partai', 'partai')
                                    ->toArray()
                            )
                            ->searchable()
                            ->required()
                            // Fitur untuk menambah nama baru langsung dari select
                            ->createOptionForm([
                                Forms\Components\TextInput::make('partai')
                                    ->label('Nama Pihak Baru')
                                    ->dehydrateStateUsing(fn(?string $state) => strtoupper($state))
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->required(),
                            ])
                            ->createOptionUsing(fn(array $data) => $data['partai']),
                        Forms\Components\TextInput::make('no_plat')
                            ->label('Nomor Plat')
                            ->required()
                            ->dehydrateStateUsing(fn(?string $state) => strtoupper($state))
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->unique(ignoreRecord: true)
                            ->placeholder('Contoh: BG 1234 AB'),

                        Forms\Components\TextInput::make('no_lambung')
                            ->label('Nomor Lambung')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn(?string $state) => strtoupper($state))
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->placeholder('Contoh: STL 123'),

                        Forms\Components\Select::make('nama_deposit_pilihan')
                            ->label('Pilih Saldo Atas Nama')
                            ->options(Deposit::query()->distinct()->pluck('nama_pihak', 'nama_pihak')->toArray())
                            ->searchable()
                            ->live(),

                        Forms\Components\Select::make('driver_id')
                            ->label('Driver Utama')
                            ->relationship('driver', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->nullable(),

                        Forms\Components\TextInput::make('kapasitas')
                            ->label('Kapasitas (Ton/M3)')
                            ->required(),

                        Forms\Components\Select::make('tahun')
                            ->label('Tahun Kendaraan')
                            ->required()
                            ->options(collect(range(date('Y'), 2000))->mapWithKeys(fn($year) => [$year => $year])),

                        Forms\Components\Select::make('status')
                            ->label('Status Kendaraan')
                            ->options([
                                'aktif' => 'Aktif',
                                'perbaikan' => 'Dalam Perbaikan',
                            ])
                            ->required()
                            ->default('aktif'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_lambung')
                    ->label('No. Lambung')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('partai')
                    ->label('Partai')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_deposit_pilihan')
                    ->label('PC')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('driver.nama')
                    ->label('Driver')
                    ->placeholder('Belum Ada Driver')
                    ->searchable(),

                Tables\Columns\TextColumn::make('kapasitas')
                    ->label('Kapasitas')
                    ->suffix(' Ton'),

                Tables\Columns\TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable(),

                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Non-Aktif',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'aktif' => 'Aktif',
                        'perbaikan' => 'Perbaikan',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
        // ->bulkActions([
        //     Tables\Actions\BulkActionGroup::make([
        //         Tables\Actions\DeleteBulkAction::make(),
        //     ]),
        // ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
