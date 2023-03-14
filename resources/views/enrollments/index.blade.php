@extends('parent')

@section('title', 'Turmas com inscrições abertas')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Disciplinas com inscrições abertas</h1>

            @if (count($turmas) > 0)

                <form method='post' action="{{ route('schoolRecords.update', $estudante->getSchoolRecordFromOpenSchoolTerm()) }}" enctype='multipart/form-data' >
                    @csrf
                    @method('patch')
                    <div class="text-right" style="height: 50px;">
                        <input  class="custom-form-input" type='file' name='file' >
                        <button class="btn btn-outline-primary" type='submit' name='submit' >
                            <i class="fas fa-file-upload"></i>
                            Reenviar histórico escolar
                        </button>
                    </div>
                </form>

                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th>Sigla da Disciplina</th>
                        <th>Nome da Disciplina</th>
                        <th>Departamento</th>
                        <th>Inscrito</th>
                        <th></th>
                    </tr>

                    @foreach($turmas->pluck("coddis")->unique()->toArray() as $coddis)
                        @php
                            $turma = $turmas->where("coddis", $coddis)->first();
                        @endphp
                        <tr style="font-size:12px;">
                            <td style="text-align: center">{{ $turma->coddis }}</td>
                            <td>{{ $turma->nomdis }}</td>
                            <td style="text-align: center">{{ $turma->department->nomabvset == "450" ? "Interdepartamental" : $turma->department->nomabvset }}</td>
                            @if($turma->isStudentEnrolled($estudante))
                                <td style="text-align: center">Sim</td>
                                <td >
                                    <div class="row justify-content-center">
                                        <a class="btn btn-outline-dark"
                                            data-toggle="tooltip" data-placement="top"
                                            title="Fazer Inscrição"
                                            href="{{ route('enrollments.edit', $turma->getEnrollmentByStudent($estudante)) }}"
                                        >
                                            Editar
                                        </a>
                                        <form method="POST" action="{{ route('enrollments.destroy', $turma->getEnrollmentByStudent($estudante)) }}">
                                            @method('delete')
                                            @csrf
                                            <button class="btn btn-outline-dark"
                                                data-toggle="tooltip" data-placement="top"
                                                title="Remover Inscrição"
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            @else
                                <td style="text-align: center">Não</td>
                                <td class="text-center" style="white-space: nowrap;">
                                    <form method="GET" action="{{ route('enrollments.create') }}">
                                        <input type="hidden" name="school_class_id" value="{{ $turma->id }}">
                                        <button class="btn btn-outline-dark" type="submit">
                                            Inscrição
                                        </button>
                                    </form> 
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            @else
                <p class="text-center">Não há turmas cadastradas</p>
            @endif
        </div>
    </div>
</div>
@endsection