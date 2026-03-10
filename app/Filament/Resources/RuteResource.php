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
    protected static ?int $navigationSort = 8;

    /*
    |--------------------------------------------------------------------------
    | FORMAT INPUT RUPIAH (TANPA .00)
    |--------------------------------------------------------------------------
    */

    protected static function currencyInput(string $name, string $label)
    {
        return Forms\Components\TextInput::make($name)
            ->label($label)
            ->prefix('Rp')
            ->required()
            ->formatStateUsing(
                fn($state) =>
                $state ? number_format((int) $state, 0, ',', '.') : null
            )
            ->mask(RawJs::make('$money($input)'))
            ->stripCharacters(['.', ','])
            ->numeric()
            ->dehydrateStateUsing(
                fn($state) =>
                (int) str_replace(['.', ','], '', $state ?? 0)
            );
    }

    /*
    |--------------------------------------------------------------------------
    | FORM
    |--------------------------------------------------------------------------
    */

    public static function form(Form $form): Form
    {
        return $form->schema([

            /*
            |--------------------------------------------------------------------------
            | INFORMASI RUTE
            |--------------------------------------------------------------------------
            */

            Forms\Components\Section::make('Informasi Rute')
                ->schema([

                    Forms\Components\TextInput::make('nama_rute')
                        ->label('Nama Rute')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Contoh: PORT A - PORT B'),

                    Forms\Components\TextInput::make('jarak')
                        ->label('Jarak (KM)')
                        ->numeric()
                        ->required(),

                ])->columns(2),

            /*
            |--------------------------------------------------------------------------
            | KOMPONEN BIAYA & PENDAPATAN
            |--------------------------------------------------------------------------
            */

            Forms\Components\Section::make('Komponen Biaya & Pendapatan')
                ->schema([

                    /*
                    |--------------------------------------------------------------------------
                    | UANG JALAN (AUTO HITUNG)
                    |--------------------------------------------------------------------------
                    */
                    Forms\Components\TextInput::make('harga_tonase_pusat')
                        ->label('Harga Induk / Kg')
                        ->prefix('Rp')
                        ->required()
                        ->live(onBlur: true)
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(['.', ','])
                        ->numeric()
                        ->dehydrateStateUsing(
                            fn($state) =>
                            (int) str_replace(['.', ','], '', $state ?? 0)
                        ),

                    Forms\Components\TextInput::make('harga_tonase_vendor')
                        ->label('Harga Vendor / Kg')
                        ->prefix('Rp')
                        ->required()
                        ->live(onBlur: true)
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(['.', ','])
                        ->numeric()
                        ->dehydrateStateUsing(
                            fn($state) =>
                            (int) str_replace(['.', ','], '', $state ?? 0)
                        ),

                    Forms\Components\TextInput::make('uang_jalan')
                        ->label('Uang Jalan')
                        ->prefix('Rp')
                        ->required()
                        ->live(onBlur: true)
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(['.', ','])
                        ->numeric()
                        ->dehydrateStateUsing(
                            fn($state) =>
                            (int) str_replace(['.', ','], '', $state ?? 0)
                        )
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {

                            $uangJalan = (int) str_replace(['.', ','], '', $state ?? 0);

                            if ($uangJalan <= 0) {
                                $set('uang_makan', 0);
                                $set('bahan_bakar', 0);
                                $set('gaji_pokok', 0);
                                $set('pungli', 0);
                                $set('insentif', 0);
                                return;
                            }

                            // Pembagian otomatis
                            $uangMakan   = round($uangJalan * 0.05);
                            $bahanBakar  = round($uangJalan * 0.70);
                            $gajiPokok   = round($uangJalan * 0.10);
                            $pungli      = round($uangJalan * 0.15);
                            // $insentif    = 0;

                            $set('uang_makan', $uangMakan);
                            $set('bahan_bakar', $bahanBakar);
                            $set('gaji_pokok', $gajiPokok);
                            $set('pungli', $pungli);
                            // $set('insentif', $insentif);
                        }),

                    Forms\Components\TextInput::make('insentif')
                        ->label('Insentif')
                        ->prefix('Rp')
                        ->required()
                        ->default(0)
                        ->live(onBlur: true)
                        ->mask(RawJs::make('$money($input)'))
                        ->stripCharacters(['.', ','])
                        ->numeric()
                        ->dehydrateStateUsing(
                            fn($state) =>
                            (int) str_replace(['.', ','], '', $state ?? 0)
                        ),
                    /*
                    |--------------------------------------------------------------------------
                    | FIELD HIDDEN (AUTO SAVE KE DATABASE)
                    |--------------------------------------------------------------------------
                    */

                    Forms\Components\Hidden::make('uang_makan')->dehydrated(),
                    Forms\Components\Hidden::make('bahan_bakar')->dehydrated(),
                    Forms\Components\Hidden::make('gaji_pokok')->dehydrated(),
                    // Forms\Components\Hidden::make('insentif')->dehydrated(),
                    Forms\Components\Hidden::make('pungli')->dehydrated(),

                ])->columns(2),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TABLE (TANPA .00)
    |--------------------------------------------------------------------------
    */

    public static function table(Table $table): Table
    {
        return $table->columns([

            Tables\Columns\TextColumn::make('nama_rute')
                ->label('Nama Rute')
                ->searchable(),

            Tables\Columns\TextColumn::make('jarak')
                ->label('Jarak')
                ->suffix(' KM'),

            // Tables\Columns\TextColumn::make('gaji_pokok')
            //     ->label('Gaji Pokok')
            //     ->formatStateUsing(
            //         fn($state) =>
            //         'Rp ' . number_format((int) $state, 0, ',', '.')
            //     ),

            Tables\Columns\TextColumn::make('uang_jalan')
                ->label('Uang Jalan')
                ->formatStateUsing(
                    fn($state) =>
                    'Rp ' . number_format((int) $state, 0, ',', '.')
                ),

            Tables\Columns\TextColumn::make('harga_tonase_pusat')
                ->label('Harga Induk')
                ->formatStateUsing(
                    fn($state) =>
                    'Rp ' . number_format((int) $state, 0, ',', '.')
                ),

            Tables\Columns\TextColumn::make('harga_tonase_vendor')
                ->label('Harga Vendor')
                ->formatStateUsing(
                    fn($state) =>
                    'Rp ' . number_format((int) $state, 0, ',', '.')
                ),

            Tables\Columns\TextColumn::make('insentif')
                ->label('Insentif')
                ->formatStateUsing(
                    fn($state) =>
                    'Rp ' . number_format((int) $state, 0, ',', '.')
                ),

        ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PAGES
    |--------------------------------------------------------------------------
    */

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRutes::route('/'),
            'create' => Pages\CreateRute::route('/create'),
            'edit' => Pages\EditRute::route('/{record}/edit'),
        ];
    }
}
