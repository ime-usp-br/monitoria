@extends('parent')

@section('title', 'Modelos de E-mails')

@section('content')
@parent
<div id="layout_conteudo">
    <div class="justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>E-mails</h1>

            @include('mailtemplates.modals.testTemplate')
            <p class="text-right">
                <a  class="btn btn-outline-primary my-1"
                    title="Cadastrar Novo Modelo" 
                    href="{{ route('mailtemplates.create') }}"
                >
                    <i class="fas fa-plus-circle"></i>
                    Cadastrar Novo Modelo
                </a>
                <a  id="btn-addTestTemplateModal"
                    class="btn btn-outline-primary my-1"
                    data-toggle="modal"
                    data-target="#testTemplateModal"
                    title="Enviar E-mail de Teste" 
                >
                    <i class="icon-envelope"></i>
                    Testar Modelo
                </a>
            </p>

            @if (count($mailtemplates) > 0)
                <table class="table table-bordered table-striped table-hover" style="font-size:12px;">
                    <tr>
                        <th>Nome do Modelo</th>
                        <th>Descrição</th>
                        <th>Frequência</th>
                        <th>Em Uso</th>
                        <th></th>
                    </tr>

                    @foreach($mailtemplates as $mailtemplate)
                        <tr style="font-size:12px;">
                            <td style="text-align: center">{{ $mailtemplate->name }}</td>
                            <td>{{ $mailtemplate->description }}</td>
                            <td style="text-align: center">
                                @if($mailtemplate->sending_frequency == "Única")
                                    {{  "Única - " .$mailtemplate->sending_date . " às " .$mailtemplate->sending_hour }}
                                @elseif($mailtemplate->sending_frequency == "Mensal")
                                    {{  "Mensal - Dia " .$mailtemplate->sending_date . " às " .$mailtemplate->sending_hour }}
                                @elseif($mailtemplate->sending_frequency == "Inicio do período de avaliação")
                                    @if($mailtemplate->sending_date==0)
                                        {{  "Primeiro dia do período de avaliação às " .$mailtemplate->sending_hour }}
                                    @else
                                        {{  $mailtemplate->sending_date ." dia".( $mailtemplate->sending_date > 1 ? "s" : "" )." após o inicio do período de avaliação às " .$mailtemplate->sending_hour }}
                                    @endif
                                @elseif($mailtemplate->sending_frequency == "Final do período de avaliação")
                                    @if($mailtemplate->sending_date==0)
                                        {{  "Ultimo dia do período de avaliação às " .$mailtemplate->sending_hour }}
                                    @else
                                        {{  $mailtemplate->sending_date ." dia".( $mailtemplate->sending_date > 1 ? "s" : "" )." antes do fim do período de avaliação às " .$mailtemplate->sending_hour }}
                                    @endif
                                @else
                                    {{ $mailtemplate->sending_frequency }}
                                @endif

                            </td>
                            <td style="text-align: center">
                                {{ $mailtemplate->active ? "Sim" : "Não" }}
                                @if($mailtemplate->active)
                                    <a href="{{ route('mailtemplates.deactivate', ['mailtemplate'=>$mailtemplate->id]) }}" title="Desabilitar Modelo"><i style="color:green" class="fa fa-toggle-on"></i></a>
                                @else
                                    <a href="{{ route('mailtemplates.activate', ['mailtemplate'=>$mailtemplate->id]) }}" title="Habilitar Modelo"><i style="color:red" class="fa fa-toggle-off"></i></a>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('mailtemplates.edit', ['mailtemplate'=>$mailtemplate->id]) }}" 
                                class="btn px-0" title="Editar Modelo">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="post"  action="{{ route('mailtemplates.destroy',$mailtemplate) }}" style="display: inline;">
                                    @method('delete')
                                    @csrf
                                    <button class="btn px-0"
                                        onclick="return confirm('Você tem certeza que deseja excluir esse modelo?')" 
                                        title="Excluir Modelo" >
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </form>
            @else
                <p class="text-center">Não há modelos de e-mails cadastrados.</p>
            @endif
        </div>
    </div>
</div>
@endsection
