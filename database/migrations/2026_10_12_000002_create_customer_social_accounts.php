<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 16);
            $table->string('provider_id', 191);
            $table->timestamps();
            $table->unique(['provider', 'provider_id']);
            $table->unique(['customer_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_social_accounts');
    }
};
