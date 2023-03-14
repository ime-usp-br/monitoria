@extends('parent')

@section('title', 'Editar inscrição')

@section('content')
@parent
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class='text-center'>
                Editar Inscrição
            </h3>

            <form method="POST"
                action="{{ route('enrollments.update', $inscricao) }}"
            >
                @method('patch')
                @csrf

                @include('enrollments.partials.form', ['buttonText' => 'Editar'])
            </form>
        </div>
    </div>
</div>
@endsection