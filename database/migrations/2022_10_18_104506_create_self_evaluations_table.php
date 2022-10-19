<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSelfEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('self_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('selection_id');
            $table->unsignedInteger("student_amount");
            $table->unsignedInteger("homework_amount");
            $table->string("secondary_activity", 512)->nullable();
            $table->string("workload");
            $table->string("workload_reason", 512)->nullable();
            $table->string("comments", 512)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('self_evaluations');
    }
}
