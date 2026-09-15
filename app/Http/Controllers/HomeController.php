<?php

namespace App\Http\Controllers;

use App\Models\CastingProject;
use App\Models\User;

class HomeController extends Controller
{
    public function index()
    {
        $proyekDibukaCount = CastingProject::where('status', 'dibuka')->count();
        $totalProyek = CastingProject::count();
        $jumlahAdmin = User::where('role', 'like', 'admin_%')->count();
        $jumlahExtras = User::where('role', 'extras')->count();

        return view('welcome', compact('proyekDibukaCount', 'totalProyek', 'jumlahAdmin', 'jumlahExtras'));
    }
}
