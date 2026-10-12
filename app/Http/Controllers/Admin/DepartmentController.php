<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    //list
    public function list(){
        $departments = Department::paginate(10);
        return view('admin.department.list', compact('departments'));
    }


    //create
    public function create(){
        return view('admin.department.create');
    }

    //store
    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'code' => 'required',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Department::create([
        'name' => $request->name,
        'code' => $request->code,
        'status' => $request->status,
     ]);

        return redirect()->route('admin.departments.list')->with('success', 'Department created successfully');
    }


    //edit
    public function edit($id){
        $department = Department::findOrFail($id);
        return view('admin.department.edit', compact('department'));
    }

    //update
    public function update(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'code' => 'required',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    //update query
        $department = Department::findOrFail($id);
        $department->update([
            'name' => $request->name,
            'code' => $request->code,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.departments.list')->with('success', 'Department updated successfully');
    }


    //delete
    public function delete($id){
        $department = Department::findOrFail($id);
        $department->delete();
        return redirect()->route('admin.departments.list')->with('success', 'Department deleted successfully');
    }
}
