@extends('laravel-usp-theme::master')

@section('styles')
  @parent
  <link rel="stylesheet" href="{{ asset('css/listmenu_v.css').'?version=2' }}" />
@endsection

@section('javascripts_bottom')
@parent
  @vite('resources/js/app.js')
@endsection

@section('content')
@if(Auth::check())
  <div id="layout_menu">
      <ul id="menulateral" class="menulist">
          <li class="menuHeader">Acesso Restrito</li>
          <li>
              <a href="{{ route('home') }}">Página Inicial</a>
          </li>
          @can("editar usuario")
              <li>
                  <a href="{{ route('users.index') }}">Usuários</a>
                  <ul>
                      <li>
                          <a href="{{ route('users.loginas') }}">Logar Como</a>
                      </li>
                  </ul>
              </li>
          @endcan
          @can("visualizar periodo letivo")
              <li>
                  <a href="{{ route('schoolterms.index') }}">Períodos Letivos</a>

                  <ul>
                      <li>
                          <a href="{{ route('schoolterms.create') }}">Cadastrar</a>
                      </li>
                  </ul>
              </li>
          @endcan
          @can("visualizar turma")
              <li>
                  <a href="{{ route('schoolclasses.index') }}">Turmas</a>
              </li>
          @endcan
          @can("visualizar docente")
              <li>
                  <a href="{{ route('instructors.index') }}">Docentes</a>
              </li>
          @endcan
          @can("visualizar todos inscritos")
              <li>
                  <a href="{{ route('enrollments.showAll') }}">Inscritos</a>
              </li>
          @endcan
          @can("visualizar monitores")
              <li>
                  <a href="{{ route('tutors.index') }}">Monitores</a>
              </li>
          @endcan
          @can("Editar E-mails")
              <li>
                  <a href="{{ route('mailtemplates.index') }}">E-mails</a>
              </li>
          @endcan
          @can("Visualizar auto avaliações")
              <li>
                  <a href="{{ route('selfevaluations.index') }}">Auto Avaliações</a>
              </li>
          @endcan
          @can("Visualizar avaliações dos docentes")
              <li>
                  <a href="{{ route('instructorevaluations.index') }}">Avaliações dos Docentes</a>
              </li>
          @endcan
          @if(Auth::user()->hasRole("Administrador"))
              <li>
                  <a href="{{ route('olddb.index') }}">Importar DB Antigo</a>
              </li>
          @endif
          <li>
              <form style="padding:0px;" action="{{ route('logout') }}" method="POST" id="logout_form2">
                  @csrf
                  <a href="#" id="logout-link">Sair</a>
              </form>
          </li>
      </ul>
      @php
            $hasSelection = App\Models\Selection::whereHas("student", function($query){$query->where("codpes",Auth::user()->codpes);})->get()->isNotEmpty();
      @endphp

      @if(Auth::user()->hasAnyPermission([
            "criar solicitação de monitor",
            "fazer inscrição",
            "Selecionar monitor",
            "Disparar emails",
            "gerar relatorio",
            "Emitir Atestado",
        ]) or $hasSelection)
      <ul id="menulateral" class="menulist mt-1">
          <li class="menuHeader">Ações</li>
          @can("criar solicitação de monitor")
              <li>
                  <a href="{{ route('requisitions.index') }}">Solicitar Monitor</a>
              </li>
          @endcan
          @can("fazer inscrição")
              <li>
                  <a href="{{ route('enrollments.index') }}">Fazer Inscrição</a>
              </li>
          @endcan
          @if(Auth::user()->hasRole("Aluno"))
              <li>
                  <a href="{{ route('selfevaluations.studentIndex') }}">Auto Avaliação</a>
              </li>
          @endif
          @if(Auth::user()->hasRole("Docente"))
              <li>
                  <a href="{{ route('frequencies.index') }}">Registrar Frequências</a>
              </li>
          @endif
          @if(Auth::user()->hasRole("Docente"))
              <li>
                  <a href="{{ route('instructorevaluations.instructorIndex') }}">Avaliações dos Monitores</a>
              </li>
          @endif
          @can("Selecionar monitor")
              <li>
                  <a href="{{ route('selections.index') }}">Selecionar Monitores</a>
              </li>
          @endcan
          @can("Disparar emails")
              <li>
                  <a href="{{ route('emails.index') }}">Disparar E-mails</a>
              </li>
          @endcan
          @can("gerar relatorio")
              <li>
                  <a href="{{ route('reports.index') }}">Emitir Relatório</a>
              </li>
          @endcan
          @if($hasSelection or Auth::user()->can("Emitir Atestado"))
              <li>
                  <a href="{{ route('certificates.index') }}">Emitir Atestado</a>
              </li>
          @endif
      </ul>
      @endif
  </div>
@endif
<div id="layout_conteudo">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="flash-message">
    @foreach (['danger', 'warning', 'success', 'info'] as $msg)
        @if(Session::has('alert-' . $msg))
        <p class="alert alert-{{ $msg }}">{{ Session::get('alert-' . $msg) }}</p>
        <?php Session::forget('alert-' . $msg) ?>
        @endif
    @endforeach
    </div>
</div>
@endsection