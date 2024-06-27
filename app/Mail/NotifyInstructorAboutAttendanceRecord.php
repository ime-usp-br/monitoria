<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\MailTemplate;
use App\Models\Frequency;
use Illuminate\Support\Facades\Blade;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class NotifyInstructorAboutAttendanceRecord extends Mailable
{
    use Queueable, SerializesModels;

    public $schoolclass, $instructor, $student, $month, $year, $period, $link, $mailtemplate;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Frequency $frequency, $link, MailTemplate $mailtemplate)
    {
        $this->schoolclass = $frequency->schoolclass;
        $this->instructor = $frequency->schoolclass->requisition->instructor;
        $this->student = $frequency->student;
        $this->month = $frequency->month;
        $this->year = $frequency->schoolclass->schoolterm->year;
        $this->period = $frequency->schoolclass->schoolterm->period;
        $this->link = $link;
        $this->mailtemplate = $mailtemplate;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $cssToInlineStyles = new CssToInlineStyles();

        $subject = Blade::render(
            html_entity_decode($this->mailtemplate->subject),
            [
                "schoolclass"=>$this->schoolclass,
                "instructor"=>$this->instructor,
                "student"=>$this->student,
                "month"=>$this->month,
                "year"=>$this->year,
                "period"=>$this->period,
                "link"=>$this->link,
            ]
        );
        
        $body = Blade::render(
            html_entity_decode($this->mailtemplate->body),
            [
                "schoolclass"=>$this->schoolclass,
                "instructor"=>$this->instructor,
                "student"=>$this->student,
                "month"=>$this->month,
                "year"=>$this->year,
                "period"=>$this->period,
                "link"=>$this->link,
            ]
        );

        $css = file_get_contents(base_path() . '/public/css/mail.css');

        return $this->html($cssToInlineStyles->convert($body, $css))
                    ->subject($subject);
    }
}
