<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('ticket_events',function(Blueprint $t) { $t->json('layout_dividers')->nullable(); });
    }
    public function down(): void {
        Schema::table('ticket_events',function(Blueprint $t) { $t->dropColumn('layout_dividers'); });
    }
};
