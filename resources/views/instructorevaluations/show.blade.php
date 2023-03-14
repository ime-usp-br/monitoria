@extends('parent')

@section('title', 'Avaliação do Docente')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-12">
            <h1 class='text-center mb-5'>Avaliação do Docente</h1>

            <h4 class='text-center mb-5'>{{ $ie->schoolclass->schoolterm->period . ' de ' . $ie->schoolclass->schoolterm->year }}</h4>

            <div class="card mb-3">
                <div class="card-body">

                    <div class="row custom-form-group">
                        <div class="row col-lg lg-pb-3">
                            <div class="col-lg-auto pr-0">
                                <label>Monitor:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $ie->student->nompes }}
                            </div>
                        </div>
                        <div class="row col-lg lg-pb-3">
                            <div class="col-lg-auto pr-0">
                                <label>E-mail do Monitor:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $ie->student->codema }}
                            </div>
                        </div>
                        <div class="row col-lg">
                            <div class="col-lg-auto pr-0">
                                <label>Docente Responsável:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $ie->instructor->nompes }}
                            </div>
                        </div>
                    </div>

                    <div class="row custom-form-group">
                        <div class="row col-lg lg-pb-3">
                            <div class="col-lg-auto pr-0">
                                <label>Disciplina:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $ie->schoolclass->nomdis }}
                            </div>
                        </div>
                        <div class="row col-lg lg-pb-3">
                            <div class="col-lg-auto pr-0">
                                <label>Código da Disciplina:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $ie->schoolclass->coddis }}
                            </div>
                        </div>
                        <div class="row col-lg">
                            <div class="col-lg-auto pr-0">
                                <label>Departamento:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $ie->schoolclass->department->nomset }}
                            </div>
                        </div>
                    </div>

                    <hr/>

                    <div class="row custom-form-group">
                        <div class="row col-lg lg-pb-3">
                            <div class="col-lg-auto pr-0">
                                <label>Facilidade de Contato:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $ie->getEaseOfContactAsString() }}
                            </div>
                        </div>
                        <div class="row col-lg lg-pb-3">
                            <div class="col-lg-auto pr-0">
                                <label>Eficiência:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $ie->getEfficiencyAsString() }}
                            </div>
                        </div>
                        <div class="row col-lg lg-pb-3">
                            <div class="col-lg-auto pr-0">
                                <label>Confiabilidade:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $ie->getReliabilityAsString() }}
                            </div>
                        </div>
                        <div class="row col-lg">
                            <div class="col-lg-auto pr-0">
                                <label>Geral:</label>
                            </div>
                            <div class="col-lg-auto">
                                {{ $ie->getOverallAsString() }}
                            </div>
                        </div>
                    </div>

                    <div class="row custom-form-group">
                        <div class="row col-lg">
                            <div class="col-lg-auto pr-0">
                                <label>{{ $ie->comments ? "Comentários" : "Não foram feitos comentários" }}:</label>
                            </div>
                            <div class="col-lg-auto">
                                {!! $ie->comments !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection