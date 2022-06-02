<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClassScheduleSchoolClassTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('class_schedule_school_class', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_schedule_id')->unsigned();
            $table->unsignedBigInteger('school_class_id')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('class_schedule_school_class');
    }
}
