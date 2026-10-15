<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Location;
use App\Models\Upazila;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LocationController extends Controller
{
    //list
    public function list()
    {
        $locations = Location::with('country', 'state', 'city', 'upazila')->paginate(20);
        return view('admin.location.list', compact('locations'));
    }
    //create
    public function create()
    {
        return view('admin.location.create');
    }


    //store
    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        if ($validation->fails()) {
            return redirect()->back()->withErrors($validation)->withInput();
        }

        if ($request->hasFile('csv_file')) {
            $file = $request->file('csv_file');
            $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/location'), $filename);

            $filepath = public_path('uploads/location/'.$filename);
            if (($handle = fopen($filepath, "r")) !== false) {
                $isHeader = true;
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    if ($isHeader) {
                        $isHeader = false;
                        continue;
                    }

                    // CSV columns:
                    // division_bn, division_en, district_bn, district_en, upazila_bn, upazila_en, postcode, postcode_status
                    $divisionBn  = $data[0] ?? null;
                    $divisionEn  = $data[1] ?? null;
                    $districtBn  = $data[2] ?? null;
                    $districtEn  = $data[3] ?? null;
                    $upazilaBn   = $data[4] ?? null;
                    $upazilaEn   = $data[5] ?? null;
                    $postcode    = $data[6] ?? null;

                    if (!$divisionEn || !$districtEn || !$upazilaEn) {
                        continue;
                    }

                    // Country
                    $countryName = 'Bangladesh';
                    $country = Country::firstOrCreate(
                        ['name' => $countryName],
                        [
                            'code' => 'BGD',
                            'slug' => Str::slug($countryName),
                        ]
                    );

                    // State = Division
                    $stateName = $divisionEn;
                    $state = State::firstOrCreate(
                        ['name' => $stateName, 'country_id' => $country->id],
                        [
                            'code' => strtoupper(substr($stateName, 0, 3)) . '-' . Str::random(3, '0123456789'),
                            'slug' => Str::slug($stateName),
                        ]
                    );

                    // City = District
                    $cityName = $districtEn;
                    $city = City::firstOrCreate(
                        ['name' => $cityName, 'state_id' => $state->id],
                        [
                            'code' => strtoupper(substr($cityName, 0, 3)) . '-' . Str::random(3, '0123456789'),
                            'slug' => Str::slug($cityName),
                        ]
                    );

                    // Thana = Upazila
                    $thanaName = $upazilaEn;
                    $upazila = Upazila::firstOrCreate(
                        ['name' => $thanaName, 'city_id' => $city->id],
                        [
                            'code' => strtoupper(substr($thanaName, 0, 3)) . '-' . Str::random(3, '0123456789'),
                            'slug' => Str::slug($thanaName),
                        ]
                    );

                    // Optional postcode stored on upazila (union layer removed)
                    if ($postcode) {
                        Upazila::where('id', $upazila->id)->update([
                            'postcode' => $postcode,
                        ]);
                    }

                    // Location
                    $location = Location::firstOrCreate(
                        [
                            'country_id' => $country->id,
                            'state_id' => $state->id,
                            'city_id' => $city->id,
                            'upazila_id' => $upazila->id,
                        ],
                        [
                            'status' => 'active',
                        ]
                    );
                }
                fclose($handle);
            }
        }

        return redirect()->route('admin.locations.list')
            ->with('success', 'Locations imported successfully.');
    }


    public function changeStatus(Request $request)
    {
        $location = Location::find($request->id);

        if (!$location) {
            return response()->json(['success' => false, 'message' => 'Location not found!']);
        }

        $location->status = $location->status === 'active' ? 'inactive' : 'active';
        $location->save();

        return response()->json([
            'success' => true,
            'status' => $location->status,
            'message' => 'Status updated successfully!'
        ]);
    }


}
