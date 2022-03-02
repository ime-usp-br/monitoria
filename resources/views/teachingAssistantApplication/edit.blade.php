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

            <form method="POST"
                action="{{ route('requestAssistant.update', $turma->teachingAssistantApplication) }}"
            >
                @method('patch')
                @csrf
                @include('teachingAssistantApplication.partials.form', ['buttonText' => 'Editar'])
            </form>
        </div>
    </div>
</div>
@endsection