@extends('parent')

@section('title', 'Solicitação de Monitores')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Solicitação de Monitores</h1>
            @include('requisitions.modals.addSchoolClass')

                @if (count($turmas) > 0)
                    <p class="alert alert-info rounded-0 text-center">
                        Preencher um formulário para cada turma
                    </p>
                @else
                    @if(App\Models\SchoolTerm::isRequisitionPeriod())
                        <p class="alert alert-warning rounded-0 text-center">
                            Você não ministra turmas esse semestre
                        </p>
                    @else
                        <p class="alert alert-warning rounded-0 text-center">
                            Período de solicitação de monitores encerrado
                        </p>
                    @endif
                @endif
                @if(App\Models\SchoolTerm::isRequisitionPeriod())
                    <p class="text-right">
                        <a class="btn btn-outline-primary"
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
                    <tr class="text-center">
                        <th>Sigla da Disciplina</th>
                        <th>Código da Turma</th>
                        <th>Nome da Disciplina</th>
                        <th>Horários</th>
                        <th>N.° da Solicitação</th>
                        <th>Data da Solicitação</th>
                        <th>N.° de Monitores</th>
                        <th>Alunos Indicados</th>
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
                                @if($turma->requisition)
                                    {{str_pad($turma->requisition->id,5,'0',STR_PAD_LEFT)}}
                                @endif
                            </td>
                            <td>
                                @if($turma->requisition)
                                    {{$turma->requisition->created_at}}
                                @endif
                            </td>
                            <td>
                                @if($turma->requisition)
                                    {{$turma->requisition->requested_number}}
                                @endif
                            </td>
                            <td class="text-left" style="white-space: nowrap;">
                                @if($turma->requisition)
                                    @if($turma->requisition->recommendations)
                                        @foreach($turma->requisition->recommendations as $indicacao)
                                            {{ $indicacao->student->nompes }} <br/>
                                        @endforeach
                                    @endif
                                @endif
                            </td>
                            @if($turma->requisition)
                                <td class="text-center" style="max-width:200px">
                                    @if($turma->requisition->instructor->codpes == Auth::user()->codpes)
                                        <a class="btn btn-outline-dark btn-sm"
                                            data-toggle="tooltip" data-placement="top"
                                            title="Editar Solicitação"
                                            href="{{ route('requisitions.edit', $turma->requisition) }}"
                                        >
                                            Editar
                                        </a>
                                    @else
                                        Já foi solicitado {{ $turma->requisition->instructor->getPronounTreatment() == 'Prof. Dr. ' ? 'pelo' : 'pela' }}
                                        {{ $turma->requisition->instructor->getPronounTreatment() }}
                                        {{ $turma->requisition->instructor->nompes }}
                                    @endif
                                </td>
                            @else
                                <td class="text-center" style="white-space: nowrap;">
                                    <form method="GET" action="{{ route('requisitions.create') }}">
                                        <input type="hidden" name="school_class_id" value="{{ $turma->id }}">
                                        <button class="btn btn-outline-dark btn-sm" type="submit" title="Solicitar Monitor">
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