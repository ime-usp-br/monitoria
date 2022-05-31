<html>
    <body>
        <p>A{{ $instructor->getPronounTreatment() == 'Prof. Dr. ' ? 'o' : ''}} {{ $instructor->getPronounTreatment() }} {{ $instructor->nompes }},</p>
        <p></p>
        <p>
            Informamos que já é possível <a href="{{ $link }}">registrar a frequência do monitor</a> <strong>{{$student->nompes}}</strong> da disciplina <strong>{{$schoolclass->nomdis}}</strong> turma <strong>{{$schoolclass->codtur}}</strong> referente ao mês {{$month}} do {{$period}} de {{$year}}.
        </p>
        <p></p> <br>
        <p>Lembretes:</p>
        <p>
            Cadastrar mensalmente a frequência de seu aluno-monitor no Sistema de Monitoria entre os dias 25 e o último dia do mês vigente, durante 
            o período de duração da monitoria;
        </p>
        <p>
            No caso dos monitores bolsistas (remunerados), a ausência do cadastramento da frequência no período estipulado poderá ocasionar o não 
            pagamento da bolsa de monitoria na data prevista para o crédito.
        </p>
        <p>
            Ao cadastrar a frequência do último mês, realizar, em campo específico do sistema, a avaliação de desempenho de cada monitor sob sua 
            supervisão;
        </p>
        <p>
            Informar a Comissão de Monitoria caso haja desistência ou desligamento de qualquer monitor sob sua supervisão.
        </p> <br>
        <p>
            Essa mensagem foi gerada automaticamente pelo <a href="{{ url('') }}">Sistema de Monitoria</a>
        </p>

    </body>
    <head>
        <style>

            .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
            }

            .justify-content-start {
            justify-content: flex-start !important;
            }

            .container {
            min-width: 992px !important;
            }

            .col-md-auto {
            flex: 0 0 auto;
            width: auto;
            max-width: 100%;
            }

            .table {
            width: 100%;
            max-width: 100%;
            margin-bottom: 1rem;
            }

            .table th,
            .table td {
            padding: 0.75rem;
            vertical-align: top;
            border-top: 1px solid #eceeef;
            }

            .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #eceeef;
            }

            .table tbody + tbody {
            border-top: 2px solid #eceeef;
            }

            .table .table {
            background-color: #fff;
            }

            .table-sm th,
            .table-sm td {
            padding: 0.3rem;
            }

            .table-bordered {
            border: 1px solid #eceeef;
            }

            .table-bordered th,
            .table-bordered td {
            border: 1px solid #eceeef;
            }

            .table-bordered thead th,
            .table-bordered thead td {
            border-bottom-width: 2px;
            }

            .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.05);
            }

            .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
            }

            .table-active,
            .table-active > th,
            .table-active > td {
            background-color: rgba(0, 0, 0, 0.075);
            }

            .table-hover .table-active:hover {
            background-color: rgba(0, 0, 0, 0.075);
            }

            .table-hover .table-active:hover > td,
            .table-hover .table-active:hover > th {
            background-color: rgba(0, 0, 0, 0.075);
            }

            .table-success,
            .table-success > th,
            .table-success > td {
            background-color: #dff0d8;
            }

            .table-hover .table-success:hover {
            background-color: #d0e9c6;
            }

            .table-hover .table-success:hover > td,
            .table-hover .table-success:hover > th {
            background-color: #d0e9c6;
            }

            .table-info,
            .table-info > th,
            .table-info > td {
            background-color: #d9edf7;
            }

            .table-hover .table-info:hover {
            background-color: #c4e3f3;
            }

            .table-hover .table-info:hover > td,
            .table-hover .table-info:hover > th {
            background-color: #c4e3f3;
            }

            .table-warning,
            .table-warning > th,
            .table-warning > td {
            background-color: #fcf8e3;
            }

            .table-hover .table-warning:hover {
            background-color: #faf2cc;
            }

            .table-hover .table-warning:hover > td,
            .table-hover .table-warning:hover > th {
            background-color: #faf2cc;
            }

            .table-danger,
            .table-danger > th,
            .table-danger > td {
            background-color: #f2dede;
            }

            .table-hover .table-danger:hover {
            background-color: #ebcccc;
            }

            .table-hover .table-danger:hover > td,
            .table-hover .table-danger:hover > th {
            background-color: #ebcccc;
            }

            .thead-inverse th {
            color: #fff;
            background-color: #292b2c;
            }

            .thead-default th {
            color: #464a4c;
            background-color: #eceeef;
            }

            .table-inverse {
            color: #fff;
            background-color: #292b2c;
            }

            .table-inverse th,
            .table-inverse td,
            .table-inverse thead th {
            border-color: #fff;
            }

            .table-inverse.table-bordered {
            border: 0;
            }

            .table-responsive {
            display: block;
            width: 100%;
            overflow-x: auto;
            -ms-overflow-style: -ms-autohiding-scrollbar;
            }

            .table-responsive.table-bordered {
            border: 0;
            }
        </style>
    </head>
</html>