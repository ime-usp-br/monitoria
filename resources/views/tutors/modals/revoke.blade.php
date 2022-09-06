<div class="modal fade" id="revokeModal">
   <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Desligamento de Monitor</h4>
            </div>
            <form id="revokeForm" method="POST"
            enctype="multipart/form-data"
            >

            @csrf
            @method("PATCH")
            <div class="modal-body">
                <div class="row custom-form-group align-items-center">
                    <div class="col-4 text-right">
                        <label for="motdes">Motivo*:</label>   
                    </div> 
                    <div class="col-8">
                        <textarea class="custom-form-control" name="motdes" style="max-width:300px;height:150px"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btn-revoke" class="btn btn-default" type="submit">Desligar</button>
                <button class="btn btn-default" type="button" data-dismiss="modal">Fechar</button>
            </div>
            </form>
        </div>
    </div>
</div> 