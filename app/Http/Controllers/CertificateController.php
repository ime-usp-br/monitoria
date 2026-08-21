<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexCertificateRequest;
use Illuminate\Http\Request;
use App\Models\Selection;
use App\Models\Student;
use App\Models\SchoolTerm;
use App\Models\MailTemplate;
use App\Mail\NotifyCertificateRequest;
use Illuminate\Support\Facades\Mail;
use Ismaelw\LaraTeX\LaraTeX;
use Auth;
use Session;

class CertificateController extends Controller
{
    public function index(IndexCertificateRequest $request)
    {
        if(Auth::check()){
            $hasSelection = Selection::whereHas("student", function($query){$query->where("codpes",Auth::user()->codpes);})->get()->isNotEmpty();
            if(!Auth::user()->hasRole(["Aluno", "Secretaria", "Administrador"])){
                if(!$hasSelection){
                    abort(403);
                }
            }
        }else{
            return redirect("login");
        }

        $validated = $request->validated();

        if(isset($validated['periodoId'])){
            $schoolterm = SchoolTerm::find($validated['periodoId']);
        }else{
            $schoolterm = SchoolTerm::getOpenSchoolTerm();

            if(!$schoolterm){
                $schoolterm = SchoolTerm::getLatest();
            }
        }

        if(!$schoolterm){
            Session::flash('alert-warning', 'Não foi encontrado um periodo letivo.');
            return back();
        }

        if(Auth::user()->hasRole(["Secretaria", "Administrador"])){
            $selections = Selection::whereHas("schoolclass", function($query)use($schoolterm){
                $query->whereBelongsTo($schoolterm);
            })->where("sitatl", "!=", "Desligado")->get()->sortBy("student.nompes");
        }elseif(Auth::user()->hasRole("Aluno") or $hasSelection){
            $selections = Selection::whereBelongsTo(Student::where("codpes", Auth::user()->codpes)->first())
                ->where("sitatl", "!=", "Desligado")->get()->sortBy(["schoolclass.schoolterm.year", "schoolclass.schoolterm.period"])->reverse();
        }
        

        if($selections->isEmpty()){
            Session::flash("alert-warning", "Você não realizou nenhuma monitoria.");
            return back();
        }

        return view('certificates.index', compact(['selections','schoolterm']));
    }

    public function make(Selection $selection)
    {
        $isSecretariaAdmin = false;

        if(Auth::check()){
            if(!Auth::user()->hasRole(["Secretaria", "Administrador"])){
                if($selection->student_id != (Student::where("codpes", Auth::user()->codpes)->first()->id ?? "")){
                    abort(403);
                }
            }else{
                $isSecretariaAdmin = true;
            }
        }else{
            return redirect("login");
        }

        if($selection->sitatl == "Concluido" || $selection->sitatl == "Ativo"){
            // Para a Secretaria/Administrador, que encaminha ao USP ASSINA,
            // o certificado é gerado normalmente (sem assinatura em foto).
            if($isSecretariaAdmin){
                $template = $selection->sitatl == "Concluido" ? "certificates.completed" : "certificates.ongoing";
                return (new LaraTeX($template))->with([
                    'selection' => $selection,
                ])->download('atestado_' . $selection->student->codpes . '.pdf');
            }

            // Para o aluno, o certificado não vai mais direto ao aluno com a
            // assinatura em foto: o sistema avisa a Secretaria da solicitação,
            // que encaminha ao USP ASSINA para validação e posterior envio.

            $mailtemplate = MailTemplate::where("mail_class", "NotifyCertificateRequest")->where("active", true)->where("sending_frequency", "Manual")->first();

            if(!$mailtemplate){
                Session::flash('alert-warning', 'Não foi encontrado nenhum modelo de e-mail ativo com frequência manual para notificar a Secretaria sobre a solicitação de certificado.');
                return back();
            }

            $secretariaEmail = config('certificate.secretaria_email');
            if($secretariaEmail){
                Mail::to($secretariaEmail)->send(new NotifyCertificateRequest($selection, $mailtemplate));
            }

            Session::flash('alert-info', 'Sua solicitação de Certificado de Monitoria foi registrada. A Secretaria de Monitoria será notificada e o certificado, após validação no USP ASSINA, será enviado a você por e-mail.');
            return back();
        }
    }
}
