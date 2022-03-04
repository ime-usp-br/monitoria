<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label>Nome </label>
    </div>
    <div class="col-12 col-md-5">
        <a >{{ Auth::user()->name }} </a>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label>N.° USP </label>
    </div>
    <div class="col-12 col-md-5">
        <a >{{ Auth::user()->codpes }} </a>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label>E-mail *</label>
    </div>
    <div class="col-12 col-md-5">
        <input class="custom-form-control" type="text" name="codema" id="codema"
            value='{{ Auth::user()->email }}'
        />
    </div>
</div>

@php
    use App\Models\Student;
    $replicado = Student::getFromReplicadoByCodpes(Auth::user()->codpes);
@endphp

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label for="sexo">Sexo *</label>
    </div>
    <div class="col-12 col-md-2">
        <select class="custom-form-control" type="text" name="sexo"
            id="sexo"
        >
            @foreach ([
                        'M'=>'Masculino',
                        'F'=>'Feminino',
                     ] as $key=>$sexo)
                <option value="{{ $sexo }}" {{ ($replicado[0]['sexpes'] == $key) ? 'selected' : ''}}>{{ $sexo }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label>RG *</label>
    </div>
    <div class="col-12 col-md-2">
        <input class="custom-form-control" type="text" name="rg" id="rg"
            value='{{ $replicado[0]['tipdocidf'] == 'RG' ? $replicado[0]['numdocidf'] : '' }}'
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label>CPF *</label>
    </div>
    <div class="col-12 col-md-2">
        <input class="custom-form-control" type="text" name="cpf" id="cpf"
            value='{{ $replicado[0]['numcpf'] }}'
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label>Endereço *</label>
    </div>
    <div class="col-12 col-md-5">
        <input class="custom-form-control" type="text" name="endereco" id="endereco"
            value='{{ $replicado[0]['nomtiplgr'] . " " . $replicado[0]['epflgr'] . ", " . $replicado[0]['numlgr']}}'
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label>Complemento </label>
    </div>
    <div class="col-12 col-md-5">
        <input class="custom-form-control" type="text" name="complemento" id="complemento"
            value=''
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label>CEP *</label>
    </div>
    <div class="col-12 col-md-2">
        <input class="custom-form-control" type="text" name="cep" id="cep"
            value='{{ $replicado[0]['codendptl'] }}'
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label>Bairro *</label>
    </div>
    <div class="col-12 col-md-5">
        <input class="custom-form-control" type="text" name="bairro" id="bairro"
            value='{{ $replicado[0]['nombro'] }}'
        />
    </div>
</div>

<div class="row custom-form-group align-items-center">
    <div class="col-12 col-lg-4 text-lg-right">
        <label>Cidade *</label>
    </div>
    <div class="col-12 col-md-2">
        <input class="custom-form-control" type="text" name="cidade" id="cidade"
            value='{{ $replicado[0]['cidloc'] }}'
        />
    </div>
</div>

<div class="row custom-form-group">
    <div class="col-12 col-lg-4 text-lg-right">
        <label>Estado *</label>
    </div>
    <div class="col-12 col-md-1">
        <input class="custom-form-control" type="text" name="estado" id="estado"
            value='{{ $replicado[0]['sglest'] }}'
        />
    </div>
</div>

@php
    foreach($replicado as $rep){ 
        if($rep['tiptelpes']=='celular'){
            $celular = $rep['codddi'] . $rep['codddd'] . $rep['numtel'];
        }
    }
@endphp

<div class="row custom-form-group">
    <div class="col-12 col-lg-4 text-lg-right">
        <label>Telefone celular </label>
    </div>
    <div class="col-12 col-md-2">
        <input class="custom-form-control" type="text" name="tel_celular" id="tel_celular"
            value='{{ ($celular ?? '') ? $celular : '' }}'
        />
    </div>
</div>

@php
    foreach($replicado as $rep){ 
        if($rep['tiptelpes']=='residencial'){
            $residencial = $rep['codddi'] . $rep['codddd'] . $rep['numtel'];
        }
    }
@endphp

<div class="row custom-form-group">
    <div class="col-12 col-lg-4 text-lg-right">
        <label>Telefone residencial </label>
    </div>
    <div class="col-12 col-md-2">
        <input class="custom-form-control" type="text" name="tel_residencial" id="tel_residencial"
            value='{{ ($residencial ?? '') ? $residencial : '' }}'
        />
    </div>
</div>

<div class="row custom-form-group">
    <div class="col-12 col-lg-4 text-lg-right">
        <label>Possui conta no Banco do Brasil? </label>
    </div>
    <div class="col-12 col-md-2">
        <input type="checkbox" name="possui_conta_bb" id="possui_conta_bb"
            value=1
        />
    </div>
</div>

<div class="row">
    <div class="col-4 d-none d-lg-block"></div>
    <div class="col-md-12 col-lg-6">
        <button type="submit" class="btn btn-outline-dark">
            {{ $buttonText }}
        </button>
        <a class="btn btn-outline-dark"
            href="{{ route('users.index') }}"
        >
            Cancelar
        </a>
    </div>
</div>
