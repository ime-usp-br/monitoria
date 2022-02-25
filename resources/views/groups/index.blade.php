@extends('parent')

@section('title', 'Turmas')

@section('content')
@parent
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Turmas</h1>

            @include('groups.modals.import')
            @include('groups.modals.addGroup')
            <p class="text-right">
                <a class="btn btn-primary"
                    data-toggle="modal"
                    data-target="#addGroupModal"
                    title="Cadastrar" 
                >
                    <i class="fas fa-plus-circle"></i>
                    Cadastrar
                </a>
                <a class="btn btn-primary"
                    data-toggle="modal"
                    data-target="#importGroupModal"
                    title="Importar" 
                >
                    <i class="fas fa-file-upload"></i>
                    Importar do Jupiter
                </a>
                
                <button class="btn btn-primary" id="btn-search" data-toggle="modal" data-target="#groupsSearchModal">
                    <i class="fas fa-search"></i>
                    Buscar
                </button>
            </p>
            @include('groups.modals.search')

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
                        <th></th>
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
                            <td class="text-center" style="white-space: nowrap;">
                                <a class="text-dark text-decoration-none"
                                    data-toggle="tooltip" data-placement="top"
                                    title="Editar"
                                    href="{{ route('groups.edit', $turma) }}"
                                >
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a class="text-dark text-decoration-none"
                                    data-toggle="modal"
                                    data-target="#removalModal"
                                    title="Remover"
                                    href="{{ route(
                                        'groups.destroy',
                                        $turma
                                    ) }}"
                                >
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </table>
                @include('groups.modals.removal')
            @else
                <p class="text-center">Não há turmas cadastradas</p>
            @endif
        </div>
    </div>
</div>
@endsection