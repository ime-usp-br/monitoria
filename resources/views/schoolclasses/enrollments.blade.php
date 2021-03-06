@extends('parent')

@section('title', 'Alunos Inscritos')

@section('content')
@parent
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Alunos Inscritos</h1>
            <h4 class='text-center mb-5'>
                <b>Departamento de {{ $turma->department->nomset }}</b> <br>
                <b>Disciplina:</b>  {{ $turma->nomdis }} <br>
                <b>Turma:</b> {{ $turma->codtur }}
            </h4>

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
                    </tr>

                    @foreach($turma->enrollments as $inscricao)
                        <tr>
                            <td class="text-center">{{ $inscricao->student->codpes }}</td>
                            <td style="white-space: nowrap;">{{ $inscricao->student->nompes }}</td>
                            <td class="text-center">
                                <form method="POST" action="{{ route('schoolrecords.download') }}" target="_blank">
                                    @csrf
                                    <input type='hidden' name='path' value="{{ $inscricao->student->getSchoolRecordFromOpenSchoolTerm()->file_path }}">
                                    <button class="btn btn-link"
                                        data-toggle="tooltip" data-placement="top"
                                        title="Download"
                                    >
                                        Download
                                    </button>
                                </form>                            
                            </td>
                            <td class="text-center">{{ $inscricao->disponibilidade_diurno ? 'Sim' : 'Não'}}</td>
                            <td class="text-center">{{ $inscricao->disponibilidade_noturno ? 'Sim' : 'Não'}}</td>
                            <td class="text-center">{{ $inscricao->preferencia_horario }}</td>
                            <td class="text-center">{{ $inscricao->voluntario ? 'Sim' : 'Não'}}</td>
                            <td class="text-center">{{ $inscricao->observacoes }}</td>
                            <td>
                                @if($turma->requisition)
                                    {{ $turma->requisition->isStudentRecommended($inscricao->student) ? 'Sim' : 'Não' }} <br/>
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