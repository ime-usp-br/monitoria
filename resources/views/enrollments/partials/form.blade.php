<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label >Unidade: </label>
    </div>
    <div class="col-12 col-md-5">
        <a >{{ $turma->department->nomund ?? $inscricao->schoolclass->department->nomund }} </a>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label >Departamento: </label>
    </div>
    <div class="col-12 col-md-5">
        <a >{{ $turma->department->nomset ?? $inscricao->schoolclass->department->nomset }} </a>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label >Disciplina: </label>
    </div>
    <div class="col-12 col-md-5">
        <a >{{ $turma->nomdis ?? $inscricao->schoolclass->nomdis }} </a>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label >Sigla: </label>
    </div>
    <div class="col-12 col-md-5">
        <a >{{ $turma->coddis ?? $inscricao->schoolclass->coddis }} </a>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label >Turma: </label>
    </div>
    <div class="col-12 col-md-5">
        <a >{{ $turma->codtur ?? $inscricao->schoolclass->codtur }} </a>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label >Horários:</label>
    </div>
    <div class="col-12 col-md-5">
        @php $horarios = $turma->classschedules ?? $inscricao->schoolclass->classschedules @endphp
        @foreach($horarios as $horario)
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
            value="1" {{ isset($inscricao) ? ($inscricao->disponibilidade_diurno ? 'checked' : '') : 
                ($estudante->hasEnrollmentinEnrollmentPeriod() ? ($estudante->getEnrollmentsInEnrollmentPeriod()[0]->disponibilidade_diurno ? 'checked' : '') : '')  }}/>
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
            value="1" {{ isset($inscricao) ? ($inscricao->disponibilidade_noturno ? 'checked' : '') : 
                ($estudante->hasEnrollmentinEnrollmentPeriod() ? ($estudante->getEnrollmentsInEnrollmentPeriod()[0]->disponibilidade_noturno ? 'checked' : '') : '') }}/>
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
                <option value="{{ $preferencia }}" {{ isset($inscricao) ? ($inscricao->preferencia_horario==$preferencia ? 'selected' : '') : 
                    ($estudante->hasEnrollmentinEnrollmentPeriod() ? ($estudante->getEnrollmentsInEnrollmentPeriod()[0]->preferencia_horario == $preferencia ? 'selected' : '') : '') }}>{{ $preferencia }}</option>
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
            value="1" {{ isset($inscricao) ? ($inscricao->voluntario ? 'checked' : '') : ($estudante->hasEnrollmentinEnrollmentPeriod() ? ($estudante->getEnrollmentsInEnrollmentPeriod()[0]->voluntario ? 'checked' : '') : '') }}/>
        </div>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="observacoes">Observações:</label>
    </div>
    <div class="col-12 col-md-5">
        <div>
            <textarea class="custom-form-control" type="checkbox" name="observacoes">{{ isset($inscricao) ? $inscricao->observacoes : '' }}</textarea>
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
            href="{{ route('enrollments.index') }}"
        >
            Cancelar
        </a>
    </div>
</div>
