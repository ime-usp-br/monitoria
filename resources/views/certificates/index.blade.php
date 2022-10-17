@extends('parent')

@section('title', 'Emitir Declaração')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Atestado de Monitoria</h1>

            @if (count($selections) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th class="text-center" style="vertical-align: middle;">Sigla da Disciplina</th>
                        <th class="text-center" style="vertical-align: middle;">Nome da Disciplina</th>
                        <th class="text-center" style="vertical-align: middle;">Professor(a)</th>
                        <th class="text-center" style="vertical-align: middle;">Semestre</th>
                        <th class="text-center" style="vertical-align: middle;">Observações</th>
                        <th></th>
                    </tr>
                    @foreach($selections as $selection)
                        <tr class="text-center">
                            <td>{{ $selection->schoolclass->coddis }}</td>
                            <td class="text-left">{{ $selection->schoolclass->nomdis }}</td>
                            <td class="text-left">{{ $selection->requisition->instructor->nompes }}</td>   
                            <td class="text-left">{{ $selection->schoolclass->schoolterm->period." de ".$selection->schoolclass->schoolterm->year }}</td>   
                            @php
                                $instructor = $selection->requisition->instructor;
                                $st = $selection->schoolclass->schoolterm;
                                $label = "";
                                if(!in_array($selection->schoolclass->coddis, App\Models\SchoolClass::getDisciplinesFromReplicadoBySchoolTermAndInstructor($st, $instructor))){
                                    $label = "Os dados desta monitoria foram importados do antigo sistema. No processo de importação foi constatado que ".( $instructor->getSexo() == "M" ? "o Prof. " : "a Profa. ");
                                    $label .= explode(" ", $instructor->nompes)[0]." não ministrou a disciplina ".$selection->schoolclass->coddis." no ".$st->period." de ".$st->year.".";
                                    $label .= " Isto se deve provavelmente a algum erro na solicitação da vaga de monitoria. Caso você precise que o atestado seja corrigido entre em contato com a secretaria de monitoria.";
                                }
                            @endphp
                            <td class="text-left" style="max-width:600px;">{{ $label }}</td>
                            <td class="text-center"><a href="{{ route('certificates.make',['selection'=>$selection->id]) }}" class="btn btn-outline-dark btn-sm">Emitir Atestado</a></td> 
                        </tr>
                    @endforeach
                </table>
            @else
                <p class="text-center">Você não concluiu nenhuma monitoria.</p>
            @endif
        </div>
    </div>
</div>
@endsection 