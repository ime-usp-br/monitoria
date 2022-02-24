@extends('parent')

@section('title', 'Editar turma')

@section('content')
@parent
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class='h5 font-weight-bold my-3'>
                Editar turma
            </h1>

            <p class="alert alert-info rounded-0">
                <b>Atenção:</b>
                Os campos assinalados com * são de preenchimento obrigatório.
            </p>

            <form method="POST"
                action="{{ route('groups.update', $turma) }}"
            >
                @method('patch')
                @csrf

                @include('groups.partials.form', ['buttonText' => 'Editar'])
            </form>

            @include('groups.modals.addClassSchedule')
            @include('groups.modals.addInstructor')
        </div>
    </div>
</div>
@endsection