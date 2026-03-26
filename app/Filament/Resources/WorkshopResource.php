<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkshopResource\Pages;
use App\Models\Workshop;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkshopResource extends Resource
{
    protected static ?string $model = Workshop::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $navigationGroup = 'Hauling';
    protected static ?string $pluralLabel = 'Input Data Bengkel';
    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Bengkel')
                    ->description('Masukkan detail bengkel mitra untuk keperluan service armada.')
                    ->schema([
                        Forms\Components\TextInput::make('nama_bengkel')
                            ->label('Nama Bengkel')
                            ->required()
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn (?string $state) => strtoupper($state))
    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->placeholder('Contoh: Bengkel Maju Jaya'),

                        Forms\Components\TextInput::make('lokasi')
                            ->label('Lokasi/Alamat')
                            ->required()
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn (?string $state) => strtoupper($state))
    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->placeholder('Contoh: Jl. Sudirman No. 123, Jakarta'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_bengkel')
                    ->label('Nama Bengkel')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('lokasi')
                    ->label('Lokasi')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar Sejak')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkshops::route('/'),
            'create' => Pages\CreateWorkshop::route('/create'),
            'edit' => Pages\EditWorkshop::route('/{record}/edit'),
        ];
    }
}
