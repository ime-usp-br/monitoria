
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
                     ] as $key=>$value)
                <option value='{"description":"{{$key}}","mail_class":"{{$value}}"}' {{ ( $mailtemplate->mail_class === $value) ? 'selected' : ''}}>{{ $key }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row custom-form-group">
    <div class="col-md-3">
        <div class="col-md-12 text-lg-left">
            <label for="sending_frequency">Frequência de envio*:</label>
        </div>
        <div class="col-md-8">
            <select class="custom-form-control" type="text" name="sending_frequency" id="sending_frequency"
            >
                    <option value="" {{ ($mailtemplate->sending_frequency) ? '' : 'selected'}}></option>

                @foreach ([
                            "Manual",
                            "Única",
                            "Mensal",
                        ] as $frequency)
                    <option value='{{$frequency}}' {{ ( $mailtemplate->sending_frequency === $frequency) ? 'selected' : ''}}>{{ $frequency }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div id="date-div" class="col-md-3">
        @if($mailtemplate->sending_frequency == "Única")
            <div class="col-md-12 text-lg-left">
                <label for="sending_date">Data*:</label>
            </div>
            <div class="col-md-8">
                <input  class="custom-form-control custom-datepicker" name="sending_date" autocomplete="off" value="{{ $mailtemplate->sending_date }}">
            </div>
        @elseif($mailtemplate->sending_frequency == "Mensal")
            <div class="col-md-12 text-lg-left">
                <label for="sending_date">Dia*:</label>
            </div>
            <div class="col-md-8">
                <input  class="custom-form-control" name="sending_date" value="{{ $mailtemplate->sending_date }}">
            </div>
        @endif
    </div>
    <div id="hour-div" class="col-md-3">
        @if($mailtemplate->sending_frequency == "Única" or $mailtemplate->sending_frequency == "Mensal")
            <div class="col-md-12 text-lg-left">
                <label for="sending_hour">Hora*:</label>
            </div>
            <div class="col-md-6">
                <input class="custom-form-control" name="sending_hour" type="time" value="{{ $mailtemplate->sending_hour }}">
            </div>
        @endif
    </div>
    <div class="col-md-3">
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