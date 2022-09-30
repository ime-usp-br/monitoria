@extends('parent')

@section('title', 'Editar Modelo de E-mail')

@section('content')
@parent
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>
            Editar Modelo de E-mail
            </h1>

            <p class="alert alert-info rounded-0">
                <b>Atenção:</b>
                Os campos assinalados com * são de preenchimento obrigatório.
            </p>

            @include('mailtemplates.modals.instructionsForUse')
            <form method="POST"
                action="{{ route('mailtemplates.update', $mailtemplate) }}"
            >
                @csrf
                @method("patch")
                @include('mailtemplates.partials.form', ['buttonText' => 'Editar'])
            </form>

        </div>
    </div>
</div>
@endsection


@section('javascripts_bottom')
 @parent
<script>
    $("#sending_frequency").change(function(){
        var value = $("#sending_frequency option:selected").val();
        var datediv = $("#date-div");
        var hourdiv = $("#hour-div");
        $("#date-div").empty();
        $("#hour-div").empty();
        if(value=="Única"){
            datediv.append('<div class="col-md-12 text-lg-left">'+
                            '<label for="sending_date">Data*:</label>'+
                           '</div>'+
                           '<div class="col-md-8">'+
                            '<input  class="custom-form-control custom-datepicker" name="sending_date" autocomplete="off">'+
                           '</div>');
            hourdiv.append('<div class="col-md-12 text-lg-left">'+
                            '<label for="sending_hour">Hora*:</label>'+
                           '</div>'+
                           '<div class="col-md-6">'+
                            '<input class="custom-form-control" name="sending_hour" type="time">'+
                            '</div>');
            $('.custom-datepicker').datepicker({showOn: 'both',buttonText: '<i class="far fa-calendar"></i>'});
        }else if(value=="Mensal"){
            datediv.append('<div class="col-md-12 text-lg-left">'+
                            '<label for="sending_date">Dia*:</label>'+
                           '</div>'+
                           '<div class="col-md-4">'+
                            '<input  class="custom-form-control" name="sending_date">'+
                           '</div>');
            hourdiv.append('<div class="col-md-12 text-lg-left">'+
                            '<label for="sending_hour">Hora*:</label>'+
                           '</div>'+
                           '<div class="col-md-6">'+
                            '<input  class="custom-form-control" name="sending_hour" type="time">'+
                           '</div>');
        }
    });
    tinymce.init({
    selector: '#bodymailtemplate',
    plugins: 'link,code',
    link_default_target: '_blank'
    });
</script>
@endsection