<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveEvaluationPeriodFromSchooltermsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('school_terms', function (Blueprint $table) {
            $table->dropColumn("evaluation_period");
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
            $table->string('evaluation_period');
        });
    }
}
