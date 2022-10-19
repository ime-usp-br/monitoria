<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'visualizar menu de configuração']);

        Permission::firstOrCreate(['name' => 'editar usuario']);

        Permission::firstOrCreate(['name' => 'visualizar periodo letivo']);
        Permission::firstOrCreate(['name' => 'criar periodo letivo']);
        Permission::firstOrCreate(['name' => 'editar periodo letivo']);
        Permission::firstOrCreate(['name' => 'deletar periodo letivo']);
        
        Permission::firstOrCreate(['name' => 'visualizar turma']);
        Permission::firstOrCreate(['name' => 'criar turma']);
        Permission::firstOrCreate(['name' => 'editar turma']);
        Permission::firstOrCreate(['name' => 'deletar turma']);
        Permission::firstOrCreate(['name' => 'importar turmas do replicado']);
        Permission::firstOrCreate(['name' => 'buscar turmas']);

        Permission::firstOrCreate(['name' => 'visualizar solicitação de monitor']);
        Permission::firstOrCreate(['name' => 'criar solicitação de monitor']);
        Permission::firstOrCreate(['name' => 'editar solicitação de monitor']);
        Permission::firstOrCreate(['name' => 'deletar solicitação de monitor']);

        Permission::firstOrCreate(['name' => 'visualizar docente']);
        Permission::firstOrCreate(['name' => 'criar docente']);
        Permission::firstOrCreate(['name' => 'editar docente']);
        Permission::firstOrCreate(['name' => 'deletar docente']);

        Permission::firstOrCreate(['name' => 'visualizar inscrição']);
        Permission::firstOrCreate(['name' => 'fazer inscrição']);
        Permission::firstOrCreate(['name' => 'editar inscrição']);
        Permission::firstOrCreate(['name' => 'deletar inscrição']);

        Permission::firstOrCreate(['name' => 'baixar histórico escolar']);

        Permission::firstOrCreate(['name' => 'Selecionar monitor']);
        Permission::firstOrCreate(['name' => 'Preterir monitor']);

        Permission::firstOrCreate(['name' => 'Disparar emails']);

        Permission::firstOrCreate(['name' => 'registrar frequencia']);

        Permission::firstOrCreate(['name' => 'gerar relatorio']);

        Permission::firstOrCreate(['name' => 'visualizar monitores']);

        Permission::firstOrCreate(['name' => 'visualizar todos inscritos']);

        Permission::firstOrCreate(['name' => 'Emitir Atestado']);

        Permission::firstOrCreate(['name' => 'Editar E-mails']);

        Permission::firstOrCreate(['name' => 'Visualizar auto avaliações']);

        Role::firstOrCreate(['name' => 'Secretaria'])
            ->givePermissionTo('visualizar todos inscritos')
            ->givePermissionTo('visualizar menu de configuração')
            ->givePermissionTo('editar usuario')
            ->givePermissionTo('visualizar periodo letivo')
            ->givePermissionTo('criar periodo letivo')
            ->givePermissionTo('editar periodo letivo')
            ->givePermissionTo('visualizar turma')
            ->givePermissionTo('criar turma')
            ->givePermissionTo('editar turma')
            ->givePermissionTo('importar turmas do replicado')
            ->givePermissionTo('visualizar docente')
            ->givePermissionTo('buscar turmas')
            ->givePermissionTo('visualizar inscrição')
            ->givePermissionTo('editar inscrição')
            ->givePermissionTo('deletar inscrição')
            ->givePermissionTo('Selecionar monitor')
            ->givePermissionTo('Preterir monitor')
            ->givePermissionTo('Disparar emails')
            ->givePermissionTo('registrar frequencia')
            ->givePermissionTo('gerar relatorio')
            ->givePermissionTo('visualizar monitores')
            ->givePermissionTo('Editar E-mails')
            ->givePermissionTo('Visualizar auto avaliações')
            ->givePermissionTo('baixar histórico escolar');

        Role::firstOrCreate(['name' => 'Docente'])
            ->givePermissionTo('visualizar menu de configuração')
            ->givePermissionTo('visualizar periodo letivo')
            ->givePermissionTo('visualizar turma')
            ->givePermissionTo('criar turma')
            ->givePermissionTo('editar turma')
            ->givePermissionTo('visualizar solicitação de monitor')
            ->givePermissionTo('criar solicitação de monitor')
            ->givePermissionTo('registrar frequencia')
            ->givePermissionTo('visualizar monitores')
            ->givePermissionTo('editar solicitação de monitor');

        Role::firstOrCreate(['name' => 'Aluno'])
            ->givePermissionTo('visualizar periodo letivo')
            ->givePermissionTo('visualizar turma')
            ->givePermissionTo('visualizar inscrição')
            ->givePermissionTo('fazer inscrição')
            ->givePermissionTo('editar inscrição')
            ->givePermissionTo('Emitir Atestado')
            ->givePermissionTo('deletar inscrição');
        
        Role::firstOrCreate(['name' => 'Monitor']);

        Role::firstOrCreate(['name' => 'Presidente de Comissão'])
            ->givePermissionTo('Visualizar auto avaliações')
            ->givePermissionTo('visualizar todos inscritos');

        Role::firstOrCreate(['name' => 'Vice Presidente de Comissão'])
            ->givePermissionTo('Visualizar auto avaliações')
            ->givePermissionTo('visualizar todos inscritos');

        Role::firstOrCreate(['name' => 'Membro Comissão'])
            ->givePermissionTo('Visualizar auto avaliações')
            ->givePermissionTo('visualizar todos inscritos')
            ->givePermissionTo('Selecionar monitor')
            ->givePermissionTo('Preterir monitor')
            ->givePermissionTo('gerar relatorio')
            ->givePermissionTo('visualizar monitores')
            ->givePermissionTo('baixar histórico escolar');

        Role::firstOrCreate(['name' => 'Administrador'])
            ->givePermissionTo(Permission::all());

    }
}
