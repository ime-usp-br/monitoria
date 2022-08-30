<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMailTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mail_templates', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("mail_class");
            $table->string("description", 256);
            $table->string("sending_frequency");
            $table->string("sending_date")->nullable();
            $table->string("sending_hour")->nullable();
            $table->boolean("active")->default(false);
            $table->string("subject",256);
            $table->string("body",8192);
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
        Schema::dropIfExists('mail_templates');
    }
}
