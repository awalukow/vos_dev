<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('ticket_payment_methods', function (Blueprint $table) {
            $table->boolean('parameters_locked')->default(false);
        });
    }
    public function down(): void {
        Schema::table('ticket_payment_methods', fn (Blueprint $table) => $table->dropColumn('parameters_locked'));
    }
};
