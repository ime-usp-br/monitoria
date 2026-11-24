<?php

namespace App\Services;

use InvalidArgumentException;

class MailTemplateSyntax
{
    private const FORBIDDEN_DIRECTIVES = [
        'php',
        'endphp',
        'include',
        'extends',
        'section',
        'yield',
        'push',
        'stack',
        'component',
        'livewire',
        'inject',
        'once',
        'verbatim',
        'endverbatim',
    ];

    public function toTwig(string $source): string
    {
        $source = $this->assertNoPhp($source);

        $source = $this->replaceDirective($source, 'isset', function (string $expression) {
            if (! preg_match('/^[A-Za-z_][A-Za-z0-9_.]*$/', $expression)) {
                throw new InvalidArgumentException('Diretiva @isset aceita apenas uma variável simples.');
            }

            return '{% if '.$expression.' is defined and '.$expression.' is not null %}';
        });

        $source = $this->replaceDirective($source, 'foreach', function (string $expression) {
            $parts = preg_split('/\s+as\s+/', $expression, 2);

            if (count($parts) !== 2 || trim($parts[1]) === '') {
                throw new InvalidArgumentException('Diretiva @foreach sem "as $variavel".');
            }

            $variable = preg_replace('/^\$/', '', trim($parts[1]));

            return '{% for '.$variable.' in '.trim($parts[0]).' %}';
        });

        $source = $this->replaceDirective($source, 'elseif', fn (string $expression) => '{% elseif '.$expression.' %}');
        $source = $this->replaceDirective($source, 'if', fn (string $expression) => '{% if '.$expression.' %}');

        $source = str_replace('@endforeach', '{% endfor %}', $source);
        $source = str_replace('@endif', '{% endif %}', $source);
        $source = str_replace('@else', '{% else %}', $source);
        $source = str_replace('@endisset', '{% endif %}', $source);

        $source = preg_replace_callback('/\{\{\s*(.*?)\s*\}\}/s', function (array $matches) {
            return '{{ '.$this->expression($matches[1]).' }}';
        }, $source);

        $source = preg_replace_callback('/\{!!\s*(.*?)\s*!!\}/s', function (array $matches) {
            return '{{ '.$this->expression($matches[1]).' }}';
        }, $source);

        $this->assertNoUnsupportedDirective($source);

        return $source;
    }

    public function unsupportedDirectives(string $source): array
    {
        preg_match_all('/@([A-Za-z_][A-Za-z0-9_]*)/', $source, $matches);

        return array_values(array_unique(array_diff($matches[1], [
            'foreach', 'endforeach', 'if', 'elseif', 'else', 'endif',
            'isset', 'endisset',
        ])));
    }

    private function expression(string $expression): string
    {
        $expression = str_replace('->', '.', $expression);
        $expression = preg_replace('/\$(?=[A-Za-z_])/', '', $expression);
        $expression = preg_replace('/\bgetSexo\s*\(\s*\)/i', 'sexo', $expression);

        if (preg_match('/^(.+?)\s*\?\?\s*(.+)$/s', $expression, $matches)) {
            $expression = $matches[1].'|default('.$matches[2].')';
        }

        return trim($expression);
    }

    private function replaceDirective(string $source, string $name, callable $format): string
    {
        $pattern = '/@'.$name.'\s*\(/';
        $offset = 0;

        while (preg_match($pattern, $source, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $start = $match[0][1];
            $parenthesisStart = $start + strlen($match[0][0]) - 1;
            $depth = 0;
            $end = null;

            for ($i = $parenthesisStart; $i < strlen($source); $i++) {
                if ($source[$i] === '(') {
                    $depth++;
                } elseif ($source[$i] === ')') {
                    $depth--;

                    if ($depth === 0) {
                        $end = $i;
                        break;
                    }
                }
            }

            if ($end === null) {
                throw new InvalidArgumentException('Diretiva @'.$name.' sem fechamento.');
            }

            $expression = substr($source, $parenthesisStart + 1, $end - $parenthesisStart - 1);
            $replacement = $format($this->expression($expression));

            $source = substr($source, 0, $start).$replacement.substr($source, $end + 1);
            $offset = $start + strlen($replacement);
        }

        return $source;
    }

    private function assertNoPhp(string $source): string
    {
        if (str_contains($source, '<?')) {
            throw new InvalidArgumentException('O template contém código PHP.');
        }

        foreach (self::FORBIDDEN_DIRECTIVES as $directive) {
            if (preg_match('/@'.$directive.'\b/', $source)) {
                throw new InvalidArgumentException('Diretiva @'.$directive.' não permitida.');
            }
        }

        return $source;
    }

    private function assertNoUnsupportedDirective(string $source): void
    {
        if (preg_match('/(^|[^A-Za-z0-9_])@[A-Za-z_]/', $source)) {
            throw new InvalidArgumentException('O template contém diretivas não suportadas.');
        }
    }
}
