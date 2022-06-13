@extends('parent')

@section('title', 'Período Letivo')

@section('content')
@parent

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h1 class='text-center mb-5'>Sistema de Monitoria</h1>

            <p>O Programa de Monitoria do IME-USP tem o objetivo de incentivar alunos com destacado desempenho acadêmico a obterem 
                    vivência em ensino-aprendizagem através da assistência supervisionada a alunos e docentes nas disciplinas da graduação, 
                    fornecendo às turmas um canal acessível para atendimento de dúvidas.</p>

            @php $schoolterm = App\Models\SchoolTerm::getOpenSchoolTerm(); @endphp
            @if($schoolterm)
                <form method="POST" action="{{ route('schoolterms.download') }}" target="_blank">
                    @csrf
                    <input type='hidden' name='path' value="{{ $schoolterm->public_notice_file_path }}">
                    <button class="btn btn-link"
                        data-toggle="tooltip" data-placement="top"
                        title="Baixar Edital"
                    >
                        Edital de Seleção - Monitoria {{ $schoolterm->period . " " . $schoolterm->year }} - Período de inscrição 
                        de {{ $schoolterm->start_date_enrollments }} até {{ $schoolterm->end_date_enrollments }}
                    </button>
                </form>       
            @endif
        </div>
    </div>
</div>

@endsection