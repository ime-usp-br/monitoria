<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateColumnPublicNoticeFilePathToSchoolTermsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('school_terms', function (Blueprint $table) {
            $table->string("public_notice_file_path")->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('school_terms', function (Blueprint $table) {
            $table->string("public_notice_file_path")->change();
        });
    }
}
