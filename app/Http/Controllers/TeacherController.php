<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    /**
     * Create a new resource.
     */
    public function create(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|string|in:male,female,other',
            'phone_number' => 'required|string|max:15|unique:teachers,phone_number',
            'address' => 'required|string|max:255',
            'subject_specialization' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $teacher = Teacher::create([
            "first_name" => $request->first_name,
            "last_name" => $request->last_name,
            "gender" => $request->gender,
            "phone_number" => $request->phone_number,
            "address" => $request->address,
            "subject_specialization" => $request->subject_specialization
        ]);

        return response()->json(["message" => "Teacher created successfully", "data" => $teacher]);
    }

    /**
     * Retrieve the specified resource in storage.
     */
    public function get($id)
    {
        $teacher = Teacher::find($id);
        if (!$teacher) {
            return response()->json(['error' => 'Teacher not found'], 404);
        }
        return response()->json(["data" => $teacher], 200);
    }

    /**
     * Delete the specified resource in storage.
     */
    public function delete($id)
    {
        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json(['error' => 'Teacher not found'], 404);
        }

        $teacher->delete();

        return response()->json(["message" => "Teacher deleted successfully"], 200);
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
            'phone_number' => 'sometimes|required|string|max:15|unique:teachers,phone_number',
            'address' => 'sometimes|required|string|max:255',
            'subject_specialization' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $teacher = Teacher::find($id);

        if (!$teacher) {
            return response()->json(['error' => 'Teacher not found'], 404);
        }

        $teacher->update($request->only([
            "first_name" => $request->first_name,
            "last_name" => $request->last_name,
            "gender" => $request->gender,
            "phone_number" => $request->phone_number,
            "address" => $request->address,
            "subject_specialization" => $request->subject_specialization
        ]));

        return response()->json(["data" => $teacher], 200);
    }
}
