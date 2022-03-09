<div class="modal fade" id="selectUnenrolledStudentModal">
   <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h4 class="modal-title">Eleger como monitor aluno não inscrito</h4>
            </div>
            <form action="{{ route('selections.selectunenrolled') }}" method="POST">
                @csrf
                <input name="school_class_id" value="{{$turma->id}}" type="hidden">
                <div class="modal-body">
                    <div class="row custom-form-group align-items-center">
                        <div class="col-12 col-lg-4 text-lg-right">
                            <label for="codpes-select">Número USP</label>   
                        </div> 
                        <div class="col-12 col-md-4">
                            <input class="custom-form-control" type="text" name="codpes-select"
                                id="codpes-select" value=''
                            />
                        </div>
                    </div>
                    <div class="row custom-form-group align-items-center">
                        <div class="col-12 col-lg-4 text-lg-right">
                            <label for="nompes-select">Nome </label>   
                        </div> 
                        <div class="col-12 col-md-8">
                            <input class="custom-form-control" type="text" name="nompes-select"
                                id="nompes-select" value=''
                            />
                        </div>
                    </div>
                    <div class="row custom-form-group align-items-center">
                        <div class="col-12" id="msn-div">
                        </div>
                    </div>

                    <div class="row custom-form-group align-items-center">
                        <div class="col-md-11" id="select-student-div">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="btn-chooseSelectModal" class="btn btn-default" type="sunmit">Eleger Monitor</button>
                    <button id="btn-searchSelectModal" class="btn btn-default" type="button">Buscar</button>
                    <button class="btn btn-default" type="button" data-dismiss="modal">Fechar</button>
                </div>
            </form>
        </div>
    </div>
</div>