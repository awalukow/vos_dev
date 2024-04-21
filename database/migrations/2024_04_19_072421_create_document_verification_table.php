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
        Schema::create('DocumentVerification', function ($table) {
            $table->increments('id');
            $table->string('documentName');
            $table->datetime('documentSignature');
            $table->string('signatureBy');
            $table->string('signatureId');
            $table->boolean('documentStatus');
            $table->boolean('signatureStatus');
            $table->datetime('document_createdDate');
            $table->datetime('document_validThru')->nullable();
            $table->datetime('signature_signedDate');
            $table->datetime('signature_validThru')->nullable();
            $table->boolean('isForcedInvalidity')->default(false);
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
        Schema::dropIfExists('document_verification');
    }
};
