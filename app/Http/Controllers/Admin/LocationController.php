<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\State;
use App\Models\Upazila;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LocationController extends Controller
{
    // list
    public function list()
    {
        $locations = Location::with('country', 'state', 'city', 'upazila')->paginate(20);

        return view('admin.location.list', compact('locations'));
    }

    // create
    public function create()
    {
        return view('admin.location.create');
    }

    // store
    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        if ($validation->fails()) {
            return redirect()->back()->withErrors($validation)->withInput();
        }

        $path = $request->file('csv_file')->store('location-imports');
        $preview = $this->analyseImport(Storage::path($path));
        if ($preview['errors']) {
            Storage::delete($path);

            return back()->withErrors(['csv_file' => implode(' ', $preview['errors'])]);
        }

        session(['location_import_path' => $path]);

        return view('admin.location.create', compact('preview'));
    }

    public function applyImport()
    {
        $path = session('location_import_path');
        abort_unless($path && Storage::exists($path), 422, 'The location import preview has expired.');
        $preview = $this->analyseImport(Storage::path($path));
        if ($preview['errors']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['csv_file' => implode(' ', $preview['errors'])]);
        }

        DB::transaction(function () use ($path) {
            $handle = fopen(Storage::path($path), 'r');
            fgetcsv($handle, 0, ',');
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                [$divisionBn, $divisionEn, $districtBn, $districtEn, $upazilaBn, $upazilaEn, $postcode] = $data;
                $country = Country::firstOrCreate(['name' => 'Bangladesh'], ['code' => 'BGD', 'slug' => 'bangladesh']);
                $state = State::firstOrCreate(['name' => $divisionEn, 'country_id' => $country->id], ['code' => Str::upper(Str::substr($divisionEn, 0, 3)).'-'.Str::random(3, '0123456789'), 'slug' => Str::slug($divisionEn)]);
                $city = City::firstOrCreate(['name' => $districtEn, 'state_id' => $state->id], ['code' => Str::upper(Str::substr($districtEn, 0, 3)).'-'.Str::random(3, '0123456789'), 'slug' => Str::slug($districtEn.'-'.$state->id)]);
                $upazila = Upazila::firstOrCreate(['name' => $upazilaEn, 'city_id' => $city->id], ['code' => Str::upper(Str::substr($upazilaEn, 0, 3)).'-'.Str::random(3, '0123456789'), 'slug' => Str::slug($upazilaEn)]);
                if ($postcode) {
                    $upazila->update(['postcode' => $postcode]);
                }
                Location::firstOrCreate(['country_id' => $country->id, 'state_id' => $state->id, 'city_id' => $city->id, 'upazila_id' => $upazila->id], ['status' => 'active']);
            }
            fclose($handle);
        });

        Storage::delete($path);
        session()->forget('location_import_path');

        return redirect()->route('admin.locations.list')->with('success', 'Locations imported successfully.');
    }

    private function analyseImport(string $path): array
    {
        $expected = ['division_bn', 'division_en', 'district_bn', 'district_en', 'upazila_bn', 'upazila_en', 'postcode', 'postcode_status'];
        $handle = fopen($path, 'r');
        $header = array_map(fn ($value) => trim((string) $value), fgetcsv($handle, 0, ',') ?: []);
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }
        if ($header !== $expected) {
            fclose($handle);

            return ['rows' => 0, 'new_states' => 0, 'new_cities' => 0, 'new_upazilas' => 0, 'errors' => ['CSV header does not match the expected columns exactly.']];
        }
        $rows = 0;
        $states = [];
        $cities = [];
        $upazilas = [];
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            if (! filled(implode('', $data))) {
                continue;
            }
            $rows++;
            [$divisionBn, $divisionEn, $districtBn, $districtEn, $upazilaBn, $upazilaEn] = array_pad($data, 6, null);
            if (! $divisionEn || ! $districtEn || ! $upazilaEn) {
                return ['rows' => $rows, 'new_states' => 0, 'new_cities' => 0, 'new_upazilas' => 0, 'errors' => ["Row {$rows}: division, district, and upazila English names are required."]];
            }
            $states["{$divisionEn}"] = true;
            $cities["{$divisionEn}|{$districtEn}"] = true;
            $upazilas["{$districtEn}|{$upazilaEn}"] = true;
        }
        fclose($handle);
        $country = Country::where('name', 'Bangladesh')->first();
        $newStates = collect(array_keys($states))->filter(fn ($name) => ! $country || ! State::where('country_id', $country->id)->where('name', $name)->exists())->count();
        $newCities = 0;
        foreach (array_keys($cities) as $key) {
            [$division, $district] = explode('|', $key, 2);
            $state = $country ? State::where('country_id', $country->id)->where('name', $division)->first() : null;
            if (! $state || ! City::where('state_id', $state->id)->where('name', $district)->exists()) {
                $newCities++;
            }
        }
        $newUpazilas = 0;
        foreach (array_keys($upazilas) as $key) {
            [$district, $upazila] = explode('|', $key, 2);
            $city = City::where('name', $district)->first();
            if (! $city || ! Upazila::where('city_id', $city->id)->where('name', $upazila)->exists()) {
                $newUpazilas++;
            }
        }

        return ['rows' => $rows, 'new_states' => $newStates, 'new_cities' => $newCities, 'new_upazilas' => $newUpazilas, 'errors' => []];
    }

    public function changeStatus(Request $request)
    {
        $location = Location::find($request->id);

        if (! $location) {
            return response()->json(['success' => false, 'message' => 'Location not found!']);
        }

        $location->status = $location->status === 'active' ? 'inactive' : 'active';
        $location->save();

        return response()->json([
            'success' => true,
            'status' => $location->status,
            'message' => 'Status updated successfully!',
        ]);
    }
}
