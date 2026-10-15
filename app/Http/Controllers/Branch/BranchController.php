<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\City;
use App\Models\Country;
use App\Models\Warehouse;
use App\Models\Location;
use App\Models\State;
use App\Models\Upazila;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    //List
    public function list()
    {
        $branches = Branch::accessibleTo(auth()->user())->with('location.country',
        'location.state',
        'location.city',
        'location.upazila')->paginate(10);
        return view('admin.branch.list', compact('branches'));
    }

    //Create
    public function create()
    {
        $countries = Country::all();
        return view('admin.branch.create', compact('countries'));
    }

    //Store
    public function store(Request $request)
    {

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
        ]);

        if($validation->fails()){
            return redirect()->back()->withErrors($validation)->withInput();
        }

        //find location id
        $location = Location::where([
            'country_id' => $request->country_id,
            'state_id' => $request->state_id,
            'city_id' => $request->city_id,
            'upazila_id' => $request->upazila_id,
        ])->first();

        if(!$location){
            $location = Location::create([
                'country_id' => $request->country_id,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'upazila_id' => $request->upazila_id,
            ]);
        }

        $branch = Branch::create([
            'name' => $request->name,
            'code' => $request->code,
            'slug' => $request->slug,
            'location_id' => $location->id,
            'capacity' => $request->capacity,
            'starting_date' => $request->starting_date,
            'status' => $request->status,
        ]);

        return redirect()->route('admin.branch.list')->with('success', 'Branch created successfully');

    }

    //edit
    public function edit($id){
        $branch = Branch::accessibleTo(auth()->user())->findOrFail($id);
        $countries = Country::all();
        return view('admin.branch.edit', compact('branch', 'countries'));
    }

    //update
    public function update(Request $request, $id){
        $branch = Branch::accessibleTo(auth()->user())->findOrFail($id);
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
        ]);

        if($validation->fails()){
            return redirect()->back()->withErrors($validation)->withInput();
        }//find location id
        $location = Location::where([
            'country_id' => $request->country_id,
            'state_id' => $request->state_id,
            'city_id' => $request->city_id,
            'upazila_id' => $request->upazila_id,
        ])->first();

        if(!$location){
            $location = Location::create([
                'country_id' => $request->country_id,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'upazila_id' => $request->upazila_id,
            ]);
        }

        //queary
        $branch->update([
            'name' => $request->name,
            'code' => $request->code,
            'slug' => $request->slug,
            'location_id' => $location->id,
            'capacity' => $request->capacity,
            'starting_date' => $request->starting_date,
            'status' => $request->status,
        ]);
        return redirect()->route('admin.branch.list')->with('success', 'Branch updated successfully');
    }


    //delete
    public function delete($id){
        $branch = Branch::accessibleTo(auth()->user())->findOrFail($id);
        $branch->delete();
        return redirect()->route('admin.branch.list')->with('success', 'Branch deleted successfully');
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
