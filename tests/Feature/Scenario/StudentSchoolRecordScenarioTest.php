<?php

namespace Tests\Feature\Scenario;

use App\Models\SchoolRecord;
use App\Models\SchoolTerm;
use App\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestHelpers\ReplicadoStubs;

class StudentSchoolRecordScenarioTest extends ScenarioTestCase
{
    // ------------------------------------------------------------------
    // Student
    // ------------------------------------------------------------------

    public function test_cen_student_001_busca_json_por_codpes_retorna_dados_do_aluno()
    {
        $response = $this->getJson('/students?codpes=1234567');

        $response->assertOk()->assertJson([
            'codpes' => '1234567',
            'nompes' => 'Aluno Do Ime',
            'codema' => 'aluno1234567@ime.usp.br',
        ]);
    }

    public function test_cen_student_002_busca_json_por_codpes_inexistente_retorna_vazio()
    {
        ReplicadoStubs::rule('FROM PESSOA AS P, EMAILPESSOA', []);

        $response = $this->getJson('/students?codpes=999999');

        $response->assertOk()->assertExactJson(['']);
    }

    public function test_cen_student_003_busca_json_por_nompes_retorna_somente_pessoas_com_vinculo_aluno()
    {
        ReplicadoStubs::rule('VP.TIPVIN', [['tipvin' => 'ALUNOGR', 'dtafimvin' => null, 'tipfnc' => null]]);

        $response = $this->getJson('/students?nompes=Aluno');

        $response->assertOk();
        $json = $response->json();

        $this->assertIsArray($json);
        $this->assertNotEmpty($json);
        foreach ($json as $aluno) {
            $this->assertArrayHasKey('codpes', $aluno);
            $this->assertArrayHasKey('nompes', $aluno);
        }
    }

    public function test_cen_student_003b_busca_json_por_nompes_sem_resultado_retorna_vazio()
    {
        ReplicadoStubs::rule('FROM PESSOA AS P, EMAILPESSOA', []);

        $response = $this->getJson('/students?nompes=Ninguem');

        $response->assertOk()->assertExactJson(['']);
    }

    public function test_cen_student_004_metodos_de_criacao_edicao_sao_stubs()
    {
        $aluno = $this->aluno();

        $this->actingAs($aluno)->get('/students/create')->assertStatus(200);

        // storage stub (StoreStudentRequest authorize() retorna false)
        $count = Student::count();
        $this->actingAs($aluno)->post('/students', ['codpes' => 1])->assertForbidden();
        $this->assertSame($count, Student::count());

        $student = $this->createStudent();
        $this->actingAs($aluno)->get('/students/'.$student->id)->assertStatus(200);
        $this->actingAs($aluno)->get('/students/'.$student->id.'/edit')->assertStatus(200);
        $this->actingAs($aluno)->put('/students/'.$student->id, ['codpes' => 2])->assertForbidden();
        $this->actingAs($aluno)->delete('/students/'.$student->id)->assertStatus(200);
        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }

    // ------------------------------------------------------------------
    // SchoolRecord
    // ------------------------------------------------------------------

    public function test_cen_schoolrecord_001_create_exige_aluno_no_periodo_de_inscricoes()
    {
        // aluno fora do período de inscrições (janela de inscrição encerrada)
        $closedTerm = $this->createOpenTerm([
            'start_date_enrollments' => now()->subDays(30)->format('d/m/Y'),
            'end_date_enrollments' => now()->subDays(20)->format('d/m/Y'),
            'start_date_requisitions' => now()->subDays(30)->format('d/m/Y'),
            'end_date_requisitions' => now()->subDays(20)->format('d/m/Y'),
            'start_date_evaluations' => now()->subDays(30)->format('d/m/Y'),
            'end_date_evaluations' => now()->subDays(20)->format('d/m/Y'),
        ]);

        $docente = $this->docente();
        $this->actingAs($docente)->get('/schoolRecords/create')->assertForbidden();

        $aluno = $this->aluno();
        $this->from('/')->actingAs($aluno)->get('/schoolRecords/create')->assertRedirect('/');
        $this->assertSessionHasWarningContaining('inscrições encerrado');
    }

    public function test_cen_schoolrecord_002_store_persiste_historico_e_redireciona_para_inscricoes()
    {
        $this->createOpenTerm();
        $aluno = $this->aluno();
        $file = UploadedFile::fake()->create('historico.pdf', 100, 'application/pdf');

        $this->actingAs($aluno)->from('/schoolRecords/create')
            ->post('/schoolRecords', ['file' => $file])
            ->assertRedirect(route('enrollments.index'));

        $this->assertDatabaseHas('school_records', [
            'student_id' => Student::where('codpes', $aluno->codpes)->first()->id,
            'schoolterm_id' => SchoolTerm::getSchoolTermInEnrollmentPeriod()->id,
        ]);

        $record = SchoolRecord::first();
        $this->assertNotNull($record);
        $this->assertStringContainsStringIgnoringCase($file->hashName(), $record->file_path);
    }

    public function test_cen_schoolrecord_003_validacao_arquivo_obrigatorio_pdf_1000kb()
    {
        $this->createOpenTerm();
        $aluno = $this->aluno();

        $this->actingAs($aluno)->post('/schoolRecords', [])->assertSessionHasErrors('file');
        $this->actingAs($aluno)->post('/schoolRecords', ['file' => UploadedFile::fake()->create('x.txt', 100, 'text/plain')])->assertSessionHasErrors('file');
        $this->actingAs($aluno)->post('/schoolRecords', ['file' => UploadedFile::fake()->create('x.pdf', 1100, 'application/pdf')])->assertSessionHasErrors('file');
    }

    public function test_cen_schoolrecord_004_update_substitui_o_arquivo_no_lugar()
    {
        $term = $this->createOpenTerm();
        $student = $this->createStudent(['codpes' => $aluno = mt_rand(10000000, 99999999)]);
        $this->createSchoolRecord($student, $term, '20261/antigo.pdf');
        $alunoUser = $this->aluno(['codpes' => $student->codpes], $student);
        $record = $student->schoolrecords()->first();

        $this->actingAs($alunoUser)->from('/schoolRecords')
            ->put('/schoolRecords/'.$record->id, ['file' => UploadedFile::fake()->create('novo.pdf', 100, 'application/pdf')])
            ->assertRedirect(route('enrollments.index'));

        $record->refresh();
        $this->assertSame(1, SchoolRecord::count());
        $this->assertNotSame('20261/antigo.pdf', $record->file_path);
        $this->assertStringNotContainsString('antigo', $record->file_path);
    }

    public function test_cen_schoolrecord_005_download_valida_existencia_no_storage()
    {
        Storage::put('20261/historico.pdf', 'conteudo');

        $secretaria = $this->secretaria();
        $resp = $this->actingAs($secretaria)->post('/schoolrecords/download', ['path' => '20261/historico.pdf']);
        $resp->assertOk();
        $this->assertStringContainsString('20261.pdf', (string) $resp->headers->get('content-disposition'));

        // arquivo inexistente falha com mensagem do StorageFileExists
        $this->from('/enrollments')->actingAs($secretaria)
            ->post('/schoolrecords/download', ['path' => '20261/nao-existe.pdf'])
            ->assertSessionHasErrors('path');
    }

    public function test_cen_schoolrecord_005b_download_nome_primeiro_segmento_do_caminho()
    {
        Storage::put('20261/historico.pdf', 'conteudo');
        $secretaria = $this->secretaria();

        $resp = $this->actingAs($secretaria)->post('/schoolrecords/download', ['path' => '20261/historico.pdf']);
        $this->assertStringContainsString('20261.pdf', (string) $resp->headers->get('content-disposition'));
    }

    public function test_cen_schoolrecord_006_demais_metodos_sao_stubs()
    {
        $term = $this->createOpenTerm();
        $student = $this->createStudent();
        $aluno = $this->aluno(['codpes' => $student->codpes], $student);
        $record = $this->createSchoolRecord($student, $term);

        $this->actingAs($aluno)->get('/schoolRecords')->assertStatus(200);
        $this->actingAs($aluno)->get('/schoolRecords/'.$record->id)->assertStatus(200);
        $this->actingAs($aluno)->get('/schoolRecords/'.$record->id.'/edit')->assertStatus(200);
        $this->actingAs($aluno)->delete('/schoolRecords/'.$record->id)->assertStatus(200);
        $this->assertDatabaseHas('school_records', ['id' => $record->id]);
    }

    public function test_cen_schoolrecord_007_unicidade_de_historico_por_aluno_e_periodo()
    {
        $term = $this->createOpenTerm();
        $student = $this->createStudent();
        $this->createSchoolRecord($student, $term, '20261/um.pdf');

        try {
            SchoolRecord::create([
                'student_id' => $student->id,
                'schoolterm_id' => $term->id,
                'file_path' => '20261/dois.pdf',
            ]);
            $this->fail('Esperava violacao de unicidade em (student_id, schoolterm_id).');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertSame(1, SchoolRecord::count());
        }
    }
}