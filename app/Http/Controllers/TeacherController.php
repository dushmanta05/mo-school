<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    /**
     * Create a new resource.
     */
    public function create(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'gender' => 'required|string|in:male,female,other',
            'phone_number' => 'required|string|max:15|unique:teachers,phone_number',
            'address' => 'required|string|max:255',
            'subject_specialization' => 'required|string|max:255',
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

            $teacher = $user->teacher()->create([
                "first_name" => $request->first_name,
                "last_name" => $request->last_name,
                "gender" => $request->gender,
                "phone_number" => $request->phone_number,
                "address" => $request->address,
                "subject_specialization" => $request->subject_specialization,
            ]);

            DB::commit();

            return response()->json([
                "success" => true,
                "message" => "Teacher created successfully",
                "teacher" => $teacher->load('user')
            ], 201);
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error('Teacher creation failed: ' . $exception->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create teacher', "error" => $exception->getMessage()], 500);
        }
    }

    /**
     * Retrieve the specified resource in storage.
     */
    public function get($id)
    {
        $teacher = Teacher::with('user')->find($id);
        if (!$teacher) {
            return response()->json(['success' => false, 'error' => 'Teacher not found'], 404);
        }
        return response()->json(['success' => true, "data" => $teacher], 200);
    }

    /**
     * Delete the specified resource in storage.
     */
    public function delete($id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json(['success' => false, 'error' => 'Teacher not found'], 404);
        }

        try {
            DB::beginTransaction();

            $teacher->user()->delete();
            $teacher->delete();

            DB::commit();

            return response()->json(['success' => true, "message" => "Teacher deleted successfully"], 200);
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error('Teacher deletion failed: ' . $exception->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete teacher'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function patch(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'gender' => 'sometimes|required|string|in:male,female,other',
            'phone_number' => "sometimes|required|string|max:15|unique:teachers,phone_number,{$id},id",
            'address' => 'sometimes|required|string|max:255',
            'subject_specialization' => 'sometimes|required|string|max:255',
            'email' => "sometimes|required|email|unique:users,email,{$id},id",
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $teacher = Teacher::with('user')->find($id);

        if (!$teacher) {
            return response()->json(['success' => false, 'error' => 'Teacher not found'], 404);
        }

        try {
            DB::beginTransaction();

            $teacher->fill($request->only([
                'first_name', 'last_name', 'gender', 'phone_number', 'address', 'subject_specialization'
            ]));
            $teacher->save();

            if ($request->has('email')) {
                $teacher->user->email = $request->email;
                $teacher->user->name = $request->first_name . ' ' . $request->last_name;
                $teacher->user->save();
            }

            DB::commit();
            return response()->json(['success' => false, "data" => $teacher->fresh('user')], 200);
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error('Teacher update failed: ' . $exception->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update teacher'], 500);
        }
    }
}
