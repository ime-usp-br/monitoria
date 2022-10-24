@extends('parent')

@section('title', 'Auto Avaliação')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-12">
            <h1 class='text-center mb-5'>Auto Avaliação</h1>

            <h4 class='text-center mb-5'>{{ $se->schoolclass->schoolterm->period . ' de ' . $se->schoolclass->schoolterm->year }}</h4>

            <div class="card mb-3">
                <div class="card-body">

                    <div class="row custom-form-group">
                        <div class="row col-lg lg-pb-3">
                            <div class="col-lg-auto pr-0">
                                <label>Monitor:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $se->student->nompes }}
                            </div>
                        </div>
                        <div class="row col-lg lg-pb-3">
                            <div class="col-lg-auto pr-0">
                                <label>E-mail do Monitor:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $se->student->codema }}
                            </div>
                        </div>
                        <div class="row col-lg">
                            <div class="col-lg-auto pr-0">
                                <label>Docente Responsável:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $se->selection->requisition->instructor->nompes }}
                            </div>
                        </div>
                    </div>

                    <div class="row custom-form-group">
                        <div class="row col-lg lg-pb-3">
                            <div class="col-lg-auto pr-0">
                                <label>Disciplina:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $se->schoolclass->nomdis }}
                            </div>
                        </div>
                        <div class="row col-lg lg-pb-3">
                            <div class="col-lg-auto pr-0">
                                <label>Código da Disciplina:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $se->schoolclass->coddis }}
                            </div>
                        </div>
                        <div class="row col-lg">
                            <div class="col-lg-auto pr-0">
                                <label>Departamento:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $se->schoolclass->department->nomset }}
                            </div>
                        </div>
                    </div>

                    <hr/>

                    <div class="row custom-form-group">
                        <div class="row col-lg lg-pb-3">
                            <div class="col-lg-auto pr-0">
                                <label>Média de alunos atendidos por plantão:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $se->student_amount }}
                            </div>
                        </div>
                        <div class="row col-lg">
                            <div class="col-lg-auto pr-0">
                                <label>Média de listas de exercícios corrigidas por mês:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $se->homework_amount }}
                            </div>
                        </div>
                    </div>

                    <div class="row custom-form-group">
                        <div class="row col-lg">
                            <div class="col-lg-auto pr-0">
                                <label>Atividades além do atendimento aos alunos e correção de listas de exercícios:</label>
                            </div>
                            <div class="col-lg-auto">
                                {!! $se->secondary_activity !!}
                            </div>
                        </div>
                    </div>

                    <div class="row custom-form-group">
                        <div class="row col-lg">
                            <div class="col-lg-auto pr-0">
                                <label>A carga de trabalho da monitoria foi avaliada como "{{ $se->getWorkloadAsString() }}"{{ $se->workload_reason ? " porque:" : " e não foi apresentada justificativa." }}
                                </label>
                            </div>
                            @if($se->workload_reason)
                                <div class="col-lg-auto">
                                    {!! $se->workload_reason !!}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="row custom-form-group">
                        <div class="row col-lg">
                            <div class="col-lg-auto pr-0">
                                <label>
                                    {{ $se->comments ? "Foram feitas as seguintes observações/sugestões/reclamações:" : "Não foram feitas observações/sugestões/reclamações." }}
                                </label>
                            </div>
                            @if($se->comments)
                                <div class="col-lg-auto">
                                    {!! $se->comments !!}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection