@extends('parent')

@section('title', 'Seleção de Monitores')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-3'>Seleção de Monitores</h1>
            @if(Auth::user()->hasRole(['Secretaria', 'Administrador', 'Presidente de Comissão']))
                <h4 class='text-center'>Todos Departamentos</h4>
            @elseif(Auth::user()->hasRole('Membro Comissão'))
                <h4 class='text-center'>Departamento de {{ App\Models\Instructor::where(['codpes'=>Auth::user()->codpes])->first()->department->nomset }}</h4>
            @endif

            <h4 class='text-center mb-5'>{{ $schoolterm->period . ' de ' . $schoolterm->year }}</h4>

            @if (count($solicitacoes) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr class="text-center">
                        <th>Sigla da Disciplina</th>
                        <th>Código da Turma</th>
                        <th>Nome da Disciplina</th>
                        <th>Horários</th>
                        <th>Professor(a) Solicitante</th>
                        <th>N.° de Monitores Solicitados</th>
                        <th>N.° de Inscritos</th>
                        <th>Monitores Eleitos</th>
                        <th></th>
                    </tr>

                    @foreach($solicitacoes as $solicitacao)
                        <tr class="text-center">
                            <td>{{ $solicitacao->schoolclass->coddis }}</td>
                            <td>{{ $solicitacao->schoolclass->codtur }}</td>
                            <td class="text-left">{{ $solicitacao->schoolclass->nomdis }}</td>
                            <td style="white-space: nowrap;">
                                @foreach($solicitacao->schoolclass->classschedules as $horario)
                                    {{ $horario->diasmnocp . ' ' . $horario->horent . ' ' . $horario->horsai }} <br/>
                                @endforeach
                            </td>
                            <td class="text-left" style="white-space: nowrap;">{{ $solicitacao->instructor->getPronounTreatment() . $solicitacao->instructor->nompes}}</td>
                            <td>{{$solicitacao->requested_number}}</td>
                            <td>{{count($solicitacao->schoolclass->enrollments)}}</td>
                            <td class="text-left" style="white-space: nowrap;">
                                @foreach($solicitacao->schoolclass->selections()->where("sitatl", "!=", "Desligado")->get() as $selecionado)
                                    <b>{{$selecionado->student->nompes}}</b> <br/>
                                @endforeach
                            </td>
                            <td>
                                <a class='btn btn-outline-dark btn-sm'
                                    data-toggle="tooltip" data-placement="top"
                                    title="Inscritos"
                                    href="{{ route('selections.enrollments', $solicitacao->schoolclass) }}"
                                >
                                    Selecionar Monitores
                                </a>
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