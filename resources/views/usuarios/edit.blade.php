@extends('parent')

@section('title', 'Editar perfis')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class='h5 font-weight-bold my-3'>
                Editar usuário {{ $usuario->name }}
            </h1>

            <p class="alert alert-info rounded-0">
                <b>Atenção:</b>
                Os campos assinalados com * são de preenchimento obrigatório.
            </p>

            <form method="POST"
                action="{{ route('usuarios.update', $usuario) }}"
            >
                @method('patch')
                @csrf

                @include('usuarios.partials.form', ['buttonText' => 'Editar'])
            </form>
        </div>
    </div>
</div>
@endsection