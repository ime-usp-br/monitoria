@extends('parent')

@section('title', 'Docentes')

@section('content')
@parent
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Docentes</h1>

            <p class="text-right">
                <button class="btn btn-primary" id="btn-search" data-toggle="modal" data-target="#instructorsSearchModal">
                    <i class="fas fa-search"></i>
                    Buscar
                </button>
            </p>

            @include('instructors.modals.search')

            @if (count($docentes) > 0)
                <table class="table table-bordered table-striped table-hover">
                    <tr class="text-center">
                        <th>Nome</th>
                        <th>N.° USP</th>
                        <th>E-mail</th>
                        <th>Departamento</th>
                        <th>Solicitações de monitores</th>
                    </tr>

                    @foreach($docentes as $docente)
                        <tr>
                            <td>{{ $docente->nompes }}</td>
                            <td class="text-center">{{ $docente->codpes }}</td>
                            <td>{{ $docente->codema }}</td>
                            <td class="text-center">{{ $docente->department->nomabvset }}</td>
                            <td class="text-center">
                                @if($docente->requisitions()->exists())
                                    <a 
                                        data-toggle="tooltip" data-placement="top"
                                        title="Solicitações"
                                        href="{{ route('instructors.requisitions', $docente) }}"
                                        class="btn btn-outline-dark btn-sm"
                                    >
                                        Solicitações
                                    </a>
                                @else
                                    Nenhuma Solicitação
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p class="text-center">Não há docentes cadastrados</p>
            @endif
        </div>
    </div>
</div>
@endsection