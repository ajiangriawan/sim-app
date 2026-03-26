<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepositResource\Pages;
use App\Models\Deposit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\RawJs;

class DepositResource extends Resource
{
    protected static ?string $model = Deposit::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Hauling';
    protected static ?string $pluralLabel = 'Input Histori Deposit';
    protected static ?int $navigationSort = 3;

    protected static function currencyInput(string $name, string $label)
    {
        return Forms\Components\TextInput::make($name)
            ->label($label)
            ->prefix('Rp')
            ->required()
            ->mask(RawJs::make('$money($input)'))
            ->stripCharacters(['.', ','])
            ->numeric()
            ->dehydrateStateUsing(
                fn($state) =>
                (int) str_replace(['.', ','], '', $state)
            );
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Deposit')
                    ->description('Masukkan informasi pengirim dan jumlah dana yang diterima.')
                    ->schema([
                        // Disinkronkan ke 'nama_pihak' sesuai migration baru
                        Forms\Components\Select::make('nama_pihak')
                            ->label('Nama Pengirim / Pemilik')
                            ->options(
                                Deposit::query()
                                    ->distinct()
                                    ->pluck('nama_pihak', 'nama_pihak')
                                    ->toArray()
                            )
                            ->searchable()
                            ->required()
                            // Fitur untuk menambah nama baru langsung dari select
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nama_pihak')
                                    ->label('Nama Pihak Baru')
                                    ->dehydrateStateUsing(fn(?string $state) => strtoupper($state))
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->required(),
                            ])
                            ->createOptionUsing(fn(array $data) => $data['nama_pihak']),

                        Forms\Components\DatePicker::make('tanggal_deposit')
                            ->label('Tanggal Deposit')
                            ->required()
                            ->default(now())
                            ->native(false) // UI lebih modern
                            ->displayFormat('d/m/Y'),

                        self::currencyInput('jumlah_deposit', 'Jumlah Deposit'),

                        // Menghubungkan otomatis ke user yang sedang login
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan / Sumber Dana')
                            ->dehydrateStateUsing(fn(?string $state) => strtoupper($state))
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->placeholder('Contoh: Deposit Operasional Januari dari PT. XYZ')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_pihak')
                    ->label('Nama Pihak')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tanggal_deposit')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('jumlah_deposit')
                    ->label('Jumlah')
                    ->money('idr')
                    ->sortable()
                    // Menampilkan total otomatis di bawah tabel
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total Saldo')),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->keterangan),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diinput Pada')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal_deposit', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('nama_pihak')
                    ->label('Filter Pihak')
                    ->options(fn() => Deposit::query()->distinct()->pluck('nama_pihak', 'nama_pihak')->toArray()),

                Tables\Filters\Filter::make('tanggal_deposit')
                    ->form([
                        Forms\Components\DatePicker::make('dari'),
                        Forms\Components\DatePicker::make('sampai'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['dari'], fn($q) => $q->whereDate('tanggal_deposit', '>=', $data['dari']))
                            ->when($data['sampai'], fn($q) => $q->whereDate('tanggal_deposit', '<=', $data['sampai']));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari'] ?? null) $indicators[] = 'Dari: ' . $data['dari'];
                        if ($data['sampai'] ?? null) $indicators[] = 'Sampai: ' . $data['sampai'];
                        return $indicators;
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeposits::route('/'),
            'create' => Pages\CreateDeposit::route('/create'),
            'edit' => Pages\EditDeposit::route('/{record}/edit'),
        ];
    }
}
