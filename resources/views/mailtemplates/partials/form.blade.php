
<div class="custom-form-group align-items-center">
    <div class="col-md-12 text-lg-left">
        <label for="body">Nome do Modelo*:</label>
    </div>
    <div class="col-md-12">
        <input class="custom-form-control" type="text" name="name" id="name"
            value="{{ old('name') ?? $mailtemplate->name ?? ''}}" 
        />
    </div>
</div>

<div class="custom-form-group align-items-center">
    <div class="col-md-12 text-lg-left">
        <label for="body">Aplicação*:</label>
    </div>
    <div class="col-md-12">
        <select class="custom-form-control" type="text" name="description_and_mail_class"
        >
                <option value="" {{ ($mailtemplate->mail_class) ? '' : 'selected'}}></option>

            @foreach ([
                        "E-mail enviado aos professores ao final do processo de seleção"=>"NotifyInstructorAboutSelectAssistant",
                        "E-mail enviado aos monitores ao final do processo de seleção"=>"NotifySelectStudent",
                        "E-mail enviado aos professores sobre o registro de frequência dos monitores"=>"NotifyInstructorAboutAttendanceRecord",
                        "E-mail enviado aos monitores sobre a auto avaliação"=>"NotifyStudentAboutSelfEvaluation",
                        "E-mail enviado aos professores sobre a avaliação dos monitores"=>"NotifyInstructorAboutEvaluation",
                     ] as $key=>$value)
                <option value='{"description":"{{$key}}","mail_class":"{{$value}}"}' {{ ( $mailtemplate->mail_class === $value) ? 'selected' : ''}}>{{ $key }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row custom-form-group">
    <div class="col-md">
        <div class="col-md-12 text-lg-left">
            <label for="sending_frequency">Frequência de envio*:</label>
        </div>
        <div class="col-md-10">
            <select class="custom-form-control" type="text" name="sending_frequency" id="sending_frequency"
            >
                    <option value="" {{ ($mailtemplate->sending_frequency) ? '' : 'selected'}}></option>

                @foreach ([
                            "Manual",
                            "Única",
                            "Mensal",
                            "Inicio do período de avaliação",
                            "Final do período de avaliação"
                        ] as $frequency)
                    <option value='{{$frequency}}' {{ ( $mailtemplate->sending_frequency === $frequency) ? 'selected' : ''}}>{{ $frequency }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div id="date-div" class="col-md">
        @if($mailtemplate->sending_frequency == "Única")
            <div class="col-12 text-left">
                <label for="sending_date">Data*:</label>
            </div>
            <div class="col-12">
                <input  class="custom-form-control custom-datepicker" style="max-width:130px" name="sending_date" autocomplete="off" value="{{ $mailtemplate->sending_date }}">
            </div>
        @elseif($mailtemplate->sending_frequency == "Mensal")
            <div class="col-12 text-left">
                <label for="sending_date">Dia*:</label>
            </div>
            <div class="col-12">
                <input  class="custom-form-control" style="max-width:80px" type="number" min="1" max="31" name="sending_date" value="{{ $mailtemplate->sending_date }}">
            </div>
        @elseif($mailtemplate->sending_frequency == "Inicio do período de avaliação")
            <div class="col-12 text-left">
                <label for="sending_date">Dias após o inicio do período*:</label>
            </div>
            <div class="col-12">
                <input  class="custom-form-control" style="max-width:80px" type="number" min="0" name="sending_date" value="{{ $mailtemplate->sending_date }}">
            </div>
        @elseif($mailtemplate->sending_frequency == "Final do período de avaliação")
            <div class="col-12 text-left">
                <label for="sending_date">Dias antes do final do período*:</label>
            </div>
            <div class="col-12">
                <input  class="custom-form-control" style="max-width:80px" type="number" min="0" name="sending_date" value="{{ $mailtemplate->sending_date }}">
            </div>
        @endif
    </div>
    <div id="hour-div" class="col-md">
        @if($mailtemplate->sending_frequency != "Manual")
            <div class="col-12 text-left">
                <label for="sending_hour">Hora*:</label>
            </div>
            <div class="col-12">
                <input class="custom-form-control" style="max-width:100px" name="sending_hour" type="time" value="{{ $mailtemplate->sending_hour }}">
            </div>
        @endif
    </div>
</div>

<div class="custom-form-group align-items-center">
    <div class="col-md-12 text-lg-left">
        <label for="body">Assunto*:</label>
    </div>
    <div class="col-md-12">
        <input class="custom-form-control" type="text" name="subject" id="subject"
            value="{{ old('subject') ?? $mailtemplate->subject ?? ''}}" 
        />
    </div>
</div>

<div class="custom-form-group align-items-center">
    <div class="col-md-12 text-lg-left">
        <label for="body">Corpo*:</label>
        <a  class="link" style="cursor: pointer;"
            data-toggle="modal"
            data-target="#instructionsForUseModal"
            title="Instruções de uso do e-mail" 
        >
            <i class="icon-code"></i>
            Instruções de Uso
        </a>
    </div>
    <div class="col-md-12">
        <textarea class="custom-form-control" name="body" id="bodymailtemplate">{{ old('body') ?? $mailtemplate->body ?? ''}}</textarea>
    </div>
</div>

<div class="row">
    <div class="col-4 d-none d-lg-block"></div>
    <div class="col-md-12 col-lg-6">
        <button type="submit" class="btn btn-outline-dark">
            {{ $buttonText }}
        </button>
        <a class="btn btn-outline-dark"
            href="{{ route('mailtemplates.index') }}"
        >
            Cancelar
        </a>
    </div>
</div>