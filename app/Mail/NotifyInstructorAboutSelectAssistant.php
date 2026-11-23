<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\SchoolClass;
use App\Models\MailTemplate;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class NotifyInstructorAboutSelectAssistant extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $schoolclass, $instructor, $mailtemplate;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(SchoolClass $schoolclass, MailTemplate $mailtemplate)
    {
        $this->schoolclass = $schoolclass;
        $this->instructor = $schoolclass->requisition->instructor;
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

        $plural = count($this->schoolclass->selections) > 1 ? 1 : 0;

        $subject = app(\App\Services\MailTemplateRenderer::class)->render(
            html_entity_decode($this->mailtemplate->subject),
            [
                "schoolclass"=>$this->schoolclass,
                "instructor"=>$this->instructor,
                "plural"=>$plural,
            ]
        );
        
        $body = app(\App\Services\MailTemplateRenderer::class)->render(
            html_entity_decode($this->mailtemplate->body),
            [
                "schoolclass"=>$this->schoolclass,
                "instructor"=>$this->instructor,
                "plural"=>$plural,
            ]
        );

        $css = file_get_contents(base_path() . '/public/css/mail.css');

        return $this->html($cssToInlineStyles->convert($body, $css))
                    ->subject($subject);
    }
}
