<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Student;
use App\Models\SchooClass;

class NotifySelectStudent extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $student;
    public $schoolclass;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Student $student)
    {
        $this->student = $student;
        $this->schoolclass = $this->student->getSelectionFromOpenSchoolTerm()->schoolclass;
        $this->afterCommit();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = "[Sistema de Monitoria] Você foi selecionado como monitor da disciplina ".$this->schoolclass->coddis." 
                    turma ".$this->schoolclass->codtur;
        return $this->view('emails.student')
                    ->subject($subject);
    }
}
