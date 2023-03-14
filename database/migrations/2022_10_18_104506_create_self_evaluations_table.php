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
            $table->text("secondary_activity")->nullable();
            $table->unsignedInteger("workload");
            $table->text("workload_reason")->nullable();
            $table->text("comments")->nullable();
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
