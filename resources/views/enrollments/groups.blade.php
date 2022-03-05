@extends('parent')

@section('title', 'Turmas')

@section('content')
@parent
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Turmas</h1>

            @if (count($turmas) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th>Sigla da Disciplina</th>
                        <th>Código da Turma</th>
                        <th>Nome da Disciplina</th>
                        <th>Departamento</th>
                        <th>Tipo da Turma</th>
                        <th>Horários</th>
                        <th>Prof(a)</th>
                        <th>Inscrito</th>
                        <th></th>
                    </tr>

                    @foreach($turmas as $turma)
                        <tr style="font-size:12px;">
                            <td>{{ $turma->coddis }}</td>
                            <td>{{ $turma->codtur }}</td>
                            <td>{{ $turma->nomdis }}</td>
                            <td style="text-align: center">{{ $turma->department->nomabvset }}</td>
                            <td>{{ $turma->tiptur }}</td>
                            <td style="white-space: nowrap;">
                                @foreach($turma->classschedules as $horario)
                                    {{ $horario->diasmnocp . ' ' . $horario->horent . ' ' . $horario->horsai }} <br/>
                                @endforeach
                            </td>
                            <td style="white-space: nowrap;">
                                @foreach($turma->instructors as $instrutor)
                                    {{ $instrutor->nompes }} <br/>
                                @endforeach
                            </td>
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
                                        <input type="hidden" name="group_id" value="{{ $turma->id }}">
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