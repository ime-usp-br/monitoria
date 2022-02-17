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


        Role::create(['name' => 'Secretária'])
            ->givePermissionTo('editar usuario');

        Role::create(['name' => 'Docente']);
        Role::create(['name' => 'Aluno']);
        Role::create(['name' => 'Monitor']);
        Role::create(['name' => 'Presidente de Comissão']);
        Role::create(['name' => 'Vice Presidente de Comissão']);
        Role::create(['name' => 'Membro Comissão']);


        Role::create(['name' => 'Administrador'])
            ->givePermissionTo(Permission::all());

    }
}
