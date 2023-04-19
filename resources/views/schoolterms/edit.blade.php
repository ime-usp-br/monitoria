@extends('parent')

@section('title', 'Editar período letivo')

@section('content')
@parent
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class='h5 font-weight-bold my-3'>
                Editar período letivo
            </h1>

            <p class="alert alert-info rounded-0">
                <b>Atenção:</b>
                Os campos assinalados com * são de preenchimento obrigatório.
            </p>

            <form method="POST"
                action="{{ route('schoolterms.update', $periodo) }}"
                enctype='multipart/form-data'
            >
                @method('patch')
                @csrf

                @include('schoolterms.partials.form', ['buttonText' => 'Salvar'])
            </form>
        </div>
    </div>
</div>
@endsection