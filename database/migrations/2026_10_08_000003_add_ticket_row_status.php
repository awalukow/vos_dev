<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        foreach (['ticket_events', 'ticket_orders', 'ticket_order_items'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->tinyInteger('RowStatus')->default(0)->index();
            });
        }
    }

    public function down(): void {
        foreach (['ticket_order_items', 'ticket_orders', 'ticket_events'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('RowStatus'));
        }
    }
};
