@extends('parent')

@section('title', 'Auto Avaliações')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-12">
            <h1 class='text-center mb-5'>Auto Avaliações</h1>

            @if($schoolterm)
                <h4 class='text-center mb-5'>{{ $schoolterm->period . ' de ' . $schoolterm->year }}</h4>
            @endif

            @include('selfevaluations.modals.chooseSchoolTerm')

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
            @if (count($ses) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th>Monitor</th>
                        <th>Professor Responsável</th>
                        <th>Código<br> da<br> Disciplina</th>
                        <th>Nome da Disciplina</th>
                        <th></th>
                    </tr>
                    @foreach($ses as $se)
                        <tr style="font-size:12px;">
                            <td>{{ $se->student->nompes }}</td>
                            <td>{{ $se->selection->requisition->instructor->nompes }}</td>
                            <td class="text-center">{{ $se->schoolclass->coddis }}</td>
                            <td>{{ $se->schoolclass->nomdis }}</td>
                            <td class="text-center"><a href="{{ route('selfevaluations.show', $se) }}" class="btn btn-outline-dark btn-sm">Visualizar</a></td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p class="text-center">Não há auto avaliações cadastradas</p>
            @endif
        </div>
    </div>
</div>
@endsection