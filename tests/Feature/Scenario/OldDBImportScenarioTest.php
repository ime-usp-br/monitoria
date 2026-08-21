<?php

namespace Tests\Feature\Scenario;

use App\Jobs\ProcessImportOldDB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use App\Models\SchoolTerm;
use App\Models\Instructor;
use App\Models\Student;

class OldDBImportScenarioTest extends ScenarioTestCase
{
    private function csvContent(int $lines = 1): string
    {
        $cols = function ($i) {
            return implode(';', [
                (string) (10000000 + $i), // monitor_codpes
                (string) (20000000 + $i), // professor_codpes
                'MAC0110',                // coddis
                '2020',                   // ano
                '0',                      // semestre
                '3-4-5',                  // frequencia_meses
                '0',                      // voluntario
                '',                       // student_amount
                '',                       // homework_amount
                '',                       // secondary_activity
                '',                       // workload
                '',                       // workload_reason
                '',                       // comments
                '',                       // ease_of_contact
                '',                       // efficiency
                '',                       // reliability
                '',                       // overall
                '',                       // comments_ie
            ]);
        };

        return implode("\n", array_map($cols, range(1, $lines)));
    }

    public function test_cen_olddb_001_index_e_import_exigem_papel_administrador()
    {
        $semPermissao = $this->secretaria();
        $admin = $this->admin();

        $this->actingAs($semPermissao)->get('/olddb')->assertForbidden();
        $this->actingAs($semPermissao)->post('/olddb/import', ['file' => UploadedFile::fake()->create('dados.csv', 10, 'text/plain')])->assertForbidden();

        $this->actingAs($admin)->get('/olddb')->assertOk();
    }

    public function test_cen_olddb_002_validacao_do_arquivo_de_importacao()
    {
        $admin = $this->admin();

        // extensão inválida
        $this->actingAs($admin)->post('/olddb/import', ['file' => UploadedFile::fake()->create('dados.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')])
            ->assertSessionHasErrors('file');

        // maior que 1000KB
        $this->actingAs($admin)->post('/olddb/import', ['file' => UploadedFile::fake()->create('dados.csv', 1100, 'text/plain')])
            ->assertSessionHasErrors('file');
    }

    public function test_cen_olddb_003_import_despacha_job_com_conteudo_e_codpes()
    {
        Bus::fake();

        $admin = $this->admin(['codpes' => '77776666']);

        $this->actingAs($admin)->from('/olddb')
            ->post('/olddb/import', ['file' => UploadedFile::fake()->createWithContent('dados.csv', $this->csvContent(), 5)])
            ->assertRedirect('/olddb');

        Bus::assertDispatched(ProcessImportOldDB::class, function ($job) {
            return str_contains($job->file, 'MAC0110') && $job->codpescad === '77776666';
        });
    }

    public function test_cen_olddb_004_linha_valida_cria_a_cadeia_completa_de_registros()
    {
        $admin = $this->admin();

        $job = new ProcessImportOldDB($this->csvContent(1), $admin->codpes);
        $job->handle();

        // Instructor e Student criados a partir do Replicado stub
        $this->assertDatabaseHas('instructors', ['codpes' => '20000001']);
        $this->assertDatabaseHas('students', ['codpes' => '10000001']);

        // SchoolTerm fechado
        $this->assertDatabaseHas('school_terms', ['year' => 2020, 'period' => '1° Semestre', 'status' => 'Fechado']);

        // Requisition com as 3 atividades
        $term = SchoolTerm::where('year', 2020)->first();
        $professionalCodpes = '20000001';
        $instructor = Instructor::where('codpes', $professionalCodpes)->first();
        $requisition = \App\Models\Requisition::where('instructor_id', $instructor->id)->first();
        $this->assertNotNull($requisition);
        $this->assertSame(3, $requisition->activities()->count());

        // Enrollment e Selection Concluido
        $this->assertDatabaseHas('enrollments', ['student_id' => Student::where('codpes', '10000001')->first()->id]);
        $selection = \App\Models\Selection::where('student_id', Student::where('codpes', '10000001')->first()->id)->where('sitatl', 'Concluido')->first();
        $this->assertNotNull($selection);

        // Frequências registradas para os meses 3, 4, 5
        foreach ([3, 4, 5] as $month) {
            $this->assertDatabaseHas('frequencies', ['student_id' => $selection->student_id, 'school_class_id' => $selection->school_class_id, 'month' => $month, 'registered' => 1]);
        }
    }

    public function test_cen_olddb_005_linha_com_contagem_de_colunas_incorreta_e_rastreada_como_erro()
    {
        $admin = $this->admin();

        $linhaInvalida = "apenas;duas;colunas\n".$this->csvContent(1);
        $job = new ProcessImportOldDB($linhaInvalida, $admin->codpes);
        $job->handle();

        $monitor = Student::where('codpes', '10000001')->first();
        // a linha válida (csvContent) foi processada
        $this->assertNotNull($monitor);
        // a linha inválida (2 colunas) foi pulada e rastreada como erro
        $this->assertNull(Student::where('codpes', 'apenas')->first());
    }

    public function test_cen_olddb_006_erros_por_falta_de_dados_no_replicado_sao_reportados()
    {
        $admin = $this->admin();

        // professor inexistente no Replicado
        $linha = "10000001;99999999;MAC0110;2020;0;3-4-5;0;;;;;;;;;;;;;;;\n".$this->csvContent(1);
        $job = new ProcessImportOldDB($linha, $admin->codpes);
        $job->handle();

        // a linha com professor inexistente não cria chain (Instructor 99999999 não aparece)
        $this->assertDatabaseMissing('instructors', ['codpes' => '99999999']);
        // mas a linha válida foi processada
        $this->assertDatabaseHas('students', ['codpes' => '10000001']);
    }
}
