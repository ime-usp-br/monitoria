<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SafeMailTemplate implements Rule
{
    private const FORBIDDEN = [
        '@php',
        '@endphp',
        '<?',
        '<?php',
        '@eval',
        '@include',
        '@extends',
        '@section',
        '@yield',
        '@push',
        '@stack',
        '@component',
        '@livewire',
        '@inject',
        '@once',
        '@verbatim',
        '@endverbatim',
    ];

    public function passes($attribute, $value)
    {
        $normalized = strtolower((string) $value);

        foreach (self::FORBIDDEN as $needle) {
            if (str_contains($normalized, $needle)) {
                return false;
            }
        }

        return true;
    }

    public function message()
    {
        return 'O template contém diretivas PHP não permitidas.';
    }
}
