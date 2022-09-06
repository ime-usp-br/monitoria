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
                        <th class="text-center" style="vertical-align: middle;">Código da Turma</th>
                        <th class="text-center" style="vertical-align: middle;">Nome da Disciplina</th>
                        <th class="text-center" style="vertical-align: middle;">Professor(a)</th>
                        <th></th>
                    </tr>
                    @foreach($selections as $selection)
                        <tr class="text-center">
                            <td>{{ $selection->schoolclass->coddis }}</td>
                            <td>{{ $selection->schoolclass->codtur }}</td>
                            <td class="text-left">{{ $selection->schoolclass->nomdis }}</td>
                            <td class="text-left">{{ $selection->requisition->instructor->nompes }}</td>
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