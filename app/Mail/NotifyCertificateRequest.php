<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Selection;
use App\Models\MailTemplate;
use Illuminate\Support\Facades\Blade;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Ismaelw\LaraTeX\LaraTeX;

class NotifyCertificateRequest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $student, $schoolclass, $schoolterm, $selection, $mailtemplate;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Selection $selection, MailTemplate $mailtemplate)
    {
        $this->student = $selection->student;
        $this->schoolclass = $selection->schoolclass;
        $this->schoolterm = $selection->schoolclass->schoolterm;
        $this->selection = $selection;
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
                "student" => $this->student,
                "schoolclass" => $this->schoolclass,
                "schoolterm" => $this->schoolterm,
                "selection" => $this->selection,
            ]
        );

        $body = Blade::render(
            html_entity_decode($this->mailtemplate->body),
            [
                "student" => $this->student,
                "schoolclass" => $this->schoolclass,
                "schoolterm" => $this->schoolterm,
                "selection" => $this->selection,
            ]
        );

        $css = file_get_contents(base_path() . '/public/css/mail.css');

        // Gera o certificado (atestado) em PDF e anexa ao e-mail enviado à
        // Secretaria, para que ela possa encaminhar ao USP ASSINA para
        // validação e posterior envio ao aluno.
        $template = $this->selection->sitatl == "Concluido"
            ? "certificates.completed"
            : "certificates.ongoing";

        $pdfContent = (new LaraTeX($template))->with([
            'selection' => $this->selection,
        ])->content('raw');

        $filename = 'atestado_' . $this->student->codpes . '.pdf';

        return $this->html($cssToInlineStyles->convert($body, $css))
                    ->subject($subject)
                    ->attachData($pdfContent, $filename, ['mime' => 'application/pdf']);
    }
}
