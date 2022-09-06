@extends('parent')

@section('title', 'Registro de Frequência')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Registro de Frequência</h1>
            <h4 class='text-center mb-5'>{{ $turma->schoolterm->period . ' de ' . $turma->schoolterm->year }}</h4>
            <h4 class='text-center mb-5'>
                <b>Monitor(a):</b> {{$monitor->nompes}} <b>N.° USP:</b> {{$monitor->codpes}}
            </h4>
            <h4 class='text-center mb-5'>
                <b>Disciplina:</b>  {{ $turma->nomdis }} <b>Turma:</b> {{ $turma->codtur }}
            </h4>

            @if ( !is_null($monitor->frequencies) && count($monitor->frequencies) > 0)
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                            <tr class="text-center">
                                <th>Mês</th>
                                <th>Presente</th>
                                <th></th>
                            </tr>

                            @foreach($monitor->frequencies()->whereBelongsTo($turma)->get() as $frequencia)
                                <tr class="text-center" style="font-size:12px;">
                                    <td>{{ $frequencia->month }}</td>
                                    <td>{{ $frequencia->registered ? 'Sim' : 'Não' }}</td>

                                    @if($frequencia->registered)
                                        <td style="width: 150px;"><a href="{{ \Illuminate\Support\Facades\URL::signedRoute('frequencies.update',['frequency'=>$frequencia->id]) }}" class="btn btn-outline-danger btn-sm">Desmarcar</a></td>
                                    @else  
                                        <td style="width: 150px;"><a href="{{ \Illuminate\Support\Facades\URL::signedRoute('frequencies.update',['frequency'=>$frequencia->id]) }}" class="btn btn-outline-success btn-sm">Registrar</a></td>
                                    @endif
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            @else
                <p class="text-center">Este monitor não possui frequências para serem registradas.</p>
            @endif
        </div>
    </div>
</div>
@endsection