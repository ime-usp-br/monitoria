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

        Permission::create(['name' => 'visualizar menu de configuração']);

        Permission::create(['name' => 'editar usuario']);

        Permission::create(['name' => 'visualizar periodo letivo']);
        Permission::create(['name' => 'criar periodo letivo']);
        Permission::create(['name' => 'editar periodo letivo']);
        Permission::create(['name' => 'deletar periodo letivo']);
        
        Permission::create(['name' => 'visualizar turma']);
        Permission::create(['name' => 'criar turma']);
        Permission::create(['name' => 'editar turma']);
        Permission::create(['name' => 'deletar turma']);
        Permission::create(['name' => 'importar turmas do replicado']);
        Permission::create(['name' => 'buscar turmas']);

        Permission::create(['name' => 'visualizar solicitação de monitor']);
        Permission::create(['name' => 'criar solicitação de monitor']);
        Permission::create(['name' => 'editar solicitação de monitor']);
        Permission::create(['name' => 'deletar solicitação de monitor']);

        Permission::create(['name' => 'visualizar docente']);
        Permission::create(['name' => 'criar docente']);
        Permission::create(['name' => 'editar docente']);
        Permission::create(['name' => 'deletar docente']);

        Permission::create(['name' => 'visualizar inscrição']);
        Permission::create(['name' => 'fazer inscrição']);
        Permission::create(['name' => 'editar inscrição']);
        Permission::create(['name' => 'deletar inscrição']);

        Permission::create(['name' => 'baixar histórico escolar']);

        Role::create(['name' => 'Secretária'])
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
            ->givePermissionTo('baixar histórico escolar');

        Role::create(['name' => 'Docente'])
            ->givePermissionTo('visualizar menu de configuração')
            ->givePermissionTo('visualizar periodo letivo')
            ->givePermissionTo('visualizar turma')
            ->givePermissionTo('criar turma')
            ->givePermissionTo('editar turma')
            ->givePermissionTo('visualizar solicitação de monitor')
            ->givePermissionTo('criar solicitação de monitor')
            ->givePermissionTo('editar solicitação de monitor');

        Role::create(['name' => 'Aluno'])
            ->givePermissionTo('visualizar periodo letivo')
            ->givePermissionTo('visualizar turma')
            ->givePermissionTo('visualizar inscrição')
            ->givePermissionTo('fazer inscrição')
            ->givePermissionTo('editar inscrição')
            ->givePermissionTo('deletar inscrição');

        Role::create(['name' => 'Aluno sem cadastro']);
        
        Role::create(['name' => 'Monitor']);

        Role::create(['name' => 'Presidente de Comissão'])
            ->givePermissionTo('visualizar menu de configuração')
            ->givePermissionTo('baixar histórico escolar');

        Role::create(['name' => 'Vice Presidente de Comissão'])
            ->givePermissionTo('visualizar menu de configuração')
            ->givePermissionTo('baixar histórico escolar');

        Role::create(['name' => 'Membro Comissão'])
            ->givePermissionTo('visualizar menu de configuração')
            ->givePermissionTo('baixar histórico escolar');

        Role::create(['name' => 'Administrador'])
            ->givePermissionTo(Permission::all());

    }
}
