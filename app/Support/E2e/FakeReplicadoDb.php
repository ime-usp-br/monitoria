<?php

namespace App\Support\E2e;

/**
 * Implementação de Uspdev\Replicado\DB usada quando o ASE não está disponível.
 * O contrato é o mesmo (fetch/fetchAll) e os dados vêm de
 * /e2e-artifacts/replicado.json, gerado pelo comando e2e:seed.
 *
 * Nunca é carregada fora do ambiente E2E: o E2eServiceProvider só faz o
 * class_alias com E2E_MODE=true e E2E_REPLICADO_FAKE=true.
 */
class FakeReplicadoDb
{
    private static ?array $fixture = null;

    public static function fetch(string $query, array $param = null)
    {
        $rows = self::fetchAll($query, $param);
        return $rows[0] ?? [];
    }

    public static function fetchAll(string $query, array $param = null)
    {
        $rows = self::route($query, $param ?? []);
        return array_values($rows);
    }

    public static function getInstance()
    {
        return null;
    }

    public static function getQuery($filename, array $replaces = [])
    {
        $path = base_path('vendor/uspdev/replicado/resources/queries/'.$filename);
        $query = is_file($path) ? (string) file_get_contents($path) : (string) $filename;

        foreach ($replaces as $key => $val) {
            if (str_starts_with($key, '--') || str_starts_with($key, '__')) {
                $query = str_replace($key, $val, $query);
            } else {
                $query = str_replace(["__{$key}__", "--{$key}--"], $val, $query);
            }
        }

        return $query;
    }

    public static function sybase()
    {
        return false;
    }

    public static function overrideFetch(string $fetchType, string $query, array $param = null)
    {
        return $fetchType === 'fetch' ? self::fetch($query, $param) : self::fetchAll($query, $param);
    }

    private static function route(string $query, array $param): array
    {
        $data = self::data();
        $q = preg_replace('/\s+/', ' ', strtoupper($query));
        $p = array_change_key_case($param, CASE_LOWER);

        // Graduacao::obterCursoAtivo (Replicado >= 1.28)
        if (strpos($q, 'CURSOGR') !== false && strpos($q, 'HABILITACAOGR') !== false) {
            $codpes = self::codpes($p);
            $person = self::find($data['PESSOA'], fn ($x) => (int) $x['codpes'] === $codpes);
            $vinculo = self::find($data['VINCULOPESSOAUSP'], fn ($v) => (int) $v['codpes'] === $codpes && $v['tipvin'] === 'ALUNOGR');

            if (! $person || ! $vinculo) {
                return [];
            }

            $programa = self::find($data['HABILPROGGR'], fn ($h) => (int) $h['codpes'] === $codpes);
            $codcur = (int) ($programa['codcur'] ?? $data['CURSOGR'][0]['codcur'] ?? 1);

            return [[
                'codpes' => $codpes,
                'nompes' => $person['nompes'],
                'codcur' => $codcur,
                'nomcur' => 'Ciencia da Computacao',
                'codhab' => 0,
                'nomhab' => 'Bacharelado',
                'dtainivin' => $vinculo['dtainivin'],
                'codcurgrd' => $codcur,
            ]];
        }

        if (strpos($q, 'LOCALIZAPESSOA') !== false) {
            $codpes = self::codpes($p);
            $vinculos = self::filter($data['VINCULOPESSOAUSP'], fn ($r) => $codpes === null || (int) $r['codpes'] === $codpes);
            return array_map(function ($v) use ($data) {
                $setor = self::find($data['SETOR'], fn ($s) => (int) $s['codset'] === (int) $v['codset']);
                return [
                    'codpes' => $v['codpes'],
                    'tipvinext' => $v['tipvin'],
                    'nomfnc' => $v['tipfnc'],
                    'nomabvset' => $setor['nomabvset'] ?? null,
                    'nomund' => 'Instituto de Matematica e Estatistica',
                ];
            }, $vinculos);
        }

        // Instructor::getFromReplicadoByCodpes
        if (strpos($q, 'VINCULOPESSOAUSP AS VP') !== false && strpos($q, 'VP.TIPFNC') !== false && preg_match('/FROM PESSOA AS P, VINCULOPESSOAUSP/', $q)) {
            $codpes = self::codpes($p);
            $rows = self::filter($data['VINCULOPESSOAUSP'], fn ($v) => (int) $v['codpes'] === $codpes && $v['tipfnc'] === 'Docente');
            return array_map(function ($v) use ($data) {
                $person = self::find($data['PESSOA'], fn ($x) => (int) $x['codpes'] === (int) $v['codpes']);
                $email = self::find($data['EMAILPESSOA'], fn ($x) => (int) $x['codpes'] === (int) $v['codpes']);
                return [
                    'codpes' => $person['codpes'] ?? $v['codpes'],
                    'nompes' => $person['nompes'] ?? '',
                    'codema' => $email['codema'] ?? '',
                    'codset' => $v['codset'],
                ];
            }, $rows);
        }

        // Student::getFromReplicadoByCodpes / ByNompes
        if (preg_match('/FROM PESSOA AS P, EMAILPESSOA/', $q)) {
            $codpes = self::codpes($p);
            if ($codpes !== null) {
                $person = self::find($data['PESSOA'], fn ($x) => (int) $x['codpes'] === $codpes);
                $email = self::find($data['EMAILPESSOA'], fn ($x) => (int) $x['codpes'] === $codpes);
                return $person ? [[
                    'codpes' => $person['codpes'],
                    'nompes' => $person['nompes'],
                    'codema' => $email['codema'] ?? '',
                ]] : [];
            }

            $needle = strtolower((string) ($p['nompes'] ?? ''));
            $needle = trim($needle, '%');
            $rows = [];
            foreach (self::filter($data['PESSOA'], fn ($person) => $needle === '' || strpos(strtolower($person['nompes']), $needle) !== false) as $person) {
                $vinculo = self::find($data['VINCULOPESSOAUSP'], fn ($v) => (int) $v['codpes'] === (int) $person['codpes'] && strpos($v['tipvin'], 'ALUNO') === 0);
                if (! $vinculo) {
                    continue;
                }
                $email = self::find($data['EMAILPESSOA'], fn ($x) => (int) $x['codpes'] === (int) $person['codpes']);
                $rows[] = ['codpes' => $person['codpes'], 'nompes' => $person['nompes'], 'codema' => $email['codema'] ?? ''];
            }
            return $rows;
        }

        // Pessoa::obterNome
        if (strpos($q, 'FROM PESSOA') !== false && strpos($q, 'NOMPESTTD') !== false) {
            preg_match('/CODPES IN \(([^)]+)\)/', $q, $matches);
            $codpesList = [];
            if (! empty($matches[1])) {
                $codpesList = array_map('intval', array_map('trim', explode(',', $matches[1])));
            }
            return array_map(fn ($person) => [
                'codpes' => $person['codpes'],
                'nompesttd' => $person['nompes'],
            ], self::filter($data['PESSOA'], fn ($person) => empty($codpesList) || in_array((int) $person['codpes'], $codpesList, true)));
        }

        if (strpos($q, 'P.SEXPES') !== false) {
            $codpes = self::codpes($p);
            $person = self::find($data['PESSOA'], fn ($x) => $codpes === null || (int) $x['codpes'] === $codpes);
            return $person ? [['sexpes' => $person['sexpes']]] : [];
        }

        if (strpos($q, 'TELEFPESSOA') !== false) {
            $codpes = self::codpes($p);
            return self::filter($data['TELEFPESSOA'], fn ($r) => $codpes === null || (int) $r['codpes'] === $codpes);
        }

        if (strpos($q, 'VINCULOPESSOAUSP') !== false) {
            $codpes = self::codpes($p);
            $rows = self::filter($data['VINCULOPESSOAUSP'], function ($v) use ($codpes, $q, $p) {
                if ($codpes !== null && (int) $v['codpes'] !== $codpes) {
                    return false;
                }
                if (strpos($q, 'VP.TIPFNC') !== false && isset($p['tipfnc'])) {
                    return $v['tipfnc'] === $p['tipfnc'];
                }
                return true;
            });

            if (strpos($q, 'VP.DTAINIVIN') !== false) {
                return array_map(fn ($v) => ['tipvin' => $v['tipvin']], $rows);
            }

            return $rows;
        }

        if (strpos($q, 'FROM SETOR AS S, UNIDADE AS U') !== false && strpos($q, 'S.CODSET = :CODSET') !== false) {
            $codset = (int) ($p['codset'] ?? 0);
            $setor = self::find($data['SETOR'], fn ($s) => (int) $s['codset'] === $codset);
            return $setor ? [self::withUnit($setor, $data)] : [];
        }

        if (strpos($q, 'S.NOMABVSET') !== false) {
            $nomabvset = $p['nomabvset'] ?? '';
            $setor = self::find($data['SETOR'], fn ($s) => strcasecmp($s['nomabvset'], $nomabvset) === 0);
            return $setor ? [self::withUnit($setor, $data)] : [];
        }

        if (strpos($q, 'FROM SETOR AS S, UNIDADE AS U') !== false) {
            return array_map(fn ($setor) => self::withUnit($setor, $data), $data['SETOR']);
        }

        if (strpos($q, 'PREFIXODISCIP') !== false && strpos($q, 'DISCIPGRCODIGO') !== false) {
            $codclg = array_column(self::filter($data['PREFIXODISCIP'], fn ($r) => true), 'codclg');
            return array_map(
                fn ($r) => ['coddis' => $r['coddis']],
                self::filter($data['DISCIPGRCODIGO'], fn ($r) => in_array($r['codclg'], $codclg, true))
            );
        }

        if (strpos($q, 'MINISTRANTE') !== false && strpos($q, 'M.CODPES') !== false) {
            $codpes = self::codpes($p);
            $coddis = $p['coddis'] ?? null;
            $codtur = $p['codtur'] ?? '%';
            $rows = self::filter($data['MINISTRANTE'], fn ($r) => ($codpes === null || (int) $r['codpes'] === $codpes) && (! $coddis || $r['coddis'] === $coddis) && self::like($r['codtur'], $codtur));

            if (strpos($q, 'SELECT M.CODDIS') !== false) {
                return array_map(fn ($r) => ['coddis' => $r['coddis']], $rows);
            }

            return array_map(fn ($r) => ['codpes' => $r['codpes']], $rows);
        }

        if (strpos($q, 'MINISTRANTE') !== false) {
            $coddis = $p['coddis'] ?? null;
            $codtur = $p['codtur'] ?? null;
            return self::filter($data['MINISTRANTE'], fn ($r) => (! $coddis || $r['coddis'] === $coddis) && (! $codtur || self::like($r['codtur'], $codtur)));
        }

        if (strpos($q, 'PERIODOHORARIO') !== false) {
            $coddis = $p['coddis'] ?? null;
            $codtur = $p['codtur'] ?? null;
            $ocupacoes = self::filter($data['OCUPTURMA'], fn ($r) => (! $coddis || $r['coddis'] === $coddis) && (! $codtur || self::like($r['codtur'], $codtur)));
            if (! $ocupacoes) {
                return [];
            }
            $periodo = $data['PERIODOHORARIO'][0];
            return [[
                'diasmnocp' => $ocupacoes[0]['diasmnocp'],
                'horent' => $periodo['horent'],
                'horsai' => $periodo['horsai'],
            ]];
        }

        if (strpos($q, 'TOTALINSCRITOS') !== false) {
            $coddis = $p['coddis'] ?? null;
            $codtur = $p['codtur'] ?? '%';
            $turmas = self::filter($data['TURMAGR'], fn ($t) => (! $coddis || $t['coddis'] === $coddis) && self::like($t['codtur'], $codtur));
            $total = 0;
            foreach ($turmas as $turma) {
                $total += (int) $turma['numins'] + (int) $turma['numinscpl'] + (int) $turma['numinsopt'] + (int) $turma['numinsecr'] + (int) $turma['numinsoptlre'];
            }
            return [['TOTALINSCRITOS' => $total]];
        }

        if (strpos($q, 'TURMAGR') !== false) {
            $coddis = $p['coddis'] ?? null;
            $codtur = $p['codtur'] ?? null;
            return self::filter($data['TURMAGR'], fn ($t) => (! $coddis || $t['coddis'] === $coddis) && (! $codtur || self::like($t['codtur'], $codtur)));
        }

        if (strpos($q, 'DISCIPLINAGR') !== false) {
            $coddis = $p['coddis'] ?? null;
            return self::filter($data['DISCIPLINAGR'], fn ($d) => ! $coddis || $d['coddis'] === $coddis);
        }

        if (strpos($q, 'PROGRAMAGR') !== false) {
            $codpes = self::codpes($p);
            $aluno = self::find($data['PROGRAMAGR'], fn ($r) => $codpes === null || (int) $r['codpes'] === $codpes);
            return $aluno ? [[
                'nomcur' => 'Bacharelado em Ciencia da Computacao',
                'nomund' => 'Instituto de Matematica e Estatistica',
                'sglund' => 'IME',
            ]] : [];
        }

        if (strpos($q, 'AGPROGRAMA') !== false) {
            $codpes = self::codpes($p);
            $aluno = self::find($data['AGPROGRAMA'], fn ($r) => $codpes === null || (int) $r['codpes'] === $codpes);
            return $aluno ? [[
                'nivpgm' => $aluno['nivpgm'],
                'nomcur' => 'Matematica',
                'nomund' => 'Instituto de Matematica e Estatistica',
                'sglund' => 'IME',
            ]] : [];
        }

        return [];
    }

    private static function data(): array
    {
        if (self::$fixture !== null) {
            return self::$fixture;
        }

        $path = getenv('E2E_REPLICADO_FIXTURE') ?: '/e2e-artifacts/replicado.json';
        if (! is_file($path)) {
            return self::$fixture = [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        return self::$fixture = is_array($decoded) ? $decoded : [];
    }

    private static function codpes(array $param): ?int
    {
        return isset($param['codpes']) ? (int) $param['codpes'] : null;
    }

    private static function withUnit(array $setor, array $data): array
    {
        $unit = $data['UNIDADE'][0] ?? [];
        return [
            'codset' => $setor['codset'],
            'nomabvset' => $setor['nomabvset'],
            'nomset' => $setor['nomset'],
            'sglund' => $unit['sglund'] ?? 'IME',
            'nomund' => $unit['nomund'] ?? 'Instituto de Matematica e Estatistica',
        ];
    }

    private static function like(string $value, string $pattern): bool
    {
        $regex = '/^'.str_replace(['%', '_'], ['.*', '.'], preg_quote($pattern, '/')).'$/i';
        return (bool) preg_match($regex, $value);
    }

    private static function filter(array $rows, callable $callback): array
    {
        return array_values(array_filter($rows, $callback));
    }

    private static function find(array $rows, callable $callback): ?array
    {
        foreach ($rows as $row) {
            if ($callback($row)) {
                return $row;
            }
        }
        return null;
    }
}
