<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    // list
    public function list()
    {
        $permissions = Permission::orderBy('name')->paginate(15);
        return view('admin.permission.list', compact('permissions'));
    }

    // edit (status only)
    public function edit($id)
    {
        $permission = Permission::findOrFail($id);
        return view('admin.permission.edit', compact('permission'));
    }

    // update (status only; name and guard come from PermissionSeeder)
    public function update(Request $request, $id)
    {
        $validate = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
        ]);

        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $permission = Permission::findOrFail($id);
        $permission->update(['status' => $request->status]);

        return redirect()->route('admin.permissions.list')->with('success', 'Permission updated successfully');
    }
}
