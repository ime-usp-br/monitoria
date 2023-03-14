<div class="modal fade" id="testTemplateModal">
   <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Testar Modelo</h4>
            </div>
            <form id="testTemplateForm" action="{{ route('mailtemplates.test') }}" method="POST"
            enctype="multipart/form-data"
            >

            @csrf
            <div class="modal-body">
                <div class="row custom-form-group align-items-center">
                    <div class="col-md-5 text-lg-right">
                        <label for="template">Modelo*:</label>   
                    </div> 
                    <div class="col-md-7">
                        <select class="custom-form-control" type="text" name="mailtemplate_id">
                            <option value="" selected></option>
                            @foreach(App\Models\MailTemplate::all() as $mailtemplate)
                                <option value="{{ $mailtemplate->id }}">{{ $mailtemplate->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row custom-form-group align-items-center">
                    <div class="col-md-5 text-lg-right">
                        <label for="email">Enviar para o e-mail*:</label>   
                    </div> 
                    <div class="col-md-7">
                        <input type="text" value="" name="email" class="custom-form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btn-testTemplate" class="btn btn-default" type="submit">Enviar E-mail de Teste</button>
                <button class="btn btn-default" type="button" data-dismiss="modal">Fechar</button>
            </div>
            </form>
        </div>
    </div>
</div>