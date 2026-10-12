<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDO;

class DashboardController extends Controller
{
    public function dashboard(){
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        $role = $user->role; // Make sure your User model has a 'role' relationship
        
        return view("admin.dashboard", compact('role'));
    }
}
