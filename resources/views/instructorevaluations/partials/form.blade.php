
<div class="card mb-3">
    <div class="card-body">

        <div class="row custom-form-group">
            <div class="row col-lg lg-pb-3">
                <div class="col-lg-auto pr-0">
                    <label>Monitor:</label>
                </div>
                <div class="col-lg-auto">
                    {{ $selection->student->nompes }}
                </div>
            </div>
            <div class="row col-lg lg-pb-3">
                <div class="col-lg-auto pr-0">
                    <label>E-mail do Monitor:</label>
                </div>
                <div class="col-lg-auto">
                    {{ $selection->student->codema }}
                </div>
            </div>
            <div class="row col-lg">
                <div class="col-lg-auto pr-0">
                    <label>Docente Responsável:</label>
                </div>
                <div class="col-lg-auto">
                    {{ $selection->requisition->instructor->nompes }}
                </div>
            </div>
        </div>

        <div class="row custom-form-group">
            <div class="row col-lg lg-pb-3">
                <div class="col-lg-auto pr-0">
                    <label>Disciplina:</label>
                </div>
                <div class="col-lg-auto">
                    {{ $selection->schoolclass->nomdis }}
                </div>
            </div>
            <div class="row col-lg lg-pb-3">
                <div class="col-lg-auto pr-0">
                    <label>Código da Disciplina:</label>
                </div>
                <div class="col-lg-auto">
                    {{ $selection->schoolclass->coddis }}
                </div>
            </div>
            <div class="row col-lg">
                <div class="col-lg-auto pr-0">
                    <label>Departamento:</label>
                </div>
                <div class="col-lg-auto">
                    {{ $selection->schoolclass->department->nomset }}
                </div>
            </div>
        </div>

        <hr/>


        <p class="alert alert-info rounded-0">
                <b>Atenção:</b>
                Os campos assinalados com * são de preenchimento obrigatório.
        </p>


        <div class="row custom-form-group">
            <div class="row col-lg">
                <div class="col-lg-auto lg-pb-3">
                    <label style="margin-top:5px;">Facilidade de contato:*</label>
                </div>
                <div class="col-lg-auto lg-pb-3">
                    <select class="custom-form-control" style="width:150px" type="text" name="ease_of_contact" id="ease_of_contact"
                    >
                            <option value="" {{ old('ease_of_contact') ? '' : ($selection->instructorevaluation ? '' : 'selected') }}></option>
                            @foreach(App\Models\InstructorEvaluation::$eval_as_string as $key=>$val)
                                <option value="{{ $key }}" {{ old('ease_of_contact') ? old('ease_of_contact') == $key ? 'selected' : '' : (($selection->instructorevaluation) ? $selection->instructorevaluation->ease_of_contact == $key ? 'selected' : '' : '') }}>{{ $val }}</option>
                            @endforeach
                    </select>
                </div>
            </div>
            <div class="row col-lg">
                <div class="col-lg-auto lg-pb-3">
                    <label style="margin-top:5px;">Eficiência:*</label>
                </div>
                <div class="col-lg-auto lg-pb-3">
                    <select class="custom-form-control" style="width:150px" type="text" name="efficiency" id="efficiency"
                    >
                            <option value="" {{ old('efficiency') ? '' : ($selection->instructorevaluation ? '' : 'selected') }}></option>
                            @foreach(App\Models\InstructorEvaluation::$eval_as_string as $key=>$val)
                                <option value="{{ $key }}" {{ old('efficiency') ? old('efficiency') == $key ? 'selected' : '' : (($selection->instructorevaluation) ? $selection->instructorevaluation->efficiency == $key ? 'selected' : '' : '') }}>{{ $val }}</option>
                            @endforeach
                    </select>
                </div>
            </div>
            <div class="row col-lg">
                <div class="col-lg-auto lg-pb-3">
                    <label style="margin-top:5px;">Confiabilidade:*</label>
                </div>
                <div class="col-lg-auto lg-pb-3">
                    <select class="custom-form-control" style="width:150px" type="text" name="reliability" id="reliability"
                    >
                            <option value="" {{ old('reliability') ? '' : ($selection->instructorevaluation ? '' : 'selected') }}></option>
                            @foreach(App\Models\InstructorEvaluation::$eval_as_string as $key=>$val)
                                <option value="{{ $key }}" {{ old('reliability') ? old('reliability') == $key ? 'selected' : '' : (($selection->instructorevaluation) ? $selection->instructorevaluation->reliability == $key ? 'selected' : '' : '') }}>{{ $val }}</option>
                            @endforeach
                    </select>
                </div>
            </div>
            <div class="row col-lg">
                <div class="col-lg-auto lg-pb-3">
                    <label style="margin-top:5px;">Geral:*</label>
                </div>
                <div class="col-lg-auto lg-pb-3">
                    <select class="custom-form-control" style="width:150px" type="text" name="overall" id="overall"
                    >
                            <option value="" {{ old('overall') ? '' : ($selection->instructorevaluation ? '' : 'selected') }}></option>
                            @foreach(App\Models\InstructorEvaluation::$eval_as_string as $key=>$val)
                                <option value="{{ $key }}" {{ old('overall') ? old('overall') == $key ? 'selected' : '' : (($selection->instructorevaluation) ? $selection->instructorevaluation->overall == $key ? 'selected' : '' : '') }}>{{ $val }}</option>
                            @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="row custom-form-group">
            <div class="col-lg">
                    <label class="pb-1" >Comentários:</label>
                    <input class="custom-form-control" type="text" name="comments" id="tinymcetextarea"
                        value="{{ old('comments') ?? $selection->instructorevaluation->comments ?? ''}}"
                    />
            </div>
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
            href="{{ route('selfevaluations.studentIndex') }}"
        >
            Cancelar
        </a>
    </div>
</div>
