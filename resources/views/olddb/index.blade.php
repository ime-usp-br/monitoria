@extends('parent')

@section('title', 'DB Antigo')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='h5 font-weight-bold my-3 text-center'>
                Carregar dados do sistema antigo
            </h1>

            <div id="msg-top">
            </div>

            <div class="alert alert-info rounded-0">
                <b>Atenção:</b>
                O arquivo deve estar em formato csv, com as colunas separadas por ponto e virgula, seguindo a seguinte ordem: 

                    <ul>
                        <li>
                            primeira coluna com o numero usp do monitor, 
                        </li>
                        <li>
                            segunda coluna com o numero usp do docente responsavel, 
                        </li>
                        <li>
                            terceira coluna com o código da disciplina,
                        </li>
                        <li>
                            quarta coluna com o ano,
                        </li>
                        <li>
                            quinta coluna com o semestre sendo "0" para 1° semestre e "1" para 2° semestre,
                        </li>
                        <li>
                            sexta coluna com os meses em que foi resgistrada a frequencia Ex: "8-9-10-11",
                        </li>
                        <li>
                            setima coluna com a informação se a monitoria foi voluntaria também no formato 0/1.
                        </li>
                        <li>
                            oitava coluna com o numero de estudantes atendidos nas monitorias.
                        </li>
                        <li>
                            nona coluna com listas de exercicios corrigidas por mes.
                        </li>
                        <li>
                            decima coluna com descrição das atividades extras.
                        </li>
                        <li>
                            decima primeira coluna com o que o monitor achou do trabalho, sendo 0=ótimo, 1=bom e 2=regular.
                        </li>
                        <li>
                            decima segunda coluna com justificativa da nota que o monitor deu ao trabalho.
                        </li>
                        <li>
                            decima terceira coluna com observações, sugestoes e reclamações do monitor.
                        </li>
                        <li>
                            decima quarta coluna com a facilidade de contato atribuida ao monitor pelo docente no formato 0,1 ou 2.
                        </li>
                        <li>
                            decima quinta coluna com a eficiência atribuida ao monitor pelo docente no formato 0,1 ou 2.
                        </li>
                        <li>
                            decima sexta coluna com a confiabilidade atribuida ao monitor pelo docente no formato 0,1 ou 2.
                        </li>
                        <li>
                            decima setima coluna com a nota no geral atribuida ao monitor pelo docente no formato 0,1 ou 2.
                        </li>
                        <li>
                            decima oitava coluna com os comentários do docente.
                        </li>
                    </ul>

                </div>
            <br>

            <div id="progressbar-div">
            </div>

            <br>

            <form method='post' action="{{ route('olddb.import') }}" enctype='multipart/form-data' >
                @csrf
                <div class="text-center" style="height: 50px;">
                <input  class="custom-form-input" type='file' name='file' >
                <button id="btn-importfile" class="btn btn-outline-primary" type='submit' name='submit' >
                    <i class="fas fa-file-upload"></i>
                    Importar CSV
                </button>
                </div>
            </form>        
        
        </div>



    </div>
</div>
@endsection

@section('javascripts_bottom')
@parent
<script>
$( function() {       
    function progress() {
        $.ajax({
            url: window.location.origin+'/monitor/getImportOldDBJob',
            dataType: 'json',
            success: function success(json){
                if('progress' in json){
                    if(!json["data"] && !json['failed']){
                        if(document.getElementById('progressbar')){
                            $( "#progressbar" ).progressbar( "value", json['progress'] );
                        }else if(json['progress'] != 100){
                            document.getElementById("btn-importfile").disabled = true;
                            $('#progressbar-div').append("<div id='progressbar'><div class='progress-label'></div></div>");
                            var progressbar = $( "#progressbar" ),
                            progressLabel = $( ".progress-label" );
                            progressbar.progressbar({
                                value: false,
                                change: function() {
                                    progressLabel.text( progressbar.progressbar( "value" ) + "%" );
                                },
                                complete: function() {
                                    document.getElementById("btn-importfile").disabled = false;
                                    $( "#progressbar" ).remove();
                                    $('#msg-top').empty();
                                    $('#msg-top').append("<p id='success-message' class='alert alert-success'>Os dados foram importados com sucesso.</p>");
                                }
                            });
                        }
                    }else if((json["data"]) && (JSON.parse(json["data"])["status"] == "failed") && !(document.getElementById('error-message-bottom'))){
                        document.getElementById("btn-importfile").disabled = false;
                        $( "#progressbar" ).remove();
                        var failed_lines = JSON.parse(json["data"])["linhas_com_erros"];
                        $('#msg-top').empty();
                        $('#msg-top').append("<p id='error-message' class='alert alert-danger'>Ocorreram alguns erros na importação iniciada em "+json['started_at_exact']+". Linhas do arquivo que não foram possíveis a importação: "+failed_lines+"</p>");
                    }else if(json['failed']){
                        document.getElementById("btn-importfile").disabled = false;
                        $( "#progressbar" ).remove();
                        $('#msg-top').empty();
                        $('#msg-top').append("<p id='error-message' class='alert alert-danger'>Não foi possivel importar os dados iniciada em "+json['started_at_exact']+". Falha critica.</p>");
                    }
                }
                var timeouthandle = setTimeout( progress, 1000);
            }
        });
    }        
    setTimeout( progress, 50 );
});
</script>
@endsection