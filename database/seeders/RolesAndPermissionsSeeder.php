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

        Permission::create(['name' => 'editar usuario']);
        Permission::create(['name' => 'visualizar periodo letivo']);
        Permission::create(['name' => 'criar periodo letivo']);
        Permission::create(['name' => 'editar periodo letivo']);
        Permission::create(['name' => 'deletar periodo letivo']);


        Role::create(['name' => 'Secretária'])
            ->givePermissionTo('editar usuario')
            ->givePermissionTo('visualizar periodo letivo')
            ->givePermissionTo('criar periodo letivo')
            ->givePermissionTo('editar periodo letivo')
            ->givePermissionTo('deletar periodo letivo');

        Role::create(['name' => 'Docente'])
            ->givePermissionTo('visualizar periodo letivo');

        Role::create(['name' => 'Aluno'])
            ->givePermissionTo('visualizar periodo letivo');

        Role::create(['name' => 'Monitor'])
            ->givePermissionTo('visualizar periodo letivo');

        Role::create(['name' => 'Presidente de Comissão'])
            ->givePermissionTo('visualizar periodo letivo');

        Role::create(['name' => 'Vice Presidente de Comissão'])
            ->givePermissionTo('visualizar periodo letivo');

        Role::create(['name' => 'Membro Comissão'])
            ->givePermissionTo('visualizar periodo letivo');

        Role::create(['name' => 'Administrador'])
            ->givePermissionTo(Permission::all());

    }
}
