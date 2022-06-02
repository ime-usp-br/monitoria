<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\SchoolClass;

class NotifyInstructorAboutSelectAssistant extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $schoolclass;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(SchoolClass $schoolclass)
    {
        $this->schoolclass = $schoolclass;
        $this->afterCommit();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $plural = count($this->schoolclass->selections) > 1 ? 1 : 0;
        $subject = "[Sistema de Monitoria] Monitor".($plural ? 'es' : '')." selecionado".($plural ? 's' : '')." 
                    para disciplina ".$this->schoolclass->coddis." turma ".$this->schoolclass->codtur;
        return $this->view('emails.instructor')
                    ->subject($subject);
    }
}
