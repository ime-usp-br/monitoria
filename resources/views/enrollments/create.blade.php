@extends('parent')

@section('title', 'Formulário de Requisição de Monitor(es)')

@section('content')
@parent
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class='text-center'>
                Concurso Interno de Monitores<br>
                Ficha de Inscrição
            </h3>

            <form method="POST"
                action="{{ route('enrollments.store') }}"
            >
                <input name="school_class_id" value="{{$turma->id}}" type="hidden">
                @csrf
                @include('enrollments.partials.form', ['buttonText' => 'Cadastrar'])
            </form>
        </div>
    </div>
</div>
@endsection