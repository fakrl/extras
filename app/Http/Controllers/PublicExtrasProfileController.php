<?php

namespace App\Http\Controllers;

use App\Models\ExtrasProfile;

class PublicExtrasProfileController extends Controller
{
    public function show(string $token)
    {
        $profile = ExtrasProfile::where('share_token', $token)->firstOrFail();
        $profile->load(['user', 'photos']);

        return view('public.extras-profile', compact('profile'));
    }
}
