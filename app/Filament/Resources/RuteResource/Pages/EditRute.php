<?php

namespace App\Filament\Resources\RuteResource\Pages;

use App\Filament\Resources\RuteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRute extends EditRecord
{
    protected static string $resource = RuteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
