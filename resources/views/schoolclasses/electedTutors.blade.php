@extends('parent')

@section('title', 'Monitores Eleitos')

@section('content')
@parent
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Monitores Eleitos</h1>
            <h2 class='text-center mb-5'>
                Departamento de {{ $turma->department->nomset }}<br>
            </h2>
            <h4 class='text-center mb-5'>
                <b>Disciplina:</b>  {{ $turma->nomdis }} <b>Turma:</b> {{ $turma->codtur }}
            </h4>
            <div id="progressbar-div">
            </div>
            @if ( !is_null($turma->requisition) && count($turma->requisition->selections) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th>N.° USP</th>
                        <th>Nome do aluno</th>
                        <th>Professor(a) solicitante</th>
                        <th>Atividades atribuidas</th>
                        <th>Registros de frequência</th>
                    </tr>

                    @foreach($turma->requisition->selections as $eleicao)
                        <tr style="font-size:12px;">
                            <td>{{ $eleicao->student->codpes }}</td>
                            <td>{{ $eleicao->student->nompes }}</td>
                            <td class="text-left" style="white-space: nowrap;">{{ $turma->requisition->instructor->getPronounTreatment() . $turma->requisition->instructor->nompes}}</td>
                            <td class="text-left"  style="white-space: nowrap;">
                            @foreach($turma->requisition->activities as $atividade)
                                {{ $atividade->description }} <br/>
                            @endforeach
                            </td>
                            <td><a href="/schoolclasses/{{$turma->id}}/electedTutors/{{$eleicao->student->id}}/frequencies" class="btn btn-outline-dark btn-sm">Registrar frequência</a></td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p class="text-center">Esta turma não possui monitores.</p>
            @endif
        </div>
    </div>
</div>
@endsection