<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    // User list
    public function list()
    {
        $users = User::with(['department', 'designation', 'roles'])->paginate(10);
        return view('admin.user.list', compact('users'));
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

        // Assign role safely
        $role = Role::find($request->role_id);
        if ($role && !$user->hasRole($role->name)) {
            $user->assignRole($role->name);
        }

        return redirect()
            ->route('admin.users.list')
            ->with('success', '✅ User created successfully!');
    }
}
