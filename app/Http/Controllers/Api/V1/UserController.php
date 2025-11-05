<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get the authenticated user
     */
    public function show(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * Update the authenticated user's profile
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$request->user()->id,
        ]);

        $request->user()->update($validated);

        return new UserResource($request->user()->fresh());
    }
}
