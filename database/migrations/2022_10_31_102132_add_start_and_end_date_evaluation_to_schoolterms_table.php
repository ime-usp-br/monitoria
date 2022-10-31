<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStartAndEndDateEvaluationToSchooltermsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('school_terms', function (Blueprint $table) {
            $table->timestamp('start_date_evaluations')->nullable();
            $table->timestamp('end_date_evaluations')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('school_terms', function (Blueprint $table) {
            $table->dropColumn('start_date_evaluations');
            $table->dropColumn('end_date_evaluations');
        });
    }
}
