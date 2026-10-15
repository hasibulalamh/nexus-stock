<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Location;
use App\Models\Upazila;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Validator;

class WarehouseController extends Controller
{
    //list
    public function list()
    {
        $warehouses = Warehouse::accessibleTo(auth()->user())->with('location.country',
            'location.state',
            'location.city',
            'location.upazila')->paginate(10);
        return view('warehouse.list', compact('warehouses'));
    }

    //create
    public function create()
    {
        $countries = Country::all();
        $branches = Branch::all();
        return view('warehouse.create', compact('countries', 'branches'));
    }

    //store
    public function store(Request $request){
        $validation = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'code' => 'required|max:255',
            'slug' => 'required|max:255',
            'country_id' => 'required',
            'state_id' => 'required',
            'city_id' => 'required',
            'upazila_id' => 'required',
            'capacity' => 'required',
            'starting_date' => 'required',
            'status' => 'required|in:active,inactive',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($validation->fails()) {
            return redirect()->back()->withErrors($validation)->withInput();
        }

        // find an location id
        $location = Location::where([
            'country_id' => $request->country_id,
            'state_id'   => $request->state_id,
            'city_id'    => $request->city_id,
            'upazila_id'   => $request->upazila_id,
        ])->first();

        if ($location) {
            $request->merge(['location_id' => $location->id]);
        } else {
            $location = Location::create([
                'country_id' => $request->country_id,
                'state_id'   => $request->state_id,
                'city_id'    => $request->city_id,
                'upazila_id'   => $request->upazila_id,
            ]);
            $request->merge(['location_id' => $location->id]);
        }

        //queary
        $warehouse = Warehouse::create([
            'name' => $request->name,
            'code' => $request->code,
            'slug' => $request->slug,
            'location_id' => $location->id,
            'branch_id' => $request->branch_id,
            'capacity' => $request->capacity,
            'starting_date' => $request->starting_date,
            'status' => $request->status,
        ]);
        return redirect()->route('admin.warehouse.list')->with('success','Warehouse created');
    }

    //edit
    public function edit($id){
        $warehouse = Warehouse::accessibleTo(auth()->user())->findOrFail($id);
        $countries = Country::all();
        $branches = Branch::all();
        return view('warehouse.edit', compact('warehouse', 'countries', 'branches'));
    }

    //update
    public function update(Request $request, $id){
        $validation = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'code' => 'required|max:255',
            'slug' => 'required|max:255',
            'country_id' => 'required',
            'state_id' => 'required',
            'city_id' => 'required',
            'upazila_id' => 'required',
            'capacity' => 'required',
            'starting_date' => 'required',
            'status' => 'required|in:active,inactive',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if ($validation->fails()) {
            return redirect()->back()->withErrors($validation)->withInput();
        }

        // find an location id
        $location = Location::where([
            'country_id' => $request->country_id,
            'state_id'   => $request->state_id,
            'city_id'    => $request->city_id,
            'upazila_id'   => $request->upazila_id,
        ])->first();

        if ($location) {
            $request->merge(['location_id' => $location->id]);
        } else {
            $location = Location::create([
                'country_id' => $request->country_id,
                'state_id'   => $request->state_id,
                'city_id'    => $request->city_id,
                'upazila_id'   => $request->upazila_id,
            ]);
            $request->merge(['location_id' => $location->id]);
        }

        //queary
        $warehouse = Warehouse::accessibleTo(auth()->user())->findOrFail($id);
        $warehouse->update([
            'name' => $request->name,
            'code' => $request->code,
            'slug' => $request->slug,
            'location_id' => $location->id,
            'branch_id' => $request->branch_id,
            'capacity' => $request->capacity,
            'starting_date' => $request->starting_date,
            'status' => $request->status,
        ]);
        return redirect()->route('admin.warehouse.list')->with('success','Warehouse updated');
    }

    //delete
    public function delete($id){
        $warehouse = Warehouse::accessibleTo(auth()->user())->findOrFail($id);
        $warehouse->delete();
        return redirect()->route('admin.warehouse.list')->with('success','Warehouse deleted');
    }

    // AJAX routes
    public function getStates($countryId){
        $country = Country::find($countryId);
        return response()->json($country ? $country->states : []);
    }

    public function getCities($stateId){
        $state = State::find($stateId);
        return response()->json($state ? $state->cities : []);
    }

    public function getUpazilas($cityId){
        $city = City::find($cityId);
        return response()->json($city ? $city->upazilas : []);
    }

    public function getZipcode($upazilaId){
        $upazila = Upazila::find($upazilaId);
        return response()->json([
            'zipcode' => $upazila?->postcode ?? ''
        ]);
    }

}