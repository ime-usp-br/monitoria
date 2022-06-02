<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFrequenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('frequencies', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->boolean('registered')->default(FALSE);
            $table->integer('month');
            $table->unsignedBigInteger('student_id')->unsigned();
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->unsignedBigInteger('school_class_id')->unsigned();
            $table->foreign('school_class_id')->references('id')->on('school_classes')->onDelete('cascade');
            $table->unique(['student_id', 'school_class_id', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('frequencies');
    }
}
