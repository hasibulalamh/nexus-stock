<?php

namespace Database\Seeders;

use App\Services\AreaImportService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class AreaSeeder extends Seeder
{
    public function __construct(private readonly ?string $fixturePath = null)
    {
    }

    public function run(AreaImportService $importer): void
    {
        $path = $this->fixturePath ?: base_path('database/data/areas_dhaka.csv');
        if (!File::exists($path)) {
            $this->command?->warn("Area CSV not found at {$path} - skipping area seed.");
            return;
        }

        $preview = $importer->apply($path);
        $this->command?->info("Imported areas: {$preview['to_create']} created, {$preview['to_update']} updated.");
    }
}
