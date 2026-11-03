<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class GenerateProductBarcodesCommand extends Command
{
    protected $signature = 'products:generate-barcodes {--dry-run}';
    protected $description = 'Generate internal EAN-13 barcodes for products without one';

    public function handle(): int
    {
        $products = Product::query()->whereNull('barcode')->orderBy('id')->get();
        $this->info(($this->option('dry-run') ? 'Would generate ' : 'Generating ').$products->count().' barcode(s).');

        if (! $this->option('dry-run')) {
            foreach ($products as $product) {
                $product->update(['barcode' => Product::internalBarcodeForId($product->id)]);
            }
        }

        return self::SUCCESS;
    }
}
