<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AreaRequest;
use App\Models\Area;
use App\Models\City;
use App\Services\AreaImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AreaController extends Controller
{
    public function index(Request $request)
    {
        $areas = Area::with('city')->when($request->search, fn ($q, $search) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('name_bn', 'like', "%{$search}%")))
            ->when($request->city_id, fn ($q, $city) => $q->where('city_id', $city))
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->has('verified'), fn ($q) => $q->where('verified', $request->boolean('verified')))
            ->paginate(20)->withQueryString();

        return view('admin.area.list', ['areas' => $areas, 'cities' => City::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.area.form', ['area' => new Area, 'cities' => City::orderBy('name')->get()]);
    }

    public function store(AreaRequest $request)
    {
        Area::create($request->validated());

        return redirect()->route('admin.areas.list')->with('success', 'Area created successfully.');
    }

    public function edit(Area $area)
    {
        return view('admin.area.form', ['area' => $area, 'cities' => City::orderBy('name')->get()]);
    }

    public function update(AreaRequest $request, Area $area)
    {
        $area->update($request->validated());

        return redirect()->route('admin.areas.list')->with('success', 'Area updated successfully.');
    }

    public function destroy(Area $area)
    {
        $area->delete();

        return redirect()->route('admin.areas.list')->with('success', 'Area deleted successfully.');
    }

    public function toggleStatus(Area $area)
    {
        $area->update(['status' => $area->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', 'Area status updated.');
    }

    public function import()
    {
        return view('admin.area.import');
    }

    public function preview(Request $request, AreaImportService $importer)
    {
        $request->validate(['csv_file' => ['required', 'file', 'mimes:csv,txt']]);
        $path = $request->file('csv_file')->store('area-imports');
        session(['area_import_path' => $path]);

        return view('admin.area.import', ['preview' => $importer->analyse(Storage::path($path))]);
    }

    public function applyImport(AreaImportService $importer)
    {
        $path = session('area_import_path');
        abort_unless($path && Storage::exists($path), 422, 'The import preview has expired.');
        $result = $importer->apply(Storage::path($path));
        Storage::delete($path);
        session()->forget('area_import_path');

        return redirect()->route('admin.areas.list')->with('success', "Imported {$result['total']} areas.");
    }
}
