<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstructorEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instructor_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('selection_id');
            $table->unsignedTinyInteger("ease_of_contact");
            $table->unsignedTinyInteger("efficiency");
            $table->unsignedTinyInteger("reliability");
            $table->unsignedTinyInteger("overall");
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
        Schema::dropIfExists('instructor_evaluations');
    }
}
