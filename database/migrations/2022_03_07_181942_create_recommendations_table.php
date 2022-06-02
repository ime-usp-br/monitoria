<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecommendationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->unsigned();
            $table->unsignedBigInteger('requisition_id')->unsigned();
            $table->timestamps();
            $table->unique(['student_id','requisition_id']);
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('requisition_id')->references('id')->on('requisitions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('recommendations');
    }
}
