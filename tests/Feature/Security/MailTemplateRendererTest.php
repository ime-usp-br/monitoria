<?php

namespace Tests\Feature\Security;

use App\Models\SchoolClass;
use App\Models\Selection;
use App\Models\Student;
use App\Models\User;
use App\Services\MailTemplateRenderer;
use App\Services\MailTemplateSyntax;
use InvalidArgumentException;
use Tests\TestCase;
use Twig\Error\SyntaxError;

class MailTemplateRendererTest extends TestCase
{
    private function renderer(): MailTemplateRenderer
    {
        return app(MailTemplateRenderer::class);
    }

    public function test_renderiza_variaveis_simples_e_aninhadas(): void
    {
        $student = (new Student)->forceFill([
            'nompes' => 'Ana',
            'codpes' => 123,
            'codema' => 'ana@example.com',
        ]);
        $schoolclass = (new SchoolClass)->forceFill([
            'nomdis' => 'Computação',
            'coddis' => 'MAC0101',
        ]);
        $schoolclass->setRelation('schoolterm', null);
        $schoolclass->setRelation('requisition', null);
        $schoolclass->setRelation('selections', collect());

        $html = $this->renderer()->render(
            '<p>{{ $student->nompes }} - {{ $schoolclass->coddis }}</p>',
            compact('student', 'schoolclass')
        );

        $this->assertSame('<p>Ana - MAC0101</p>', $html);
    }

    public function test_renderiza_default_ternario_e_count(): void
    {
        $student = (new Student)->forceFill(['nompes' => 'Ana']);
        $selections = collect([1, 2]);

        $html = $this->renderer()->render(
            '{{ $student->nompes ?? "aluno" }}|{{ count($selections) > 1 ? "monitores" : "monitor" }}',
            compact('student', 'selections')
        );

        $this->assertSame('Ana|monitores', $html);
    }

    public function test_renderiza_foreach_e_if(): void
    {
        $student = (new Student)->forceFill(['nompes' => 'Ana']);
        $selection = (new Selection)->forceFill(['id' => 1]);
        $selection->setRelation('student', $student);
        $selections = collect([$selection, $selection]);

        $template = '@if(count($selections) > 1)<ul>@foreach($selections as $selection)<li>{{ $selection->student->nompes }}</li>@endforeach</ul>@else<p>um</p>@endif';

        $html = $this->renderer()->render($template, compact('selections'));

        $this->assertSame('<ul><li>Ana</li><li>Ana</li></ul>', $html);
    }

    public function test_rejeita_funcao_php_no_template(): void
    {
        $this->expectException(SyntaxError::class);

        $this->renderer()->render('{{ system("id") }}', []);
    }

    public function test_rejeita_diretivas_php(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->renderer()->render('@php system("id"); @endphp', []);
    }

    public function test_rejeita_include(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->renderer()->render('@include("x")', []);
    }

    public function test_nao_expoe_propriedade_sensivel(): void
    {
        $user = (new User)->forceFill([
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'secret',
        ]);

        $html = $this->renderer()->render('{{ user.name }}|{{ user.password }}', compact('user'));

        $this->assertSame('Ana|', $html);
        $this->assertStringNotContainsString('secret', $html);
    }

    public function test_converte_getsexo_para_sexo(): void
    {
        $syntax = app(MailTemplateSyntax::class);

        $this->assertSame('{{ student.sexo }}', $syntax->toTwig('{{ $student->getSexo() }}'));
    }
}
