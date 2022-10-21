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
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\FrequencyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TutorController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\MailTemplateController;
use App\Http\Controllers\SelfEvaluationController;
use App\Http\Controllers\InstructorEvaluationController;
use App\Http\Controllers\OldDBController;

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

Route::get('/', [MainController::class, 'index'])->name("home");

Route::get('/users/loginas', [UserController::class, 'loginas'])->name("users.loginas");
Route::get('/users/search', [UserController::class, 'search'])->name('users.search');
Route::resource('users', UserController::class);

Route::resource('schoolterms', SchoolTermController::class);
Route::post('/schoolterms/download', [SchoolTermController::class, 'download'])->name('schoolterms.download');

Route::get('/schoolclasses/{schoolclass}/enrollments', [SchoolClassController::class, 'enrollments'])->name('schoolclasses.enrollments');
Route::get('/schoolclasses/search', [SchoolClassController::class, 'search'])->name('schoolclasses.search');
Route::patch('/schoolclasses/import', [SchoolClassController::class, 'import'])->name('schoolclasses.import');
Route::get('/schoolclasses/{schoolclass}/electedTutors', [SchoolClassController::class, 'electedTutors'])->name('schoolclasses.electedTutors');
Route::get('/schoolclasses/{schoolclass}/electedTutors/{tutor}/frequencies', [SchoolClassController::class, 'showFrequencies'])->name('schoolclasses.showFrequencies');
Route::resource('schoolclasses', SchoolClassController::class);

Route::get('/instructors/{instructor}/requisitions', [InstructorController::class, 'requisitions'])->name('instructors.requisitions');
Route::get('/instructors/search', [InstructorController::class, 'search'])->name('instructors.search');
Route::resource('instructors', InstructorController::class);

Route::resource('requisitions', RequisitionController::class);

Route::get('/students/test', [StudentController::class, 'test'])->name("students.test");
Route::resource('students', StudentController::class);

Route::get('/enrollments/showAll', [EnrollmentController::class, 'showAll'])->name('enrollments.showAll');
Route::resource('enrollments', EnrollmentController::class);

Route::post('/schoolrecords/download', [SchoolRecordController::class, 'download'])->name('schoolrecords.download');
Route::resource('schoolRecords', SchoolRecordController::class);

Route::post('/selections/selectunenrolled', [SelectionController::class, 'selectUnenrolled'])->name('selections.selectunenrolled');
Route::get('/selections/{schoolclass}/enrollments', [SelectionController::class, 'enrollments'])->name('selections.enrollments');
Route::resource('selections', SelectionController::class);

Route::get('/monitor/getimportschoolclassesjob', [MonitorController::class, 'getImportSchoolClassesJob']);

Route::get('/emails', [EmailController::class, 'index'])->name('emails.index');
Route::post('/emails/dispatch', [EmailController::class, 'dispatchForAll'])->name('emails.dispatch');

Route::get('frequencies/{frequency}', [FrequencyController::class,"update"])->name('frequencies.update');;

Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::post('/reports/make', [ReportController::class, 'make'])->name('reports.make');

Route::patch('/tutors/revoke/{selection}', [TutorController::class, 'revoke'])->name('tutors.revoke'); 
Route::get('/tutors', [TutorController::class, 'index'])->name('tutors.index'); 

Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
Route::get('/certificates/make/{selection}', [CertificateController::class, 'make'])->name('certificates.make');

Route::post('/mailtemplates/test', [MailTemplateController::class, 'test'])->name('mailtemplates.test');
Route::get('/mailtemplates/activate/{mailtemplate}', [MailTemplateController::class, 'activate'])->name('mailtemplates.activate');
Route::get('/mailtemplates/deactivate/{mailtemplate}', [MailTemplateController::class, 'deactivate'])->name('mailtemplates.deactivate');
Route::resource('mailtemplates', MailTemplateController::class);

Route::get('/olddb', [OldDBController::class, "index"])->name('olddb.index');
Route::post('/olddb/import', [OldDBController::class, "import"])->name('olddb.import');

Route::resource('selfevaluations', SelfEvaluationController::class);

Route::resource('instructorevaluations', InstructorEvaluationController::class);

Route::get('/monitor/getImportOldDBJob', [MonitorController::class, 'getImportOldDBJob']);