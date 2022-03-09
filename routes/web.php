<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SchoolTermController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\RequisitionController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\SchoolRecordController;
use App\Http\Controllers\SelectionController;

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

Route::get('/schoolclasses/{schoolclass}/enrollments', [SchoolClassController::class, 'enrollments'])->name('schoolclasses.enrollments');
Route::get('/schoolclasses/search', [SchoolClassController::class, 'search'])->name('schoolclasses.search');
Route::patch('/schoolclasses/import', [SchoolClassController::class, 'import'])->name('schoolclasses.import');
Route::resource('schoolclasses', SchoolClassController::class);

Route::get('/instructors/{instructor}/requisitions', [InstructorController::class, 'requisitions'])->name('instructors.requisitions');
Route::get('/instructors/search', [InstructorController::class, 'search'])->name('instructors.search');
Route::resource('instructors', InstructorController::class);

Route::resource('requisitions', RequisitionController::class);

Route::resource('students', StudentController::class);

Route::resource('enrollments', EnrollmentController::class);

Route::post('/schoolrecords/download', [SchoolRecordController::class, 'download'])->name('schoolrecords.download');
Route::resource('schoolRecords', SchoolRecordController::class);

Route::post('/selections/selectunenrolled', [SelectionController::class, 'selectUnenrolled'])->name('selections.selectunenrolled');
Route::get('/selections/{schoolclass}/enrollments', [SelectionController::class, 'enrollments'])->name('selections.enrollments');
Route::resource('selections', SelectionController::class);