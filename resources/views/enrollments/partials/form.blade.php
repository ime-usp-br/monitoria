
<div class="d-flex justify-content-center">
<div class="col-12 col-lg-8 my-3">
<table class="table table-bordered table-striped table-hover" style="font-size:15px;">
    <tr>
        <th>Sigla da Disciplina</th>
        <th>Nome da Disciplina</th>
    </tr>

    <tr>
        <td style="text-align: center">{{ $turma->coddis ?? $inscricao->schoolclass->coddis }}</td>
        <td style="text-align: center">{{ $turma->nomdis ?? $inscricao->schoolclass->nomdis }}</td>
    </tr>
</table>
</div>
</div>


<div class="row custom-form-group justify-content-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="disponibilidade_diurno">Disponibilidade para trabalhar de dia:</label>
    </div>
    <div class="col-12 col-md-6">
        <div>
            <input class="checkbox" type="checkbox" name="disponibilidade_diurno"
            value="1" {{ isset($inscricao) ? ($inscricao->disponibilidade_diurno ? 'checked' : '') : 
                ($estudante->hasEnrollmentinEnrollmentPeriod() ? ($estudante->getEnrollmentsInEnrollmentPeriod()[0]->disponibilidade_diurno ? 'checked' : '') : '')  }}/>
        </div>
    </div>
</div>

<div class="row custom-form-group justify-content-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="disponibilidade_noturno">Disponibilidade para trabalhar de noite:</label>
    </div>
    <div class="col-12 col-md-6">
        <div>
            <input class="checkbox" type="checkbox" name="disponibilidade_noturno"
            value="1" {{ isset($inscricao) ? ($inscricao->disponibilidade_noturno ? 'checked' : '') : 
                ($estudante->hasEnrollmentinEnrollmentPeriod() ? ($estudante->getEnrollmentsInEnrollmentPeriod()[0]->disponibilidade_noturno ? 'checked' : '') : '') }}/>
        </div>
    </div>
</div>

<div class="row custom-form-group justify-content-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="preferencia_horario">Preferência de trabalhar no período*:</label>
    </div>
    <div class="col-12 col-md-6">
        <select id="preferencia_horario" name="preferencia_horario" class="custom-form-control" style="max-width:200px;">
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

<div class="row custom-form-group justify-content-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="voluntario">Aceita ser monitor voluntário (sem bolsa):</label>
    </div>
    <div class="col-12 col-md-6">
        <div>
            <input class="checkbox" type="checkbox" name="voluntario"
            value="1" {{ isset($inscricao) ? ($inscricao->voluntario ? 'checked' : '') : ($estudante->hasEnrollmentinEnrollmentPeriod() ? ($estudante->getEnrollmentsInEnrollmentPeriod()[0]->voluntario ? 'checked' : '') : '') }}/>
        </div>
    </div>
</div>


<div class="row custom-form-group justify-content-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="voluntario">Se inscreveu em outros programas de monitoria?<br> Em caso afirmativo, informe quais.</label>
    </div>
    <div class="col-12 col-md-6">
        <div>
            @foreach(App\Models\Scholarship::all() as $scholarship)
                <div>
                    <input class="checkbox" type="checkbox" name="scholarships[]"
                    value={{ $scholarship->id }} {{ isset($inscricao) ? ($inscricao->others_scholarships()->where("id",$scholarship->id)->exists() ? 'checked' : '') : ($estudante->hasEnrollmentinEnrollmentPeriod() ? ($estudante->getEnrollmentsInEnrollmentPeriod()[0]->others_scholarships()->where("id",$scholarship->id)->exists() ? 'checked' : '') : '') }}/>
                    <a>{{ $scholarship->name }}</a>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row custom-form-group justify-content-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="observacoes">Observações:</label>
    </div>
    <div class="col-12 col-md-6">
        <div>
            <textarea class="custom-form-control" type="checkbox" name="observacoes" style="max-width:400px;height:200px">{{ isset($inscricao) ? $inscricao->observacoes : '' }}</textarea>
        </div>
    </div>
</div>

<div class="row custom-form-group justify-content-center">
    <div class="col-sm-6 text-center text-sm-right my-1">
        <button type="submit" class="btn btn-outline-dark">
            {{ $buttonText }}
        </button>
    </div>
    <div class="col-sm-6 text-center text-sm-left my-1">
        <a class="btn btn-outline-dark"
            href="{{ route('enrollments.index') }}"
        >
            Cancelar
        </a>
    </div>
</div>
