<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pastikan 'items' ada dan merupakan array sebelum di-loop
        $items = $data['items'] ?? [];

        foreach ($items as $item) {
            // Validasi jika product_id atau quantity kosong
            if (empty($item['product_id']) || empty($item['quantity'])) {
                continue;
            }

            $product = Product::find($item['product_id']);
            
            if ($product && $item['quantity'] > $product->stok) {
                throw new \Exception("Stok untuk produk '{$product->nama_barang}' tidak mencukupi. Sisa: {$product->stok}");
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}