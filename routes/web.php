<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SchoolTermController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\TeachingAssistantApplicationController;
use App\Http\Controllers\MainController;
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

Route::get('/groups/search', [GroupController::class, 'search'])->name('groups.search');
Route::patch('/groups/import', [GroupController::class, 'import'])->name('groups.import');
Route::resource('groups', GroupController::class);

Route::get('/instructors/{instructor}/requests', [InstructorController::class, 'requests'])->name('instructors.requests');
Route::get('/instructors/search', [InstructorController::class, 'search'])->name('instructors.search');
Route::resource('instructors', InstructorController::class);

Route::resource('requestAssistant', TeachingAssistantApplicationController::class);