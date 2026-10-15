<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function __construct()
    {
        // Use correct guard for super admin
        $this->middleware('auth:web');
    }

    // List all roles
    public function list()
    {
        $roles = Role::with('parent')->paginate(10);
        return view('admin.role.list', compact('roles'));
    }

    // Create page
    public function create()
    {
        $role = new Role();
        $roles = Role::where('status', 'active')->get();
        return view('admin.role.create', compact('role', 'roles'));
    }

    // Store role
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'icon_class' => 'required|string|starts_with:fa-',
            'parent_id' => 'nullable|exists:roles,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validate->fails()) {
            return redirect()->route('admin.roles.create')->withErrors($validate)->withInput();
        }
        if (Role::whereRaw('LOWER(name) = ?', [strtolower($request->name)])->exists()) {
            return redirect()->route('admin.roles.create')
                ->withErrors(['name' => 'The name has already been taken.'])
                ->withInput();
        }

        // All roles land on the common dashboard
        $dashboardRoute = 'admin.dashboard';

        // Create role
        $role = Role::create([
            'name' => $request->name,
            'icon_class' => $request->icon_class,
            'parent_id' => $request->parent_id ?: null,
            'status' => $request->status,
            'guard_name' => 'web',
            'dashboard_route' => $dashboardRoute,
        ]);

        return redirect()->route('admin.roles.list')->with('success', 'Role created successfully');
    }

    // Edit page
    public function edit($id)
    {
        $role = Role::findOrFail($id);
        $roles = Role::where('status', 'active')->get();
        return view('admin.role.edit', compact('role', 'roles'));
    }

    // Update role
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validation = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'icon_class' => 'required|string|starts_with:fa-',
            'parent_id' => 'nullable|exists:roles,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validation->fails()) {
            return redirect()->back()->withErrors($validation)->withInput();
        }
        if (Role::whereRaw('LOWER(name) = ?', [strtolower($request->name)])
            ->where('id', '!=', $id)->exists()) {
            return redirect()->back()->withErrors(['name' => 'The name has already been taken.'])->withInput();
        }

        if ($role->name === 'superadmin') {
            return redirect()->back()->withInput()->with('error', 'The superadmin role cannot be edited.');
        }

        try {
            $role->update([
                'name' => $request->name,
                'icon_class' => $request->icon_class,
                'parent_id' => $request->parent_id ?: null,
                'status' => $request->status,
                'guard_name' => 'web',
            ]);

            return redirect()->route('admin.roles.list')
                             ->with('success', 'Role updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                             ->withInput()
                             ->with('error', 'Error updating role: ' . $e->getMessage());
        }
    }

    // Delete role
    public function delete($id)
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'superadmin') {
            return redirect()->back()->with('error', 'The superadmin role cannot be deleted.');
        }

        // Prevent deleting parent roles or assigned roles
        $hasChildren = Role::where('parent_id', $role->id)->exists();
        if ($hasChildren) {
            return redirect()->back()->with('error', 'Cannot delete this role because it has child roles.');
        }

        $hasUsers = DB::table('model_has_roles')->where('role_id', $role->id)->exists();
        if ($hasUsers) {
            return redirect()->back()->with('error', 'Cannot delete this role because it is assigned to users.');
        }

        $role->delete();

        return redirect()->route('admin.roles.list')
                         ->with('success', 'Role deleted successfully');
    }

    // Role audit log view
    public function auditLog($id)
    {
        $role = Role::findOrFail($id);
        $activities = Activity::with('causer')
            ->where('subject_type', get_class($role))
            ->where('subject_id', $role->id)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.role.audit-log', compact('role', 'activities'));
    }

    // Permission assign page
    public function permissionAssign($id)
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::orderBy('name')->get();
        return view('admin.role.permission-assign', compact('role', 'permissions'));
    }

    // Store assigned permissions
    public function permissionAssignStore(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $permissions = Permission::whereIn('id', $request->permissions ?? [])->pluck('name');
        $role->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Log activity
        $user = Auth::user();
        activity()
            ->causedBy($user)
            ->performedOn($role)
            ->withProperties(['permissions' => $permissions])
            ->log('Assigned permissions to role');

        return redirect()->route('admin.roles.list')
                         ->with('success', 'Permissions updated successfully');
    }
}
