@extends('parent')

@section('title', 'Avaliações dos Docentes')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-12">
            <h1 class='text-center mb-5'>Avaliações dos Docentes</h1>

            @if($schoolterm)
                <h4 class='text-center mb-5'>{{ $schoolterm->period . ' de ' . $schoolterm->year }}</h4>
            @endif

            @include('instructorevaluations.modals.chooseSchoolTerm')

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
            @if (count($ies) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th>Professor Responsável</th>
                        <th>Monitor</th>
                        <th>Código<br> da<br> Disciplina</th>
                        <th>Nome da Disciplina</th>
                        <th></th>
                    </tr>
                    @foreach($ies as $ie)
                        <tr style="font-size:12px;">
                            <td>{{ $ie->selection->requisition->instructor->nompes }}</td>
                            <td>{{ $ie->student->nompes }}</td>
                            <td class="text-center">{{ $ie->schoolclass->coddis }}</td>
                            <td>{{ $ie->schoolclass->nomdis }}</td>
                            <td class="text-center"><a href="{{ route('instructorevaluations.show', $ie) }}" class="btn btn-outline-dark btn-sm">Visualizar</a></td>
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