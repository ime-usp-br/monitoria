<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\MailTemplate;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Illuminate\Support\Facades\Blade;

class NotifySelectStudent extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $student, $schoolclass, $mailtemplate;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Student $student, SchoolClass $schoolclass, MailTemplate $mailtemplate)
    {
        $this->student = $student;
        $this->schoolclass = $schoolclass;
        $this->mailtemplate = $mailtemplate;
        $this->afterCommit();
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
                "student"=>$this->student,
            ]
        );
        
        $body = Blade::render(
            html_entity_decode($this->mailtemplate->body),
            [
                "schoolclass"=>$this->schoolclass,
                "student"=>$this->student,
            ]
        );

        $css = file_get_contents(base_path() . '/public/css/mail.css');

        return $this->html($cssToInlineStyles->convert($body, $css))
                    ->subject($subject);
    }
}
