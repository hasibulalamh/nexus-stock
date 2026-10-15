<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Upazila;
use App\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class BangladeshLocationSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = base_path('database/data/bangladesh_divisions_districts_upazilas_bn_en.csv');

        if (!File::exists($csvPath)) {
            $this->command->warn("CSV not found at {$csvPath} — skipping Bangladesh location seed.");
            return;
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            $this->command->warn("Could not open {$csvPath} — skipping Bangladesh location seed.");
            return;
        }

        $row = fgetcsv($handle, 0, ',');
        if ($row === false || count($row) < 6) {
            fclose($handle);
            $this->command->warn("CSV header missing expected columns — skipping Bangladesh location seed.");
            return;
        }

        $bar = $this->command->getOutput()->createProgressBar();
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $bar->setMessage('divisions');
        $bar->start(0);

        $country = Country::firstOrCreate(
            ['name' => 'Bangladesh'],
            [
                'code' => 'BGD',
                'slug' => 'bangladesh',
            ]
        );

        $divisions = [];
        $rowCount = 0;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if ($row === null || count($row) < 6) {
                continue;
            }

            $divisionEn = trim($row[1] ?? '');
            $districtEn = trim($row[3] ?? '');
            $upazilaEn  = trim($row[5] ?? '');
            $postcode   = trim($row[6] ?? '') !== '' ? trim($row[6] ?? '') : null;

            if (!$divisionEn || !$districtEn || !$upazilaEn) {
                continue;
            }

            $state = State::firstOrCreate(
                ['name' => $divisionEn, 'country_id' => $country->id],
                [
                    'code' => strtoupper(substr($divisionEn, 0, 3)) . '-' . ($rowCount + 100),
                    'slug' => str_replace(' ', '-', strtolower($divisionEn)),
                ]
            );

            if (!isset($divisions[$divisionEn])) {
                $divisions[$divisionEn] = true;
                $bar->setMessage('divisions');
                $bar->advance();
            }

            $city = City::firstOrCreate(
                ['name' => $districtEn, 'state_id' => $state->id],
                [
                    'code' => strtoupper(substr($divisionEn, 0, 3)) . '-' . ($rowCount + 200),
                    'slug' => str_replace(' ', '-', strtolower($districtEn . '-' . $rowCount)),
                ]
            );

            $upazila = Upazila::firstOrCreate(
                ['name' => $upazilaEn, 'city_id' => $city->id],
                [
                    'code' => strtoupper(substr($upazilaEn, 0, 3)) . '-' . ($rowCount + 300),
                    'slug' => str_replace(' ', '-', strtolower($upazilaEn)),
                ]
            );

            if ($postcode !== null) {
                Upazila::where('id', $upazila->id)->update([
                    'postcode' => $postcode,
                ]);
            }

            $rowCount++;
            $bar->setMessage("upazilas: {$rowCount}");
        }

        fclose($handle);
        $bar->finish();

        $this->command->newLine(1);
        $this->command->info("Seeded Bangladesh hierarchy: 1 country, " . count($divisions) . " divisions, upazilas processed: {$rowCount}");
    }
}