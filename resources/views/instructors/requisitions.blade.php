@extends('parent')

@section('title', 'Solicitações de Monitores')

@section('content')
@parent
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Solicitações de Monitores</h1>
            <h4 class='text-center mb-5'>{{ $docente->getPronountreatment() . $docente->nompes }}</h4>

            <p class="text-right">
                <a class="btn btn-primary"
                    data-toggle="tooltip" data-placement="top"
                    title="Voltar"
                    href="{{ route('instructors.index') }}"
                >
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </a>
            </p>

            @if (count($docente->getRequests()) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr class="text-center">
                        <th>Sigla da Disciplina</th>
                        <th>Código da Turma</th>
                        <th>Nome da Disciplina</th>
                        <th>Horários</th>
                        <th>N.° de Monitores</th>
                        <th>Atividades atribuidas</th>
                        <th>Prioridade</th>
                        <th>Alunos indicados</th>
                        <th>Inscritos</th>
                    </tr>

                    @foreach($docente->getRequests() as $solicitacao)
                        <tr>
                            <td class="text-center">{{ $solicitacao->schoolclass->coddis }}</td>
                            <td class="text-center">{{ $solicitacao->schoolclass->codtur }}</td>
                            <td>{{ $solicitacao->schoolclass->nomdis }}</td>
                            <td style="white-space: nowrap;">
                                @foreach($solicitacao->schoolclass->classschedules as $horario)
                                    {{ $horario->diasmnocp . ' ' . $horario->horent . ' ' . $horario->horsai }} <br/>
                                @endforeach
                            </td>
                            <td class="text-center">{{$solicitacao->requested_number}}</td>
                            <td style="white-space: nowrap;">
                                @foreach($solicitacao->activities as $atividade)
                                    {{ $atividade->description }} <br/>
                                @endforeach
                            </td>
                            <td>{{$solicitacao->getPriority()}}</td>
                            <td class="text-left" style="white-space: nowrap;">
                                @if($solicitacao->recommendations)
                                    @foreach($solicitacao->recommendations as $indicacao)
                                        {{ $indicacao->student->nompes }} <br/>
                                    @endforeach
                                @endif
                            </td>
                            <td class="text-center">
                                @if($solicitacao->schoolclass->enrollments()->exists())
                                    <a 
                                        data-toggle="tooltip" data-placement="top"
                                        title="Ver Inscritos"
                                        href="{{ route('schoolclasses.enrollments', $solicitacao->schoolclass) }}"
                                    >
                                        Ver Inscritos
                                    </a>
                                @else
                                    Nenhuma Inscrição
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p class="text-center">Não há solicitações de monitores cadastradas</p>
            @endif
        </div>
    </div>
</div>
@endsection