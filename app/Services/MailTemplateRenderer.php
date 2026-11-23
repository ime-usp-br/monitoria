<?php

namespace App\Services;

use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

class MailTemplateRenderer
{
    public function __construct(
        private MailTemplateSyntax $syntax,
        private MailTemplateContext $context
    ) {
    }

    public function render(?string $template, array $context): string
    {
        $twigTemplate = $this->syntax->toTwig((string) $template);
        $context = $this->context->build($context, $twigTemplate);

        $twig = new Environment(new ArrayLoader(['mail' => $twigTemplate]), [
            'autoescape' => 'html',
            'cache' => false,
            'strict_variables' => false,
        ]);

        $twig->addExtension(new SandboxExtension(new MailTemplateSecurityPolicy(), true));
        $twig->addFunction(new TwigFunction('count', function ($value) {
            return is_countable($value) ? count($value) : 0;
        }));

        return $twig->render('mail', $context);
    }
}
