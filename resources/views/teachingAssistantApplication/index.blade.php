@extends('parent')

@section('title', 'Solicitar Monitor')

@section('content')
@parent
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Turmas ministradas</h1>

            <p class="alert alert-info rounded-0 text-center">
                (Preencher um formulário para cada turma)
            </p>

            <p class="text-right">
                <a class="btn btn-primary"
                    data-toggle="modal"
                    data-target="#addGroupModal"
                    title="Cadastrar" 
                >
                    <i class="fas fa-plus-circle"></i>
                    Cadastrar Nova Turma
                </a>
            </p>
            @include('groups.modals.addGroup')

            @if (count($turmas) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th>Código da Turma</th>
                        <th>Código da Disciplina</th>
                        <th>Nome da Disciplina</th>
                        <th>Horários</th>
                        <th></th>
                    </tr>

                    @foreach($turmas as $turma)
                        <tr style="font-size:12px;">
                            <td>{{ $turma->codtur }}</td>
                            <td>{{ $turma->coddis }}</td>
                            <td>{{ $turma->nomdis }}</td>
                            <td style="white-space: nowrap;">
                                @foreach($turma->classschedules as $horario)
                                    {{ $horario->diasmnocp . ' ' . $horario->horent . ' ' . $horario->horsai }} <br/>
                                @endforeach
                            </td>
                            @if($turma->teachingAssistantApplication)
                                <td class="text-center" style="white-space: nowrap;">
                                    <a class="btn btn-outline-dark"
                                        data-toggle="tooltip" data-placement="top"
                                        title="Editar Solicitação"
                                        href="{{ route('requestAssistant.edit', $turma->teachingAssistantApplication) }}"
                                    >
                                        Editar Solicitação
                                    </a>
                                </td>
                            @else
                                <td class="text-center" style="white-space: nowrap;">
                                    <form method="GET" action="{{ route('requestAssistant.create') }}">
                                        <input type="hidden" name="group_id" value="{{ $turma->id }}">
                                        <button class="btn btn-outline-dark" type="submit">
                                            Solicitar Monitor
                                        </button>
                                    </form> 
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            @else
                <p class="text-center">Você não ministra turmas esse semestre</p>
            @endif
        </div>
    </div>
</div>
@endsection