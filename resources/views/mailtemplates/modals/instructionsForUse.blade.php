<div class="modal fade" id="instructionsForUseModal">
   <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Instruções de uso do e-mail</h4>
            </div>
            <div class="modal-body">
                <label class="font-weight-bold">Como mostrar o valor de uma variável no corpo ou assunto do e-mail: </label>
                <div class="col-12">
                    Você pode <a href="https://laravel.com/docs/5.4/blade#displaying-data" target="_blank">exibir os valores das variáveis</a> no corpo ou assunto 
                    do e-mail envolvendo a variável entre chaves. <br>
                    Por exemplo, exibir o nome da disciplina: <br> 
                    <b>{{ "{"."{ $"."schoolclass->nomdis "."}"."}" }}</b>.
                </div><br>
                <label class="font-weight-bold">Como realizar uma comparação(condicional): </label>
                <div class="col-12">
                    Caso você queira realizar uma operação condicional pode-se usar o <a href="https://en.wikipedia.org/wiki/Elvis_operator" target="_blank">Elvis Operator</a> 
                    envolvido entre chaves. <br>
                    Por exemplo, escrever "monitora" se for do sexo feminino e "monitor" se for do sexo masculino: <br>
                    <b>{{ "{"."{ $"."student->getSexo() == 'F' ? 'monitora' : 'monitor' "."}"."}" }}</b>.<br> 
                    Outro exemplo seria escrever "monitores" se houver mais de um
                    monitor eleito para mesma turma ou escrever "monitor" se apenas um foi eleito: <br>
                    <b>{{ "{"."{ "."count($"."schoolclass->selections) > 1 ? 'monitores' : 'monitor' "."}"."}" }} </b>
                </div><br>
                <label class="font-weight-bold">Como correr todos elementos de uma coleção: </label>
                <div class="col-12">
                    Caso você queira realizar uma operação para cada elemento de uma coleção use a diretiva <a href="https://laravel.com/docs/5.4/blade#loops" target="_blank">{!! "@"."foreach" !!}</a>. <br>
                    Por exemplo, exibir o nome de cada monitor eleito para uma turma: <br>
                    <b>{!! "@"."foreach($"."schoolclass->selections as $"."selection) "."{"."{ $"."selection->student->nompes "."}"."}"." @"."endforeach" !!}</b>
                </div><br>
                <label class="font-weight-bold">Variáveis disponíveis no e-mail enviado aos docentes no final do processo de seleção:</label>
                <div class="col-12">
                    <b>$schoolclass->nomdis</b> - Nome da disciplina<br>
                    <b>$schoolclass->coddis</b> - Código da disciplina<br>
                    <b>$schoolclass->codtur</b> - Código da turma<br>
                    <b>$schoolclass->schoolterm->period</b> - Período que a turma será ministrada Ex. 1° Semestre<br>
                    <b>$schoolclass->schoolterm->year</b> - Ano que a turma será ministrada<br>
                    <b>$schoolclass->selections</b> - Coleção com os monitores eleitos, use a diretiva {!! "@"."foreach" !!} do blade para correr por toda coleção<br>
                    <b>$selection->student->nompes</b> - Nome do monitor. Supõe que você usou {!! "@"."foreach($"."schoolclass->selections as $"."selection)" !!}<br>
                    <b>$selection->student->codema</b> - E-mail do monitor. Supõe que você usou {!! "@"."foreach($"."schoolclass->selections as $"."selection)" !!}<br>
                    <b>$selection->student->codpes</b> - Número USP do monitor. Supõe que você usou {!! "@"."foreach($"."schoolclass->selections as $"."selection)" !!}<br>
                    <b>$selection->student->getSexo()</b> - Sexo do monitor. Supõe que você usou {!! "@"."foreach($"."schoolclass->selections as $"."selection)" !!}<br>
                    <b>$instructor->nompes</b> - Nome do docente que solicitou a vaga<br>
                    <b>$instructor->codema</b> - E-mail do docente que solicitou a vaga<br>
                    <b>$instructor->codpes</b> - Número USP do docente que solicitou a vaga<br>
                    <b>$instructor->department->nomset</b> - Nome do departamento do docente que solicitou a vaga<br>
                    <b>$instructor->department->nomabvset</b> - Nome abreviado do departamento do docente que solicitou a vaga<br>
                </div><br>
                <label class="font-weight-bold">Variáveis disponíveis no e-mail enviado ao monitor no final do processo de seleção:</label>
                <div class="col-12">
                    <b>$schoolclass->nomdis</b> - Nome da disciplina<br>
                    <b>$schoolclass->coddis</b> - Código da disciplina<br>
                    <b>$schoolclass->codtur</b> - Código da turma<br>
                    <b>$schoolclass->schoolterm->period</b> - Período que a turma será ministrada Ex. 1° Semestre<br>
                    <b>$schoolclass->schoolterm->year</b> - Ano que a turma será ministrada<br>
                    <b>$student->nompes</b> - Nome do monitor<br>
                    <b>$student->codema</b> - E-mail do monitor<br>
                    <b>$student->codpes</b> - Número USP do monitor<br>
                    <b>$student->getSexo()</b> - Sexo do monitor<br>
                    <b>$schoolclass->requisition->instructor->nompes</b> - Nome do docente que solicitou a vaga<br>
                    <b>$schoolclass->requisition->instructor->codema</b> - E-mail do docente que solicitou a vaga<br>
                    <b>$schoolclass->requisition->instructor->codpes</b> - Número USP do docente que solicitou a vaga<br>
                    <b>$schoolclass->requisition->instructor->department->nomset</b> - Nome do departamento do docente que solicitou a vaga<br>
                    <b>$schoolclass->requisition->instructor->department->nomabvset</b> - Nome abreviado do departamento do docente que solicitou a vaga<br>
                </div><br>
                <label class="font-weight-bold">Variáveis disponíveis no e-mail enviado ao docente sobre o registro de frequencia dos monitores(É enviado um e-mail por monitor): </label>
                <div class="col-12">
                    <b>$schoolclass->nomdis</b> - Nome da disciplina<br>
                    <b>$schoolclass->coddis</b> - Código da disciplina<br>
                    <b>$schoolclass->codtur</b> - Código da turma<br>
                    <b>$period</b> - Período que a turma será ministrada Ex. 1° Semestre<br>
                    <b>$year</b> - Ano que a turma será ministrada<br>
                    <b>$month</b> - Mẽs de registro da frequencia<br>
                    <b>$link</b> - Link para registro da frequência no Sistema de Monitoria<br>
                    <b>$student->nompes</b> - Nome do monitor<br>
                    <b>$student->codema</b> - E-mail do monitor<br>
                    <b>$student->codpes</b> - Número USP do monitor<br>
                    <b>$student->getSexo()</b> - Sexo do monitor<br>
                    <b>$instructor->nompes</b> - Nome do docente que solicitou a vaga<br>
                    <b>$instructor->codema</b> - E-mail do docente que solicitou a vaga<br>
                    <b>$instructor->codpes</b> - Número USP do docente que solicitou a vaga<br>
                    <b>$instructor->department->nomset</b> - Nome do departamento do docente que solicitou a vaga<br>
                    <b>$instructor->department->nomabvset</b> - Nome abreviado do departamento do docente que solicitou a vaga<br>
                </div><br>
                
            </div>
            <div class="modal-footer">
                <button class="btn btn-default" type="button" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>