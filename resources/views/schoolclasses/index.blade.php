@extends('parent')

@section('title', 'Turmas')

@section('content')
@parent
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Turmas</h1>

            @include('schoolclasses.modals.import')
            @include('schoolclasses.modals.addSchoolClass')
            <p class="text-right">
                @if(Auth::user()->hasPermissionTo('criar turma'))
                <a class="btn btn-primary"
                    data-toggle="modal"
                    data-target="#addSchoolClassModal"
                    title="Cadastrar" 
                >
                    <i class="fas fa-plus-circle"></i>
                    Cadastrar
                </a>
                @endif
                @if(Auth::user()->hasPermissionTo('importar turmas do replicado'))
                <a class="btn btn-primary"
                    data-toggle="modal"
                    data-target="#importSchoolClassModal"
                    title="Importar" 
                >
                    <i class="fas fa-file-upload"></i>
                    Importar do Jupiter
                </a>
                @endif
                @if(Auth::user()->hasPermissionTo('buscar turmas'))
                <button class="btn btn-primary" id="btn-search" data-toggle="modal" data-target="#schoolclassesSearchModal">
                    <i class="fas fa-search"></i>
                    Buscar
                </button>
                @endif
            </p>
            @include('schoolclasses.modals.search')

            @if (count($turmas) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th>Código da Turma</th>
                        <th>Código da Disciplina</th>
                        <th>Nome da Disciplina</th>
                        <th>Departamento</th>
                        <th>Tipo da Turma</th>
                        <th>Horários</th>
                        <th>Prof(a)</th>
                        <th>Início</th>
                        <th>Fim</th>
                        @if(Auth::user()->hasPermissionTo('editar turma') || Auth::user()->hasPermissionTo('deletar turma'))
                        <th></th>
                        @endif
                    </tr>

                    @foreach($turmas as $turma)
                        <tr style="font-size:12px;">
                            <td>{{ $turma->codtur }}</td>
                            <td>{{ $turma->coddis }}</td>
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
                            <td>{{ $turma->dtainitur->format('Y-m-d') }}</td>
                            <td>{{ $turma->dtafimtur->format('Y-m-d') }}</td>
                            @if(Auth::user()->hasPermissionTo('editar turma') || Auth::user()->hasPermissionTo('deletar turma'))
                            <td class="text-center" style="white-space: nowrap;">
                                @if(Auth::user()->hasPermissionTo('editar turma'))
                                <a class="text-dark text-decoration-none"
                                    data-toggle="tooltip" data-placement="top"
                                    title="Editar"
                                    href="{{ route('schoolclasses.edit', $turma) }}"
                                >
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endif
                                @if(Auth::user()->hasPermissionTo('deletar turma'))
                                <a class="text-dark text-decoration-none"
                                    data-toggle="modal"
                                    data-target="#removalModal"
                                    title="Remover"
                                    href="{{ route(
                                        'schoolclasses.destroy',
                                        $turma
                                    ) }}"
                                >
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                                @endif
                            </td>
                            @endif
                        </tr>
                    @endforeach
                </table>
                @include('schoolclasses.modals.removal')
            @else
                <p class="text-center">Não há turmas cadastradas</p>
            @endif
        </div>
    </div>
</div>
@endsection