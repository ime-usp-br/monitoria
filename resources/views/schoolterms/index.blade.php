@extends('parent')

@section('title', 'Período Letivo')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Período Letivo</h1>

            <p class="text-right">
                <a class="btn btn-outline-primary" href="{{ route('schoolterms.create') }}">
                    <i class="fas fa-plus-circle"></i>
                    Cadastrar período letivo
                </a>
            </p>


            @if (count($periodos) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:15px;">
                    <tr>
                        <th>Ano</th>
                        <th>Período</th>
                        <th>Estado</th>
                        <th>Período de avaliação</th>
                        <th>Maxímo de inscrições por aluno</th>
                        <th>Edital</th>
                        <th>Data inícial</th>
                        <th>Data final</th>
                        <th>Data inicial dos pedidos pelos docentes</th>
                        <th>Data final dos pedidos pelos docentes</th>
                        <th>Data inicial das inscrições pelos alunos</th>
                        <th>Data final das inscrições pelos alunos</th>
                        <th></th>
                    </tr>

                    @foreach($periodos as $periodo)
                        <tr class="text-center">
                            <td>{{ $periodo->year }}</td>
                            <td style="white-space: nowrap;">{{ $periodo->period }}</td>
                            <td>{{ $periodo->status }}</td>
                            <td>{{ $periodo->evaluation_period }}</td>
                            <td>{{ $periodo->max_enrollments }}</td>
                            <td>
                                <form method="POST" action="{{ route('schoolterms.download') }}" target="_blank">
                                    @csrf
                                    <input type='hidden' name='path' value="{{ $periodo->public_notice_file_path }}">
                                    <button class="btn btn-link"
                                        data-toggle="tooltip" data-placement="top"
                                        title="Baixar Edital"
                                    >
                                        Download
                                    </button>
                                </form>                            
                            </td>
                            <td>{{ $periodo->started_at }}</td>
                            <td>{{ $periodo->finished_at }}</td>
                            <td>{{ $periodo->start_date_requisitions }}</td>
                            <td>{{ $periodo->end_date_requisitions }}</td>
                            <td>{{ $periodo->start_date_enrollments }}</td>
                            <td>{{ $periodo->end_date_enrollments }}</td>
                            <td>
                                <a class="text-dark text-decoration-none"
                                    data-toggle="tooltip" data-placement="top"
                                    title="Editar"
                                    href="{{ route('schoolterms.edit', $periodo) }}"
                                >
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p class="text-center">Não há períodos letivos cadastrados</p>
            @endif
        </div>
    </div>
</div>
@endsection