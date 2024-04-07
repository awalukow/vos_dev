<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schedule', function ($table) {
            $table->increments('id');
            $table->datetime('program_date');
            $table->datetime('program_until');
            $table->string('event_name');
            $table->string('event_detail');
            $table->string('event_image');
            $table->string('event_imageCaptionUrl');
            $table->boolean('isSundayService');
            $table->integer('rowstatus');
            $table->string('createdBy');
            $table->timestamp('createdDate')->nullable();
            $table->string('modifiedBy');
            $table->timestamp('modifiedDate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
