<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Http\Resources\ProfileResource;

class ProfileController extends Controller
{
    // show current user profile
    public function me()
    {
        return new ProfileResource(auth()->user());
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = auth()->user();
        $user->update($request->validated());
        return new ProfileResource($user);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = auth()->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);
        
        return response()->json(['message' => 'Password updated successfully'], 200);
    }

    public function destroy()
    {
        $user = auth()->user();
        $user->delete();

        // revoke all tokens
        $user->tokens()->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }
}
