<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GuardianController extends Controller
{
    /**
     * Create a new resource.
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'gender' => 'required|string|in:male,female,other',
            'phone_number' => 'required|string|size:10|unique:guardians,phone_number',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                "name" => $request->first_name . " " . $request->last_name,
                "email" => $request->email,
                "password" => $request->password
            ]);

            $guardian = $user->guardian()->create([
                "first_name" => $request->first_name,
                "last_name" => $request->last_name,
                "gender" => $request->gender,
                "phone_number" => $request->phone_number,
            ]);

            DB::commit();

            return response()->json([
                "success" => true,
                "message" => "Guardian created successfully",
                "guardian" => $guardian->load('user')
            ], 201);
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error('Guardian creation failed: ' . $exception->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create guardian', "error" => $exception->getMessage()], 500);
        }
    }
}
