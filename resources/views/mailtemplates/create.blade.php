@extends('parent')

@section('title', 'Cadastrar Novo Modelo de E-mail')

@section('content')
@parent
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>
                Cadastrar Novo Modelo de E-mail
            </h1>

            <p class="alert alert-info rounded-0">
                <b>Atenção:</b>
                Os campos assinalados com * são de preenchimento obrigatório.
            </p>

            @include('mailtemplates.modals.instructionsForUse')
            <form method="POST"
                action="{{ route('mailtemplates.store', $mailtemplate) }}"
            >
                @csrf
                @include('mailtemplates.partials.form', ['buttonText' => 'Cadastrar'])
            </form>

        </div>
    </div>
</div>
@endsection


@section('javascripts_bottom')
 @parent
@endsection