@extends('parent')

@section('title', 'Editar Avaliação de Monitor')

@section('content')
@parent
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class='text-center'>
                Editar Avaliação de Monitor
            </h1>

            <h4 class='text-center mb-5'>{{ $selection->schoolclass->schoolterm->period . ' de ' . $selection->schoolclass->schoolterm->year }}</h4>

            <form method="POST"
                action="{{ route('instructorevaluations.update', $selection->instructorevaluation) }}"
            >
                @csrf
                @method("patch")
                @include('instructorevaluations.partials.form', ['buttonText' => 'Editar'])
            </form>
        </div>
    </div>
</div>
@endsection

@section('javascripts_bottom')
 @parent
<script>
    tinymce.init({
    selector: '#tinymcetextarea',
    plugins: 'link,code',
    link_default_target: '_blank'
    });
</script>
@endsection