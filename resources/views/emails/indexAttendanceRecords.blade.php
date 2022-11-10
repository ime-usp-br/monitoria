@extends('parent')

@section('title', 'Informar sobre Frequências')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-3'>Informar sobre Frequências</h1>

            @php
                $meses = [1=>"janeiro", 2=>"fevereiro", 3=>"março", 4=>"abril", 5=>"maio", 6=>"junho", 7=>"julho", 8=>"agosto", 9=>"setembro", 10=>"outubro", 11=>"novembro", 12=>"dezembro"];
            @endphp

            @include('emails.modals.chooseMonth')

            @if (count($frequencies) > 0)
                <h4 class='text-center mb-5'>{{ ucfirst($meses[$month]) . ' de ' . $schoolterm->year }}</h4>

                <form method="POST"
                    action="{{ route('emails.triggerAttendanceRecords') }}"
                >
                @csrf
            @endif

            <p class="text-right">
                <a  id="btn-chooseMonthModal"
                    class="btn btn-outline-primary"
                    data-toggle="modal"
                    data-target="#chooseMonthModal"
                    title="Escolher Outro Mês" 
                >
                    Escolher Mês
                </a>

                @if (count($frequencies) > 0)
                    <button id="btn-dispatchEmails"type="button" class="btn btn-outline-primary" value=0>
                        <i class="icon-check"></i>
                        Marcar Todos
                    </button>
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-envelope"></i>
                        Disparar e-mails
                    </button>
                @endif
            </p>

            @if (count($frequencies) > 0)

                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th></th>
                        <th>Código da Disciplina</th>
                        <th>Código da Turma</th>
                        <th>Nome da Disciplina</th>
                        <th>Prof(a) Solicitante</th>
                        <th>Monitor</th>
                    </tr>

                    @foreach($frequencies as $frequency)
                        <tr style="font-size:12px;">
                            <td>
                                <input id="frequencies_id" class="checkbox" type="checkbox" name="frequencies_id[]" value="{{ $frequency->id }}">
                            </td>
                            <td>{{ $frequency->schoolclass->coddis }}</td>
                            <td>{{ $frequency->schoolclass->codtur }}</td>
                            <td>{{ $frequency->schoolclass->nomdis }}</td>
                            <td style="white-space: nowrap;">
                                {{ $frequency->schoolclass->requisition->instructor->nompes }}
                            </td>
                            <td style="white-space: nowrap;">
                                {{ $frequency->student->nompes }}
                            </td>
                        </tr>
                    @endforeach
                </table>
            </form>
            @else
                <p class="text-center mt-5">Não foram encontradas frequências a serem registradas em {{ $meses[$month] . ' de ' . $schoolterm->year }}</p>
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