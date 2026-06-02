<?php

namespace App\Http\Controllers\Api; // 1. Namespace sahi kar diya (Api)

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Inputs Validate Karo (API Format me)
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'portal_mode' => 'required' 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Database se user email check karo
        $user = User::where('email', $request->email)->first();

        // 3. User check aur password verify
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid identity records or password match.'
            ], 401);
        }

        // 4. Role Match check karo
        $selectedRole = str_contains(strtolower($request->portal_mode), 'abstractor') ? 'abstractor' : 'admin';

        if ($user->role !== $selectedRole) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access mode for this account.'
            ], 403);
        }

        // 5. Status check karo (User active hai ya nahi)
        if (isset($user->status) && $user->status != 1) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is currently deactivated.'
            ], 403);
        }

        // 6. Flutter ke liye Token Generate Karo (Sanctum)
        // Agar aapke user model me tokens create karne ka feature nahi hai, toh ye line badalni padegi.
        $token = $user->createToken('auth_token')->plainTextToken;

        // 7. Success Response (Flutter ko data bhejo)
        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ]
        ], 200);
    }
}