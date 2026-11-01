<?php

namespace App\Services;

use App\Models\Area;
use App\Models\City;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AreaImportService
{
    public const HEADERS = [
        'osm_id', 'area', 'area_bn', 'type', 'osm_place', 'lat', 'lng',
        'city', 'thana_hint', 'source', 'verified', 'notes',
    ];

    public function analyse(string|UploadedFile $file): array
    {
        $handle = $this->open($file);
        $header = fgetcsv($handle, 0, ',');
        $header = $this->normaliseHeader($header ?: []);

        if ($header !== self::HEADERS) {
            fclose($handle);

            return [
                'total' => 0,
                'to_create' => 0,
                'to_update' => 0,
                'errors' => [['row' => 1, 'message' => 'CSV header does not match the expected columns exactly.']],
            ];
        }

        $preview = ['total' => 0, 'to_create' => 0, 'to_update' => 0, 'errors' => []];
        $rowNumber = 1;
        while (($values = fgetcsv($handle, 0, ',')) !== false) {
            $rowNumber++;
            if ($this->isEmptyRow($values)) {
                continue;
            }
            if (count($values) !== count(self::HEADERS)) {
                $preview['errors'][] = ['row' => $rowNumber, 'message' => 'The row does not contain exactly the expected number of columns.'];

                continue;
            }

            $preview['total']++;
            $row = $this->normalise(array_combine(self::HEADERS, array_pad($values, count(self::HEADERS), null)));
            $city = City::where('name', $row['city'])->first();
            if (! $city) {
                $preview['errors'][] = ['row' => $rowNumber, 'message' => "City '{$row['city']}' was not found."];

                continue;
            }

            $existing = $this->findExisting($city->id, $row);
            $preview[$existing ? 'to_update' : 'to_create']++;
        }
        fclose($handle);

        return $preview;
    }

    public function apply(string|UploadedFile $file): array
    {
        $preview = $this->analyse($file);
        if ($preview['errors']) {
            throw ValidationException::withMessages([
                'csv_file' => collect($preview['errors'])->map(fn ($error) => "Row {$error['row']}: {$error['message']}")->implode(' '),
            ]);
        }

        return DB::transaction(function () use ($file, $preview) {
            $handle = $this->open($file);
            fgetcsv($handle, 0, ',');
            while (($values = fgetcsv($handle, 0, ',')) !== false) {
                if ($this->isEmptyRow($values)) {
                    continue;
                }

                $row = $this->normalise(array_combine(self::HEADERS, array_pad($values, count(self::HEADERS), null)));
                $city = City::where('name', $row['city'])->firstOrFail();
                $area = $this->findExisting($city->id, $row) ?: new Area;
                $area->fill([
                    ...collect($row)->except('city')->all(),
                    'city_id' => $city->id,
                    'upazila_id' => null,
                    'verified' => $row['verified'] ?? false,
                    'status' => 'active',
                ]);
                $area->save();
            }
            fclose($handle);

            return $preview;
        });
    }

    private function open(string|UploadedFile $file)
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw ValidationException::withMessages(['csv_file' => 'The CSV file could not be opened.']);
        }

        return $handle;
    }

    private function normalise(array $row): array
    {
        $row = array_map(fn ($value) => $this->nullable(trim((string) $value)), $row);
        $row['osm_id'] = $row['osm_id'] !== null && ctype_digit($row['osm_id']) ? (int) $row['osm_id'] : null;
        $row['lat'] = is_numeric($row['lat']) ? $row['lat'] : null;
        $row['lng'] = is_numeric($row['lng']) ? $row['lng'] : null;
        $row['verified'] = in_array(strtolower((string) $row['verified']), ['1', 'true', 'yes'], true);

        return [
            'osm_id' => $row['osm_id'],
            'name' => $row['area'],
            'name_bn' => $row['area_bn'],
            'type' => $row['type'],
            'osm_place' => $row['osm_place'],
            'lat' => $row['lat'],
            'lng' => $row['lng'],
            'city' => $row['city'],
            'thana_hint' => $row['thana_hint'],
            'source' => $row['source'],
            'verified' => $row['verified'],
            'notes' => $row['notes'],
        ];
    }

    private function normaliseHeader(array $header): array
    {
        $header = array_map(fn ($value) => trim((string) $value), $header);
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }

        return $header;
    }

    private function findExisting(int $cityId, array $row): ?Area
    {
        return $row['osm_id'] !== null
            ? Area::where('osm_id', $row['osm_id'])->first()
            : Area::where('city_id', $cityId)->where('name_bn', $row['name_bn'])->first();
    }

    private function nullable(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    private function isEmptyRow(array $values): bool
    {
        return count($values) === 0 || ! filled(implode('', $values));
    }
}
