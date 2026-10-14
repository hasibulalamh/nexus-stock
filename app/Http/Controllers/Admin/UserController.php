<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    // User list
    public function list(Request $request)
    {
        $perPage = in_array((int) $request->per_page, [10, 25, 50]) ? (int) $request->per_page : 10;

        $users = User::with(['department', 'designation', 'roles'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%' . trim($request->q) . '%';
                $query->where(function ($w) use ($term) {
                    $w->where('full_name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('employee_id', 'like', $term);
                });
            })
            ->when($request->filled('department_id'), fn($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('designation_id'), fn($q) => $q->where('designation_id', $request->designation_id))
            ->when(in_array($request->status, ['active', 'inactive']), fn($q) => $q->where('status', $request->status))
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        $departments  = Department::orderBy('name')->get(['id', 'name']);
        $designations = Designation::orderBy('name')->get(['id', 'name']);

        return view('admin.user.list', compact('users', 'departments', 'designations'));
    }

    // Create form
    public function create()
    {
        $roles = Role::all();
        $departments = Department::all();
        $designations = Designation::all();

        return view('admin.user.create', compact('roles', 'departments', 'designations'));
    }

    // Store new user
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|max:255|confirmed',
            'phone' => 'required|numeric|digits:11',
            'gender' => 'required|in:male,female',
            'employee_id' => 'required|unique:users,employee_id',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'required|exists:departments,id',
            'designation_id' => 'required|exists:designations,id',
            'join_date' => 'required|date',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Only a superadmin may hand out the superadmin role.
        $requestedRole = Role::find($request->role_id);
        if ($requestedRole && $requestedRole->name === 'superadmin' && !$request->user()->hasRole('superadmin')) {
            return redirect()->back()->withInput()->with('error', 'Only a superadmin can assign or edit a superadmin account.');
        }

        // Ensure upload folder exists
        $uploadPath = public_path('uploads/user');
        if (!File::exists($uploadPath)) {
            File::makeDirectory($uploadPath, 0777, true, true);
        }

        // Handle profile photo
        $filename = 'default.png';
        if ($request->hasFile('profile_photo')) {
            $profile_photo = $request->file('profile_photo');
            $filename = time() . '.' . $profile_photo->getClientOriginalExtension();
            $profile_photo->move($uploadPath, $filename);
        }

        // Create user
        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'gender' => $request->gender,
            'employee_id' => $request->employee_id,
            'role_id' => $request->role_id,
            'department_id' => $request->department_id,
            'designation_id' => $request->designation_id,
            'join_date' => $request->join_date,
            'profile_photo' => $filename,
            'status' => $request->status,
        ]);

        // Assign role safely (the guard above already vetted the assignment)
        if ($requestedRole && !$user->hasRole($requestedRole->name)) {
            $user->assignRole($requestedRole->name);
        }

        return redirect()
            ->route('admin.users.list')
            ->with('success', '✅ User created successfully!');
    }

    // Edit form
    public function edit($id)
    {
        $user = User::with('roles')->findOrFail($id);
        $roles = Role::all();
        $departments = Department::all();
        $designations = Designation::all();

        return view('admin.user.edit', compact('user', 'roles', 'departments', 'designations'));
    }

    // Update existing user
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|max:255|confirmed',
            'phone' => 'required|numeric|digits:11',
            'gender' => 'required|in:male,female',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'required|exists:departments,id',
            'designation_id' => 'required|exists:designations,id',
            'join_date' => 'required|date',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // A non-superadmin may neither edit a superadmin nor promote anyone to one.
        $requestedRole = Role::find($request->role_id);
        $editingSuperadmin = $user->hasRole('superadmin');
        $assigningSuperadmin = $requestedRole && $requestedRole->name === 'superadmin';
        if (($editingSuperadmin || $assigningSuperadmin) && !$request->user()->hasRole('superadmin')) {
            return redirect()->back()->withInput()->with('error', 'Only a superadmin can assign or edit a superadmin account.');
        }

        // Employee ID is generated on create and stays fixed afterwards.
        $attributes = [
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'gender' => $request->gender,
            'role_id' => $request->role_id,
            'department_id' => $request->department_id,
            'designation_id' => $request->designation_id,
            'join_date' => $request->join_date,
            'status' => $request->status,
        ];

        // Only touch the password when a new one was supplied (the model's
        // `hashed` cast takes care of hashing it).
        if ($request->filled('password')) {
            $attributes['password'] = $request->password;
        }

        // Handle a replacement profile photo (keep the old file name around so
        // the replaced file can be removed after a successful update).
        $oldPhoto = null;
        if ($request->hasFile('profile_photo')) {
            $uploadPath = public_path('uploads/user');
            if (!File::exists($uploadPath)) {
                File::makeDirectory($uploadPath, 0777, true, true);
            }

            $profile_photo = $request->file('profile_photo');
            $filename = time() . '.' . $profile_photo->getClientOriginalExtension();
            $profile_photo->move($uploadPath, $filename);

            $oldPhoto = $user->profile_photo;
            $attributes['profile_photo'] = $filename;
        }

        $user->update($attributes);

        // Remove the replaced photo only when it was a real, different file —
        // never the default avatar shipped with the app.
        if ($oldPhoto && $oldPhoto !== 'default.png' && $oldPhoto !== $user->profile_photo) {
            $oldPath = public_path('uploads/user/' . $oldPhoto);
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
        }

        // A user holds a single role: keep users.role_id and the Spatie role in
        // sync by always mirroring the submitted role (also prunes stale roles).
        if ($requestedRole) {
            $user->syncRoles([$requestedRole->name]);
        }

        return redirect()
            ->route('admin.users.list')
            ->with('success', '✅ User updated successfully!');
    }

    // Soft delete an existing user (guards below)
    public function delete($id)
    {
        $user = User::findOrFail($id);

        if ($error = $this->guardStatusChange($user, 'delete')) {
            return redirect()->back()->with('error', $error);
        }

        // Drop active sessions so the user is signed out immediately.
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $user->delete();

        return redirect()->route('admin.users.list')->with('success', 'User deleted');
    }

    /**
     * Shared guards for delete / restrict / restore.
     * Returns an error message when the action is not allowed, null otherwise.
     */
    private function guardStatusChange(User $target, string $action): ?string
    {
        $actor = auth()->user();

        if (!$actor) {
            return 'Authentication required.';
        }

        if ($target->id === $actor->id) {
            return "You cannot {$action} your own account.";
        }

        if ($target->hasRole('superadmin') && !$actor->hasRole('superadmin')) {
            return "Only a superadmin can {$action} a superadmin account.";
        }

        // Never let the last active superadmin disappear.
        if ($target->hasRole('superadmin') && $target->status === 'active') {
            $otherActiveSuperadmins = User::where('id', '!=', $target->id)
                ->where('status', 'active')
                ->whereHas('roles', fn($q) => $q->where('name', 'superadmin'))
                ->count();

            if ($otherActiveSuperadmins === 0) {
                return "The last active superadmin cannot be {$action}d.";
            }
        }

        return null;
    }
}
