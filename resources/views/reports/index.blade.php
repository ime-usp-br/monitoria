@extends('parent')

@section('title', 'Relatório')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Relatório</h1>

            <div id="progressbar-div">
            </div>
            <br>
            @include('reports.modals.makeReport')
            <p class="text-right">
                <a  id="btn-addModal"
                    class="btn btn-outline-primary"
                    data-toggle="modal"
                    data-target="#makeReportModal"
                    title="Gerar Relatório" 
                >
                    <i class="icon-file-text"></i>
                    Gerar Relatório
                </a>
            </p>
        </div>
    </div>
</div>
@endsection 