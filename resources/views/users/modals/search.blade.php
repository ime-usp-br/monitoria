<div class="modal fade" id="usersSearchModal">
   <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Usuário</h4>
            </div>
        <form id="searchForm" action="{{ route('users.search') }}" method="get">

            <div class="modal-body">
                    <div class="row custom-form-group align-items-center">
                        <div class="col-12 col-lg-4 text-lg-right">
                            <label for="nome">Nome</label>
                        </div>

                        <div class="col-12 col-md-8">
                            <input class="custom-form-control" type="text" name="name" id="name"
                                value=''
                            />
                        </div>
                    </div>

                    <div class="row custom-form-group align-items-center">
                        <div class="col-12 col-lg-4 text-lg-right">
                            <label for="codpes">Número USP</label>
                        </div>

                        <div class="col-12 col-md-8">
                            <input class="custom-form-control" type="text"
                                name="codpes" id="codpes" 
                            />
                        </div>
                    </div>

                    <div class="row custom-form-group align-items-center">
                        <div class="col-12 col-lg-4 text-lg-right">
                            <label for="perfis_id">Perfis </label>
                        </div>

                        <div class="col-12 col-md-8">
                            @foreach ($roles as $role)
                                <div>
                                    <input class="checkbox" type="checkbox" name="roles[]" id="check-box-{{$role->id}}" 
                                    value="{{ $role->name }}" />
                                    <label for="check-box-{{$role->id}}">{{$role->name}}</label>
                                </div>
                            @endforeach 
                        </div>
                    </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" type="submit">Buscar</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Fechar</button>
            </div>
        </form>

        </div>
    </div>
</div>