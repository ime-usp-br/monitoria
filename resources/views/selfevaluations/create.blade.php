@extends('parent')

@section('title', 'Cadastrar Auto Avaliação')

@section('content')
@parent
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class='text-center'>
                Cadastrar Relatório das Atividades Desenvolvidas
            </h1>

            <h4 class='text-center mb-5'>{{ $selection->schoolclass->schoolterm->period . ' de ' . $selection->schoolclass->schoolterm->year }}</h4>

            <form method="POST"
                action="{{ route('selfevaluations.store') }}"
            >
                @csrf
                <input name="selection_id" value="{{$selection->id}}" type="hidden">
                <input name="selection_hash" value="{{ Illuminate\Support\Facades\Hash::make(json_encode($selection->toArray())) }}" type="hidden">

                @include('selfevaluations.partials.form', ['buttonText' => 'Cadastrar'])
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