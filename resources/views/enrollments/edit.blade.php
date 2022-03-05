@extends('parent')

@section('title', 'Editar inscrição')

@section('content')
@parent
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class='h5 font-weight-bold my-3'>
                Editar inscrição
            </h1>

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