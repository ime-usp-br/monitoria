@extends('parent')

@section('title', 'Informar sobre Auto Avaliações')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-3'>Informar sobre Auto Avaliações</h1>


            <h4 class='text-center mb-5'>{{ $schoolterm->period . ' de ' . $schoolterm->year }}</h4>
            <form method="POST"
                action="{{ route('emails.triggerSelfEvaluations') }}"
            >
            @csrf

            <p class="alert alert-info rounded-0">
                <b>Atenção:</b>
                Lembre-se que o monitor só poderar cadastrar a auto avaliação de {{ $schoolterm->start_date_evaluations }} à {{ $schoolterm->end_date_evaluations }}.
            </p>
            
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
            @if (count($selections) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th></th>
                        <th>Monitor</th>
                        <th>Código da Disciplina</th>
                        <th>Código da Turma</th>
                        <th>Nome da Disciplina</th>
                        <th>Prof(a) Solicitante</th>
                    </tr>

                    @foreach($selections as $selection)
                        <tr style="font-size:12px;">
                            <td>
                                <input id="selections_id" class="checkbox" type="checkbox" name="selections_id[]" value="{{ $selection->id }}">
                            </td>
                            <td style="white-space: nowrap;">
                                {{ $selection->student->nompes }}
                            </td>
                            <td>{{ $selection->schoolclass->coddis }}</td>
                            <td>{{ $selection->schoolclass->codtur }}</td>
                            <td>{{ $selection->schoolclass->nomdis }}</td>
                            <td style="white-space: nowrap;">
                                {{ $selection->requisition->instructor->nompes }}
                            </td>
                        </tr>
                    @endforeach
                </table>
            </form>
            @else
                <p class="text-center">Não foram encontradas auto avaliações por se fazer</p>
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