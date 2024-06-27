<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Scholarship;

class ScholarshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Scholarship::firstOrCreate(["name"=>"PEEG"]);
        Scholarship::firstOrCreate(["name"=>"PAE"]);
        Scholarship::firstOrCreate(["name"=>"PAPI"]);
        Scholarship::firstOrCreate(["name"=>"Pós do IME"]);
        Scholarship::firstOrCreate(["name"=>"Outras"]);
    }
}
