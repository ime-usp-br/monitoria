<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Selection;
use Illuminate\Support\Facades\Blade;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class NotifyCertificateRequest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $student, $schoolclass, $schoolterm, $selection;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Selection $selection)
    {
        $this->student = $selection->student;
        $this->schoolclass = $selection->schoolclass;
        $this->schoolterm = $selection->schoolclass->schoolterm;
        $this->selection = $selection;
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

        $body = Blade::render(
            nl2br("O aluno {{ \$student->nompes }} (Nº USP {{ \$student->codpes }}) solicitou o Certificado de Monitoria da disciplina "
                . "{{ \$schoolclass->coddis }} - {{ \$schoolclass->nomdis }} referente ao "
                . "{{ \$schoolterm->period }} de {{ \$schoolterm->year }}.<br><br>"
                . "Encaminhe o certificado ao USP ASSINA para validação e posterior envio ao aluno."),
            [
                "student" => $this->student,
                "schoolclass" => $this->schoolclass,
                "schoolterm" => $this->schoolterm,
            ]
        );

        $css = file_get_contents(base_path() . '/public/css/mail.css');

        return $this->html($cssToInlineStyles->convert($body, $css))
                    ->subject('Solicitação de Certificado de Monitoria');
    }
}
