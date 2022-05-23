<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\SchoolTerm;
use App\Models\Frequency;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotifyInstructorAboutAttendanceRecord;

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
            $data_atual = date('Y-m-d H:i:s');
            $periodo = SchoolTerm::where('started_at', '<=', $data_atual)
                                    ->where('finished_at', '>=', $data_atual)
                                    ->select('*')
                                    ->get();
            
            $turmas = $periodo[0]->schoolclasses;
    
            foreach($turmas as $turma){
                $eleicoes = $turma->selections;
                foreach($eleicoes as $eleicao){
                    $frequencia = new Frequency();
                    $frequencia->student_id = $eleicao->student->id;
                    $frequencia->save();
                    Mail::to($turma->instructor->codema)->send(new NotifyInstructorAboutAttendanceRecord($turma, $eleicao->student, $frequencia->created_at->format('m'), $periodo[0]->year, $periodo[0]->period));
                }
            }
        })->monthlyOn(25, '00:00');
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
