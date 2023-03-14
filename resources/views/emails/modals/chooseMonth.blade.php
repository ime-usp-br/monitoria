<div class="modal fade" id="chooseMonthModal">
   <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Escolher outro mês</h4>
            </div>
            <form id="chooseSchoolTermForm" action="{{ route('emails.indexAttendanceRecords') }}" method="GET"
            enctype="multipart/form-data"
            >

                @csrf
                <div class="modal-body">
                    <div class="row custom-form-group align-items-center">
                        <div class="col-12 col-lg-6 text-lg-right">
                            <label for="periodoId">Mês</label>   
                        </div> 
                        <div class="col-12 col-md-5">
                            @php
                                $months = $schoolterm->period == "1° Semestre" ? [3=>"março", 4=>"abril", 5=>"maio", 6=>"junho"] : [8=>"agosto", 9=>"setembro", 10=>"outubro", 11=>"novembro"] ;
                            @endphp

                            <select id="month" name="month" class="custom-form-control">
                                    <option value="" selected></option>
                                @foreach($months as $key=>$value)
                                    <option value={{ $key }}>{{ $value }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="btn-chooseSchoolTerm" class="btn btn-default" type="submit">Buscar</button>
                    <button class="btn btn-default" type="button" data-dismiss="modal">Fechar</button>
                </div>
            </form>
        </div>
    </div>
</div>