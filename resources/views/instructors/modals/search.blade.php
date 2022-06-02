<div class="modal fade" id="instructorsSearchModal">
   <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Docente</h4>
            </div>
            <form id="searchForm" action="{{ route('instructors.search') }}" method="get">
                <div class="modal-body">
                        <div class="row custom-form-group align-items-center">
                            <div class="col-12 col-lg-6 text-lg-right">
                                <label for="codpes">N.° USP </label>
                            </div>

                            <div class="col-12 col-md-4">
                                <input class="custom-form-control" type="text" name="codpes" id="codpes"
                                    value=''
                                />
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