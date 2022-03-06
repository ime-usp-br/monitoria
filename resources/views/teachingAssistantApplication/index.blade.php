@extends('parent')

@section('title', 'Solicitação de Monitores')

@section('content')
@parent
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Solicitação de Monitores</h1>
            @include('schoolclasses.modals.addSchoolClass')

                @if (count($turmas) > 0)
                    <p class="alert alert-info rounded-0 text-center">
                        Preencher um formulário para cada turma
                    </p>
                @else
                    @if(App\Models\SchoolTerm::isApplicationPeriod())
                        <p class="alert alert-warning rounded-0 text-center">
                            Você não ministra turmas esse semestre
                        </p>
                    @else
                        <p class="alert alert-warning rounded-0 text-center">
                            Período de solicitação de monitores encerrado
                        </p>
                    @endif
                @endif
                @if(App\Models\SchoolTerm::isApplicationPeriod())
                    <p class="text-right">
                        <a class="btn btn-primary"
                            data-toggle="modal"
                            data-target="#addSchoolClassModal"
                            title="Cadastrar" 
                        >
                            <i class="fas fa-plus-circle"></i>
                            Cadastrar Nova Turma
                        </a>
                    </p>
                @endif

            @if (count($turmas) > 0)

                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th>Sigla da Disciplina</th>
                        <th>Código da Turma</th>
                        <th>Nome da Disciplina</th>
                        <th>Horários</th>
                        <th>N.° da Solicitação</th>
                        <th>Data da Solicitação</th>
                        <th>N.° de Monitores</th>
                        <th></th>
                    </tr>

                    @foreach($turmas as $turma)
                        <tr style="font-size:12px;" class="text-center">
                            <td>{{ $turma->coddis }}</td>
                            <td>{{ $turma->codtur }}</td>
                            <td>{{ $turma->nomdis }}</td>
                            <td style="white-space: nowrap;">
                                @foreach($turma->classschedules as $horario)
                                    {{ $horario->diasmnocp . ' ' . $horario->horent . ' ' . $horario->horsai }} <br/>
                                @endforeach
                            </td>
                            <td>
                                @if($turma->teachingAssistantApplication)
                                    {{str_pad($turma->teachingAssistantApplication->id,5,'0',STR_PAD_LEFT)}}
                                @endif
                            </td>
                            <td>
                                @if($turma->teachingAssistantApplication)
                                    {{$turma->teachingAssistantApplication->created_at}}
                                @endif
                            </td>
                            <td>
                                @if($turma->teachingAssistantApplication)
                                    {{$turma->teachingAssistantApplication->requested_number}}
                                @endif
                            </td>
                            @if($turma->teachingAssistantApplication)
                                <td class="text-center" style="max-width:200px">
                                    @if($turma->teachingAssistantApplication->instructor->codpes == Auth::user()->codpes)
                                        <a class="btn btn-outline-dark"
                                            data-toggle="tooltip" data-placement="top"
                                            title="Editar Solicitação"
                                            href="{{ route('requestAssistant.edit', $turma->teachingAssistantApplication) }}"
                                        >
                                            Editar
                                        </a>
                                    @else
                                        Já foi solicitado {{ $turma->teachingAssistantApplication->instructor->getPronounTreatment() == 'Prof. Dr. ' ? 'pelo' : 'pela' }}
                                        {{ $turma->teachingAssistantApplication->instructor->getPronounTreatment() }}
                                        {{ $turma->teachingAssistantApplication->instructor->nompes }}
                                    @endif
                                </td>
                            @else
                                <td class="text-center" style="white-space: nowrap;">
                                    <form method="GET" action="{{ route('requestAssistant.create') }}">
                                        <input type="hidden" name="school_class_id" value="{{ $turma->id }}">
                                        <button class="btn btn-outline-dark" type="submit">
                                            Solicitar
                                        </button>
                                    </form> 
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>
    </div>
</div>
@endsection