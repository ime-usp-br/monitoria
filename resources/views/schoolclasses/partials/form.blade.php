<div class="row custom-form-group justify-content-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="codtur">Código da Turma {{ $buttonText === "Cadastrar" ? "*":"" }}</label>
    </div>
    <div class="col-12 col-md-6">
        <input class="custom-form-control" style="max-width:200px;" type="text" name="codtur" id="codtur"
            value="{{ old('codtur') ?? $turma->codtur ?? ''}}"" {{ $buttonText === "Editar" ? "disabled":"" }}
        />
    </div>
</div>

<div class="row custom-form-group justify-content-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="coddis">Código da Disciplina {{ $buttonText === "Cadastrar" ? "*":"" }}</label>
    </div>
    <div class="col-12 col-md-6">
        <input class="custom-form-control" style="max-width:200px;" type="text" name="coddis" id="coddis"
            value="{{ old('coddis') ?? $turma->coddis ?? ''}}" {{ $buttonText === "Editar" ? "disabled":"" }}
        />
    </div>
</div>

<div class="row custom-form-group justify-content-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="nomdis">Nome da Disciplina {{ $buttonText === "Cadastrar" ? "*":"" }}</label>
    </div>
    <div class="col-12 col-md-6">
        <input class="custom-form-control" style="max-width:520px;" type="text" name="nomdis" id="nomdis"
            value="{{ old('nomdis') ?? $turma->nomdis ?? ''}}" {{ $buttonText === "Editar" ? "disabled":"" }}
        />
    </div>
</div>

<div class="row custom-form-group justify-content-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="tiptur">Tipo da Turma {{ $buttonText === "Cadastrar" ? "*":"" }}</label>
    </div>
    <div class="col-12 col-md-6">
        <input class="custom-form-control" style="max-width:200px;" type="text" name="tiptur" id="tiptur"
            value="{{ old('tiptur') ?? $turma->tiptur ?? ''}}" {{ $buttonText === "Editar" ? "disabled":"" }}
        />
    </div>
</div>
@if ($buttonText === "Cadastrar")
<div class="row custom-form-group justify-content-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="department_id" >Departamento *</label>
    </div>
    <div class="col-12 col-md-6">        
        <select id="department_id" name="department_id" class="custom-form-control" style="max-width:520px;">
                <option value="" {{ old('department_id') ? '' : 'selected'}}></option>
            @foreach(App\Models\Department::where('sglund', 'IME')->get() as $department)
                <option value={{ $department->id }} {{ old('department_id') == $department->id ? 'selected' : ''}}>Departamento de {{ $department->nomset }}</option>
            @endforeach
        </select>
    </div>
</div>
@endif
<div class="row custom-form-group justify-content-center">
    <div class="col-12 col-md-6 text-md-right">
        <label >Horários</label>
    </div>
    <div class="col-12 col-md-6">
        <span id="count-classSchedule" value=0>
        @foreach($turma->classschedules as $horario)
            <div id="horario-{{ $horario->id }}">
                <input id="horarios[{{$horario->id}}][diasmnocp]" name="horarios[{{$horario->id}}][diasmnocp]" type="hidden" value={{ $horario->diasmnocp }}>
                <input id="horarios[{{$horario->id}}][horent]" name="horarios[{{$horario->id}}][horent]" type="hidden" value={{ $horario->horent }}>
                <input id="horarios[{{$horario->id}}][horsai]" name="horarios[{{$horario->id}}][horsai]" type="hidden" value={{ $horario->horsai }}>
                <label id="label-horario-{{$horario->id}}" class="font-weight-normal">{{ $horario->diasmnocp . ' ' . $horario->horent . ' ' . $horario->horsai }}</label> 
                
                <a class="btn btn-link btn-sm text-dark text-decoration-none"
                    style="padding-left:0px"
                    id="btn-remove-horario-{{ $horario->id }}"
                    onclick="removeHorario({{ $horario->id }})"
                    title="Remover"
                >
                    <i class="fas fa-trash-alt"></i>
                </a>
                <br/>
            </div>
        @endforeach

        <div id="novos-horarios"></div>
        <label class="font-weight-normal">Adicionar horário</label> 
        <input id="count-new-classSchedule" value=0 type="hidden" disabled>
        <a class="btn btn-link btn-sm text-dark text-decoration-none" id="btn-addCassSchedule" 
            data-toggle="modal" data-target="#addClassScheduleModal"
            title="Adicionar">
            <i class="fas fa-plus-circle"></i>
        </a>

        <script>
            function removeHorario(id){
                document.getElementById("horario-"+id).remove();
            }
        </script>
    </div>
</div>

<div class="row custom-form-group justify-content-center">
    <div class="col-12 col-md-6 text-md-right">
        <label >Prof(a)</label>
    </div>
    <div class="col-12 col-md-6">
        @foreach($turma->instructors as $instrutor)
            <div id="instrutor-{{ $instrutor->id }}">
                <input id="instrutores[{{$instrutor->id}}][codpes]" name="instrutores[{{$instrutor->id}}][codpes]" type="hidden" value="{{ $instrutor->codpes }}">
                <label id="label-instrutor-{{ $instrutor->id }}" class="font-weight-normal">{{ $instrutor->nompes }}</label> 
                
                <a class="btn btn-link btn-sm text-dark text-decoration-none"
                    style="padding-left:0px"
                    id="btn-remove-instrutor-{{ $instrutor->id }}"
                    onclick="removeInstrutor({{ $instrutor->id }})"
                    title="Remover"
                >
                    <i class="fas fa-trash-alt"></i>
                </a>
                <br/>
            </div>
        @endforeach

        <div id="novos-instrutores"></div>
        <label class="font-weight-normal">Adicionar professor(a)</label> 
        <input id="count-new-instructor" value=0 type="hidden" disabled>
        <a class="btn btn-link btn-sm text-dark text-decoration-none" id="btn-addInstructor" 
            data-toggle="modal" data-target="#addInstructorModal"
            title="Adicionar">
            <i class="fas fa-plus-circle"></i>
        </a>

        <script>
            function removeInstrutor(id){
                document.getElementById("instrutor-"+id).remove();
            }
        </script>
    </div>
</div>

<div class="row custom-form-group justify-content-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="dtainitur">Início *</label>
    </div>

    <div class="col-12 col-md-6" style="white-space: nowrap;">
        <input class="custom-form-control custom-datepicker" style="max-width:200px;"
            type="text" name="dtainitur" id="dtainitur" autocomplete="off"
            value="{{ old('dtainitur') ?? $turma->dtainitur ?? ''}}"
        />
    </div>
</div>

<div class="row custom-form-group justify-content-center">
    <div class="col-12 col-md-6 text-md-right">
        <label for="dtafimtur">Fim *</label>
    </div>

    <div class="col-12 col-md-6" style="white-space: nowrap;">
        <input class="custom-form-control custom-datepicker" style="max-width:200px;"
            type="text" name="dtafimtur" id="dtafimtur" autocomplete="off"
            value="{{ old('dtafimtur') ?? $turma->dtafimtur ?? ''}}"
        />
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
            href="{{ route('schoolclasses.index') }}"
        >
            Cancelar
        </a>
    </div>
</div>
