@extends('parent')

@section('title', 'Editar inscrição')

@section('content')
@parent
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h3 class='font-weight-bold my-3 text-center'>
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