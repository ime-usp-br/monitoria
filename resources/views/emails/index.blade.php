@extends('parent')

@section('title', 'Turmas com Monitores Eleitos')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Comunicar Resultado das Seleções</h1>

            <form method="POST"
                action="{{ route('emails.dispatch') }}"
            >
            @csrf
            
            <p class="text-right">
                <button id="btn-dispatchEmails"type="button" class="btn btn-outline-primary" value=0>
                    <i class="icon-check"></i>
                    Marcar Todos
                </button>
                <button type="submit" class="btn btn-outline-primary">
                    <i class="fas fa-envelope"></i>
                    Disparar e-mails
                </button>
            </p>
            @if (count($turmas) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th></th>
                        <th>Código da Disciplina</th>
                        <th>Código da Turma</th>
                        <th>Nome da Disciplina</th>
                        <th>Departamento</th>
                        <th>Prof(a) Solicitante</th>
                        <th>Monitor(es) Eleito(s)</th>
                    </tr>

                    @foreach($turmas as $turma)
                        <tr style="font-size:12px;">
                            <td>
                                <input id="school_classes_id" class="checkbox" type="checkbox" name="school_classes_id[]" value="{{ $turma->id }}">
                            </td>
                            <td>{{ $turma->coddis }}</td>
                            <td>{{ $turma->codtur }}</td>
                            <td>{{ $turma->nomdis }}</td>
                            <td style="text-align: center">{{ $turma->department->nomabvset }}</td>
                            <td style="white-space: nowrap;">
                                @foreach($turma->instructors as $instrutor)
                                    {{ $instrutor->nompes }} <br/>
                                @endforeach
                            </td>
                            <td style="white-space: nowrap;">
                                @foreach($turma->selections as $selecao)
                                    {{ $selecao->student->nompes }} <br/>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                </table>
            </form>
            @else
                <p class="text-center">Não há turmas com monitores eleitos</p>
            @endif
        </div>
    </div>
</div>
@endsection

@section('javascripts_bottom')
    <script>
        $( function() {        
            $('#btn-dispatchEmails').on('click', function(){
                if($('#btn-dispatchEmails').val() == 1){
                    $('input:checkbox').prop('checked', false);
                    $('#btn-dispatchEmails').val(0);
                    $('#btn-dispatchEmails').text(' Marcar Todos');
                    $('#btn-dispatchEmails').prepend("<i class='icon-check'></i>");
                }else{
                    $('input:checkbox').prop('checked', true);
                    $('#btn-dispatchEmails').val(1);
                    $('#btn-dispatchEmails').text(' Desmarcar Todos');
                    $('#btn-dispatchEmails').prepend("<i class='icon-check-empty'></i>");
                }
            })
        });
    </script>
@endsection