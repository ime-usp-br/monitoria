<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchoolTermsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('school_terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('year');
            $table->string('period');
            $table->string('status');
            $table->string('evaluation_period');
            $table->integer('max_enrollments');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('start_date_requisitions')->nullable();
            $table->timestamp('end_date_requisitions')->nullable();
            $table->timestamp('start_date_enrollments')->nullable();
            $table->timestamp('end_date_enrollments')->nullable();
            $table->timestamps();
            $table->unique(['year', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('school_terms');
    }
}
