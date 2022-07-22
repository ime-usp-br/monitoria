@extends('parent')

@section('title', 'Alunos Inscritos')

@section('content')
@parent
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Alunos Inscritos</h1>

            <h4 class='text-center mb-5'>{{ $schoolterm->period . ' de ' . $schoolterm->year }}</h4>

            @if (count($alunos) > 0)

                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th>N USP</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Histórico Escolar</th>
                        <th>Inscrito nas Turmas</th>
                    </tr>

                    @foreach($alunos as $aluno)
                        <tr style="font-size:12px;">
                            <td style="text-align: center">{{ $aluno->codpes }}</td>
                            <td style="white-space: nowrap;">{{ $aluno->nompes }}</td>
                            <td style="white-space: nowrap;">{{ $aluno->codema }}</td>
                            <td style="text-align: center">
                                <form method="POST" action="{{ route('schoolrecords.download') }}" target="_blank">
                                    @csrf
                                    <input type='hidden' name='path' value="{{ $aluno->getSchoolRecordFromOpenSchoolTerm()->file_path }}">
                                    <button class="btn btn-link"
                                        data-toggle="tooltip" data-placement="top"
                                        title="Baixar Histórico Escolar"
                                    >
                                        Download
                                    </button>
                                </form> 
                            </td>
                            <td style="white-space: nowrap;text-align: center">
                                @foreach($aluno->enrollments()->whereHas("schoolclass", function($query)use($schoolterm){$query->whereBelongsTo($schoolterm);})->get() as $inscricao)
                                    @if($inscricao->schoolclass->requisition()->exists())
                                        <a href="{{ route('selections.enrollments', $inscricao->schoolclass) }}">{{ $inscricao->schoolclass->coddis." T.".substr($inscricao->schoolclass->codtur,-2,2) }}</a> <br/>
                                    @else
                                        {{ $inscricao->schoolclass->coddis." T.".substr($inscricao->schoolclass->codtur,-2,2) }} <br/>
                                    @endif
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p class="text-center">Não há alunos inscritos esse semestre</p>
            @endif
        </div>
    </div>
</div>
@endsection