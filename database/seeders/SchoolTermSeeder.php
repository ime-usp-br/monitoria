<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolTerm;

class SchoolTermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $schoolTerm = new SchoolTerm;
        $schoolTerm->year = 2022;
        $schoolTerm->period = "1° Semestre";
        $schoolTerm->status = "Aberto";
        $schoolTerm->evaluation_period = "Aberto";
        $schoolTerm->started_at = "2022-03-14";
        $schoolTerm->finished_at = "2022-07-30";
        $schoolTerm->start_date_teacher_requests = "2022-02-01";
        $schoolTerm->end_date_teacher_requests = "2022-02-30";
        $schoolTerm->start_date_student_registration = "2022-02-01";
        $schoolTerm->end_date_student_registration = "2022-02-30";
        $schoolTerm->save();


        $schoolTerm = new SchoolTerm;
        $schoolTerm->year = 2021;
        $schoolTerm->period = "1° Semestre";
        $schoolTerm->status = "Fechado";
        $schoolTerm->evaluation_period = "Fechado";
        $schoolTerm->started_at = "2021-03-14";
        $schoolTerm->finished_at = "2021-07-30";
        $schoolTerm->start_date_teacher_requests = "2021-02-01";
        $schoolTerm->end_date_teacher_requests = "2021-02-30";
        $schoolTerm->start_date_student_registration = "2021-02-01";
        $schoolTerm->end_date_student_registration = "2021-02-30";
        $schoolTerm->save();


        $schoolTerm = new SchoolTerm;
        $schoolTerm->year = 2021;
        $schoolTerm->period = "2° Semestre";
        $schoolTerm->status = "Fechado";
        $schoolTerm->evaluation_period = "Fechado";
        $schoolTerm->started_at = "2021-08-14";
        $schoolTerm->finished_at = "2021-12-30";
        $schoolTerm->start_date_teacher_requests = "2021-07-01";
        $schoolTerm->end_date_teacher_requests = "2021-07-30";
        $schoolTerm->start_date_student_registration = "2021-07-01";
        $schoolTerm->end_date_student_registration = "2021-07-30";
        $schoolTerm->save();
    }
}
