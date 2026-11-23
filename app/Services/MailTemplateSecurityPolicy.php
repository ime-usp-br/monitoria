<?php

namespace App\Services;

use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityPolicyInterface;

class MailTemplateSecurityPolicy implements SecurityPolicyInterface
{
    private const TAGS = ['if', 'for', 'set'];

    private const FILTERS = [
        'default',
        'e',
        'escape',
        'length',
        'lower',
        'nl2br',
        'trim',
        'upper',
    ];

    private const FUNCTIONS = ['count'];

    public function checkSecurity($tags, $filters, $functions): void
    {
        foreach ($tags as $tag) {
            if (! in_array($tag, self::TAGS, true)) {
                throw new SecurityError(sprintf('Tag "%s" não permitida no template.', $tag));
            }
        }

        foreach ($filters as $filter) {
            if (! in_array($filter, self::FILTERS, true)) {
                throw new SecurityError(sprintf('Filtro "%s" não permitido no template.', $filter));
            }
        }

        foreach ($functions as $function) {
            if (! in_array($function, self::FUNCTIONS, true)) {
                throw new SecurityError(sprintf('Função "%s" não permitida no template.', $function));
            }
        }
    }

    public function checkMethodAllowed($obj, $method, $lineno = null): void
    {
        throw new SecurityError('Acesso a métodos não é permitido no template.');
    }

    public function checkPropertyAllowed($obj, $property, $lineno = null): void
    {
        throw new SecurityError('Acesso a propriedades de objetos não é permitido no template.');
    }
}
