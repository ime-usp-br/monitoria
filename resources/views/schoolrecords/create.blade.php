@extends('parent')

@section('title', 'Upload do histórico escolar')

@section('content')
@parent
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class='h5 font-weight-bold my-3 text-center'>
                Upload do histórico escolar
            </h1>

            <p class="alert alert-info rounded-0">
                <b>Atenção:</b>
                Faça o upload do histórico escolar(JupiterWeb) atualizado em formato PDF. 
            </p>
            <br>
            <form method='post' action="{{ route('schoolRecords.store') }}" enctype='multipart/form-data' >
                @csrf
                <div class="text-center" style="height: 50px;">
                <input  class="custom-form-input" type='file' name='file' >
                <button class="btn btn-outline-primary" type='submit' name='submit' >
                    <i class="fas fa-file-upload"></i>
                    Importar PDF
                </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection