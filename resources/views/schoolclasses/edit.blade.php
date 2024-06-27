@extends('parent')

@section('title', 'Editar turma')

@section('content')
@parent
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class='text-center'>
                Editar turma
            </h1>

            <p class="alert alert-info rounded-0">
                <b>Atenção:</b>
                Os campos assinalados com * são de preenchimento obrigatório.
            </p>

            <form method="POST"
                action="{{ route('schoolclasses.update', $turma) }}"
            >
                @method('patch')
                @csrf

                @include('schoolclasses.partials.form', ['buttonText' => 'Salvar'])
            </form>

            @include('schoolclasses.modals.addClassSchedule')
            @include('schoolclasses.modals.addInstructor')
        </div>
    </div>
</div>
@endsection