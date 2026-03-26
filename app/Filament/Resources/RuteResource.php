<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RuteResource\Pages;
use App\Models\Rute;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\RawJs;

class RuteResource extends Resource
{
    protected static ?string $model = Rute::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'Hauling';
    protected static ?string $pluralLabel = 'Input Data Rute';
    protected static ?int $navigationSort = 7;

    /**
     * Helper untuk input mata uang agar konsisten
     */
    protected static function currencyInput(string $name, string $label)
    {
        return Forms\Components\TextInput::make($name)
            ->label($label)
            ->prefix('Rp')
            ->required()
            ->mask(RawJs::make('$money($input)'))
            ->stripCharacters(['.', ','])
            // Jangan gunakan ->numeric() jika menggunakan mask string
            ->formatStateUsing(
                fn($state) => $state ? number_format((float) $state, 0, ',', '.') : null
            )
            ->dehydrateStateUsing(
                fn($state) => (int) str_replace(['.', ','], '', $state ?? 0)
            );
    }

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Informasi Rute')
                ->schema([
                    Forms\Components\TextInput::make('nama_rute')
                        ->label('Nama Rute')
                        ->required()
                        ->maxLength(255)
                        // Menggunakan ?string agar tidak error saat null
                        ->dehydrateStateUsing(fn(?string $state) => strtoupper($state ?? ''))
                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                        ->placeholder('Contoh: PORT A - PORT B'),

                    Forms\Components\TextInput::make('jarak')
                        ->label('Jarak (KM)')
                        ->numeric()
                        ->required(),
                ])->columns(2),

            Forms\Components\Section::make('Komponen Biaya & Pendapatan')
                ->schema([

                    static::currencyInput('harga_tonase_pusat', 'Harga Induk / Kg')
                        ->live(onBlur: true),

                    static::currencyInput('harga_tonase_vendor', 'Harga Vendor / Kg')
                        ->live(onBlur: true),

                    Forms\Components\TextInput::make('uang_jalan')
                        ->label('Uang Jalan')
                        ->prefix('Rp')
                        ->required()
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(['.', ','])
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, $state) {
                            // Bersihkan string dari mask agar bisa dihitung
                            $numericState = (int) str_replace(['.', ','], '', $state ?? 0);

                            if ($numericState <= 0) {
                                $set('uang_makan', 0);
                                $set('bahan_bakar', 0);
                                $set('gaji_pokok', 0);
                                $set('pungli', 0);
                                return;
                            }

                            // Hitung otomatis
                            $set('uang_makan', round($numericState * 0.05));
                            $set('bahan_bakar', round($numericState * 0.70));
                            $set('gaji_pokok', round($numericState * 0.10));
                            $set('pungli', round($numericState * 0.15));
                        })
                        ->formatStateUsing(
                            fn($state) => $state ? number_format((float) $state, 0, ',', '.') : null
                        )
                        ->dehydrateStateUsing(
                            fn($state) => (int) str_replace(['.', ','], '', $state ?? 0)
                        ),

                    static::currencyInput('insentif', 'Insentif')
                        ->default(0),

                    /* Field Hidden untuk menyimpan hasil kalkulasi */
                    Forms\Components\Hidden::make('uang_makan')->dehydrated(),
                    Forms\Components\Hidden::make('bahan_bakar')->dehydrated(),
                    Forms\Components\Hidden::make('gaji_pokok')->dehydrated(),
                    Forms\Components\Hidden::make('pungli')->dehydrated(),

                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('nama_rute')
                ->label('Nama Rute')
                ->searchable(),

            Tables\Columns\TextColumn::make('jarak')
                ->label('Jarak')
                ->suffix(' KM'),

            Tables\Columns\TextColumn::make('uang_jalan')
                ->label('Uang Jalan')
                ->money('IDR', locale: 'id_ID'),

            Tables\Columns\TextColumn::make('harga_tonase_pusat')
                ->label('Harga Induk')
                ->money('IDR', locale: 'id_ID'),

            Tables\Columns\TextColumn::make('harga_tonase_vendor')
                ->label('Harga Vendor')
                ->money('IDR', locale: 'id_ID'),

            Tables\Columns\TextColumn::make('insentif')
                ->label('Insentif')
                ->money('IDR', locale: 'id_ID'),
        ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRutes::route('/'),
            'create' => Pages\CreateRute::route('/create'),
            'edit' => Pages\EditRute::route('/{record}/edit'),
        ];
    }
}
