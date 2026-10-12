<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;    
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Location;
use App\Models\Thana;
use App\Models\Union;   
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LocationController extends Controller
{
    //list
    public function list()
    {
        $locations = Location::with('country', 'state', 'city', 'thana', 'union')->paginate(20);
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
    
                    // CSV লাম
                    $countryName = $data[0] ?? null;
                    $stateName   = $data[1] ?? null;
                    $cityName    = $data[2] ?? null;
                    $thanaName   = $data[3] ?? null;
                    $unionName   = $data[4] ?? null;
                    $zipcode     = $data[5] ?? null;
                  

    
                    if ($countryName) {
                        // === Country Save ===
                        $country = Country::firstOrCreate(
                            ['name' => $countryName],
                            [
                                'code' => strtoupper(substr($countryName, 0, 3)) . '-' . Str::random(3, '0123456789'),
                                'slug' => Str::slug($countryName) . '-' . Str::uuid(),
                            ]
                        );
    
                        // === State Save ===
                        $state = State::firstOrCreate(
                            ['name' => $stateName, 'country_id' => $country->id],
                            [
                                'code' => strtoupper(substr($stateName, 0, 3)) . '-' . rand(100,999),
                                'slug' => Str::slug($stateName) . '-' . uniqid(),
                            ]
                        );
    
                        // === City Save ===
                        $city = City::firstOrCreate(
                            ['name' => $cityName, 'state_id' => $state->id],
                            [
                                'code' => strtoupper(substr($cityName, 0, 3)) . '-' . rand(100,999),
                                'slug' => Str::slug($cityName) . '-' . uniqid(),
                            ]
                        );
    
                        // === Thana Save ===
                        $thana = Thana::firstOrCreate(
                            ['name' => $thanaName, 'city_id' => $city->id],
                            [
                                'code' => strtoupper(substr($thanaName, 0, 3)) . '-' . rand(100,999),
                                'slug' => Str::slug($thanaName) . '-' . uniqid(),
                            ]
                        );
    
                        // === Union Save ===
                        $union = Union::firstOrCreate(
                            [
                                'name' => $unionName, 
                                'thana_id' => $thana->id,
                                'zipcode' => $zipcode
                            ],
                            [
                                'code' => strtoupper(substr($unionName, 0, 3)) . '-' . rand(100,999),
                                'slug' => Str::slug($unionName) . '-' . uniqid(),
                                'zipcode' => $zipcode,
                            ]
                        );
    
                        // === Location Save ===
                        $location = Location::firstOrCreate(
                            [
                                'country_id' => $country->id,
                                'state_id' => $state->id,
                                'city_id' => $city->id,
                                'thana_id' => $thana->id,
                                'union_id' => $union->id,
                                
                            ],
                            [
                                'status' => 'active',
                            ]
                            
                        );
                    }
                }
                fclose($handle);
            }
        }
    
        return redirect()->route('admin.locations.list')
    ->with('success', 'Locations imported successfully.');   }


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
    