@extends('parent')

@section('title', 'Cadastrar período letivo')

@section('content')
@parent

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class='h5 font-weight-bold my-3'>Cadastrar período letivo</h1>

            <p class="alert alert-info rounded-0">
                <b>Atenção:</b>
                Os campos assinalados com * são de preenchimento obrigatório.
            </p>

            <form method="POST" action="{{ route('schoolterms.store') }}" enctype='multipart/form-data'>
                @csrf
                @include('schoolterms.partials.form', ['buttonText' => 'Cadastrar'])
            </form>
        </div>
    </div>
</div>
@endsection