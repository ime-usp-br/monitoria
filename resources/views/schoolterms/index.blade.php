@extends('parent')

@section('title', 'Período Letivo')

@section('content')
@parent
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Período Letivo</h1>

            @if (count($periodos) > 0)

                <p class="text-right">
                    <a class="btn btn-primary" href="{{ route('schoolterms.create') }}">
                        <i class="fas fa-plus-circle"></i>
                        Cadastrar período letivo
                    </a>
                </p>

                <table class="table table-bordered table-striped table-hover" style="font-size:15px;">
                    <tr>
                        <th>Ano</th>
                        <th>Período</th>
                        <th>Estado</th>
                        <th>Período de avaliação</th>
                        <th>Maxímo de inscrições por aluno</th>
                        <th>Data inícial</th>
                        <th>Data final</th>
                        <th>Data inicial dos pedidos pelos docentes</th>
                        <th>Data final dos pedidos pelos docentes</th>
                        <th>Data inicial das inscrições pelos alunos</th>
                        <th>Data final das inscrições pelos alunos</th>
                        <th></th>
                    </tr>

                    @foreach($periodos as $periodo)
                        <tr>
                            <td>{{ $periodo->year }}</td>
                            <td style="white-space: nowrap;">{{ $periodo->period }}</td>
                            <td>{{ $periodo->status }}</td>
                            <td>{{ $periodo->evaluation_period }}</td>
                            <td>{{ $periodo->max_enrollments }}</td>
                            <td>{{ $periodo->started_at->format('Y-m-d') }}</td>
                            <td>{{ $periodo->finished_at->format('Y-m-d') }}</td>
                            <td>{{ $periodo->start_date_teacher_requests->format('Y-m-d') }}</td>
                            <td>{{ $periodo->end_date_teacher_requests->format('Y-m-d') }}</td>
                            <td>{{ $periodo->start_date_student_registration->format('Y-m-d') }}</td>
                            <td>{{ $periodo->end_date_student_registration->format('Y-m-d') }}</td>
                            <td class="text-center">
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