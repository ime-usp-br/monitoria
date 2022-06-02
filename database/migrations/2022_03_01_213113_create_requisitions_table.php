<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequisitionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('instructor_id')->unsigned();
            $table->unsignedBigInteger('school_class_id')->unsigned();
            $table->integer('requested_number');
            $table->string('priority');
            $table->timestamps();
            $table->unique(['instructor_id','school_class_id']);
            $table->foreign('instructor_id')->references('id')->on('instructors')->onDelete('cascade');
            $table->foreign('school_class_id')->references('id')->on('school_classes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('requisitions');
    }
}
