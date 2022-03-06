<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SchoolTermController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\TeachingAssistantApplicationController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\SchoolRecordController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [MainController::class, 'index']);

Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
Route::resource('users', UserController::class);

Route::resource('schoolterms', SchoolTermController::class);

Route::get('/schoolclasses/search', [SchoolClassController::class, 'search'])->name('schoolclasses.search');
Route::patch('/schoolclasses/import', [SchoolClassController::class, 'import'])->name('schoolclasses.import');
Route::resource('schoolclasses', SchoolClassController::class);

Route::get('/instructors/{instructor}/requests', [InstructorController::class, 'requests'])->name('instructors.requests');
Route::get('/instructors/search', [InstructorController::class, 'search'])->name('instructors.search');
Route::resource('instructors', InstructorController::class);

Route::resource('requestAssistant', TeachingAssistantApplicationController::class);

Route::resource('students', StudentController::class);

Route::resource('enrollments', EnrollmentController::class);

Route::resource('schoolRecords', SchoolRecordController::class);