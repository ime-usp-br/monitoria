<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEnrollmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_class_id')->unsigned();
            $table->unsignedBigInteger('student_id')->unsigned();
            $table->boolean('voluntario')->default(0);
            $table->boolean('disponibilidade_diurno')->default(0);
            $table->boolean('disponibilidade_noturno')->default(0);
            $table->string('preferencia_horario');
            $table->string('observacoes')->nullable();
            $table->timestamps();
            $table->unique(['school_class_id','student_id']);
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
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
        Schema::dropIfExists('enrollments');
    }
}
