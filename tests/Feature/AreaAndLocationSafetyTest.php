<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\State;
use App\Services\AreaImportService;
use App\Services\LocationIdentityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AreaAndLocationSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_database_guard_rejects_non_memory_database(): void
    {
        config(['database.default' => 'mysql', 'database.connections.mysql.database' => 'inventory']);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('sqlite :memory:');
        try {
            $this->assertSafeTestDatabase();
        } finally {
            config(['database.default' => 'sqlite']);
        }
    }

    public function test_full_address_omits_null_upazila_without_stray_commas(): void
    {
        $country = Country::create(['name' => 'Bangladesh', 'code' => 'BD', 'slug' => 'bangladesh']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Dhaka', 'code' => 'DHK', 'slug' => 'dhaka']);
        $city = City::create(['state_id' => $state->id, 'name' => 'Dhaka', 'code' => 'DHK-C', 'slug' => 'dhaka-city']);
        $location = Location::create(['country_id' => $country->id, 'state_id' => $state->id, 'city_id' => $city->id, 'upazila_id' => null, 'area' => 'Gulshan-1', 'address_line' => 'House 1']);

        $this->assertSame('House 1, Gulshan-1, Dhaka, Dhaka, Bangladesh', $location->full_address);
        $this->assertStringNotContainsString(',,', $location->full_address);
    }

    public function test_empty_osm_rows_are_idempotently_upserted(): void
    {
        $country = Country::create(['name' => 'Bangladesh', 'code' => 'BD', 'slug' => 'bangladesh']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Dhaka', 'code' => 'DHK', 'slug' => 'dhaka']);
        City::create(['state_id' => $state->id, 'name' => 'Dhaka', 'code' => 'DHK-C', 'slug' => 'dhaka-city']);
        $path = tempnam(sys_get_temp_dir(), 'areas').'.csv';
        file_put_contents($path, "osm_id,area,area_bn,type,osm_place,lat,lng,city,thana_hint,source,verified,notes\n,মিরপুর,মিরপুর,area,,23.8,90.3,Dhaka,,test,no,\n");

        $service = app(AreaImportService::class);
        $service->apply($path);
        $service->apply($path);

        $this->assertSame(1, Area::count());
        unlink($path);
    }

    public function test_location_identity_keeps_null_upazila_and_area_details_distinct(): void
    {
        $country = Country::create(['name' => 'Bangladesh', 'code' => 'BD', 'slug' => 'bangladesh']);
        $state = State::create(['country_id' => $country->id, 'name' => 'Dhaka', 'code' => 'DHK', 'slug' => 'dhaka']);
        $city = City::create(['state_id' => $state->id, 'name' => 'Dhaka', 'code' => 'DHK-C', 'slug' => 'dhaka-city']);
        $service = app(LocationIdentityService::class);
        $base = ['country_id' => $country->id, 'state_id' => $state->id, 'city_id' => $city->id, 'upazila_id' => null, 'area_id' => null, 'address_line' => null];
        $first = $service->findOrCreate([...$base, 'area' => 'Mirpur-10']);
        $second = $service->findOrCreate([...$base, 'area' => 'Gulshan-1']);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, Location::count());
    }
}
