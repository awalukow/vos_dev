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
        Schema::create('pastEvents', function ($table) {
            $table->increments('id');
            $table->string('eventName');
            $table->string('eventDescription');
            $table->string('eventImage');
            $table->string('eventDate');
            $table->integer('Rowstatus');
            $table->string('CreatedBy');
            $table->timestamp('CreatedDate')->nullable();
            $table->string('ModifiedBy');
            $table->timestamp('ModifiedDate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
