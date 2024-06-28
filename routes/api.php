<?php

use App\Http\Controllers\TeacherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post("/teacher", [TeacherController::class, "create"]);
Route::get("/teacher/{id}", [TeacherController::class, "get"]);
Route::delete("/teacher/{id}", [TeacherController::class, "delete"]);
Route::patch("/teacher/{id}", [TeacherController::class, "patch"]);
