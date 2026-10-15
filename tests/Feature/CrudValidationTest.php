<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CrudValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function afterRefreshingDatabase()
    {
        // Only seed permission seeder + roles/users the tests create.
        // BangladeshLocationSeeder is omitted to avoid UniqueConstraintViolationException.
        // Also seed admin user so CRUD tests can find it.
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->admin = User::where('email', 'superadmin@storify.com')->firstOrFail();
    }

    // ========================= DEPARTMENT TESTS =========================

    /** TEST-020 */
    #[Test]
    public function department_can_be_created_with_valid_data(): void
    {
        $data = [
            'name' => 'Test Department',
            'code' => 'TST-DEP-001',
            'status' => 'active',
            'join_date' => '2026-01-01',
        ];

        $response = $this->actingAs($this->admin)->post(
            route('admin.departments.store'),
            $data
        );

        $response->assertRedirect(route('admin.departments.list'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('departments', ['name' => 'Test Department']);
    }

    /** TEST-021 */
    #[Test]
    public function department_creation_fails_with_missing_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->from(route('admin.departments.create'))
            ->post(route('admin.departments.store'), []);

        $response->assertRedirect(route('admin.departments.create'));
        $response->assertSessionHasErrors(['name', 'code', 'status']);
    }

    /** TEST-022 */
    #[Test]
    public function department_status_must_be_valid_enum(): void
    {
        $response = $this->actingAs($this->admin)->from(route('admin.departments.create'))
            ->post(route('admin.departments.store'), [
                'name' => 'Dep',
                'code' => 'D01',
                'status' => 'invalid-status',
            ]);

        $response->assertSessionHasErrors('status');
    }

    /** TEST-023 */
    #[Test]
    public function department_can_be_updated(): void
    {
        $dept = Department::firstOrCreate(
            ['name' => 'Original'],
            ['code' => 'ORIG', 'status' => 'active']
        );

        $response = $this->actingAs($this->admin)->put(
            route('admin.departments.update', $dept->id),
            ['name' => 'Updated', 'code' => 'UPDT', 'status' => 'inactive']
        );

        $response->assertRedirect(route('admin.departments.list'));
        $this->assertDatabaseHas('departments', ['id' => $dept->id, 'name' => 'Updated']);
    }

    /** TEST-024 */
    #[Test]
    public function department_can_be_deleted(): void
    {
        $dept = Department::create(['name' => 'ToDelete', 'code' => 'DEL', 'status' => 'active']);

        $response = $this->actingAs($this->admin)->delete(
            route('admin.departments.delete', $dept->id)
        );

        $response->assertRedirect(route('admin.departments.list'));
        $this->assertModelMissing($dept);
    }

    // ========================= DESIGNATION TESTS =========================

    /** TEST-025 */
    #[Test]
    public function designation_requires_valid_department_foreign_key(): void
    {
        $response = $this->actingAs($this->admin)->from(route('admin.designations.create'))
            ->post(route('admin.designations.store'), [
                'name' => 'Bad FK Desig',
                'code' => 'BADFK',
                'department_id' => 999999,
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors('department_id');
    }

    /** TEST-026 */
    #[Test]
    public function designation_unique_name_is_enforced(): void
    {
        $dept = Department::firstOrFail();
        Designation::create([
            'name' => 'DuplicateName',
            'code' => 'FIRST',
            'department_id' => $dept->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin)->from(route('admin.designations.create'))
            ->post(route('admin.designations.store'), [
                'name' => 'DuplicateName',
                'code' => 'SECOND',
                'department_id' => $dept->id,
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors('name');
    }

    // ========================= ROLE TESTS =========================

    /** TEST-027 */
    #[Test]
    public function role_icon_class_validation_requires_fontawesome_prefix(): void
    {
        $response = $this->actingAs($this->admin)->from(route('admin.roles.create'))
            ->post(route('admin.roles.store'), [
                'name' => 'NewRole',
                'icon_class' => 'not-fa-icon',
                'parent_id' => null,
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors('icon_class');
    }

    /** TEST-028 */
    #[Test]
    public function role_store_validation_name_unique(): void
    {
        // Use superadmin name already seeded
        $response = $this->actingAs($this->admin)->from(route('admin.roles.create'))
            ->post(route('admin.roles.store'), [
                'name' => 'superadmin',
                'icon_class' => 'fa-duplicate',
                'parent_id' => null,
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors('name');
    }

    // ========================= USER TESTS =========================

    /** TEST-029 */
    #[Test]
    public function user_store_validation_password_confirmation_required(): void
    {
        $role = Role::firstOrFail();
        $dept = Department::firstOrFail();
        $desig = Designation::firstOrFail();

        $response = $this->actingAs($this->admin)->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'full_name' => 'Test',
                'email' => 'tester@example.com',
                'password' => 'password123',
                // missing password_confirmation
                'phone' => '01712345678',
                'gender' => 'male',
                'employee_id' => 'EMP-007',
                'role_id' => $role->id,
                'department_id' => $dept->id,
                'designation_id' => $desig->id,
                'join_date' => '2025-01-01',
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors('password');
    }

    /** TEST-030 */
    #[Test]
    public function user_store_validation_phone_11_digits(): void
    {
        $role = Role::firstOrFail();
        $dept = Department::firstOrFail();
        $desig = Designation::firstOrFail();

        $response = $this->actingAs($this->admin)->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'full_name' => 'Test',
                'email' => 'tester@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'phone' => '12345', // too short
                'gender' => 'male',
                'employee_id' => 'EMP-008',
                'role_id' => $role->id,
                'department_id' => $dept->id,
                'designation_id' => $desig->id,
                'join_date' => '2025-01-01',
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors('phone');
    }

    /** TEST-031 */
    #[Test]
    public function user_store_validation_gender_in_enum(): void
    {
        $role = Role::firstOrFail();
        $dept = Department::firstOrFail();
        $desig = Designation::firstOrFail();

        $response = $this->actingAs($this->admin)->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'full_name' => 'Test',
                'email' => 'tester@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'phone' => '01712345678',
                'gender' => 'other',
                'employee_id' => 'EMP-009',
                'role_id' => $role->id,
                'department_id' => $dept->id,
                'designation_id' => $desig->id,
                'join_date' => '2025-01-01',
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors('gender');
    }

    /** TEST-032 - User edit method now exists and works */
    #[Test]
    public function user_edit_route_with_valid_user_id_returns_200(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.edit', $this->admin->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.user.edit');
    }

    /** TEST-032b - User edit method returns 404 for non-existent user */
    #[Test]
    public function user_edit_route_with_invalid_user_id_returns_404(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.edit', 99999));

        $response->assertNotFound();
    }

    // ========================= BRANCH getManagers METHOD =========================

    /** TEST-033 */
    #[Test]
    public function branch_getmanagers_route_returns_404_as_route_not_defined(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/branch/get-managers/1');

        // Route doesn't exist (removed), so returns 404
        $response->assertNotFound();
    }
}
