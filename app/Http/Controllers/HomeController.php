<?php

namespace App\Http\Controllers;

use App\Models\CastingProject;

class HomeController extends Controller
{
    /**
     * RF-55: homepage compro. Naik dari closure jadi controller karena
     * butuh query count proyek dibuka (teaser), bukan cuma render view statis.
     */
    public function index()
    {
        $proyekDibukaCount = CastingProject::where('status', 'dibuka')->count();

        return view('welcome', compact('proyekDibukaCount'));
    }
}
