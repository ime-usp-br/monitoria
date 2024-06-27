<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\SchoolTerm;
use App\Models\Frequency;
use App\Models\Selection;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotifyInstructorAboutAttendanceRecord;
use App\Mail\NotifyInstructorAboutSelectAssistant;
use App\Mail\NotifySelectStudent;
use App\Mail\NotifyStudentAboutSelfEvaluation;
use App\Mail\NotifyInstructorAboutEvaluation;
use \Illuminate\Support\Facades\URL;
use App\Models\MailTemplate;
use App\Models\SchoolClass;
use Carbon\Carbon;

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
        $emails = MailTemplate::where("active",true)->where("sending_frequency","!=","Manual")->get();

        foreach($emails as $email){
            if($email->sending_frequency=="Única"){
                $schedule->call(function () use ($email){$this->sendEmail($email);})->when(function () use ($email){
                    return date("d/m/Y") == $email->sending_date and date("H:i") == $email->sending_hour;
                })->description($email->description." no dia ".$email->sending_date." às ".$email->sending_hour);
            }elseif($email->sending_frequency=="Mensal"){
                $schedule->call(function () use ($email){$this->sendEmail($email);})
                ->monthlyon($email->sending_date, $email->sending_hour)->description($email->description);
            }elseif($email->sending_frequency=="Inicio do período de avaliação"){
                $schoolterms = SchoolTerm::where("start_date_evaluations", ">=", now()->subDays($email->sending_date))->get();

                foreach($schoolterms as $st){
                    $day = Carbon::createFromFormat('d/m/Y', $st->start_date_evaluations)->addDays($email->sending_date )->format("d/m/Y");
                    $schedule->call(function () use ($email){$this->sendEmail($email);})->when(function () use ($email, $day){
                        return  date("d/m/Y") == $day and 
                                date("H:i") == $email->sending_hour;
                    })->description($email->description . " do " . $st->period ." de " . $st->year . " agendado para o dia " . $day . " às " . $email->sending_hour);
                }
            }elseif($email->sending_frequency=="Final do período de avaliação"){
                $schoolterms = SchoolTerm::where("end_date_evaluations", ">=", now()->addDays($email->sending_date))->get();

                foreach($schoolterms as $st){
                    $day = Carbon::createFromFormat('d/m/Y', $st->end_date_evaluations)->subDays($email->sending_date )->format("d/m/Y");
                    $schedule->call(function () use ($email){$this->sendEmail($email);})->when(function () use ($email, $day){
                        return  date("d/m/Y") == $day and 
                                date("H:i") == $email->sending_hour;
                    })->description($email->description . " do " . $st->period ." de " . $st->year . " agendado para o dia " . $day . " às " . $email->sending_hour);
                }
            }
        }

        $schoolterms = SchoolTerm::where("finished_at", ">=", now())->get();

        foreach($schoolterms as $st){
            $schedule->call(function () use ($st){
                $selections = Selection::whereHas("schoolclass", function($query)use($st){
                    $query->whereBelongsTo($st);
                })->where("sitatl", "Ativo")->get();

                foreach($selections as $selection){
                    $selection->sitatl = "Concluido";
                    $selection->save();
                }
            })->when(function () use ($st){
                return  date("d/m/Y") == $st->finished_at and 
                        date("H:i") == "23:59";
            })->description("Fechamento do ".$st->period." de ".$st->year." no dia ".$st->finished_at." às 23:59");
        }
    }

    protected function sendEmail(MailTemplate $mailtemplate)
    {
        if($mailtemplate->mail_class == "NotifyInstructorAboutAttendanceRecord"){
            $frequencies = Frequency::whereHas("schoolclass.schoolterm", function($query){
                $query->where("year",date("Y"));
            })->whereHas("schoolclass.selections", function($query){
                $query->where("sitatl","Ativo");
            })->where("month", date("m"))->where("registered",false)->get();

            foreach($frequencies as $frequency){
                Mail::to($frequency->schoolclass->requisition->instructor->codema)->send(new NotifyInstructorAboutAttendanceRecord($frequency,
                    URL::signedRoute('frequencies.show', ['schoolclass'=>$frequency->schoolclass->id,'tutor'=>$frequency->student->id]), $mailtemplate));
            }
        }elseif($mailtemplate->mail_class == "NotifyInstructorAboutSelectAssistant"){
            $schoolclasses = SchoolClass::whereInOpenSchoolTerm()->whereHas('selections')->get();

            foreach($schoolclasses as $schoolclass){
                Mail::to($schoolclass->requisition->instructor->codema)->send(new NotifyInstructorAboutSelectAssistant($schoolclass, $mailtemplate));
            }
        }elseif($mailtemplate->mail_class == "NotifySelectStudent"){
            $schoolclasses = SchoolClass::whereInOpenSchoolTerm()->whereHas('selections')->get();

            foreach($schoolclasses as $schoolclass){
                foreach($schoolclass->selections as $selection){
                    Mail::to($selection->student->codema)->send(new NotifySelectStudent($selection->student, $schoolclass, $mailtemplate));
                }
            }
        }elseif($mailtemplate->mail_class == "NotifyStudentAboutSelfEvaluation"){
            $selections = Selection::whereHas("schoolclass.schoolterm", function($query){
                $query->where("id", SchoolTerm::getSchoolTermInEvaluationPeriod()->id ?? "");
            })->where("sitatl", "!=", "Desligado")->doesntHave("selfevaluation")->get();

            foreach($selections as $selection){
                Mail::to($selection->student->codema)->send(new NotifyStudentAboutSelfEvaluation($selection,
                URL::signedRoute('selfevaluations.create', ['selectionID'=>$selection->id]), $mailtemplate));
            }
        }elseif($mailtemplate->mail_class == "NotifyInstructorAboutEvaluation"){
            $selections = Selection::whereHas("schoolclass.schoolterm", function($query){
                $query->where("id", SchoolTerm::getSchoolTermInEvaluationPeriod()->id ?? "");
            })->where("sitatl", "!=", "Desligado")->doesntHave("instructorevaluation")->get();

            foreach($selections as $selection){
                Mail::to($selection->requisition->instructor->codema)->send(new NotifyInstructorAboutEvaluation($selection,
                URL::signedRoute('instructorevaluations.create', ['selectionID'=>$selection->id]), $mailtemplate));
            }
        }
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
