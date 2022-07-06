@extends('parent')

@section('title', 'Monitores')

@section('content')
@parent
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Monitores</h1>
            @if(Auth::user()->hasRole('Membro Comissão'))
                <h4 class='text-center mb-5'>Departamento de {{ App\Models\Instructor::where(['codpes'=>Auth::user()->codpes])->first()->department->nomset }}</h4>
            @endif

            @include('tutors.modals.chooseSchoolTerm')

            <p class="text-right">
                <a  id="btn-chooseSchoolTermModal"
                    class="btn btn-primary"
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
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Sigla da Disciplina</th>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Código da Turma</th>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Nome da Disciplina</th>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Monitor</th>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Email</th>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Voluntário</th>
                        <th class="text-center" colspan="5">Frequência</th>
                    </tr>
                    <tr class="text-center">
                        @if($schoolterm->period == "1° Semestre")
                            <th>Mar</th>
                            <th>Abr</th>
                            <th>Maio</th>
                            <th>Jun</th>
                            <th>Jul</th>
                        @elseif($schoolterm->period == "2° Semestre")
                            <th>Ago</th>
                            <th>Set</th>
                            <th>Out</th>
                            <th>Nov</th>
                            <th>Dez</th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($selections as $selection)
                        <tr class="text-center">
                            <td>{{ $selection->schoolclass->coddis }}</td>
                            <td>{{ $selection->schoolclass->codtur }}</td>
                            <td class="text-left">{{ $selection->schoolclass->nomdis }}</td>
                            <td class="text-left">{{ $selection->student->nompes }}</td>
                            <td class="text-left">{{ $selection->student->codema }}</td>
                            <td class="text-center">{{ $selection->enrollment->voluntario ? 'Sim' : 'Não'}}</td>
                            @foreach(range(3,7) as $month)
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