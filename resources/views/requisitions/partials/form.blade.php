<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label >Unidade: </label>
    </div>
    <div class="col-12 col-md-5">
        <a >{{ $turma->department->nomund }} </a>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label >Departamento: </label>
    </div>
    <div class="col-12 col-md-5">
        <a >{{ $turma->department->nomset }} </a>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label >Nome do Professor: </label>
    </div>
    <div class="col-12 col-md-5">
        <a >{{ Auth::user()->name }} </a>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label >Disciplina: </label>
    </div>
    <div class="col-12 col-md-5">
        <a >{{ $turma->nomdis }} </a>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label >Sigla: </label>
    </div>
    <div class="col-12 col-md-5">
        <a >{{ $turma->coddis }} </a>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label >Turma: </label>
    </div>
    <div class="col-12 col-md-5">
        <a >{{ $turma->codtur }} </a>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label >Horários:</label>
    </div>
    <div class="col-12 col-md-5">
        @foreach($turma->classschedules as $horario)
            <label id="label-horario-{{$horario->id}}" class="font-weight-normal">{{ $horario->diasmnocp . ' ' . $horario->horent . ' ' . $horario->horsai }}</label> 
            <br/>
        @endforeach
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="requested_number">Número de monitores solicitados: </label>
    </div>
    <div class="col-3 col-md-1">
        <input class="custom-form-control" type="number" name="requested_number" id="requested_number"
            value="{{ $turma->requisition->requested_number ?? '1' }}"
        />
    </div>
</div>

@include('requisitions.modals.addRecommendation')

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label >Indicar aluno(s):</label>
    </div>
    <div class="col-12 col-md-5">
        @if($turma->requisition)
            @foreach($turma->requisition->recommendations as $indicacao)
                <div id="indicacao-{{ $indicacao->id }}">
                    <input id="recommendations[{{$indicacao->id}}][codpes]" name="recommendations[{{$indicacao->id}}][codpes]" type="hidden" value="{{ $indicacao->student->codpes }}">
                    <label id="label-indicacao-{{ $indicacao->id }}" class="font-weight-normal">{{ $indicacao->student->nompes }}</label> 
                    
                    <a class="btn btn-link btn-sm text-dark text-decoration-none"
                        style="padding-left:0px"
                        id="btn-remove-indicacao-{{ $indicacao->id }}"
                        onclick="removeRecommendation({{ $indicacao->id }})"
                        title="Remover"
                    >
                        <i class="fas fa-trash-alt"></i>
                    </a>
                    <br/>
                </div>
            @endforeach
        @endif

        <div id="novas-indicacoes"></div>
        <label class="font-weight-normal">Adicionar aluno</label> 
        <input id="count-new-recommendation" value=0 type="hidden" disabled>
        <a class="btn btn-link btn-sm text-dark text-decoration-none" id="btn-addRecommendation" 
            data-toggle="modal" data-target="#addRecommendationModal"
            title="Adicionar aluno">
            <i class="fas fa-plus-circle"></i>
        </a>

        <script>
            function removeRecommendation(id){
                document.getElementById("indicacao-"+id).remove();
            }
        </script>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="nomdis">Atividade(s) que pretende atribuir ao(s) monitor(es):</label>
    </div>
    <div class="col-12 col-md-5">
        @foreach(['Atendimento a alunos',
                  'Correção de listas de exercícios',
                  'Fiscalização de provas'] as $activity)
            <div>
                <input class="checkbox" type="checkbox" name="activities[]"
                value="{{$activity}}" {{ $turma->requisition ? ($turma->requisition->hasActivity($activity) ? 'checked':'') : ''}}/>
                <a>{{$activity}}</a>
            </div>
        @endforeach
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="nomdis">De acordo com a sua avaliação, o trabalho do monitor nesta disciplina é:</label>
    </div>
    <div class="col-12 col-md-5">
        <select id="priority" name="priority" class="custom-form-control">
            <option value=""></option>
            @foreach([3=>'Imprescindivel',
                      2=>'Extremamente necessário, mas não imprescindivel',
                      1=>'Importante, porém posso abrir mão do auxilio de um monitor'] as $n=>$description)
                <option value="{{ $n }}" {{ $turma->requisition ? ($turma->requisition->priority == $n ? "selected":'') :'' }}>{{ $description }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row">
    <div class="col-4 d-none d-lg-block"></div>
    <div class="col-md-12 col-lg-6">
        <button type="submit" class="btn btn-outline-dark">
            {{ $buttonText }}
        </button>
        <a class="btn btn-outline-dark"
            href="{{ route('requisitions.index') }}"
        >
            Cancelar
        </a>
    </div>
</div>
