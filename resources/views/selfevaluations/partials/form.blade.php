
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
            <div class="row col-lg lg-pb-3">
                <div class="col-lg-auto pr-0">
                    <label style="margin-top:5px;">Quantos alunos, em média, você atendeu por plantão?*</label>
                </div>
                <div class="col-lg-auto">
                    <input class="custom-form-control" style="max-width:100px;" type="text" name="student_amount" id="student_amount"
                        value="{{ old('student_amount') ?? $selection->selfevaluation->student_amount ?? ''}}"
                    />
                </div>
            </div>
            <div class="row col-lg">
                <div class="col-lg-auto pr-0">
                    <label style="margin-top:5px;">Quantas listas de exercício, em média, você corrigiu por mês?*</label>
                </div>
                <div class="col-lg-auto">
                    <input class="custom-form-control" style="max-width:100px;" type="text" name="homework_amount" id="homework_amount"
                        value="{{ old('homework_amount') ?? $selection->selfevaluation->homework_amount ?? ''}}"
                    />
                </div>
            </div>
        </div>

        <div class="row custom-form-group">
            <div class="col-lg">
                    <label class="pb-1" >Quais atividades você desempenhou além do atendimento aos alunos e correção das listas de exercícios?</label>
                    <input class="custom-form-control" type="text" name="secondary_activity" id="tinymcetextarea"
                        value="{{ old('secondary_activity') ?? $selection->selfevaluation->secondary_activity ?? ''}}"
                    />
            </div>
        </div>

        <div class="row custom-form-group">
            <div class="row col-lg">
                <div class="col-lg-auto lg-pb-3">
                    <label style="margin-top:5px;">Como você classificaria a carga de trabalho?*</label>
                </div>
                <div class="col-lg-auto lg-pb-3">
                    <select class="custom-form-control" style="width:150px" type="text" name="workload" id="workload"
                    >
                            <option value="" {{ old('workload') ? '' : ($selection->selfevaluation ? '' : 'selected') }}></option>
                            <option value="0" {{ old('workload') ? old('workload') == 0 ? 'selected' : '' : (($selection->selfevaluation) ? $selection->selfevaluation->workload == 0 ? 'selected' : '' : '') }}>Pesado</option>
                            <option value="1" {{ old('workload') ? old('workload') == 1 ? 'selected' : '' : (($selection->selfevaluation) ? $selection->selfevaluation->workload == 1 ? 'selected' : '' : '') }}>Razoável</option>
                            <option value="2" {{ old('workload') ? old('workload') == 2 ? 'selected' : '' : (($selection->selfevaluation) ? $selection->selfevaluation->workload == 2 ? 'selected' : '' : '') }}>Leve</option>
                    </select>
                </div>
                <div class="col-lg-auto">
                    <label style="margin-top:5px;">Justifique!</label>
                </div>
            </div>
        </div>

        <div class="row custom-form-group">
            <div class="col-lg">
                    <input class="custom-form-control" type="text" name="workload_reason" id="tinymcetextarea"
                        value="{{ old('workload_reason') ?? $selection->selfevaluation->workload_reason ?? ''}}"
                    />
            </div>
        </div>

        <div class="row custom-form-group">
            <div class="col-lg">
                    <label class="pb-1" >Comentários, reclamações ou sugestões:</label>
                    <input class="custom-form-control" type="text" name="comments" id="tinymcetextarea"
                        value="{{ old('comments') ?? $selection->selfevaluation->comments ?? ''}}"
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
