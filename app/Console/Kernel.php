<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\SchoolTerm;
use App\Models\Frequency;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotifyInstructorAboutAttendanceRecord;
use \Illuminate\Support\Facades\URL;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */

     //comando CRON
     //* * * * * cd /home/dev/Desktop/estagio/monitoria && php artisan schedule:run >> /dev/null 2>&1
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function() {            
            $frequencies = Frequency::whereHas("schoolclass.schoolterm", function($query){
                    $query->where("year",date("Y"));})
                ->where("month",date("m"))->get();

            foreach($frequencies as $frequency){
                Mail::to($frequency->schoolclass->requisition->instructor->codema)->send(new NotifyInstructorAboutAttendanceRecord($frequency,
                    URL::signedRoute('schoolclasses.showFrequencies', ['schoolclass'=>$frequency->schoolclass->id,'tutor'=>$frequency->student->id])));
            }
        })->monthlyOn(20, '08:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
