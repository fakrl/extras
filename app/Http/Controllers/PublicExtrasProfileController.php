<?php

namespace App\Http\Controllers;

use App\Models\User;

class PublicExtrasProfileController extends Controller
{
    public function show(string $username)
    {
        $user = User::where('username', $username)->where('role', 'extras')->firstOrFail();
        $profile = $user->extrasProfile ?? abort(404);
        $profile->load(['photos']);

        return view('public.extras-profile', compact('profile'));
    }
}
