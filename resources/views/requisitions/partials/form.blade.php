<div class="card mb-3">
    <div class="card-body">


        <div class="row custom-form-group">
            <div class="row col-lg lg-pb-3">
                <div class="col-lg-auto pr-0">
                    <label>Unidade:</label>
                </div>
                <div class="col-lg-auto">
                    {{ $turma->department->nomund }}
                </div>
            </div>
            <div class="row col-lg">
                <div class="col-lg-auto pr-0">
                    <label>Departamento:</label>
                </div>
                <div class="col-lg-auto">
                    {{ $turma->department->nomset }}
                </div>
            </div>
        </div>

        <div class="row custom-form-group">
            <div class="row col-lg lg-pb-3">
                <div class="col-lg-auto pr-0">
                    <label>Disciplina:</label>
                </div>
                <div class="col-lg-auto">
                    {{ $turma->nomdis }}
                </div>
            </div>
            <div class="row col-lg">
                <div class="col-lg-auto pr-0">
                    <label>Sigla:</label>
                </div>
                <div class="col-lg-auto">
                    {{ $turma->coddis }}
                </div>
            </div>
            <div class="row col-lg">
                <div class="col-lg-auto pr-0">
                    <label>Turma:</label>
                </div>
                <div class="col-lg-auto">
                    {{ $turma->codtur }}
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
                    <label style="margin-top:5px;">Número de monitores solicitados:*</label>
                </div>
                <div class="col-lg-auto">
                    <input class="custom-form-control" style="max-width:70px;"  type="number" name="requested_number" id="requested_number"
                        value="{{ $turma->requisition->requested_number ?? '1' }}"
                    />
                </div>
            </div>
            <div class="row col-lg">
                <div class="col-lg-auto pr-0">
                    <label style="margin-top:5px;">Atividade(s) que pretende atribuir ao(s) monitor(es):*</label>
                </div>
                <div class="col-lg-auto">
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
        </div>

        <div class="row custom-form-group">
            <div class="row col-lg lg-pb-3">
                <div class="col-lg-auto pr-0">
                    <label style="margin-top:5px;">De acordo com a sua avaliação, o<br> trabalho do monitor nesta disciplina é:*</label>
                </div>
                <div class="col-lg-auto">
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
            <div class="row col-lg">
                <div class="col-lg-auto pr-0">
                    <label style="margin-top:5px;">Realizou outros pedidos de bolsa para monitoria além deste?<br> Em caso afirmativo, informe quais:*</label>
                </div>
                <div class="col-lg-auto">
                    @foreach(App\Models\Scholarship::all() as $scholarship)
                        <div>
                            <input class="checkbox" type="checkbox" name="scholarships[]"
                            value={{ $scholarship->id }} {{ $turma->requisition ? ($turma->requisition->others_scholarships()->where("id",$scholarship->id)->exists() ? 'checked' : '') : '' }}/>
                            <a>{{ $scholarship->name }}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @include('requisitions.modals.addRecommendation')

        <div class="row custom-form-group">
            <div class="row col-lg">
                <div class="col-lg-auto pr-0">
                    <label>Indicar aluno(s):</label>
                </div>
                <div class="col-lg-auto">
                    @if($turma->requisition)
                        @foreach($turma->requisition->recommendations as $indicacao)
                            <div id="indicacao-{{ $indicacao->id }}">
                                <input id="recommendations[{{$indicacao->id}}][codpes]" name="recommendations[{{$indicacao->id}}][codpes]" type="hidden" value="{{ $indicacao->student->codpes }}">
                                <label id="label-indicacao-{{ $indicacao->id }}" class="font-weight-normal">{{ $indicacao->student->nompes }}</label> 
                                
                                <a class="btn btn-link btn-sm text-dark text-decoration-none"
                                    style="padding-left:0px"
                                    id="btn-remove-indicacao-{{ $indicacao->id }}"
                                    onclick="removeRecommendation({{ $indicacao->id }})"
                                    title="Remover"
                                >
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                                <br/>
                            </div>
                        @endforeach
                    @endif

                    <div id="novas-indicacoes"></div>
                    <label class="font-weight-normal">Adicionar aluno</label> 
                    <input id="count-new-recommendation" value=0 type="hidden" disabled>
                    <a class="btn btn-link btn-sm text-dark text-decoration-none" id="btn-addRecommendation" 
                        data-toggle="modal" data-target="#addRecommendationModal"
                        title="Adicionar aluno">
                        <i class="fas fa-plus-circle"></i>
                    </a>

                    <script>
                        function removeRecommendation(id){
                            document.getElementById("indicacao-"+id).remove();
                        }
                    </script>
                </div>
            </div>
        </div>

        <div class="row custom-form-group">
            <div class="col-lg">
                    <label class="pb-1" >Comentários:</label>
                    <input class="custom-form-control" type="text" name="comments" id="tinymcetextarea"
                        value="{{ old('comments') ?? $turma->requisition->comments ?? ''}}"
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
            href="{{ route('requisitions.index') }}"
        >
            Cancelar
        </a>
    </div>
</div>
