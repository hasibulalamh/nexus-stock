<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserGuardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Department $department;
    protected Designation $designation;

    protected function afterRefreshingDatabase(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\AdminSeeder::class);
        $this->admin = User::where('email', 'superadmin@storify.com')->firstOrFail();
        $this->department = Department::where('name', 'Administration')->firstOrFail();
        $this->designation = Designation::where('name', 'Admin')->firstOrFail();
    }

    private function makeUser(string $roleName = 'staff', string $status = 'active', array $permissions = []): User
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        if ($permissions) {
            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web'], ['status' => 'active']);
            }
            $role->syncPermissions($permissions);
        }

        $user = User::create([
            'full_name' => ucfirst($roleName),
            'email' => uniqid($roleName, true) . '@example.com',
            'password' => 'password123',
            'phone' => '01712345678',
            'gender' => 'male',
            'employee_id' => strtoupper(uniqid('EMP')),
            'role_id' => $role->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'join_date' => '2026-01-01',
            'profile_photo' => 'default.png',
            'status' => $status,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function test_restricted_user_is_logged_out_by_active_middleware_and_cannot_log_in(): void
    {
        $restricted = $this->makeUser('restricted', 'inactive');

        $this->actingAs($restricted)->get(route('admin.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login.submit'), [
            'email' => $restricted->email,
            'password' => 'password123',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_status_toggle_requires_the_users_status_permission(): void
    {
        $staff = $this->makeUser('staff', 'active', ['users.view']);
        $target = $this->makeUser('target');

        $this->actingAs($staff)
            ->patch(route('admin.users.status', $target->id))
            ->assertForbidden();
        $this->assertSame('active', $target->fresh()->status);
    }

    public function test_user_cannot_delete_or_restrict_themselves(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.users.list'))
            ->delete(route('admin.users.delete', $this->admin->id))
            ->assertRedirect(route('admin.users.list'))
            ->assertSessionHas('error', 'You cannot delete your own account.');

        $this->actingAs($this->admin)
            ->from(route('admin.users.list'))
            ->patch(route('admin.users.status', $this->admin->id))
            ->assertRedirect(route('admin.users.list'))
            ->assertSessionHas('error', 'You cannot restrict your own account.');
    }

    public function test_last_active_superadmin_cannot_be_deleted_or_restricted(): void
    {
        $actor = $this->makeUser('operator', 'active', ['users.delete', 'users.status']);
        $target = $this->makeUser('superadmin', 'active', ['users.delete', 'users.status']);
        $actor->removeRole('superadmin');
        $target->assignRole('superadmin');

        $this->actingAs($actor)
            ->delete(route('admin.users.delete', $target->id))
            ->assertRedirect()
            ->assertSessionHas('error', 'Only a superadmin can delete a superadmin account.');

        $this->assertNotNull($target->fresh());
    }

    public function test_deleting_user_removes_database_sessions(): void
    {
        $target = $this->makeUser();
        DB::table('sessions')->insert([
            'id' => 'target-session',
            'user_id' => $target->id,
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.users.delete', $target->id))
            ->assertRedirect(route('admin.users.list'));

        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }
}
