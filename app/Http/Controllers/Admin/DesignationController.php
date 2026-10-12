<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Department;
use App\Models\Designation;

class DesignationController extends Controller
{
    // 🧾 List all designations
    public function list()
    {
        $designations = Designation::with('department')->paginate(10);
        return view('admin.designation.list', compact('designations'));
    }

    // ➕ Create form
    public function create()
    {
        $departments = Department::all();
        return view('admin.designation.create', compact('departments'));
    }

    // 💾 Store new designation
    public function store(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|max:255|unique:designations,name',
            'code' => 'required|max:255|unique:designations,code',
            'department_id' => 'required|exists:departments,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        Designation::create([
            'name' => $request->name,
            'code' => $request->code,
            'department_id' => $request->department_id,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.designations.list');
    }

    // ✏️ Edit form
    public function edit($id)
    {
        $designation = Designation::findOrFail($id);
        $departments = Department::all();
        return view('admin.designation.edit', compact('designation', 'departments'));
    }

    // 🔁 Update existing designation
    public function update(Request $request, $id)
    {
        $designation = Designation::findOrFail($id);

        $validate = Validator::make($request->all(), [
           'name'=>'required|max:255|unique:designations,name,'.$id,
           'code'=>'required|max:255|unique:designations,code,'.$id,
           'department_id'=>'required|exists:departments,id',
           'status'=>'required|in:active,inactive',
        ]);

        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $designation->update([
            'name' => $request->name,
            'code' => $request->code,
            'department_id' => $request->department_id,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.designations.list');
    }

    // 🗑️ Delete designation
    public function delete($id)
    {
        $designation = Designation::findOrFail($id);
        $designation->delete();

        return redirect()->route('admin.designations.list');
    }
}
