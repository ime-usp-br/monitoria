<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label for="codtur">Código da Turma {{ $buttonText === "Cadastrar" ? "*":"" }}</label>
    </div>
    <div class="col-12 col-md-5">
        <input class="custom-form-control" type="text" name="codtur" id="codtur"
            value="{{ old('codtur') ?? $turma->codtur ?? ''}}"" {{ $buttonText === "Editar" ? "disabled":"" }}
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label for="coddis">Código da Disciplina {{ $buttonText === "Cadastrar" ? "*":"" }}</label>
    </div>
    <div class="col-12 col-md-5">
        <input class="custom-form-control" type="text" name="coddis" id="coddis"
            value='{{ $turma->coddis ?? ""}}' {{ $buttonText === "Editar" ? "disabled":"" }}
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label for="nomdis">Nome da Disciplina {{ $buttonText === "Cadastrar" ? "*":"" }}</label>
    </div>
    <div class="col-12 col-md-5">
        <input class="custom-form-control" type="text" name="nomdis" id="nomdis"
            value='{{ $turma->nomdis ?? ""}}' {{ $buttonText === "Editar" ? "disabled":"" }}
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label for="tiptur">Tipo da Turma {{ $buttonText === "Cadastrar" ? "*":"" }}</label>
    </div>
    <div class="col-12 col-md-5">
        <input class="custom-form-control" type="text" name="tiptur" id="tiptur"
            value='{{ $turma->tiptur ?? ""}}' {{ $buttonText === "Editar" ? "disabled":"" }}
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label >Horários</label>
    </div>
    <div class="col-12 col-md-5">
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

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label >Prof(a)</label>
    </div>
    <div class="col-12 col-md-5">
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

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label for="dtainitur">Início *</label>
    </div>

    <div class="col-12 col-md-5">
        <input class="custom-form-control custom-datepicker"
            type="text" name="dtainitur" id="dtainitur" autocomplete="off"
            value="{{ old('dtainitur') ?? ($turma->dtainitur ? $turma->dtainitur->format('Y-m-d'): null) ?? ''}}"
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label for="dtafimtur">Fim *</label>
    </div>

    <div class="col-12 col-md-5">
        <input class="custom-form-control custom-datepicker"
            type="text" name="dtafimtur" id="dtafimtur" autocomplete="off"
            value="{{ old('dtafimtur') ?? ($turma->dtafimtur ? $turma->dtafimtur->format('Y-m-d'): null) ?? ''}}"
        />
    </div>
</div>


<div class="row">
    <div class="col-4 d-none d-lg-block"></div>
    <div class="col-md-12 col-lg-6">
        <button type="submit" class="btn btn-outline-dark">
            {{ $buttonText }}
        </button>
        <a class="btn btn-outline-dark"
            href="{{ route('groups.index') }}"
        >
            Cancelar
        </a>
    </div>
</div>
