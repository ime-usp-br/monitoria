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
        <label for="disponibilidade_diurno">Disponibilidade para trabalhar de dia:</label>
    </div>
    <div class="col-12 col-md-5">
        <div>
            <input class="checkbox" type="checkbox" name="disponibilidade_diurno"
            value="1"/>
        </div>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="disponibilidade_noturno">Disponibilidade para trabalhar de noite:</label>
    </div>
    <div class="col-12 col-md-5">
        <div>
            <input class="checkbox" type="checkbox" name="disponibilidade_noturno"
            value="1" />
        </div>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="preferencia_horario">Preferência de trabalhar no período*:</label>
    </div>
    <div class="col-12 col-md-2">
        <select id="preferencia_horario" name="preferencia_horario" class="custom-form-control">
            <option value=""></option>
            @foreach(['Diurno',
                      'Noturno',
                      'Indiferente'] as $preferencia)
                <option value="{{ $preferencia }}" >{{ $preferencia }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="voluntario">Aceita ser monitor voluntário (sem bolsa):</label>
    </div>
    <div class="col-12 col-md-5">
        <div>
            <input class="checkbox" type="checkbox" name="voluntario"
            value="1" />
        </div>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="observacoes">Observações:</label>
    </div>
    <div class="col-12 col-md-5">
        <div>
            <textarea class="custom-form-control" type="checkbox" name="observacoes"></textarea>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-4 d-none d-lg-block"></div>
    <div class="col-md-12 col-lg-6">
        <button type="submit" class="btn btn-outline-dark">
            {{ $buttonText }}
        </button>
        <a class="btn btn-outline-dark"
            href="{{ route('requestAssistant.index') }}"
        >
            Cancelar
        </a>
    </div>
</div>
