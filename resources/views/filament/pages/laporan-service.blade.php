<x-filament-panels::page>
    <x-filament-panels::form wire:submit="exportExcel">
        {{ $this->form }}
        
        <div class="flex justify-end mt-4">
             <x-filament::button type="submit" color="success" icon="heroicon-o-arrow-down-tray">
                Unduh Laporan Service
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>