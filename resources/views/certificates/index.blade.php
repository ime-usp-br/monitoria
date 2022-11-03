@extends('parent')

@section('title', 'Emitir Declaração')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Atestado de Monitoria</h1>
            
            @if(Auth::user()->hasRole(["Secretaria", "Administrador"]))
                <h4 class='text-center mb-5'>{{ $schoolterm->period . ' de ' . $schoolterm->year }}</h4>

                @include('certificates.modals.chooseSchoolTerm')

                <p class="text-right">
                    <a  id="btn-chooseSchoolTermModal"
                        class="btn btn-outline-primary"
                        data-toggle="modal"
                        data-target="#chooseSchoolTermModal"
                        title="Escolher Semestre" 
                    >
                        Escolher Semestre
                    </a>
                </p>
            @endif

            @if (count($selections) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        @if(Auth::user()->hasRole(["Secretaria", "Administrador"]))
                            <th class="text-center" style="vertical-align: middle;">Monitor(a)</th>
                            <th class="text-center" style="vertical-align: middle;">N° USP do<br>Monitor(a)</th>
                        @endif
                        <th class="text-center" style="vertical-align: middle;">Sigla da Disciplina</th>
                        <th class="text-center" style="vertical-align: middle;">Nome da Disciplina</th>
                        <th class="text-center" style="vertical-align: middle;">Professor(a)</th>
                        @if(!Auth::user()->hasRole(["Secretaria", "Administrador"]))
                            <th class="text-center" style="vertical-align: middle;">Semestre</th>
                        @endif
                        <th class="text-center" style="vertical-align: middle;">Observações</th>
                        <th></th>
                    </tr>
                    @foreach($selections as $selection)
                        <tr class="text-center">
                            @if(Auth::user()->hasRole(["Secretaria", "Administrador"]))
                                <td class="text-left">{{ $selection->student->nompes }}</td>
                                <td>{{ $selection->student->codpes }}</td>
                            @endif
                            <td>{{ $selection->schoolclass->coddis }}</td>
                            <td class="text-left">{{ $selection->schoolclass->nomdis }}</td>
                            <td class="text-left">{{ $selection->requisition->instructor->nompes }}</td>   
                            @if(!Auth::user()->hasRole(["Secretaria", "Administrador"]))
                                <td>{{ $selection->schoolclass->schoolterm->period." de ".$selection->schoolclass->schoolterm->year }}</td>  
                            @endif 
                            @php
                                $instructor = $selection->requisition->instructor;
                                $st = $selection->schoolclass->schoolterm;
                                $label = "";
                                if(!in_array($selection->schoolclass->coddis, App\Models\SchoolClass::getDisciplinesFromReplicadoBySchoolTermAndInstructor($st, $instructor))){
                                    $label = "Foi constatado que ".( $instructor->getSexo() == "M" ? "o Prof. " : "a Profa. ");
                                    $label .= explode(" ", $instructor->nompes)[0]." não ministrou a disciplina ".$selection->schoolclass->coddis." no ".$st->period." de ".$st->year.".";
                                    $label .= " Isto se deve provavelmente a algum erro na solicitação da vaga de monitoria. Caso você precise que o atestado seja corrigido entre em contato com a secretaria de monitoria.";
                                }
                            @endphp
                            <td class="text-left" style="max-width:600px;">{{ $label }}</td>
                            <td class="text-center"><a href="{{ route('certificates.make',['selection'=>$selection->id]) }}" class="btn btn-outline-dark btn-sm">Emitir Atestado</a></td> 
                        </tr>
                    @endforeach
                </table>
            @else
                <p class="text-center">Você não concluiu nenhuma monitoria.</p>
            @endif
        </div>
    </div>
</div>
@endsection 