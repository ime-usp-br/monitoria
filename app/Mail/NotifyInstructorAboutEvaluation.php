<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Selection;
use App\Models\MailTemplate;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Illuminate\Support\Facades\Blade;

class NotifyInstructorAboutEvaluation extends Mailable
{
    use Queueable, SerializesModels;

    public $student, $instructor, $schoolclass, $selection, $link, $mailtemplate;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Selection $selection, $link, MailTemplate $mailtemplate)
    {
        $this->student = $selection->student;
        $this->instructor = $selection->requisition->instructor;
        $this->schoolclass = $selection->schoolclass;
        $this->selection = $selection;
        $this->mailtemplate = $mailtemplate;
        $this->link = $link;
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
                "instructor"=>$this->instructor,
                "selection"=>$this->selection,
                "link"=>$this->link,
            ]
        );
        
        $body = Blade::render(
            html_entity_decode($this->mailtemplate->body),
            [
                "schoolclass"=>$this->schoolclass,
                "student"=>$this->student,
                "instructor"=>$this->instructor,
                "selection"=>$this->selection,
                "link"=>$this->link,
            ]
        );

        $css = file_get_contents(base_path() . '/public/css/mail.css');

        return $this->html($cssToInlineStyles->convert($body, $css))
                    ->subject($subject);
    }
}
