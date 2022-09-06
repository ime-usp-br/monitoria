<div class="row custom-form-group justify-content-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="year">Ano *</label>
    </div>
    <div class="col-12 col-md-6">
        <input class="custom-form-control" style="max-width:200px;" style="max-width:200px;" type="text" name="year" id="year"
            value='{{ $periodo->year ?? ""}}'
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="period">Período *</label>
    </div>
    <div class="col-12 col-md-6">
        <select class="custom-form-control" style="max-width:200px;" type="text" name="period"
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
    <div class="col-12 col-md-6 text-md-right">
        <label for="status">Estado *</label>
    </div>
    <div class="col-12 col-md-6">
        <select class="custom-form-control" style="max-width:250px;" type="text" name="status"
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
    <div class="col-12 col-md-6 text-md-right">
        <label for="evaluation_period">Período de avaliação *</label>
    </div>
    <div class="col-12 col-md-6">
        <select class="custom-form-control" style="max-width:200px;" type="text" name="evaluation_period"
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
    <div class="col-12 col-md-6 text-md-right">
        <label for="max_enrollments">Maxímo de inscrições por aluno *</label>
    </div>
    <div class="col-12 col-md-6">
        <input class="custom-form-control" style="max-width:200px;" type="text" name="max_enrollments" id="max_enrollments"
            value='{{ $periodo->max_enrollments ?? ""}}'
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="started_at">Data inicial *</label>
    </div>

    <div class="col-12 col-md-6" style="white-space: nowrap;">
        <input class="custom-form-control custom-datepicker" style="max-width:200px;"
            type="text" name="started_at" id="started_at" autocomplete="off"
            value="{{ old('started_at') ?? $periodo->started_at ?? ''}}"
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="finished_at">Data final *</label>
    </div>

    <div class="col-12 col-md-6" style="white-space: nowrap;">
        <input class="custom-form-control custom-datepicker" style="max-width:200px;"
            type="text" name="finished_at" id="finished_at" autocomplete="off"
            value="{{  old('finished_at') ?? $periodo->finished_at ?? ''}}"
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="start_date_requisitions">Data inicial dos pedidos pelos docentes *</label>
    </div>

    <div class="col-12 col-md-6" style="white-space: nowrap;">
        <input class="custom-form-control custom-datepicker" style="max-width:200px;"
            type="text" name="start_date_requisitions" id="start_date_requisitions" autocomplete="off"
            value="{{ old('start_date_requisitions') ?? $periodo->start_date_requisitions ?? ''}}"
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="end_date_requisitions">Data final dos pedidos pelos docentes *</label>
    </div>

    <div class="col-12 col-md-6" style="white-space: nowrap;">
        <input class="custom-form-control custom-datepicker" style="max-width:200px;"
            type="text" name="end_date_requisitions" id="end_date_requisitions" autocomplete="off"
            value="{{  old('end_date_requisitions') ?? $periodo->end_date_requisitions ?? ''}}"
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="start_date_enrollments">Data inicial das inscrições pelos alunos *</label>
    </div>

    <div class="col-12 col-md-6" style="white-space: nowrap;">
        <input class="custom-form-control custom-datepicker" style="max-width:200px;"
            type="text" name="start_date_enrollments" id="start_date_enrollments" autocomplete="off"
            value="{{ old('start_date_enrollments') ?? $periodo->start_date_enrollments ?? ''}}"
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="end_date_student_registration">Data final das inscrições pelos alunos *</label>
    </div>

    <div class="col-12 col-md-6" style="white-space: nowrap;">
        <input class="custom-form-control custom-datepicker" style="max-width:200px;"
            type="text" name="end_date_enrollments" id="end_date_enrollments" autocomplete="off"
            value="{{  old('end_date_enrollments') ?? $periodo->end_date_enrollments ?? ''}}"
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-md-6 text-md-right">
        <label>Edital em pdf *</label>
    </div>

    <div class="col-12 col-md-6">
        <input id="public_notice_file" class="custom-form-input2" type='file' name='public_notice' value="{{  old('public_notice_file') ?? $periodo->public_notice_file_path ?? ''}}">
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
            href="{{ route('schoolterms.index') }}"
        >
            Cancelar
        </a>
    </div>
</div>
