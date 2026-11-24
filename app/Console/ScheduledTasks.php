<?php

namespace App\Console;

use App\Mail\NotifyInstructorAboutAttendanceRecord;
use App\Mail\NotifyInstructorAboutEvaluation;
use App\Mail\NotifyInstructorAboutSelectAssistant;
use App\Mail\NotifySelectStudent;
use App\Mail\NotifyStudentAboutSelfEvaluation;
use App\Models\Frequency;
use App\Models\MailTemplate;
use App\Models\SchoolClass;
use App\Models\SchoolTerm;
use App\Models\Selection;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class ScheduledTasks
{
    public function register(Schedule $schedule): void
    {
        $schedule->call(fn () => $this->runScheduledEmails())
            ->everyMinute()
            ->name('monitoria:scheduled-emails');

        $schedule->call(fn () => $this->closeFinishedTerms())
            ->everyMinute()
            ->name('monitoria:close-finished-terms');
    }

    public function runScheduledEmails(): void
    {
        $emails = MailTemplate::where('active', true)->where('sending_frequency', '!=', 'Manual')->get();

        foreach ($emails as $email) {
            if ($email->sending_frequency == 'Única') {
                if ($this->isDueOnDate($email->sending_date, $email->sending_hour)) {
                    $this->sendEmail($email);
                }
            } elseif ($email->sending_frequency == 'Mensal') {
                if ((int) now()->day === (int) $email->sending_date && now()->format('H:i') === $email->sending_hour) {
                    $this->sendEmail($email);
                }
            } elseif ($email->sending_frequency == 'Inicio do período de avaliação') {
                $days = (int) $email->sending_date;
                $schoolterms = SchoolTerm::whereDate('start_date_evaluations', '>=', now()->subDays($days)->toDateString())->get();

                foreach ($schoolterms as $st) {
                    $day = Carbon::createFromFormat('d/m/Y', $st->start_date_evaluations)->addDays($days)->format('d/m/Y');
                    if ($this->isDueOnDate($day, $email->sending_hour)) {
                        $this->sendEmail($email);
                    }
                }
            } elseif ($email->sending_frequency == 'Final do período de avaliação') {
                $days = (int) $email->sending_date;
                $schoolterms = SchoolTerm::whereDate('end_date_evaluations', '>=', now()->addDays($days)->toDateString())->get();

                foreach ($schoolterms as $st) {
                    $day = Carbon::createFromFormat('d/m/Y', $st->end_date_evaluations)->subDays($days)->format('d/m/Y');
                    if ($this->isDueOnDate($day, $email->sending_hour)) {
                        $this->sendEmail($email);
                    }
                }
            }
        }
    }

    public function closeFinishedTerms(): void
    {
        $schoolterms = SchoolTerm::where('finished_at', '>=', now())->get();

        foreach ($schoolterms as $st) {
            if (! $this->isDueOnDate($st->finished_at, '23:59')) {
                continue;
            }

            $selections = Selection::whereHas('schoolclass', function ($query) use ($st) {
                $query->whereBelongsTo($st);
            })->where('sitatl', 'Ativo')->get();

            foreach ($selections as $selection) {
                $selection->sitatl = 'Concluido';
                $selection->save();
            }
        }
    }

    public function sendEmail(MailTemplate $mailtemplate): void
    {
        if ($mailtemplate->mail_class == 'NotifyInstructorAboutAttendanceRecord') {
            $frequencies = Frequency::whereHas('schoolclass.schoolterm', function ($query) {
                $query->where('year', now()->year);
            })->whereHas('schoolclass.selections', function ($query) {
                $query->where('sitatl', 'Ativo')
                    ->whereColumn('selections.school_class_id', 'frequencies.school_class_id');
            })->where('month', now()->month)->where('registered', false)->get();

            foreach ($frequencies as $frequency) {
                Mail::to($frequency->schoolclass->requisition->instructor->codema)->send(new NotifyInstructorAboutAttendanceRecord(
                    $frequency,
                    URL::signedRoute('frequencies.show', ['schoolclass' => $frequency->schoolclass->id, 'tutor' => $frequency->student->id]),
                    $mailtemplate
                ));
            }
        } elseif ($mailtemplate->mail_class == 'NotifyInstructorAboutSelectAssistant') {
            $schoolclasses = SchoolClass::whereInOpenSchoolTerm()->whereHas('selections')->get();

            foreach ($schoolclasses as $schoolclass) {
                Mail::to($schoolclass->requisition->instructor->codema)->send(new NotifyInstructorAboutSelectAssistant($schoolclass, $mailtemplate));
            }
        } elseif ($mailtemplate->mail_class == 'NotifySelectStudent') {
            $schoolclasses = SchoolClass::whereInOpenSchoolTerm()->whereHas('selections')->get();

            foreach ($schoolclasses as $schoolclass) {
                foreach ($schoolclass->selections as $selection) {
                    Mail::to($selection->student->codema)->send(new NotifySelectStudent($selection->student, $schoolclass, $mailtemplate));
                }
            }
        } elseif ($mailtemplate->mail_class == 'NotifyStudentAboutSelfEvaluation') {
            $selections = Selection::whereHas('schoolclass.schoolterm', function ($query) {
                $query->where('id', SchoolTerm::getSchoolTermInEvaluationPeriod()->id ?? '');
            })->where('sitatl', '!=', 'Desligado')->doesntHave('selfevaluation')->get();

            foreach ($selections as $selection) {
                Mail::to($selection->student->codema)->send(new NotifyStudentAboutSelfEvaluation(
                    $selection,
                    URL::signedRoute('selfevaluations.create', ['selectionID' => $selection->id]),
                    $mailtemplate
                ));
            }
        } elseif ($mailtemplate->mail_class == 'NotifyInstructorAboutEvaluation') {
            $selections = Selection::whereHas('schoolclass.schoolterm', function ($query) {
                $query->where('id', SchoolTerm::getSchoolTermInEvaluationPeriod()->id ?? '');
            })->where('sitatl', '!=', 'Desligado')->doesntHave('instructorevaluation')->get();

            foreach ($selections as $selection) {
                Mail::to($selection->requisition->instructor->codema)->send(new NotifyInstructorAboutEvaluation(
                    $selection,
                    URL::signedRoute('instructorevaluations.create', ['selectionID' => $selection->id]),
                    $mailtemplate
                ));
            }
        }
    }

    private function isDueOnDate(?string $date, ?string $hour): bool
    {
        return now()->format('d/m/Y') === $date && now()->format('H:i') === $hour;
    }
}
