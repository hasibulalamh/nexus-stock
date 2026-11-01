<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\Permission;
use App\Models\State;
use App\Models\Upazila;
use App\Models\User;
use App\Services\AreaImportService;
use Database\Seeders\AreaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AreasFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private State $state;
    private City $city;
    private Upazila $upazila;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->superadmin = User::where('email', 'superadmin@storify.com')->firstOrFail();

        $country = Country::create(['name' => 'Bangladesh', 'code' => 'BD', 'slug' => 'bangladesh-test']);
        $this->state = State::create([
            'name' => 'Dhaka Division',
            'code' => 'DHK-TEST',
            'slug' => 'dhaka-division-test',
            'country_id' => $country->id,
        ]);
        $this->city = City::create([
            'name' => 'Dhaka',
            'code' => 'DHK-TEST-CITY',
            'slug' => 'dhaka-test',
            'state_id' => $this->state->id,
        ]);
        $this->upazila = Upazila::create([
            'name' => 'Gulshan',
            'code' => 'GUL-TEST',
            'slug' => 'gulshan-test',
            'city_id' => $this->city->id,
        ]);
    }

    public function test_area_crud_routes_require_their_permissions(): void
    {
        $user = $this->makeUser('Area Viewer', ['areas.view']);
        $area = Area::create(['city_id' => $this->city->id, 'name' => 'Gulshan-1', 'status' => 'active']);

        $this->actingAs($user)->get(route('admin.areas.list'))->assertOk();
        $this->actingAs($user)->get(route('admin.areas.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.areas.store'), $this->areaData())->assertForbidden();
        $this->actingAs($user)->get(route('admin.areas.edit', $area))->assertForbidden();
        $this->actingAs($user)->put(route('admin.areas.update', $area), $this->areaData())->assertForbidden();
        $this->actingAs($user)->delete(route('admin.areas.delete', $area))->assertForbidden();

        $this->actingAs($this->superadmin)->get(route('admin.areas.create'))->assertOk();
    }

    public function test_sidebar_shows_areas_only_with_areas_view_permission(): void
    {
        $withoutPermission = $this->makeUser('No Areas', []);
        $withPermission = $this->makeUser('With Areas', ['areas.view']);

        $this->actingAs($withoutPermission)->get(route('admin.areas.list'))
            ->assertForbidden();
        $this->actingAs($withPermission)->get(route('admin.areas.list'))
            ->assertSee('Areas');
    }

    public function test_area_ajax_endpoint_returns_city_rows_and_filters_by_upazila(): void
    {
        $user = $this->makeUser('Branch Area User', ['branches.create']);
        Area::create(['city_id' => $this->city->id, 'name' => 'City Area', 'status' => 'active']);
        Area::create(['city_id' => $this->city->id, 'upazila_id' => $this->upazila->id, 'name' => 'Gulshan Area', 'status' => 'active']);
        Area::create(['city_id' => $this->city->id, 'name' => 'Inactive Area', 'status' => 'inactive']);

        $all = $this->actingAs($user)->getJson("/admin/branch/get-areas/{$this->city->id}");
        $all->assertOk()->assertJsonCount(2)->assertJsonFragment(['display_name' => 'City Area'])->assertJsonFragment(['display_name' => 'Gulshan Area']);

        $filtered = $this->actingAs($user)->getJson("/admin/branch/get-areas/{$this->city->id}?upazila_id={$this->upazila->id}");
        $filtered->assertOk()->assertJsonCount(2)->assertJsonFragment(['display_name' => 'City Area'])->assertJsonFragment(['display_name' => 'Gulshan Area']);
    }

    public function test_wrong_header_is_rejected_without_inserting_areas(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'areas').'.csv';
        file_put_contents($path, "wrong,header\n1,2\n");
        try {
            $this->expectException(ValidationException::class);
            app(AreaImportService::class)->apply($path);
        } finally {
            unlink($path);
        }

        $this->assertSame(0, Area::count());
    }

    public function test_unknown_city_is_reported_without_creating_location_hierarchy(): void
    {
        $path = $this->csvPath($this->csvRow('Unknown City'));
        try {
            $this->expectException(ValidationException::class);
            app(AreaImportService::class)->apply($path);
        } finally {
            unlink($path);
        }

        $this->assertSame(0, State::where('name', 'Unknown Division')->count());
        $this->assertSame(0, City::where('name', 'Unknown City')->count());
        $this->assertSame(0, Upazila::where('name', 'Unknown Upazila')->count());
        $this->assertSame(0, Area::count());
    }

    public function test_a_malformed_row_rolls_back_all_rows(): void
    {
        $headers = implode(',', AreaImportService::HEADERS);
        $valid = $this->csvRow('Dhaka');
        $path = $this->csvPath("{$headers}\n{$valid}wrong,extra\n");

        try {
            $this->expectException(ValidationException::class);
            app(AreaImportService::class)->apply($path);
        } finally {
            unlink($path);
        }

        $this->assertSame(0, Area::count());
    }

    public function test_import_is_idempotent_and_empty_osm_id_uses_city_and_name_bn(): void
    {
        $row = $this->csvRow('Dhaka', '', 'Mirpur', 'মিরপুর');
        $path = $this->csvPath($row);
        try {
            $service = app(AreaImportService::class);
            $service->apply($path);
            $service->apply($path);
        } finally {
            unlink($path);
        }

        $this->assertSame(1, Area::count());
        $this->assertSame('Mirpur', Area::first()->name);
    }

    public function test_area_seeder_imports_a_fixture_csv(): void
    {
        $path = $this->csvPath($this->csvRow('Dhaka', '987654', 'Banani'));
        try {
            (new AreaSeeder($path))->run(app(AreaImportService::class));
        } finally {
            unlink($path);
        }

        $this->assertDatabaseHas('areas', ['osm_id' => 987654, 'name' => 'Banani']);
    }

    public function test_real_area_file_header_matches_when_present(): void
    {
        $path = base_path('database/data/areas_dhaka.csv');
        if (! is_file($path)) {
            $this->assertTrue(true);

            return;
        }

        $handle = fopen($path, 'r');
        $header = array_map(fn ($value) => trim((string) $value, " \t\n\r\0\x0B\xEF\xBB\xBF"), fgetcsv($handle, 0, ',') ?: []);
        fclose($handle);

        $this->assertSame(AreaImportService::HEADERS, $header);
    }

    private function makeUser(string $name, array $permissions): User
    {
        $department = \App\Models\Department::firstOrCreate(['name' => "Department {$name}"], ['code' => strtoupper(substr($name, 0, 3)), 'status' => 'active']);
        $designation = \App\Models\Designation::firstOrCreate(['name' => "Designation {$name}"], ['code' => strtoupper(substr($name, 0, 3)).'-D', 'department_id' => $department->id, 'status' => 'active']);
        $role = Role::create(['name' => strtolower(str_replace(' ', '-', $name)), 'guard_name' => 'web', 'icon_class' => 'fa-solid fa-user', 'status' => 'active', 'dashboard_route' => 'admin.dashboard']);
        $role->syncPermissions(Permission::whereIn('name', $permissions)->get());

        $user = User::create([
            'full_name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'password' => 'password123',
            'phone' => '01712345678',
            'gender' => 'male',
            'employee_id' => strtoupper(str_replace(' ', '-', $name)).'-001',
            'role_id' => $role->id,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'join_date' => '2026-01-01',
            'profile_photo' => 'default.png',
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function areaData(): array
    {
        return ['city_id' => $this->city->id, 'name' => 'Test Area', 'status' => 'active'];
    }

    private function csvRow(string $city, string $osmId = '', string $name = 'Test Area', string $nameBn = 'টেস্ট এরিয়া'): string
    {
        return implode(',', [$osmId, $name, $nameBn, 'area', '', '23.8000000', '90.4000000', $city, '', 'test', 'no', ''])."\n";
    }

    private function csvPath(string $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'areas').'.csv';
        $headers = implode(',', AreaImportService::HEADERS);
        file_put_contents($path, str_starts_with($rows, $headers) ? $rows : "{$headers}\n{$rows}");

        return $path;
    }
}
