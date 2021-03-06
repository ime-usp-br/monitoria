<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="year">Ano *</label>
    </div>
    <div class="col-12 col-md-2">
        <input class="custom-form-control" type="text" name="year" id="year"
            value='{{ $periodo->year ?? ""}}'
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="period">Período *</label>
    </div>
    <div class="col-12 col-md-2">
        <select class="custom-form-control" type="text" name="period"
            id="period"
        >
            <option value="" {{ ( $periodo->period) ? '' : 'selected'}}></option>

            @foreach ([
                        '1° Semestre',
                        '2° Semestre',
                     ] as $period)
                <option value="{{ $period }}" {{ ( $periodo->period === $period) ? 'selected' : ''}}>{{ $period }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="status">Estado *</label>
    </div>
    <div class="col-12 col-md-3">
        <select class="custom-form-control" type="text" name="status"
            id="status"
        >
            <option value="" {{ ( $periodo->status) ? '' : 'selected'}}></option>

            @foreach ([
                        'Aberto',
                        'Aberto para inscrições',
                        'Fechado'
                     ] as $status)
                <option value="{{ $status }}" {{ ( $periodo->status === $status) ? 'selected' : ''}}>{{ $status }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="evaluation_period">Período de avaliação *</label>
    </div>
    <div class="col-12 col-md-2">
        <select class="custom-form-control" type="text" name="evaluation_period"
            id="evaluation_period"
        >
            <option value="" {{ ( $periodo->evaluation_period) ? '' : 'selected'}}></option>

            @foreach ([
                        'Aberto',
                        'Fechado'
                     ] as $evaluation_period)
                <option value="{{ $evaluation_period }}" {{ ( $periodo->evaluation_period === $evaluation_period) ? 'selected' : ''}}>{{ $evaluation_period }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="max_enrollments">Maxímo de inscrições por aluno *</label>
    </div>
    <div class="col-12 col-md-2">
        <input class="custom-form-control" type="text" name="max_enrollments" id="max_enrollments"
            value='{{ $periodo->max_enrollments ?? ""}}'
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="started_at">Data inicial *</label>
    </div>

    <div class="col-12 col-md-2">
        <input class="custom-form-control custom-datepicker"
            type="text" name="started_at" id="started_at" autocomplete="off"
            value="{{ old('started_at') ?? $periodo->started_at ?? ''}}"
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="finished_at">Data final *</label>
    </div>

    <div class="col-12 col-md-2">
        <input class="custom-form-control custom-datepicker"
            type="text" name="finished_at" id="finished_at" autocomplete="off"
            value="{{  old('finished_at') ?? $periodo->finished_at ?? ''}}"
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="start_date_requisitions">Data inicial dos pedidos pelos docentes *</label>
    </div>

    <div class="col-12 col-md-2">
        <input class="custom-form-control custom-datepicker"
            type="text" name="start_date_requisitions" id="start_date_requisitions" autocomplete="off"
            value="{{ old('start_date_requisitions') ?? $periodo->start_date_requisitions ?? ''}}"
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="end_date_requisitions">Data final dos pedidos pelos docentes *</label>
    </div>

    <div class="col-12 col-md-2">
        <input class="custom-form-control custom-datepicker"
            type="text" name="end_date_requisitions" id="end_date_requisitions" autocomplete="off"
            value="{{  old('end_date_requisitions') ?? $periodo->end_date_requisitions ?? ''}}"
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="start_date_enrollments">Data inicial das inscrições pelos alunos *</label>
    </div>

    <div class="col-12 col-md-2">
        <input class="custom-form-control custom-datepicker"
            type="text" name="start_date_enrollments" id="start_date_enrollments" autocomplete="off"
            value="{{ old('start_date_enrollments') ?? $periodo->start_date_enrollments ?? ''}}"
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label for="end_date_student_registration">Data final das inscrições pelos alunos *</label>
    </div>

    <div class="col-12 col-md-2">
        <input class="custom-form-control custom-datepicker"
            type="text" name="end_date_enrollments" id="end_date_enrollments" autocomplete="off"
            value="{{  old('end_date_enrollments') ?? $periodo->end_date_enrollments ?? ''}}"
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-5 text-lg-right">
        <label>Edital em pdf *</label>
    </div>

    <div class="col-12 col-md-2">
        <input id="public_notice_file" class="custom-form-input2" type='file' name='public_notice' value="{{  old('public_notice_file') ?? $periodo->public_notice_file_path ?? ''}}">
    </div>
</div>


<div class="row">
    <div class="col-4 d-none d-lg-block"></div>
    <div class="col-md-12 col-lg-6">
        <button type="submit" class="btn btn-outline-dark">
            {{ $buttonText }}
        </button>
        <a class="btn btn-outline-dark"
            href="{{ route('schoolterms.index') }}"
        >
            Cancelar
        </a>
    </div>
</div>
