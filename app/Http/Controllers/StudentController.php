<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
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
            'phone_number' => 'required|string|size:10|unique:students,phone_number',
            'address' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',

            'guardian_first_name' => 'required|string|max:255',
            'guardian_last_name' => 'required|string|max:255',
            'guardian_email' => 'required|email|unique:users',
            'guardian_password' => 'required|string|min:6',
            'guardian_gender' => 'required|string|in:male,female,other',
            'guardian_phone_number' => 'required|string|size:10|unique:guardians,phone_number',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        try {
            DB::beginTransaction();

            $student_user = User::create([
                "name" => $request->first_name . " " . $request->last_name,
                "email" => $request->email,
                "password" => $request->password
            ]);

            $guardian_user = User::where('email', $request->guardian_email)->first();

            if ($guardian_user) {
                if ($guardian_user->guardian()) {
                    $guardian = $guardian_user->guardian();
                } else {
                    $guardian = $guardian_user->guardian()->create([
                        "first_name" => $request->guardian_first_name,
                        "last_name" => $request->guardian_last_name,
                        "gender" => $request->guardian_gender,
                        "phone_number" => $request->guardian_phone_number,
                    ]);
                }
            } else {
                $guardian_user = User::create([
                    "name" => $request->guardian_first_name . " " . $request->guardian_last_name,
                    "email" => $request->guardian_email,
                    "password" => $request->guardian_password
                ]);

                $guardian = $guardian_user->guardian()->create([
                    "first_name" => $request->guardian_first_name,
                    "last_name" => $request->guardian_last_name,
                    "gender" => $request->guardian_gender,
                    "phone_number" => $request->guardian_phone_number,
                ]);
            }

            $student = $student_user->student()->create([
                "first_name" => $request->first_name,
                "last_name" => $request->last_name,
                "gender" => $request->gender,
                "phone_number" => $request->phone_number,
                "address" => $request->address,
                "date_of_birth" => $request->date_of_birth,
                "guardian_id" => $guardian->id
            ]);

            DB::commit();

            return response()->json([
                "success" => true,
                "message" => "Student created successfully",
                "student" => $student->load('user')
            ], 201);
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error('Student creation failed: ' . $exception->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to create student', "error" => $exception->getMessage()], 500);
        }
    }

    /**
     * Retrieve the specified resource in storage.
     */
    public function get($id)
    {
        $student = Student::with('user')->find($id);
        if (!$student) {
            return response()->json(['success' => false, 'error' => 'Student not found'], 404);
        }
        return response()->json(['success' => true, "data" => $student], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function patch(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => "sometimes|required|email|unique:users,email,{$id},id",
            'phone_number' => "sometimes|required|string|max:15|unique:students,phone_number,{$id},id",
            'gender' => 'sometimes|required|string|in:male,female,other',
            'date_of_birth' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()], 400);
        }

        $student = Student::with('user')->find($id);

        if (!$student) {
            return response()->json(['success' => false, 'error' => 'Student not found'], 404);
        }

        try {
            DB::beginTransaction();

            $student->fill($request->only([
                'first_name', 'last_name', 'gender', 'phone_number', 'address', 'date_of_birth'
            ]));
            $student->save();

            if ($request->has('email')) {
                $student->user->email = $request->email;
                $student->user->name = $request->first_name . ' ' . $request->last_name;
                $student->user->save();
            }

            DB::commit();
            return response()->json(['success' => false, "data" => $student->fresh('user')], 200);
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error('Student update failed: ' . $exception->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to update student'], 500);
        }
    }

    /**
     * Delete the specified resource from storage.
     */
    public function delete($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['success' => false, 'error' => 'Student not found'], 404);
        }

        try {
            DB::beginTransaction();

            $student->user()->delete();
            $student->delete();

            DB::commit();

            return response()->json(['success' => true, "message" => "Student deleted successfully"], 200);
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error('Student deletion failed: ' . $exception->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to delete student'], 500);
        }
    }
}
