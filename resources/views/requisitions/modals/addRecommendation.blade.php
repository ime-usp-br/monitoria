<div class="modal fade" id="addRecommendationModal">
   <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header justify-content-center">
                <h4 class="modal-title">Adicionar aluno</h4>
            </div>
            <div class="modal-body">
                <div class="row custom-form-group align-items-center">
                    <div class="col-12 col-lg-4 text-lg-right">
                        <label for="codpes-add">Número USP</label>   
                    </div> 
                    <div class="col-12 col-md-4">
                        <input class="custom-form-control" type="text" name="codpes-add"
                            id="codpes-add" value=''
                        />
                    </div>
                </div>
                <div class="row custom-form-group align-items-center">
                    <div class="col-12 col-lg-4 text-lg-right">
                        <label for="nompes-add">Nome </label>   
                    </div> 
                    <div class="col-12 col-md-8">
                        <input class="custom-form-control" type="text" name="nompes-add"
                            id="nompes-add" value=''
                        />
                    </div>
                </div>
                <div class="row custom-form-group align-items-center">
                    <div class="col-12" id="msn-div">
                    </div>
                </div>
                <div class="row custom-form-group align-items-center">
                    <div class="col-md-2">
                    </div>
                    <div class="col-md-10" id="select-student-div">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btn-chooseRecommendationModal" class="btn btn-default" type="button">Indicar</button>
                <button id="btn-addRecommendationModal" class="btn btn-default" type="button">Buscar</button>
                <button class="btn btn-default" type="button" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>