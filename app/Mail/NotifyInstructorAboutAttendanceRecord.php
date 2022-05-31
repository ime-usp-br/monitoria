<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotifyInstructorAboutAttendanceRecord extends Mailable
{
    use Queueable, SerializesModels;

    private $schoolclass, $instructor, $student, $month, $year, $period;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($schoolclass, $student, $month, $year, $period)
    {
        $this->schoolclass = $schoolclass;
        $this->instructor = $schoolclass->instructor;
        $this->student = $student;
        $this->month = $month;
        $this->year = $year;
        $this->period = $period;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = "[Sistema de Monitoria] Registro de frequência do monitor ". $student->nompes;
        return $this->view('emails.attendanceRecord')
                    ->subject($subject);
    }
}
