<?php

namespace Tests\TestHelpers;

use Mockery;

/**
 * Stubs the Uspdev\Replicado connection used by models/controllers.
 *
 * `Uspdev\Replicado\DB::fetchAll`/`DB::fetch` and `Uspdev\Replicado\Pessoa`
 * static methods are overloaded with Mockery. The router inspects the raw SQL
 * to decide what to return; tests can push `['pattern' => ..., 'result' => ...]`
 * rules on top of the defaults.
 */
class ReplicadoStubs
{
    /** @var array<int, array{pattern: string, result: mixed}> */
    public static $dbRules = [];

    /** @var array<int, array{pattern: string, result: mixed}> */
    public static $pessoaRules = [];

    /**
     * Install the overloaded mocks. Must run before any Replicado class is used.
     */
    public static function install()
    {
        self::$dbRules = [];
        self::$pessoaRules = [];

        $db = Mockery::mock('overload:Uspdev\Replicado\DB');
        $db->shouldReceive('fetchAll')->andReturnUsing(function ($query, $param = null) {
            return ReplicadoStubs::route('DB::fetchAll', $query, $param);
        });
        $db->shouldReceive('fetch')->andReturnUsing(function ($query, $param = null) {
            return ReplicadoStubs::route('DB::fetch', $query, $param);
        });

        $pessoa = Mockery::mock('overload:Uspdev\Replicado\Pessoa');
        $pessoa->shouldReceive('obterNome')->andReturnUsing(function ($codpes) {
            foreach (self::$pessoaRules as $rule) {
                if (stripos('obterNome', $rule['pattern']) !== false) {
                    $result = $rule['result'];
                    return is_callable($result) ? $result($codpes) : $result;
                }
            }
            return 'Professor Do Ime';
        });
        $pessoa->shouldReceive('vinculos')->andReturnUsing(function ($codpes) {
            foreach (self::$pessoaRules as $rule) {
                if (stripos('vinculos', $rule['pattern']) !== false) {
                    $result = $rule['result'];
                    return is_callable($result) ? $result($codpes) : $result;
                }
            }
            return ['Docente'];
        });
    }

    /**
     * Register a custom rule for a DB query.
     *
     * @param string        $pattern case-insensitive substring of the SQL query
     * @param mixed         $result  array/closure returned for matching queries
     * @param string|false  $method  restrict to fetchAll/fetch
     */
    public static function rule(string $pattern, $result, $method = false)
    {
        self::$dbRules[] = compact('pattern', 'result', 'method');
    }

    public static function registerPessoa(string $pattern, $result)
    {
        self::$pessoaRules[] = compact('pattern', 'result');
    }

    /**
     * Route a DB call to a configured rule or to the default behavior.
     *
     * @return mixed
     */
    public static function route(string $method, string $query, $param = null)
    {
        foreach (self::$dbRules as $rule) {
            if ($rule['method'] && $rule['method'] !== $method) {
                continue;
            }
            if (stripos($query, $rule['pattern']) !== false) {
                $result = $rule['result'];
                return is_callable($result) ? $result($query, $param) : $result;
            }
        }

        return self::defaultRoute($method, $query, $param);
    }

    /**
     * Reasonable defaults so flows that call the Replicado keep working.
     */
    protected static function defaultRoute(string $method, string $query, $param = null)
    {
        $q = strtoupper($query);

        // Student::getVinculoFromReplicadoAtSchoolTerm (graduacao/pos)
        if (strpos($q, 'VP.DTAINIVIN') !== false) {
            return [['tipvin' => 'ALUNOGR']];
        }

        // User::getVinculosFromReplicadoByCodpes -- no vinculo by default
        if (strpos($q, 'FROM VINCULOPESSOAUSP') !== false && strpos($q, 'VP.TIPVIN') !== false) {
            return [];
        }

        // Student::getSexo / Instructor::getSexo
        if (preg_match('/SELECT\s+P\.SEXPES/i', $q)) {
            return [['sexpes' => 'F']];
        }

        // Student::getTelefonesFromReplicado
        if (strpos($q, 'FROM TELEFPESSOA') !== false) {
            return [['codddi' => '55', 'codddd' => '11', 'numtel' => '900000000', 'tiptelpes' => 'celular']];
        }

        // SchoolClass::calcEstimadedEnrollment
        if (strpos($q, 'TOTALINSCRITOS') !== false) {
            return [['TOTALINSCRITOS' => '42']];
        }

        // Instructor::getFromReplicadoByCodpes (PESSOA + VINCULO + EMAIL + tipfnc)
        if (strpos($q, 'VINCULOPESSOAUSP AS VP') !== false && strpos($q, 'VP.TIPFNC') !== false) {
            $codpes = $param['codpes'] ?? 1;
            return [[
                'codpes' => (string) $codpes,
                'nompes' => 'Professor Do Ime',
                'codema' => 'docente@ime.usp.br',
                'codset' => 5000,
            ]];
        }

        // Department::getFromReplicadoByCodset (single)
        if (strpos($q, 'S.CODSET = :CODSET') !== false) {
            return [[
                'codset' => 5000,
                'nomabvset' => 'MAC',
                'nomset' => 'Departamento de Ciencia da Computacao',
                'sglund' => 'IME',
                'nomund' => 'Instituto de Matematica e Estatistica',
            ]];
        }

        // Department::getFromReplicadoByInstitute (list for a unidade)
        if (strpos($q, 'FROM SETOR AS S, UNIDADE AS U') !== false && strpos($q, 'U.SGLUND') !== false) {
            return [
                ['codset' => 5000, 'nomabvset' => 'MAC', 'nomset' => 'Departamento de Ciencia da Computacao', 'sglund' => 'IME', 'nomund' => 'Instituto de Matematica e Estatistica'],
                ['codset' => 5100, 'nomabvset' => 'MAP', 'nomset' => 'Departamento de Matematica Aplicada', 'sglund' => 'IME', 'nomund' => 'Instituto de Matematica e Estatistica'],
            ];
        }

        // Department::getFromReplicadoByNomabvset (single)
        if (strpos($q, 'S.NOMABVSET') !== false) {
            return [[
                'codset' => 5000,
                'nomabvset' => 'MAC',
                'nomset' => 'Departamento de Ciencia da Computacao',
                'sglund' => 'IME',
                'nomund' => 'Instituto de Matematica e Estatistica',
            ]];
        }

        // Student::getFromReplicadoByCodpes / getFromReplicadoByNompes
        if (preg_match('/FROM\s+PESSOA\s+AS\s+P,\s*EMAILPESSOA/i', $q)) {
            $rows = [];
            $codpes = $param['codpes'] ?? null;
            if ($codpes !== null) {
                return [[
                    'codpes' => (string) $codpes,
                    'nompes' => 'Aluno Do Ime',
                    'codema' => 'aluno'.(string) $codpes.'@ime.usp.br',
                ]];
            }
            if (strpos($q, 'NOMPESTTD') !== false) {
                $rows = [
                    ['codpes' => '1010', 'nompes' => 'Aluno Do Ime', 'codema' => 'aluno1010@ime.usp.br'],
                ];
            }
            return $rows;
        }

        // SchoolClass disciplines for the institute
        if (strpos($q, 'PREFIXODISCIP') !== false) {
            return [['coddis' => 'MAC0110']];
        }

        // TURMAGR queries (SchoolClass get from Replicado, one or many)
        if (strpos($q, 'TURMAGR') !== false) {
            $rows = [[
                'codtur' => '2026101',
                'coddis' => 'MAC0110',
                'nomdis' => 'Introducao a Computacao',
                'pfxdisval' => 'MAC',
                'tiptur' => 'Teoria',
                'dtainitur' => '2026-03-01 00:00:00',
                'dtafimtur' => '2026-07-10 00:00:00',
            ]];
            return $rows;
        }

        // OCUPTURMA + PERIODOHORARIO (class schedule)
        if (strpos($q, 'PERIODOHORARIO') !== false) {
            return [['diasmnocp' => 'seg', 'horent' => '08:00', 'horsai' => '10:00']];
        }

        // OCUPTURMA + MINISTRANTE (instructors of a class)
        if (strpos($q, 'MINISTRANTE') !== false) {
            return [['codpes' => '2000']];
        }

        // Course queries
        if (strpos($q, 'PROGRAMAGR') !== false) {
            return [['nomcur' => 'Bacharelado em Ciencia da Computacao', 'nomund' => 'Instituto de Matematica e Estatistica', 'sglund' => 'IME']];
        }
        if (strpos($q, 'AGPROGRAMA') !== false) {
            return [['nivpgm' => 'ME', 'nomcur' => 'Matematica Aplicada', 'nomund' => 'Instituto de Matematica e Estatistica', 'sglund' => 'IME']];
        }

        // VINCULOPESSOAUSP used by Student::getVinculoFromReplicadoAtSchoolTerm
        if (strpos($q, 'VP.DTAINIVIN') !== false) {
            return [['tipvin' => 'ALUNOGR']];
        }

        return [];
    }
}