<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Activity;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Activity::firstOrCreate(['description'=>'Atendimento a alunos']);
        Activity::firstOrCreate(['description'=>'Correção de listas de exercícios']);
        Activity::firstOrCreate(['description'=>'Fiscalização de provas']);
    }
}
