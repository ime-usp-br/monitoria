<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityTeachingAssistantApplicationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_teaching_assistant_application', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_id')->unsigned();
            $table->unsignedBigInteger('teaching_assistant_application_id')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activity_teaching_assistant_application');
    }
}
