<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Location;
use App\Models\Thana;
use App\Models\Union;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Validator;

class WarehouseController extends Controller
{
    //list
    public function list() {
        $warehouses = Warehouse::with('location.country',
            'location.state',
            'location.city',
            'location.thana',
            'location.union')->paginate(10);
        return view('warehouse.list', compact('warehouses'));
    }

    //create
    public function create() {
        $countries = Country::all();
        return view('warehouse.create', compact('countries'));
    }

    //store 
    public function store(Request $request){
      $validation= Validator::make($request->all(), [
        'name' => 'required|max:255',
        'code' => 'required|max:255',
        'slug' => 'required|max:255',
        'country_id' => 'required',
        'state_id' => 'required',
        'city_id' => 'required',
        'thana_id' => 'required',
        'union_id' => 'required',
        'capacity' => 'required',
        'starting_date' => 'required',
        'status' => 'required|in:active,inactive',
      ]);
      if ($validation->fails()) {
        return redirect()->back()->withErrors($validation)->withInput();
      }

 //  find an  location id 
  $location = Location::where([
    'country_id' => $request->country_id,
    'state_id'   => $request->state_id,
    'city_id'    => $request->city_id,
    'thana_id'   => $request->thana_id,
    'union_id'   => $request->union_id,
])->first();

if ($location) {
    $request->merge(['location_id' => $location->id]);
} else {
    $location = Location::create([
        'country_id' => $request->country_id,
        'state_id'   => $request->state_id,
        'city_id'    => $request->city_id,
        'thana_id'   => $request->thana_id,
        'union_id'   => $request->union_id,
    ]);
    $request->merge(['location_id' => $location->id]);
}


      //queary
      $warehouse = Warehouse::create([
        'name' => $request->name,
        'code' => $request->code,
        'slug' => $request->slug,
        'country_id' => $request->country_id,
        'location_id' => $location->id,
        'capacity' => $request->capacity,
        'starting_date' => $request->starting_date,
        'status' => $request->status,
      ]);
        return redirect()->route('admin.warehouse.list')->with('success','Warehouse created');
    }

    //edit
    public function edit($id){
        $warehouse = Warehouse::find($id);
        $countries = Country::all();
        return view('warehouse.edit', compact('warehouse', 'countries'));
    }


    //update
    public function update(Request $request, $id){
        $validation= Validator::make($request->all(), [
            'name' => 'required|max:255',
            'code' => 'required|max:255',
            'slug' => 'required|max:255',
            'country_id' => 'required',
            'state_id' => 'required',
            'city_id' => 'required',
            'thana_id' => 'required',
            'union_id' => 'required',
            'capacity' => 'required',
            'starting_date' => 'required',
            'status' => 'required|in:active,inactive',
          ]);
          if ($validation->fails()) {
            return redirect()->back()->withErrors($validation)->withInput();
          }

  //  find an  location id 
   $location = Location::where([
     'country_id' => $request->country_id,
     'state_id'   => $request->state_id,
     'city_id'    => $request->city_id,
     'thana_id'   => $request->thana_id,
     'union_id'   => $request->union_id,
   ])->first();

   if ($location) {
       $request->merge(['location_id' => $location->id]);
   } else {
       $location = Location::create([
           'country_id' => $request->country_id,
           'state_id'   => $request->state_id,
           'city_id'    => $request->city_id,
           'thana_id'   => $request->thana_id,
           'union_id'   => $request->union_id,
       ]);
       $request->merge(['location_id' => $location->id]);
   }


     //queary
     $warehouse = Warehouse::find($id);
     $warehouse->update([
       'name' => $request->name,
       'code' => $request->code,
       'slug' => $request->slug,
       'location_id' => $location->id,
       'capacity' => $request->capacity,
       'starting_date' => $request->starting_date,
       'status' => $request->status,
     ]);
       return redirect()->route('admin.warehouse.list')->with('success','Warehouse updated');
    }

    //delete
    public function delete($id){
        $warehouse = Warehouse::find($id);
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

    public function getThanas($cityId){
        $city = City::find($cityId);
        return response()->json($city ? $city->thanas : []);
    }

    public function getUnions($thanaId){
        $thana = Thana::with('unions')->find($thanaId);
        return response()->json($thana ? $thana->unions : []);
    }

    public function getZipcode($unionsId){
        $union = Union::find($unionsId);
        return response()->json([
            'zipcode' => $union->zipcode ?? $union->zip_code ?? ''
        ]);
    }

}
