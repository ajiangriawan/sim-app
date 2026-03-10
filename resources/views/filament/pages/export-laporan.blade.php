<x-filament-panels::page>
    <x-filament-panels::form wire:submit="submit">
        {{ $this->form }}

        <div class="flex justify-end mt-4">
            <x-filament::button type="submit" color="success">
                Generate & Download Excel
            </x-filament::button>
        </div>
    </x-filament-panels::form>
</x-filament-panels::page>
