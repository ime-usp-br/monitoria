@extends('parent')

@section('title', 'Solicitações de Monitores')

@section('content')
@parent
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Solicitações de Monitores</h1>
            <h4 class='text-center mb-5'>{{ $docente->nompes }}</h4>

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
                <table class="table table-bordered table-striped table-hover">
                    <tr class="text-center">
                        <th>Sigla da Disciplina</th>
                        <th>Código da Turma</th>
                        <th>Nome da Disciplina</th>
                        <th>Horários</th>
                        <th>N.° de Monitores</th>
                        <th>Atividades atribuidas</th>
                        <th>Prioridade</th>
                    </tr>

                    @foreach($docente->getRequests() as $solicitacao)
                        <tr>
                            <td class="text-center">{{ $solicitacao->group->coddis }}</td>
                            <td class="text-center">{{ $solicitacao->group->codtur }}</td>
                            <td>{{ $solicitacao->group->nomdis }}</td>
                            <td style="white-space: nowrap;">
                                @foreach($solicitacao->group->classschedules as $horario)
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
                        </tr>
                    @endforeach
                </table>
            @else
                <p class="text-center">Não há docentes cadastrados</p>
            @endif
        </div>
    </div>
</div>
@endsection