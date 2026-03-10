<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use App\Models\Deposit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\RawJs;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;
    protected static ?string $navigationIcon  = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Hauling';
    protected static ?string $pluralLabel     = 'Input Data Service';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(3)->schema([

                /* ================= INFORMASI INVOICE & UNIT ================= */
                Forms\Components\Section::make('Informasi Invoice')
                    ->schema([
                        Forms\Components\TextInput::make('no_invoice')
                            ->label('No. Invoice')
                            ->default(fn() => self::generateInvoiceNumber())
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\DatePicker::make('tanggal_service')
                            ->default(now())
                            ->required(),

                        Forms\Components\Select::make('is_vendor')
                            ->label('Tipe Kendaraan')
                            ->options([1 => 'Vendor', 0 => 'Pusat'])
                            ->default(0)
                            ->live()
                            ->required()
                            ->afterStateUpdated(fn(Set $set) => $set('vehicle_id', null)),

                        Forms\Components\Select::make('vehicle_id')
                            ->label('Unit Kendaraan')
                            ->relationship(
                                name: 'vehicle',
                                titleAttribute: 'no_lambung',
                                modifyQueryUsing: fn(Get $get, $query) => $query->where('is_vendor', (bool) $get('is_vendor'))
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('workshop_id')
                            ->label('Bengkel Tujuan')
                            ->relationship('workshop', 'nama_bengkel')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),

                /* ================= ITEM SERVICE (REPEATER) ================= */
                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->schema([
                        Forms\Components\TextInput::make('nama_item')
                            ->label('Nama Barang / Jasa')
                            ->required()
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Qty')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updateItemSubtotal($get, $set);
                                self::updateGrandTotal($get, $set);
                            }),

                        Forms\Components\TextInput::make('harga_satuan')
                            ->label('Harga Satuan')
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
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updateItemSubtotal($get, $set);
                                self::updateGrandTotal($get, $set);
                            }),

                        Forms\Components\TextInput::make('diskon_item')
                            ->label('Diskon Total')
                            ->prefix('Rp')
                            ->default(0)
                            ->required()
                            ->live(onBlur: true)
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(['.', ','])
                            ->numeric()
                            ->dehydrateStateUsing(
                                fn($state) =>
                                (int) str_replace(['.', ','], '', $state ?? 0)
                            )
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updateItemSubtotal($get, $set);
                                self::updateGrandTotal($get, $set);
                            }),

                        // Tampilan ke user (Rapi dengan format Rp)
                        Forms\Components\Placeholder::make('subtotal_view')
                            ->label('Subtotal')
                            ->content(function (Get $get) {
                                $amount = (float) ($get('subtotal') ?? 0);
                                return 'Rp ' . number_format($amount, 0, ',', '.');
                            }),

                        // Field yang masuk ke database
                        Forms\Components\Hidden::make('subtotal')->dehydrated(),
                    ])
                    ->columns(6)
                    ->live()
                    // Penting: Update grand total jika baris dihapus
                    ->afterStateUpdated(fn(Get $get, Set $set) => self::updateGrandTotal($get, $set))
                    ->addActionLabel('Tambah Baris Item')
                    ->columnSpanFull(),
                /* ================= KEUANGAN & SALDO ================= */
                Forms\Components\Section::make('Keuangan & Saldo')
                    ->schema([
                        Forms\Components\Toggle::make('pakai_deposit')
                            ->label('Potong Saldo Deposit?')
                            ->default(true)
                            ->live(),

                        Forms\Components\Select::make('nama_deposit_pilihan')
                            ->label('Pilih Saldo Atas Nama')
                            ->options(fn() => Deposit::distinct()->pluck('nama_pihak', 'nama_pihak'))
                            ->searchable()
                            ->live()
                            ->visible(fn(Get $get) => $get('pakai_deposit'))
                            ->required(fn(Get $get) => $get('pakai_deposit')),

                        Forms\Components\Placeholder::make('saldo_display')
                            ->label('Saldo Saat Ini')
                            ->visible(fn(Get $get) => $get('pakai_deposit'))
                            ->content(function (Get $get) {
                                $nama = $get('nama_deposit_pilihan');
                                if (!$nama) return 'Pilih nama dahulu';
                                return 'Rp ' . number_format(Deposit::getSaldoPerNama($nama), 0, ',', '.');
                            }),

                        Forms\Components\Placeholder::make('total_biaya_view')
                            ->label('Grand Total Service')
                            ->content(fn(Get $get) => 'Rp ' . number_format((float)($get('total_biaya') ?? 0), 0, ',', '.')),

                        Forms\Components\Hidden::make('total_biaya')->dehydrated(),

                        Forms\Components\Placeholder::make('simulasi_sisa_saldo')
                            ->label('Estimasi Sisa Saldo')
                            ->visible(fn(Get $get) => $get('pakai_deposit') && $get('nama_deposit_pilihan'))
                            ->content(function (Get $get) {
                                $nama = $get('nama_deposit_pilihan');
                                $total = (float) ($get('total_biaya') ?? 0);
                                $saldo = (float) Deposit::getSaldoPerNama($nama);
                                $sisa = $saldo - $total;
                                $color = $sisa < 0 ? 'text-danger-600' : 'text-success-600';
                                return new HtmlString("<span class='font-bold {$color}'>Rp " . number_format($sisa, 0, ',', '.') . "</span>");
                            }),
                    ])
                    ->columns(2),
            ]),
        ]);
    }

    /* ================= LOGIKA KALKULASI ================= */
    public static function updateItemSubtotal(Get $get, Set $set): void
    {
        // Bersihkan karakter titik/koma jika masih terbawa saat kalkulasi
        $qty    = (float) ($get('quantity') ?? 0);
        $harga  = (float) str_replace(['.', ','], '', $get('harga_satuan') ?? 0);
        $diskon = (float) str_replace(['.', ','], '', $get('diskon_item') ?? 0);

        $subtotal = ($harga * $qty) - $diskon;
        $set('subtotal', max($subtotal, 0));
    }
    public static function updateGrandTotal(Get $get, Set $set): void
    {
        // Ambil semua subtotal dari baris repeater
        $items = collect($get('items') ?? []);
        $total = $items->sum(fn($item) => (float) ($item['subtotal'] ?? 0));

        $set('total_biaya', $total);
    }

    protected static function generateInvoiceNumber(): string
    {
        $romans = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
        $noUrut = (Service::count() + 1);
        $paddedNo = str_pad($noUrut, 3, '0', STR_PAD_LEFT);
        $bulanRomawi = $romans[date('n')];
        $tahun = date('Y');
        return "{$paddedNo}/BALINK/PLG/{$bulanRomawi}/{$tahun}";
    }

    /* ================= TABLE VIEW ================= */

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_invoice')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('tanggal_service')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('vehicle.no_lambung')->label('Unit')->searchable(),
                Tables\Columns\TextColumn::make('total_biaya')
                    ->label('Total Biaya')
                    ->money('idr')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total Keseluruhan')),
            ])
            ->defaultSort('tanggal_service', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit'   => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
