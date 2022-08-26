@extends('parent')

@section('title', 'Turmas')

@section('content')
@parent
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Turmas</h1>
            @if($schoolterm)
                <h4 class='text-center mb-5'>{{ $schoolterm->period . ' de ' . $schoolterm->year }}</h4>
            @endif

            @if(Auth::user()->hasRole('Membro Comissão'))
                <h4 class='text-center mb-5'>Departamento de {{ App\Models\Instructor::where(['codpes'=>Auth::user()->codpes])->first()->department->nomset }}</h4>
            @endif

            <div id="progressbar-div">
            </div>
            <br>
            @include('schoolclasses.modals.chooseSchoolTerm')
            @include('schoolclasses.modals.import')
            @include('schoolclasses.modals.addSchoolClass')
            <p class="text-right">
                @if(Auth::user()->hasPermissionTo('criar turma'))
                    <a  id="btn-addModal"
                        class="btn btn-primary"
                        data-toggle="modal"
                        data-target="#addSchoolClassModal"
                        title="Cadastrar" 
                    >
                        <i class="fas fa-plus-circle"></i>
                        Cadastrar
                    </a>
                @endif

                <a  id="btn-chooseSchoolTermModal"
                    class="btn btn-primary"
                    data-toggle="modal"
                    data-target="#chooseSchoolTermModal"
                    title="Escolher Semestre" 
                >
                    Escolher Semestre
                </a>
                
                @if(Auth::user()->hasPermissionTo('importar turmas do replicado'))
                    <a  id="btn-importModal"
                        class="btn btn-primary"
                        data-toggle="modal"
                        data-target="#importSchoolClassModal"
                        title="Importar" 
                    >
                        <i class="fas fa-file-upload"></i>
                        Importar do Jupiter
                    </a>
                @endif
                @if(Auth::user()->hasPermissionTo('buscar turmas'))
                    <a  id="btn-searchModal" 
                        class="btn btn-primary" 
                        data-toggle="modal" 
                        data-target="#schoolclassesSearchModal"
                    >
                        <i class="fas fa-search"></i>
                        Buscar
                    </a>
                @endif
            </p>
            @include('schoolclasses.modals.search')

            @if (count($turmas) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th>Código da Turma</th>
                        <th>Código da Disciplina</th>
                        <th>Nome da Disciplina</th>
                        <th>Departamento</th>
                        <th>Horários</th>
                        <th>Professor(es)</th>
                        <th>Monitores</th>
                        @if(Auth::user()->hasPermissionTo('editar turma') || Auth::user()->hasPermissionTo('deletar turma'))
                            <th></th>
                        @endif
                    </tr>

                    @foreach($turmas as $turma)
                        <tr style="font-size:12px;">
                            <td class="text-center">{{ $turma->codtur }}</td>
                            <td class="text-center">{{ $turma->coddis }}</td>
                            <td>{{ $turma->nomdis }}</td>
                            <td style="text-align: center">{{ $turma->department->nomabvset }}</td>
                            <td style="white-space: nowrap;">
                                @foreach($turma->classschedules as $horario)
                                    {{ $horario->diasmnocp . ' ' . $horario->horent . ' ' . $horario->horsai }} <br/>
                                @endforeach
                            </td>
                            <td style="white-space: nowrap;">
                                @foreach($turma->instructors as $instructor)
                                    {{ $instructor->getPronounTreatment() . $instructor->getNomAbrev()}} <br/>
                                @endforeach
                            </td>
                            <td class="text-center">
                                @if($turma->selections()->exists())
                                    <a href="/schoolclasses/{{$turma->id}}/electedTutors" class="btn btn-outline-dark btn-sm">Monitores</a>
                                @else
                                    Nenhum Monitor
                                @endif
                            </td>
                            @if(Auth::user()->hasPermissionTo('editar turma') || Auth::user()->hasPermissionTo('deletar turma'))
                            <td class="text-center" style="white-space: nowrap;">
                                @if(Auth::user()->hasPermissionTo('editar turma'))
                                    <a class="text-dark text-decoration-none"
                                        data-toggle="tooltip" data-placement="top"
                                        title="Editar"
                                        href="{{ route('schoolclasses.edit', $turma) }}"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif
                                @if(Auth::user()->hasPermissionTo('deletar turma'))
                                    <a class="text-dark text-decoration-none"
                                        data-toggle="modal"
                                        data-target="#removalModal"
                                        title="Remover"
                                        href="{{ route(
                                            'schoolclasses.destroy',
                                            $turma
                                        ) }}"
                                    >
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                @endif
                            </td>
                            @endif
                        </tr>
                    @endforeach
                </table>
                @include('schoolclasses.modals.removal')
            @else
                <p class="text-center">Não há turmas cadastradas</p>
            @endif
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
                    url: window.location.origin+'/monitor/getimportschoolclassesjob',
                    dataType: 'json',
		    success: function success(json){
                        if('progress' in json){
                            if(document.getElementById('progressbar')){
                                $( "#progressbar" ).progressbar( "value", json['progress'] );
                            }else if(json['progress'] != 100){
                                document.getElementById("btn-searchModal").disabled = true;
                                document.getElementById("btn-addModal").disabled = true;
                                document.getElementById("btn-importModal").disabled = true;
                                $('#progressbar-div').append("<div id='progressbar'><div class='progress-label'></div></div>");
                                var progressbar = $( "#progressbar" ),
                                progressLabel = $( ".progress-label" );
                                progressbar.progressbar({
                                value: false,
                                change: function() {
                                    progressLabel.text( progressbar.progressbar( "value" ) + "%" );
                                },
				complete: function() {
					location.replace(location);
					window.clearTimeout(timeouthandle);
                                }
                                });
                            }
			}
			var timeoutehandle = setTimeout( progress, 1000);
                    }});
            }        
            setTimeout( progress, 50 );
        });
    </script>
@endsection
