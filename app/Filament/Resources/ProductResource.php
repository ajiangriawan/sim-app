<?php

// namespace App\Filament\Resources;

// use App\Filament\Resources\ProductResource\Pages;
// use App\Models\Product;
// use Filament\Forms;
// use Filament\Forms\Form;
// use Filament\Resources\Resource;
// use Filament\Tables;
// use Filament\Tables\Table;
// use Filament\Support\RawJs;

// class ProductResource extends Resource
// {
//     protected static ?string $model = Product::class;

//     protected static ?string $navigationIcon = 'heroicon-o-wrench';
//     protected static ?string $navigationGroup = 'Sparepart';
//     protected static ?string $pluralLabel = 'Input Stok Barang';
//     protected static ?int $navigationSort = 5;

//     protected static function currencyInput(string $name, string $label)
//     {
//         return Forms\Components\TextInput::make($name)
//             ->label($label)
//             ->prefix('Rp')
//             ->required()
//             ->mask(RawJs::make('$money($input)'))
//             ->stripCharacters(['.', ','])
//             ->numeric()
//             ->dehydrateStateUsing(
//                 fn($state) =>
//                 (int) str_replace(['.', ','], '', $state)
//             );
//     }

//     public static function form(Form $form): Form
//     {
//         return $form
//             ->schema([
//                 Forms\Components\Section::make('Detail Produk & Stok')
//                     ->description('Kelola informasi barang dan ketersediaan stok di bengkel tertentu.')
//                     ->schema([
//                         Forms\Components\Select::make('workshop_id')
//                             ->label('Bengkel')
//                             ->relationship('workshop', 'nama_bengkel')
//                             ->searchable()
//                             ->preload()
//                             ->required(),

//                         Forms\Components\TextInput::make('nama_barang')
//                             ->label('Nama Barang')
//                             ->required()
//                             ->maxLength(255),
//                         self::currencyInput('harga_barang', 'Harga Jual'),
//                         // Forms\Components\TextInput::make('diskon_barang')
//                         //     ->label('Diskon')
//                         //     ->numeric()
//                         //     ->prefix('Rp')
//                         //     ->default(0)
//                         //     ->required(),

//                         Forms\Components\TextInput::make('stok')
//                             ->label('Jumlah Stok')
//                             ->numeric()
//                             ->default(0)
//                             ->required(),
//                     ])->columns(2),
//             ]);
//     }

//     public static function table(Table $table): Table
//     {
//         return $table
//             ->columns([
//                 Tables\Columns\TextColumn::make('workshop.nama_bengkel')
//                     ->label('Bengkel')
//                     ->sortable()
//                     ->searchable(),

//                 Tables\Columns\TextColumn::make('nama_barang')
//                     ->label('Nama Barang')
//                     ->searchable()
//                     ->sortable(),

//                 Tables\Columns\TextColumn::make('harga_barang')
//                     ->label('Harga')
//                     ->money('idr')
//                     ->sortable(),

//                 Tables\Columns\TextColumn::make('stok')
//                     ->label('Sisa Stok')
//                     ->badge()
//                     ->color(fn(int $state): string => match (true) {
//                         $state <= 5 => 'danger',
//                         $state <= 15 => 'warning',
//                         default => 'success',
//                     })
//                     ->sortable(),
//             ])
//             ->filters([
//                 Tables\Filters\SelectFilter::make('workshop_id')
//                     ->label('Filter per Bengkel')
//                     ->relationship('workshop', 'nama_bengkel'),
//             ])
//             ->actions([
//                 Tables\Actions\EditAction::make(),
//                 Tables\Actions\DeleteAction::make(),
//             ])
//             ->bulkActions([
//                 Tables\Actions\BulkActionGroup::make([
//                     Tables\Actions\DeleteBulkAction::make(),
//                 ]),
//             ]);
//     }

//     public static function getPages(): array
//     {
//         return [
//             'index' => Pages\ListProducts::route('/'),
//             'create' => Pages\CreateProduct::route('/create'),
//             'edit' => Pages\EditProduct::route('/{record}/edit'),
//         ];
//     }
// }
