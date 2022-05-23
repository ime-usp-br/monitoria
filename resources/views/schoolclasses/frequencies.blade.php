@extends('parent')

@section('title', 'Registro de Frequência')

@section('content')
@parent
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Registro de Frequência</h1>
            <h3 class='text-center mb-5'>
                <b>Nome:</b> {{$monitor->nompes}} <b>N.° USP:</b> {{$monitor->codpes}}
            </h3>
            <h4 class='text-center mb-5'>
                <b>Disciplina:</b>  {{ $turma->nomdis }} <b>Turma:</b> {{ $turma->codtur }}
            </h4>
            <div id="progressbar-div">
            </div>

            @if ( !is_null($monitor->frequencies) && count($monitor->frequencies) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th>Ano</th>
                        <th>Mês</th>
                        <th>Frequência</th>
                    </tr>

                    @foreach($monitor->frequencies as $frequencia)
                        <tr style="font-size:12px;">
                            <td>{{ $frequencia->created_at->format('Y') }}</td>
                            <td>{{ $frequencia->created_at->format('m') }}</td>
                            <form action="/frequencies/{{$frequencia->id}}" method="POST">
                                @csrf
                                @method('patch')
                                @if($frequencia->registered)
                                    <td><button type="submit" class="btn btn-outline-success btn-sm">Sim</button></td>
                                @else  
                                    <td><button type="submit" class="btn btn-outline-danger btn-sm">Não</button></td>
                                @endif
                            </form>
                        </tr>
                    @endforeach
                </table>
            @else
                <p class="text-center">Este monitor não possui frequências para serem registradas.</p>
            @endif
        </div>
    </div>
</div>
@endsection