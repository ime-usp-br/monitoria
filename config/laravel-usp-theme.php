<?php

$submenuConfig = [
    [
        'text' => 'Usuários',
        'url' => config('app.url') . '/users',
        'can' => 'editar usuario',
    ],
    [
        'text' => 'Docentes',
        'url' => config('app.url') . '/instructors',
        'can' => 'visualizar docente',
    ],
    [
        'text' => 'Período Letivo',
        'url' => config('app.url') . '/schoolterms',
        'can' => 'visualizar periodo letivo',
    ],
    [
        'text' => 'Turmas',
        'url' => config('app.url') . '/schoolclasses',
        'can' => 'visualizar turma',
    ],
    [
        'text' => 'Monitores',
        'url' => config('app.url') . '/tutors',
        'can' => 'visualizar monitores',
    ],
    [
        'text' => 'E-mails',
        'url' => config('app.url') . '/mailtemplates',
        'can' => 'Editar E-mails',
    ],
];

$menu = [
    [
        'text' => '<i class="fas fa-home"></i>',
        'url' => '/',
    ],
    [
        # este item de menu será substituido no momento da renderização
        'key' => 'menu_dinamico',
    ],
    [
        'text' => 'Solicitar Monitor',
        'url' => config('app.url') . '/requisitions',
        'can' => 'criar solicitação de monitor',
    ],
    [
        'text' => 'Fazer Inscrição',
        'url' => config('app.url') . '/enrollments',
        'can' => 'fazer inscrição',
    ],
    [
        'text' => 'Selecionar Monitores',
        'url' => config('app.url') . '/selections',
        'can' => 'Selecionar monitor',
    ],
    [
        'text' => 'Ver Todos Inscritos',
        'url' => config('app.url') . '/enrollments/showAll',
        'can' => 'visualizar todos inscritos',
    ],
    [
        'text' => 'Disparar E-mails',
        'url' => config('app.url') . '/emails',
        'can' => 'Disparar emails',
    ],
    [
        'text' => 'Relatório',
        'url' => config('app.url') . '/reports',
        'can' => 'gerar relatorio',
    ],
    [
        'text' => 'Emitir Atestado',
        'url' => config('app.url') . '/certificates',
        'can' => 'Emitir Atestado',
    ],
];

$right_menu = [
    [
        'text' => '<i class="fas fa-cog"></i>',
        'title' => 'Configurações',
        'submenu' => $submenuConfig,
        'align' => 'right',
        'can' => 'visualizar menu de configuração',
    ],
];


return [
    # valor default para a tag title, dentro da section title.
    # valor pode ser substituido pela aplicação.
    'title' => config('app.name'),

    # USP_THEME_SKIN deve ser colocado no .env da aplicação 
    'skin' => env('USP_THEME_SKIN', 'uspdev'),

    # chave da sessão. Troque em caso de colisão com outra variável de sessão.
    'session_key' => 'laravel-usp-theme',

    # usado na tag base, permite usar caminhos relativos nos menus e demais elementos html
    # na versão 1 era dashboard_url
    'app_url' => config('app.url'),

    # login e logout
    'logout_method' => 'POST',
    'logout_url' => 'logout',
    'login_url' => 'login',

    # menus
    'menu' => $menu,
    'right_menu' => $right_menu,
];
