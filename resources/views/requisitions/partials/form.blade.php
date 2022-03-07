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
