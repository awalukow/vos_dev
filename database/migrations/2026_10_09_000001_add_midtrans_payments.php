<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void {
        Schema::table('ticket_payment_methods', function(Blueprint $t) {
            $t->string('environment')->default('sandbox');
            $t->string('merchant_id')->nullable();
            $t->text('server_key')->nullable();
            $t->text('client_key')->nullable();
            foreach (['processing','platform'] as $fee) {
                $t->string($fee.'_fee_type')->default('fixed');
                $t->decimal($fee.'_fee_value',12,2)->default(0);
            }
        });
        Schema::table('ticket_orders', function(Blueprint $t) {
            $t->text('gateway_credentials')->nullable();
            $t->text('gateway_url')->nullable();
        });
        if (!DB::table('ticket_payment_methods')->where('type','midtrans')->exists()) DB::table('ticket_payment_methods')->insert(['name'=>'QRIS (Automated Check)','type'=>'midtrans','instructions'=>'Pay securely through Midtrans. Your payment is confirmed automatically.','active'=>false,'created_at'=>now(),'updated_at'=>now()]);
        $menu=DB::table('portal_menus')->where('key','ticketing.methods')->value('id');
        if ($menu) foreach (DB::table('roles')->where('name','ticket_operator')->pluck('id') as $role) {
            DB::table('role_menu_permissions')->insertOrIgnore(['role_id'=>$role,'portal_menu_id'=>$menu,'created_at'=>now(),'updated_at'=>now()]);
        }
    }
    public function down(): void {
        // Keep historical payment method references intact when rolling back.
        DB::table('ticket_payment_methods')->where('type','midtrans')->update(['active'=>false]);
        Schema::table('ticket_orders',fn(Blueprint $t)=>$t->dropColumn(['gateway_credentials','gateway_url']));
        Schema::table('ticket_payment_methods',fn(Blueprint $t)=>$t->dropColumn(['environment','merchant_id','server_key','client_key','processing_fee_type','processing_fee_value','platform_fee_type','platform_fee_value']));
    }
};
