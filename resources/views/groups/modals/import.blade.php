<div class="modal fade" id="importGroupModal">
   <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Importar turmas</h4>
            </div>
            <form id="importGroupsForm" action="{{ route('groups.import') }}" method="POST"
            enctype="multipart/form-data"
            >

            @method('patch')
            @csrf
            <div class="modal-body">
                <div class="row custom-form-group align-items-center">
                    <div class="col-12 col-lg-6 text-lg-right">
                        <label for="periodoId">Período letivo</label>   
                    </div> 
                    <div class="col-12 col-md-5">

                        <select id="periodoId" name="periodoId" class="custom-form-control">
                            @foreach($schoolterms as $schoolterm)
                                <option value={{ $schoolterm->id }}>{{ $schoolterm->year . " " . $schoolterm->period }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btn-importGroups" class="btn btn-default" type="submit">Importar</button>
                <button class="btn btn-default" type="button" data-dismiss="modal">Fechar</button>
            </div>
            </form>
        </div>
    </div>
</div>