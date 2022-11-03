@extends('parent')

@section('title', 'Frequência dos Monitores')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-3'>Frequência dos Monitores</h1>

            <h4 class='text-center mb-5'>{{ $schoolterm->period . ' de ' . $schoolterm->year }}</h4>

            @if (count($selections) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                <thead>
                    <tr>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Monitor</th>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Email</th>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Curso</th>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Sigla da<br>Disciplina</th>
                        <th class="text-center" rowspan="2" style="vertical-align: middle;">Nome da Disciplina</th>
                        <th class="text-center" colspan="5">Frequência</th>
                    </tr>
                    <tr class="text-center">
                        @if($schoolterm->period == "1° Semestre")
                            <th>Mar</th>
                            <th>Abr</th>
                            <th>Maio</th>
                            <th>Jun</th>
                            <th></th>
                        @elseif($schoolterm->period == "2° Semestre")
                            <th>Ago</th>
                            <th>Set</th>
                            <th>Out</th>
                            <th>Nov</th>
                            <th></th>
                        @endif
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($selections as $selection)
                        <tr class="text-center">
                            <td class="text-left">{{ $selection->student->nompes }}</td>
                            <td class="text-left">{{ $selection->student->codema }}</td>
                            <td class="text-left">{{ $selection->student->courses()->whereBelongsTo($selection->schoolclass->schoolterm)->first()->nomcur ?? "Não Encontrado" }}</td>
                            <td>{{ $selection->schoolclass->coddis }}</td>
                            <td class="text-left">{{ $selection->schoolclass->nomdis }}</td>
                            @foreach(range(3,6) as $month)
                                <td>{{ $selection->student->frequencies()->where(['school_class_id'=>$selection->schoolclass->id, 'month'=>($schoolterm->period == '1° Semestre' ? $month : $month+5)])->exists() ? 
                                        $selection->student->frequencies()->where(['school_class_id'=>$selection->schoolclass->id, 'month'=>($schoolterm->period == '1° Semestre' ? $month : $month+5)])->first()->registered ? 'S' : 'N' : 'N' }}</td>
                            @endforeach        
                            <td class="text-center"><a href="{{ route('frequencies.show',['schoolclass'=>$selection->schoolclass->id,'tutor'=>$selection->student->id]) }}" class="btn btn-outline-dark btn-sm">Registrar</a></td>        
                        </tr>
                    @endforeach

                    </tbody>
                </table>
            @else
                <p class="text-center">Não há monitores ativos sob sua responsabilidade</p>
            @endif
        </div>
    </div>
</div>
@endsection 