@extends('parent')

@section('title', 'Disparo Manual de E-mails')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Disparo Manual de E-mails</h1>

            <ul>
                <li>
                    <a href="{{ route('emails.indexSelections') }}">E-mail aos docentes e monitores informando o resultado do processo de seleção</a>
                </li>
                <li>
                    <a href="{{ route('emails.indexAttendanceRecords') }}">E-mail aos docentes sobre o registro de frequência dos monitores</a>
                </li>
                <li>
                    <a href="{{ route('emails.indexSelfEvaluations') }}">E-mail aos monitores sobre a auto avaliação</a>
                </li>
                <li>
                    <a href="{{ route('emails.indexInstructorEvaluations') }}">E-mail aos professores sobre a avaliação dos monitores</a>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection