@extends('laravel-usp-theme::master')

@section('styles')
  @parent
  <link rel="stylesheet" href="{{ asset('css/app.css') }}" />
@endsection

@section('javascripts_bottom')
@parent
  <script type="text/javascript">
    let baseURL = "{{ env('APP_URL') }}";
  </script>
  <script type="text/javascript" src="{{ asset('js/app.js') }}"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  <script src="{{ asset('js/datepicker-pt-BR.js') }}"></script>
@endsection


@section('content')
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@endsection