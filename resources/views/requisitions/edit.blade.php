@extends('parent')

@section('title', 'Formulário de Requisição de Monitor(es)')

@section('content')
@parent
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class='h5 font-weight-bold my-3 text-center'>
                Formulário de Requisição de Monitor(es)
            </h1>

            <p class="alert alert-info rounded-0 text-center">
                <b>Atenção:</b>
                A indicação de alunos é apenas uma sugestão, a escolha final será feita pela comissão de monitoria. 
            </p>

            <form method="POST"
                action="{{ route('requisitions.update', $turma->requisition) }}"
            >
                @method('patch')
                @csrf
                @include('requisitions.partials.form', ['buttonText' => 'Editar'])
            </form>
        </div>
    </div>
</div>
@endsection