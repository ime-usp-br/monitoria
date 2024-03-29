@extends('parent')

@section('title', 'Monitores')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Monitores</h1>
            @if(Auth::user()->hasRole(["Administrador", "Secretaria", "Presidente de Comissão"]))
                <h4 class='text-center mb-3'>Todos Departamentos</h4>
            @elseif(Auth::user()->hasRole('Membro Comissão'))
                <h4 class='text-center mb-3'>Departamento de {{ App\Models\Instructor::where(['codpes'=>Auth::user()->codpes])->first()->department->nomset }}</h4>
            @endif

            <h4 class='text-center mb-3'>{{ $schoolterm->period . ' de ' . $schoolterm->year }}</h4>

            @include('tutors.modals.chooseSchoolTerm')
            @include('tutors.modals.revoke')

            <p class="text-right">
                <a  id="btn-chooseSchoolTermModal"
                    class="btn btn-outline-primary"
                    data-toggle="modal"
                    data-target="#chooseSchoolTermModal"
                    title="Escolher Semestre" 
                >
                    Escolher Semestre
                </a>
            </p>

            @if (count($selections) > 0)
                <table id="table_id" class="table table-bordered table-striped table-hover" style="font-size:12px;">
                <thead>
                    <tr>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;"></th>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Monitor</th>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Email</th>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Curso</th>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Situação</th>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Sigla da<br>Disciplina</th>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Nome da Disciplina</th>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Voluntário</th>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Auto<br>Avaliação</th>
                        <th class="text-center" colspan="5">Frequência</th>
                    </tr>
                    <tr class="text-center">
                        @if($schoolterm->period == "1° Semestre")
                            <th>Mar</th>
                            <th>Abr</th>
                            <th>Maio</th>
                            <th>Jun</th>
                        @elseif($schoolterm->period == "2° Semestre")
                            <th>Ago</th>
                            <th>Set</th>
                            <th>Out</th>
                            <th>Nov</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($selections as $selection)
                        <tr class="text-center">
                            <td>
                                <button  id="btn-revokeModal"
                                    class="btn btn-outline-danger btn-sm"
                                    data-toggle="modal"
                                    data-target="#revokeModal"
                                    title="Desligar Monitor" 
                                    href="{{ route('tutors.revoke', $selection) }}"
                                    {{ $selection->sitatl == "Desligado" ? 'disabled' : '' }}
                                >
                                    Desligar
                                </button>
                            </td>
                            <td class="text-left">{{ $selection->student->nompes }}</td>
                            <td class="text-left">{{ $selection->student->codema }}</td>
                            <td class="text-left">{{ $selection->student->courses()->whereBelongsTo($selection->schoolclass->schoolterm)->first()->nomcur ?? "Não Encontrado" }}</td>
                            <td>{{ $selection->sitatl }}</td>
                            <td>{{ $selection->schoolclass->coddis }}</td>
                            <td class="text-left">{{ $selection->schoolclass->nomdis }}</td>
                            <td class="text-center" style="white-space: nowrap;">
                                @if($selection->enrollment->voluntario)
                                    @if($selection->sitatl == "Ativo")
                                        <form id="revokeForm" method="POST" 
                                        enctype="multipart/form-data" action="{{ route('tutors.turnintononvolunteer',$selection) }}"
                                        >
                                            @csrf
                                            @method("PATCH")

                                            <div class="d-inline">
                                                Sim
                                                <button class="btn btn-outline-warning btn-sm" title="Remunerar Monitor" type="submit">Mudar</button>
                                            </div>
                                            
                                        </form>
                                    @else
                                        Sim
                                    @endif
                                @else
                                    @if($selection->sitatl == "Ativo")
                                        <form id="revokeForm" method="POST"
                                        enctype="multipart/form-data" action="{{ route('tutors.turnintovolunteer',$selection) }}"
                                        >
                                            @csrf
                                            @method("PATCH")
                                                                        
                                            <div class="d-inline">
                                                Não
                                                <button class="btn btn-outline-info btn-sm" title="Tornar Monitor Voluntário" type="submit">Mudar</button>
                                            </div>
                                        </form>
                                    @else
                                        Não
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if($selection->selfevaluation)
                                    <a  href="{{ route('selfevaluations.show',$selection->selfevaluation) }}" 
                                        class="btn btn-outline-dark btn-sm">
                                        Visualizar
                                    </a>
                                @else
                                    Pendente
                                @endif
                            </td>
                            @foreach(range(3,6) as $month)
                                <td>{{ $selection->student->frequencies()->where(['school_class_id'=>$selection->schoolclass->id, 'month'=>($schoolterm->period == '1° Semestre' ? $month : $month+5)])->exists() ? 
                                        $selection->student->frequencies()->where(['school_class_id'=>$selection->schoolclass->id, 'month'=>($schoolterm->period == '1° Semestre' ? $month : $month+5)])->first()->registered ? 'S' : 'N' : 'N' }}</td>
                            @endforeach        
                        </tr>
                    @endforeach

                    </tbody>
                </table>
            @else
                <p class="text-center">Não há monitores</p>
            @endif
        </div>
    </div>
</div>
@endsection 