<?php

namespace App\Console\Commands;

use App\Models\MailTemplate;
use App\Services\MailTemplateSyntax;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AuditMailTemplates extends Command
{
    protected $signature = 'mailtemplates:audit';

    protected $description = 'Audita templates de e-mail para diretivas perigosas e sintaxe suportada';

    public function handle(MailTemplateSyntax $syntax): int
    {
        $templates = MailTemplate::orderBy('id')->get();

        if ($templates->isEmpty()) {
            $this->warn('Nenhum template de e-mail encontrado.');

            return self::SUCCESS;
        }

        $rows = [];
        $errors = 0;

        foreach ($templates as $template) {
            $problems = [];
            $placeholders = [];

            foreach (['subject', 'body'] as $field) {
                $value = (string) $template->{$field};

                try {
                    $syntax->toTwig($value);
                } catch (\Throwable $exception) {
                    $problems[] = $field.': '.$exception->getMessage();
                }

                $unsupported = $syntax->unsupportedDirectives($value);

                if (! empty($unsupported)) {
                    $problems[] = $field.' diretivas: '.implode(', ', $unsupported);
                }

                preg_match_all('/\{\{\s*(.*?)\s*\}\}/s', $value, $matches);

                foreach ($matches[1] ?? [] as $placeholder) {
                    $placeholders[] = $placeholder;
                }
            }

            if (! empty($problems)) {
                $errors++;
            }

            $rows[] = [
                $template->id,
                $template->mail_class,
                $template->name,
                empty($problems) ? 'OK' : 'ERRO',
                Str::limit(implode(' | ', array_unique($problems)) ?: '-', 120),
                Str::limit(implode(', ', array_unique($placeholders)) ?: '-', 120),
            ];
        }

        $this->table(
            ['ID', 'Classe', 'Nome', 'Status', 'Problemas', 'Placeholders'],
            $rows
        );

        if ($errors > 0) {
            $this->error($errors.' template(s) com problema(s). Corrija antes de enviar e-mails em produção.');

            return self::FAILURE;
        }

        $this->info('Todos os templates passaram na auditoria estática.');

        return self::SUCCESS;
    }
}
