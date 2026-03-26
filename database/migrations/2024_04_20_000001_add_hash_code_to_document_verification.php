<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add hash_code to DocumentVerification so the portal can link
     * portal_documents to DocumentVerification rows via the QR verify URL.
     */
    public function up(): void
    {
        Schema::table('DocumentVerification', function (Blueprint $table) {
            // The hash_code from portal_documents — used in /verify/{hashCode}
            $table->string('hash_code')->nullable()->unique()->after('signatureId');
        });
    }

    public function down(): void
    {
        Schema::table('DocumentVerification', function (Blueprint $table) {
            $table->dropColumn('hash_code');
        });
    }
};
