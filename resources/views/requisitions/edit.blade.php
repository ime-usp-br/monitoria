@extends('parent')

@section('title', 'Formulário de Requisição de Monitor(es)')

@section('content')
@parent
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class='text-center'>
                Formulário de Requisição de Monitor(es)
            </h1>
            
            <form method="POST"
                action="{{ route('requisitions.update', $turma->requisition) }}"
            >
                @method('patch')
                @csrf
                @include('requisitions.partials.form', ['buttonText' => 'Editar'])
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