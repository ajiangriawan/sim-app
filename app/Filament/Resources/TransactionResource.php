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
                        ->live()
                        // Memicu kalkulasi saat Edit (Hydrated) dan Update
                        ->afterStateHydrated(fn(Set $set, $state, Get $get) => self::syncVehicleData($set, $state, $get))
                        ->afterStateUpdated(fn(Set $set, $state, Get $get) => self::syncVehicleData($set, $state, $get)),

                    Forms\Components\Select::make('rute_id')
                        ->label('Rute Perjalanan')
                        ->relationship('rute', 'nama_rute')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        // Memicu kalkulasi saat Edit (Hydrated) dan Update
                        ->afterStateHydrated(fn(Set $set, $state, Get $get) => self::syncRuteData($set, $state, $get))
                        ->afterStateUpdated(fn(Set $set, $state, Get $get) => self::syncRuteData($set, $state, $get)),

                    Forms\Components\TextInput::make('tonase')
                        ->label('Tonase (Ton)')
                        ->numeric()
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateRevenue($get, $set)),

                    Forms\Components\Select::make('status')
                        ->options(['selesai' => 'Selesai', 'batal' => 'Batal'])
                        ->default('selesai')
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateRevenue($get, $set)),
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
                        ->live(),

                    Forms\Components\Toggle::make('hitung_bonus')
                        ->label('Hitung Bonus Tonase?')
                        ->default(true)
                        ->live()
                        ->afterStateUpdated(fn(Get $get, Set $set) => self::calculateRevenue($get, $set)),

                    Forms\Components\Select::make('nama_deposit_pilihan')
                        ->label('Pilih Saldo Atas Nama')
                        ->options(Deposit::query()->distinct()->pluck('nama_pihak', 'nama_pihak')->toArray())
                        ->visible(fn(Get $get) => $get('pakai_deposit'))
                        ->required(fn(Get $get) => $get('pakai_deposit'))
                        ->searchable()
                        ->live(),

                    Forms\Components\Placeholder::make('info_saldo')
                        ->label('Informasi Saldo')
                        ->visible(fn(Get $get) => $get('pakai_deposit') && $get('nama_deposit_pilihan'))
                        ->content(function (Get $get) {
                            $nama = $get('nama_deposit_pilihan');
                            $saldo = $nama ? Deposit::getSaldoPerNama($nama) : 0;
                            $potongan = (int) ($get('uang_jalan') ?? 0);
                            $sisa = $saldo - $potongan;
                            $color = $sisa < 0 ? 'text-danger-600' : 'text-success-600';

                            return new HtmlString("
                                <div class='text-sm'>
                                    Saldo: <span class='font-bold text-primary-600'>Rp " . number_format($saldo, 0, ',', '.') . "</span><br>
                                    Sisa: <span class='font-bold {$color}'>Rp " . number_format($sisa, 0, ',', '.') . "</span>
                                </div>
                            ");
                        }),
                ])->columns(3),

            /*
            |--------------------------------------------------------------------------
            | HASIL PERHITUNGAN
            |--------------------------------------------------------------------------
            */
            Forms\Components\Section::make('Hasil Perhitungan (Otomatis)')
                ->schema([
                    self::currencyPlaceholder('pendapatan_kotor', 'Omset (Pusat)'),
                    self::currencyPlaceholder('pendapatan_bersih', 'Net Income (Laba)'),
                    self::currencyPlaceholder('bonus_tonase', 'Bonus Tonase'),

                    // State-only Hidden Fields (Tidak masuk DB tapi bantu hitung)
                    Forms\Components\Hidden::make('partai_hidden'),
                    Forms\Components\Hidden::make('harga_tonase_hidden'),
                    Forms\Components\Hidden::make('jarak_hidden'),
                    Forms\Components\Hidden::make('insentif_hidden'),

                    // Real Database Hidden Fields
                    Forms\Components\Hidden::make('harga_tonase_pusat'),
                    Forms\Components\Hidden::make('harga_tonase_vendor'),
                    Forms\Components\Hidden::make('pendapatan_kotor'),
                    Forms\Components\Hidden::make('pendapatan_bersih'),
                    Forms\Components\Hidden::make('bonus_tonase'),
                    Forms\Components\Hidden::make('uang_jalan'),
                    Forms\Components\Hidden::make('uang_makan'),
                    Forms\Components\Hidden::make('insentif'),
                ])->columns(3),
        ]);
    }

    // Helper: Sinkronisasi Data Kendaraan
    protected static function syncVehicleData(Set $set, $state, Get $get): void
    {
        if ($state) {
            $vehicle = Vehicle::find($state);
            $set('partai_hidden', $vehicle?->partai);
        }
        self::calculateRevenue($get, $set);
    }

    // Helper: Sinkronisasi Data Rute
    protected static function syncRuteData(Set $set, $state, Get $get): void
    {
        if ($state) {
            $rute = Rute::find($state);
            if ($rute) {
                $set('harga_tonase_pusat', $rute->harga_tonase_pusat ?? 0);
                $set('harga_tonase_vendor', $rute->harga_tonase_vendor ?? 0);
                $set('harga_tonase_hidden', $rute->harga_tonase_pusat ?? 0);
                $set('jarak_hidden', $rute->jarak ?? 0);
                $set('insentif_hidden', $rute->insentif ?? 0);
                $set('uang_jalan', $rute->uang_jalan ?? 0);
                $set('uang_makan', $rute->uang_makan ?? 0);
                $set('insentif', $rute->insentif ?? 0);
            }
        }
        self::calculateRevenue($get, $set);
    }

    public static function calculateRevenue(Get $get, Set $set): void
    {
        $status      = $get('status');
        $tonase      = (float) ($get('tonase') ?? 0);
        $harga       = (float) ($get('harga_tonase_hidden') ?? 0);
        $jarak       = (float) ($get('jarak_hidden') ?? 0);
        $uangJalan   = (float) ($get('uang_jalan') ?? 0);
        $insentif    = (float) ($get('insentif_hidden') ?? 0);
        $partai      = $get('partai_hidden');
        $hitungBonus = (bool) ($get('hitung_bonus') ?? true);

        if ($status === 'batal' || $harga <= 0 || $jarak <= 0) {
            $set('pendapatan_kotor', 0);
            $set('pendapatan_bersih', 0);
            $set('bonus_tonase', 0);
            return;
        }

        // 1. Hitung Omset
        $kotor = round($tonase * $jarak * $harga);
        $set('pendapatan_kotor', $kotor);

        // 2. Hitung Bonus Partai
        $bonusTotal = 0;
        if ($hitungBonus && $partai) {
            $threshold = 0;
            $partaiUpper = strtoupper($partai);

            if (str_contains($partaiUpper, 'KAFA')) {
                $threshold = 31.75;
            } elseif (str_contains($partaiUpper, 'STL')) {
                $threshold = 30.75;
            }

            if ($threshold > 0 && $tonase >= $threshold) {
                $selisih = $tonase - ($threshold - 1);
                $tonBonus = floor($selisih);
                $bonusTotal = $tonBonus * 30000;
            }
        }
        $set('bonus_tonase', $bonusTotal);

        // 3. Hitung Laba Bersih
        $bersih = $kotor - $uangJalan - $bonusTotal - $insentif;
        $set('pendapatan_bersih', round($bersih));
    }

    protected static function currencyPlaceholder(string $field, string $label)
    {
        return Forms\Components\Placeholder::make($field . '_view')
            ->label($label)
            ->content(fn(Get $get) => 'Rp ' . number_format((int)($get($field) ?? 0), 0, ',', '.'));
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('no_sjb')->label('SJB')->searchable(),
            Tables\Columns\TextColumn::make('tanggal')->date()->sortable(),
            Tables\Columns\TextColumn::make('vehicle.no_lambung')->label('Lambung'),
            Tables\Columns\TextColumn::make('tonase')->suffix(' Ton'),
            Tables\Columns\TextColumn::make('pendapatan_bersih')->label('Net')->money('IDR'),
            Tables\Columns\TextColumn::make('bonus_tonase')->label('Bonus')->money('IDR'),
        ])
            ->filters([
                Tables\Filters\SelectFilter::make('vehicle_id') // Gunakan nama kolom foreign key
                    ->label('Filter No Lambung')
                    ->relationship('vehicle', 'no_lambung') // Hubungkan ke relasi 'vehicle' dan tampilkan 'no_lambung'
                    ->searchable() // Tambahkan fitur pencarian agar mudah mencari nomor lambung
                    ->preload(),   // Load data di awal untuk UX yang lebih cepat
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
