<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModelHasScholarships extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('model_has_scholarships', function (Blueprint $table) {
            $table->unsignedBigInteger("scholarship_id");
            $table->string("model_type");
            $table->unsignedBigInteger("model_id");
            $table->index(["model_id", 'model_type'], 'model_has_scholarships_model_id_model_type_index');

            $table->foreign('scholarship_id')
                ->references('id')
                ->on("scholarships")
                ->onDelete('cascade');

            $table->primary(['scholarship_id', "model_id", 'model_type'],
                'model_has_scholarships_scholarship_model_type_primary');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('model_has_scholarships');
    }
}
