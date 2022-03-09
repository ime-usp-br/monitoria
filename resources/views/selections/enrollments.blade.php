@extends('parent')

@section('title', 'Alunos Inscritos')

@section('content')
@parent
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Seleção de Monitores</h1>
            <h2 class='text-center mb-5'>
                Departamento de {{ $turma->department->nomset }}<br>
            </h2>
            <h4 class='text-center mb-5'>
                <b>Disciplina:</b>  {{ $turma->nomdis }} <b>Turma:</b> {{ $turma->codtur }}
            </h4>

            <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr class="text-center">
                        <th>Sigla da Disciplina</th>
                        <th>Horários</th>
                        <th>{{ $turma->requisition->instructor->getPronounTreatment() == "Prof. Dr. " ? "Professor solicitante" : "Professora solicitante" }}</th>
                        <th>N.° de Monitores Solicitados</th>
                        <th>Atividades atribuidas</th>
                        <th>Prioridade</th>
                        <th>Alunos indicados</th>
                    </tr>

                    <tr class="text-center">
                        <td>{{ $turma->coddis }}</td>
                        <td style="white-space: nowrap;">
                            @foreach($turma->classschedules as $horario)
                                {{ $horario->diasmnocp . ' ' . $horario->horent . ' ' . $horario->horsai }} <br/>
                            @endforeach
                        </td>
                        <td style="white-space: nowrap;">{{ $turma->requisition->instructor->getPronounTreatment() . $turma->requisition->instructor->nompes}}</td>
                        <td>{{$turma->requisition->requested_number}}</td>
                        <td class="text-left"  style="white-space: nowrap;">
                            @foreach($turma->requisition->activities as $atividade)
                                {{ $atividade->description }} <br/>
                            @endforeach
                        </td>
                        <td>{{ $turma->requisition->getPriority()}}</td>
                        <td class="text-left" style="white-space: nowrap;">
                            @if($turma->requisition->recommendations)
                                @foreach($turma->requisition->recommendations as $indicacao)
                                    {{ $indicacao->student->nompes }} <br/>
                                @endforeach
                            @endif
                        </td>
                    </tr>
                </table> <br/>

            <h4 class='text-center mb-5'>
                Alunos Inscritos
            </h4>

            <p class="text-right">
                <a class="btn btn-primary"
                    data-toggle="tooltip" data-placement="top"
                    title="Voltar"
                    href="{{ route('selections.index') }}"
                >
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </a>

                <a class="btn btn-primary"
                    data-toggle="modal" data-target="#selectUnenrolledStudentModal"
                    title="Eleger como monitor aluno não inscrito na Turma">
                    Eleger Não Inscrito
                </a>
            </p>

            @include('selections.modals.selectUnenrolledStudentModal')

            @if (count($turma->enrollments) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr class="text-center">
                        <th>N.° USP</th>
                        <th>Nome do Aluno</th>
                        <th>Histórico Escolar</th>
                        <th>Disponibilidade para trabalhar de dia</th>
                        <th>Disponibilidade para trabalhar de noite</th>
                        <th>Preferência de trabalhar no período</th>
                        <th>Voluntário</th>
                        <th>Observações</th>
                        <th>Aluno indicado</th>
                        <th>Eleito</th>
                        <th></th>
                    </tr>

                    @foreach($inscricoes as $inscricao)
                        <tr class="text-center">
                            <td >{{ $inscricao->student->codpes }}</td>
                            <td class="text-left" style="{{ $inscricao->hasOtherSelectionInOpenSchoolTerm() ? 'text-decoration: Line-Through;white-space: nowrap;' : 'white-space: nowrap;'}}">{{ $inscricao->student->nompes }}</td>
                            <td>
                                <form method="POST" action="{{ route('schoolrecords.download') }}" target="_blank">
                                    @csrf
                                    <input type='hidden' name='path' value="{{ $inscricao->student->getSchoolRecordFromOpenSchoolTerm()->file_path }}">
                                    <button class="btn btn-link"
                                        data-toggle="tooltip" data-placement="top"
                                        title="Baixar Histórico Escolar"
                                    >
                                        Download
                                    </button>
                                </form>                            
                            </td>
                            <td>{{ $inscricao->disponibilidade_diurno ? 'Sim' : 'Não'}}</td>
                            <td>{{ $inscricao->disponibilidade_noturno ? 'Sim' : 'Não'}}</td>
                            <td>{{ $inscricao->preferencia_horario }}</td>
                            <td>{{ $inscricao->voluntario ? 'Sim' : 'Não'}}</td>
                            <td class="text-left">{{ $inscricao->observacoes }}</td>
                            <td>
                                @if($turma->requisition)
                                    {{ $turma->requisition->isStudentRecommended($inscricao->student) ? 'Sim' : 'Não' }} <br/>
                                @endif
                            </td>
                            <td>
                                <b>{{ $inscricao->selection ? 'Sim' : 'Não' }}</b> 
                            </td>
                            <td style="text-decoration-skip: all;">
                                
                                @if($inscricao->selection)
                                    <form method="POST" action="{{ route('selections.destroy', $inscricao->selection) }}">
                                        @method('delete')
                                        @csrf
                                        <button class='btn btn-outline-danger btn-sm'> Preterir Monitor</button>
                                    </form>
                                @elseif($inscricao->hasOtherSelectionInOpenSchoolTerm())
                                    Já foi eleito monitor da turma 
                                    {{ $inscricao->student->getSelectionFromOpenSchoolTerm()->schoolclass->codtur }} da disciplina 
                                    {{ $inscricao->student->getSelectionFromOpenSchoolTerm()->schoolclass->coddis }}
                                @else
                                    <form method="POST"
                                        action="{{ route('selections.store') }}"
                                    >
                                        <input name="enrollment_id" value="{{$inscricao->id}}" type="hidden">
                                        @csrf
                                        <button class='btn btn-outline-dark btn-sm'> Elerger Monitor</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p class="text-center">Não há inscrições para monitoria nesta disciplina</p>
            @endif
        </div>
    </div>
</div>
@endsection