@extends('parent')

@section('title', 'Auto Avaliações')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Relatórios das Atividades Desenvolvidas</h1>

            @if (count($selections) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
    
                    <tr>
                        <th>Código da Disciplina</th>
                        <th>Nome da Disciplina</th>
                        <th>Professor Responsável</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th></th>

                    </tr>
                    @foreach($selections as $selection)
                        <tr class="text-center">
                            <td>{{ $selection->schoolclass->coddis }}</td>
                            <td>{{ $selection->schoolclass->nomdis }}</td>
                            <td>{{ $selection->requisition->instructor->nompes }}</td>
                            <td>{{ $selection->schoolclass->schoolterm->period }} de {{ $selection->schoolclass->schoolterm->year }}</td>
                            <td>{{ $selection->sitatl }}</td>
                            <td>
                                @if($selection->selfevaluation)
                                    <a href="{{ route('selfevaluations.show', $selection->selfevaluation) }}" class="btn btn-outline-dark btn-sm">Visualizar</a>
                                @endif
                                @if(App\Models\SchoolTerm::isEvaluationPeriod())
                                    @if($selection->schoolclass->schoolterm->id == App\Models\SchoolTerm::getSchoolTermInEvaluationPeriod()->id)
                                        @if($selection->selfevaluation)
                                            <a href="{{ route('selfevaluations.edit', $selection->selfevaluation) }}" class="btn btn-outline-dark btn-sm">Editar</a>
                                        @else
                                            <form action="{{ route('selfevaluations.create') }}" method="GET">
                                                <input name="selectionID" value="{{$selection->id}}" type="hidden">
                                                <button class="btn btn-outline-dark btn-sm" type="submit">Gerar</button>

                                            </form>
                                        @endif
                                    @endif
                                @else
                                    @if(!$selection->selfevaluation)
                                        @if($selection->sitatl == "Concluido")
                                            Monitoria concluida sem relatório de Atividades
                                        @elseif($selection->sitatl == "Ativo")
                                            Período de avaliação de {{ $selection->schoolclass->schoolterm->start_date_evaluations." à ".$selection->schoolclass->schoolterm->end_date_evaluations}}
                                        @endif
                                    @endif
                                @endif

                            </td>
                        </tr>
                    @endforeach

                </table>
            @else
                <p class="text-center">Não constam monitorias em seu nome</p>
            @endif
        </div>
    </div>
</div>
@endsection 