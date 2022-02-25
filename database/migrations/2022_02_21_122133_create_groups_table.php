<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_term_id')->unsigned();
            $table->unsignedBigInteger('department_id')->unsigned();
            $table->string('codtur');
            $table->string('tiptur')->nullable();
            $table->string('nomdis')->nullable();
            $table->string('coddis');
            $table->timestamp('dtainitur')->nullable();
            $table->timestamp('dtafimtur')->nullable();
            $table->timestamps();
            $table->unique(['codtur', 'coddis']);
            $table->foreign('school_term_id')->references('id')->on('school_terms')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('groups');
    }
}
