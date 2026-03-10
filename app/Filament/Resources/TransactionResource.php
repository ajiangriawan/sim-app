<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\Rute;
use App\Models\Vehicle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'Hauling';
    protected static ?string $pluralLabel = 'Input Data Transaksi';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([

            /*
            |--------------------------------------------------------------------------
            | INFORMASI UTAMA
            |--------------------------------------------------------------------------
            */

            Forms\Components\Section::make('Informasi Utama & Operasional')
                ->schema([

                    Forms\Components\TextInput::make('no_sjb')
                        ->label('No. SJB')
                        ->required()
                        ->unique(ignoreRecord: true),

                    Forms\Components\DatePicker::make('tanggal')
                        ->required()
                        ->default(now()),

                    Forms\Components\Select::make('vehicle_id')
                        ->label('No Lambung')
                        ->relationship('vehicle', 'no_lambung')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),

                    Forms\Components\Select::make('rute_id')
                        ->label('Rute Perjalanan')
                        ->relationship('rute', 'nama_rute')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateHydrated(function (Set $set, $state) {
                            if (!$state) return;
                            $rute = Rute::find($state);
                            if (!$rute) return;
                            self::setRuteData($set, $rute);
                        })
                        ->afterStateUpdated(function (Set $set, $state, Get $get) {
                            if (!$state) return;
                            $rute = Rute::find($state);
                            if (!$rute) return;
                            self::setRuteData($set, $rute);
                            self::calculateRevenue($get, $set);
                        }),

                    Forms\Components\TextInput::make('tonase')
                        ->label('Tonase (Ton)')
                        ->numeric()
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(
                            fn(Get $get, Set $set) =>
                            self::calculateRevenue($get, $set)
                        ),

                    Forms\Components\Select::make('status')
                        ->options([
                            'selesai' => 'Selesai',
                            'batal' => 'Batal',
                        ])
                        ->default('selesai')
                        ->required()
                        ->live()
                        ->afterStateUpdated(
                            fn(Get $get, Set $set) =>
                            self::calculateRevenue($get, $set)
                        ),

                ])->columns(2),

            /*
            |--------------------------------------------------------------------------
            | KEUANGAN & SALDO
            |--------------------------------------------------------------------------
            */

            Forms\Components\Section::make('Keuangan & Saldo')
                ->schema([

                    Forms\Components\Toggle::make('pakai_deposit')
                        ->label('Potong Saldo Deposit?')
                        ->default(true)
                        ->live()
                        ->columnSpanFull(),

                    Forms\Components\Select::make('nama_deposit_pilihan')
                        ->label('Pilih Saldo Atas Nama')
                        ->options(
                            Deposit::query()
                                ->distinct()
                                ->pluck('nama_pihak', 'nama_pihak')
                                ->toArray()
                        )
                        ->searchable()
                        ->visible(fn(Get $get) => $get('pakai_deposit'))
                        ->required(fn(Get $get) => $get('pakai_deposit'))
                        ->live(),

                    Forms\Components\Placeholder::make('saldo_sebelum')
                        ->label('Saldo Sebelum Dipotong')
                        ->visible(
                            fn(Get $get) =>
                            $get('pakai_deposit') && $get('nama_deposit_pilihan')
                        )
                        ->content(function (Get $get) {

                            $nama = $get('nama_deposit_pilihan');
                            if (!$nama) return '-';

                            $saldo = Deposit::getSaldoPerNama($nama);

                            return new HtmlString(
                                "<span class='font-bold text-primary-600'>Rp " .
                                    number_format((int) $saldo, 0, ',', '.') .
                                    "</span>"
                            );
                        }),

                    Forms\Components\Placeholder::make('jumlah_potongan')
                        ->label('Jumlah Potongan (Uang Jalan)')
                        ->visible(
                            fn(Get $get) =>
                            $get('pakai_deposit') && $get('nama_deposit_pilihan')
                        )
                        ->content(function (Get $get) {

                            $potongan = (int) ($get('uang_jalan') ?? 0);

                            return new HtmlString(
                                "<span class='font-bold text-warning-600'>Rp " .
                                    number_format($potongan, 0, ',', '.') .
                                    "</span>"
                            );
                        }),

                    Forms\Components\Placeholder::make('saldo_setelah')
                        ->label('Saldo Setelah Dipotong')
                        ->visible(
                            fn(Get $get) =>
                            $get('pakai_deposit') && $get('nama_deposit_pilihan')
                        )
                        ->content(function (Get $get) {

                            $nama = $get('nama_deposit_pilihan');
                            if (!$nama) return '-';

                            $saldo = Deposit::getSaldoPerNama($nama);
                            $potongan = (int) ($get('uang_jalan') ?? 0);
                            $sisa = $saldo - $potongan;

                            $color = $sisa < 0
                                ? 'text-danger-600'
                                : 'text-success-600';

                            return new HtmlString(
                                "<span class='font-bold {$color}'>Rp " .
                                    number_format($sisa, 0, ',', '.') .
                                    "</span>"
                            );
                        }),

                ])->columns(3),


            /*
            |--------------------------------------------------------------------------
            | HASIL PERHITUNGAN OTOMATIS
            |--------------------------------------------------------------------------
            */

            Forms\Components\Section::make('Hasil Perhitungan (Otomatis)')
                ->schema([

                    self::currencyPlaceholder('pendapatan_kotor', 'Omset (Pusat)'),
                    self::currencyPlaceholder('pendapatan_bersih', 'Net Income (Laba)'),
                    self::currencyPlaceholder('bonus_tonase', 'Bonus Tonase'),

                    // FIELD YANG MASUK KE DATABASE
                    Forms\Components\Hidden::make('harga_tonase_pusat'),
                    Forms\Components\Hidden::make('harga_tonase_vendor'),
                    Forms\Components\Hidden::make('pendapatan_kotor'),
                    Forms\Components\Hidden::make('pendapatan_bersih'),
                    Forms\Components\Hidden::make('bonus_tonase'),
                    Forms\Components\Hidden::make('harga_tonase_hidden'),
                    Forms\Components\Hidden::make('jarak_hidden'),
                    Forms\Components\Hidden::make('insentif_hidden'),
                    Forms\Components\Hidden::make('uang_jalan'),
                    Forms\Components\Hidden::make('uang_makan'),
                    Forms\Components\Hidden::make('insentif'),
                    Forms\Components\Hidden::make('bahan_bakar'),
                    Forms\Components\Hidden::make('pungli'),

                ])->columns(3),

        ]);
    }

    protected static function setRuteData(Set $set, $rute): void
    {
        $set('harga_tonase_pusat', $rute->harga_tonase_pusat ?? 0);
        $set('harga_tonase_vendor', $rute->harga_tonase_vendor ?? 0);
        $set('harga_tonase_hidden', $rute->harga_tonase_pusat ?? 0);
        $set('jarak_hidden', $rute->jarak ?? 0);
        $set('insentif_hidden', $rute->insentif ?? 0);
        $set('uang_jalan', $rute->uang_jalan ?? 0);
        $set('uang_makan', $rute->uang_makan ?? 0);
        $set('insentif', $rute->insentif ?? 0);
        $set('bahan_bakar', $rute->bahan_bakar ?? 0);
        $set('pungli', $rute->pungli ?? 0);
    }

    protected static function currencyPlaceholder(string $field, string $label)
    {
        return Forms\Components\Placeholder::make($field . '_view')
            ->label($label)
            ->content(
                fn(Get $get) =>
                'Rp ' . number_format(
                    (int) ($get($field) ?? 0),
                    0,
                    ',',
                    '.'
                )
            );
    }

    public static function calculateRevenue(Get $get, Set $set): void
    {
        $status    = $get('status');
        $tonase    = (float) ($get('tonase') ?? 0);
        $harga     = (float) ($get('harga_tonase_hidden') ?? 0);
        $jarak     = (float) ($get('jarak_hidden') ?? 0);
        $uangJalan = (float) ($get('uang_jalan') ?? 0);
        $isVendor  = (bool) $get('is_vendor');
        $insentif  = (float) ($get('insentif_hidden') ?? 0);

        if ($status === 'batal' || $harga <= 0 || $jarak <= 0) {
            $set('pendapatan_kotor', 0);
            $set('pendapatan_bersih', 0);
            $set('bonus_tonase', 0);
            return;
        }

        $kotor = round($tonase * $jarak * $harga);
        $set('pendapatan_kotor', $kotor);

        $bonusTotal = 0;
        if ($tonase > 30) {
            $kelebihanTonase = $tonase - 30;
            $tonUtuh = floor($kelebihanTonase);
            $desimal = $kelebihanTonase - $tonUtuh;

            if ($desimal > 0.50) {
                $tonUtuh += 1;
            }
            $bonusTotal = $tonUtuh * 30000;
        }

        $set('bonus_tonase', $bonusTotal);

        $bersih = $kotor - $uangJalan - $bonusTotal - $insentif;
        $set('pendapatan_bersih', round($bersih));
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('no_sjb')->label('SJB')->searchable(),
            Tables\Columns\TextColumn::make('tanggal')->date()->sortable(),
            Tables\Columns\TextColumn::make('vehicle.no_lambung')->label('Lambung'),
            Tables\Columns\TextColumn::make('tonase')->suffix(' Ton'),

            Tables\Columns\TextColumn::make('pendapatan_kotor')
                ->label('Gross Income')
                ->formatStateUsing(
                    fn($state) =>
                    'Rp ' . number_format((int) $state, 0, ',', '.')
                ),
            Tables\Columns\TextColumn::make('pendapatan_bersih')
                ->label('Net Income')
                ->formatStateUsing(
                    fn($state) =>
                    'Rp ' . number_format((int) $state, 0, ',', '.')
                ),
            Tables\Columns\TextColumn::make('bonus_tonase')
                ->label('Bonus')
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
