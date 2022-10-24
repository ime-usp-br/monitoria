@extends('parent')

@section('title', 'Alunos Inscritos')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Alunos Inscritos</h1>

            <h4 class='text-center mb-5'>{{ $schoolterm->period . ' de ' . $schoolterm->year }}</h4>

            @if (count($alunos) > 0)

                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th>N USP</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Histórico Escolar</th>
                        <th>Inscrito nas Disciplinas</th>
                        <th>Disponibilidade de dia</th>
                        <th>Disponibilidade de noite</th>
                        <th>Preferência pelo período</th>
                        <th>Outras<br>Bolsas</th>
                        <th>Telefone</th>
                    </tr>

                    @foreach($alunos as $aluno)
                        <tr style="font-size:12px;">
                            <td style="text-align: center">{{ $aluno->codpes }}</td>
                            <td style="white-space: nowrap;">{{ $aluno->nompes }}</td>
                            <td style="white-space: nowrap;">{{ $aluno->codema }}</td>
                            <td style="text-align: center">
                                <form method="POST" action="{{ route('schoolrecords.download') }}" target="_blank">
                                    @csrf
                                    <input type='hidden' name='path' value="{{ $aluno->getSchoolRecordFromOpenSchoolTerm() }}">
                                    <button class="btn btn-link"
                                        data-toggle="tooltip" data-placement="top"
                                        title="Baixar Histórico Escolar"
                                    >
                                        Download
                                    </button>
                                </form> 
                            </td>
                            <td style="white-space: nowrap;text-align: center">
                                @php
                                    $disciplines = App\Models\SchoolClass::whereBelongsTo($schoolterm)->whereHas("enrollments",function($query)use($aluno){
                                        $query->whereBelongsTo($aluno);
                                    })->pluck("coddis")->unique()->toArray();
                                @endphp
                                @foreach($disciplines as $coddis)
                                    {{ $coddis }} <br/>
                                @endforeach
                            </td>
                            @php
                                $disp_dia_array = $aluno->enrollments()->whereHas("schoolclass", function($query)use($schoolterm){$query->whereBelongsTo($schoolterm);})->get()->pluck("disponibilidade_diurno")->unique()->toarray();
                            @endphp
                            <td style="text-align: center">
                                @if(count($disp_dia_array)>1)
                                    Depende<br>da Vaga
                                @else
                                    {!! $disp_dia_array[0] ? "Sim" : "Não" !!}
                                @endif
                            </td>
                            @php
                                $disp_noite_array = $aluno->enrollments()->whereHas("schoolclass", function($query)use($schoolterm){$query->whereBelongsTo($schoolterm);})->get()->pluck("disponibilidade_noturno")->unique()->toarray();
                            @endphp
                            <td style="text-align: center">
                                @if(count($disp_noite_array)>1)
                                    Depende<br>da Vaga
                                @else
                                    {!! $disp_noite_array[0] ? "Sim" : "Não" !!}
                                @endif
                            </td>
                            @php
                                $pref_hor_array = $aluno->enrollments()->whereHas("schoolclass", function($query)use($schoolterm){$query->whereBelongsTo($schoolterm);})->get()->pluck("preferencia_horario")->unique()->toarray();
                            @endphp
                            <td style="text-align: center">
                                @if(count($pref_hor_array)>1)
                                    Depende<br>da Vaga
                                @else
                                    {!! $pref_hor_array[0] !!}
                                @endif
                            </td>
                            <td class="text-left" style="white-space: nowrap;">
                                @php
                                $enrollments = $aluno->enrollments()->whereHas("schoolclass", function($query)use($schoolterm){$query->whereBelongsTo($schoolterm);})->get();
                                $scholarships = [];
                                foreach($enrollments as $enrollment){
                                    $scholarships = array_merge($scholarships,$enrollment->others_scholarships->pluck("name")->toArray());
                                }
                                $scholarships = array_unique($scholarships);
                                @endphp
                                @foreach($scholarships as $scholarship)
                                    {{ $scholarship }} <br/>
                                @endforeach
                            </td>
                            <td style="white-space: nowrap;">
                                @foreach($aluno->getTelefonesFromReplicado() as $tel)
                                    {!! "+".$tel['codddi']." (".$tel['codddd'].") ".$tel['numtel'] !!}<br>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p class="text-center">Não há alunos inscritos esse semestre</p>
            @endif
        </div>
    </div>
</div>
@endsection