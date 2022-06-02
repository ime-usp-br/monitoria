<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchoolRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('school_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->unsigned();
            $table->unsignedBigInteger('schoolterm_id')->unsigned();
            $table->string('file_path');
            $table->timestamps();
            $table->unique(['student_id','schoolterm_id']);
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('schoolterm_id')->references('id')->on('school_terms')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('school_records');
    }
}
