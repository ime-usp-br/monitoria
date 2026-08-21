<?php

namespace Tests\Feature\Scenario;

use App\Models\Course;
use App\Models\Department;
use App\Models\Instructor;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Tests\TestHelpers\ReplicadoStubs;

class ReplicadoScenarioTest extends ScenarioTestCase
{
    public function test_cen_replicado_001_student_get_from_replicado_by_codpes()
    {
        ReplicadoStubs::rule('FROM PESSOA AS P, EMAILPESSOA', [['codpes' => '4242', 'nompes' => 'Aluno Exemplo', 'codema' => 'aluno@ime.usp.br']]);

        $res = Student::getFromReplicadoByCodpes('4242');
        $this->assertSame('4242', $res['codpes']);
        $this->assertSame('Aluno Exemplo', $res['nompes']);
    }

    public function test_cen_replicado_002_student_get_from_replicado_by_nompes_filtra_nao_alunos()
    {
        // a query de nompes consulta a view de alunos (NOMPESTTD); stub devolve um aluno
        // e seu vínculo no Replicado é ALUNOGR para passar no filtro
        ReplicadoStubs::rule('VINCULOPESSOAUSP', [['tipvin' => 'ALUNOGR', 'dtafimvin' => null, 'tipfnc' => null]]);

        $res = Student::getFromReplicadoByNompes('Exemplo');
        $this->assertNotEmpty($res);
        $codpes = $res[0]['codpes'];
        // apenas pessoas com vínculo de aluno são retornadas
        $this->assertContains('Aluno', User::getVinculosFromReplicadoByCodpes($codpes));

        // sem vínculo de aluno -> nada retornado
        ReplicadoStubs::$dbRules = [];
        ReplicadoStubs::rule('VINCULOPESSOAUSP', [['tipvin' => 'SERVIDOR', 'dtafimvin' => null, 'tipfnc' => 'Outro']]);
        $res2 = Student::getFromReplicadoByNompes('Exemplo');
        $this->assertEmpty($res2);
    }

    public function test_cen_replicado_003_instrutor_obtido_com_tipfnc_docente()
    {
        // sem vínculo docente -> retorna array vazio
        ReplicadoStubs::rule('VP.TIPFNC', []);
        $this->assertSame([], Instructor::getFromReplicadoByCodpes('111'));

        // limpa regras anteriores e configura vínculo docente
        ReplicadoStubs::$dbRules = [];
        ReplicadoStubs::rule('VP.TIPFNC', [['codpes' => '222', 'nompes' => 'Professor Doc', 'codema' => 'doc@ime.usp.br', 'codset' => 5000]]);
        $res = Instructor::getFromReplicadoByCodpes('222');
        $this->assertSame('Professor Doc', $res['nompes']);
    }

    public function test_cen_replicado_004_turmas_do_replicado_por_escola_ou_termo()
    {
        $this->setEnv('UNIDADE', 'IME');
        $term = $this->createOpenTerm();

        $turmas = SchoolClass::getFromReplicadoBySchoolTerm($term);
        $this->assertNotEmpty($turmas);
        $this->assertArrayHasKey('instructors', $turmas[0]);
        $this->assertArrayHasKey('class_schedules', $turmas[0]);
    }

    public function test_cen_replicado_005_determinacao_de_vinculo_de_aluno_no_periodo()
    {
        $term = $this->createOpenTerm();
        $student = $this->createStudent(['codpes' => '4242']);

        $vinculo = $student->getVinculoFromReplicadoAtSchoolTerm($term);
        $this->assertSame('Graduação', $vinculo);
    }

    public function test_cen_replicado_006_estimativa_de_matriculas_por_turma()
    {
        $class = $this->createSchoolClass();

        $estimate = $class->calcEstimadedEnrollment();
        $this->assertSame(42, (int) $estimate);
    }

    public function test_cen_replicado_007_departments_obtidos_pelo_instituto()
    {
        $this->setEnv('UNIDADE', 'IME');

        $depts = Department::getFromReplicadoByInstitute('IME');
        $this->assertGreaterThanOrEqual(1, count($depts));
    }

    public function test_cen_replicado_008_course_get_course_from_replicado()
    {
        $term = $this->createOpenTerm();
        $student = $this->createStudent(['codpes' => '4242']);

        $course = Course::getCourseFromReplicado($student, $term);
        $this->assertNotEmpty($course);
    }

    public function test_cen_replicado_009_vinculos_de_usuario()
    {
        ReplicadoStubs::rule('VINCULOPESSOAUSP', [['tipvin' => 'SERVIDOR', 'dtafimvin' => null, 'tipfnc' => 'Docente']]);

        $vinculos = User::getVinculosFromReplicadoByCodpes('1234');
        $this->assertContains('Docente', $vinculos);
    }
}
