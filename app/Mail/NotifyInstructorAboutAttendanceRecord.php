<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotifyInstructorAboutAttendanceRecord extends Mailable
{
    use Queueable, SerializesModels;

    public $schoolclass, $instructor, $student, $month, $year, $period, $link;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($schoolclass, $student, $month, $year, $period, $link)
    {
        $this->schoolclass = $schoolclass;
        $this->instructor = $schoolclass->requisition->instructor;
        $this->student = $student;
        $this->month = $month;
        $this->year = $year;
        $this->period = $period;
        $this->link = $link;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = "[Sistema de Monitoria] Registro de frequência do monitor ".$this->student->nompes;
        return $this->view('emails.attendanceRecord')
                    ->subject($subject);
    }
}
