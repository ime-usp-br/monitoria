@extends('parent')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Confirmar Frequência</h4>
                </div>

                <div class="card-body">
                    @if(session('alert-warning'))
                        <div class="alert alert-warning">{{ session('alert-warning') }}</div>
                    @endif

                    @if(session('alert-success'))
                        <div class="alert alert-success">{{ session('alert-success') }}</div>
                    @endif

                    <p><strong>Monitor:</strong> {{ $frequency->student->nompes }} ({{ $frequency->student->codpes }})</p>
                    <p><strong>Disciplina:</strong> {{ $frequency->schoolclass->coddis }} - {{ $frequency->schoolclass->nomdis }}</p>
                    <p><strong>Mês:</strong> {{ $meses[$frequency->month] }}</p>
                    <p><strong>Status atual:</strong> {{ $frequency->registered ? 'Registrado' : 'Não registrado' }}</p>

                    @if($blockedReason)
                        <div class="alert alert-warning">{{ $blockedReason }}</div>

                        <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('frequencies.show', ['schoolclass' => $frequency->schoolclass->id, 'tutor' => $frequency->student->id]) }}"
                           class="btn btn-secondary">Voltar</a>
                    @else
                        <form method="POST"
                              action="{{ \Illuminate\Support\Facades\URL::signedRoute('frequencies.update', ['frequency' => $frequency->id]) }}">
                            @csrf
                            @method('PATCH')

                            <button type="submit" class="btn btn-success">
                                Confirmar {{ $frequency->registered ? 'desmarcação' : 'registro' }}
                            </button>

                            <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('frequencies.show', ['schoolclass' => $frequency->schoolclass->id, 'tutor' => $frequency->student->id]) }}"
                               class="btn btn-secondary">Cancelar</a>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
