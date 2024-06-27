@extends('parent')

@section('title', 'Monitores')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Monitores</h1>
            <h2 class='text-center mb-5'>
                Departamento de {{ $turma->department->nomset }}<br>
            </h2>
            <h4 class='text-center mb-5'>
                <b>Disciplina:</b>  {{ $turma->nomdis }} <b>Turma:</b> {{ $turma->codtur }}
            </h4>

            <p class="text-right">
                <a class="btn btn-outline-primary"
                    data-toggle="tooltip" data-placement="top"
                    title="Voltar"
                    href="{{ url()->previous() }}"
                >
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </a>
            </p>

            @if ( !is_null($turma->requisition) && count($turma->requisition->selections) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th>N.° USP</th>
                        <th>Nome do aluno</th>
                        <th>Atividades atribuidas</th>
                        <th>Voluntário</th>
                        <th>Observações</th>
                        <th>Aluno indicado</th>
                        <th>Registros de frequência</th>
                    </tr>

                    @foreach($turma->requisition->selections as $eleicao)
                        <tr style="font-size:12px;">
                            <td class="text-center">{{ $eleicao->student->codpes }}</td>
                            <td class="text-left">{{ $eleicao->student->nompes }}</td>
                            <td class="text-left"  style="white-space: nowrap;">
                            @foreach($turma->requisition->activities as $atividade)
                                {{ $atividade->description }} <br/>
                            @endforeach
                            </td>
                            <td class="text-center">{{ $eleicao->enrollment->voluntario ? 'Sim' : 'Não'}}</td>
                            <td class="text-center">{{ $eleicao->enrollment->observacoes }}</td>
                            <td class="text-center">
                                {{ $eleicao->requisition->isStudentRecommended($eleicao->student) ? 'Sim' : 'Não' }} <br/>
                            </td>
                            <td class="text-center"><a href="{{ route('frequencies.show',['schoolclass'=>$eleicao->schoolclass->id,'tutor'=>$eleicao->student->id]) }}" class="btn btn-outline-dark btn-sm">Registrar frequência</a></td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p class="text-center">Esta turma não possui monitores.</p>
            @endif
        </div>
    </div>
</div>
@endsection