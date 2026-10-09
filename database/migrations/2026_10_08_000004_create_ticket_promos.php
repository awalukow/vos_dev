<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};

return new class extends Migration {
    public function up(): void {
        Schema::create('ticket_promos',function(Blueprint $t) {
            $t->id(); $t->string('code',64)->unique(); $t->string('type');
            $t->unsignedInteger('value')->default(0); $t->unsignedInteger('buy_quantity')->default(1);
            $t->unsignedInteger('free_quantity')->default(1); $t->boolean('active')->default(true);
            $t->boolean('single_use')->default(false); $t->unsignedInteger('daily_limit')->nullable();
            $t->timestamp('expires_at')->nullable(); $t->boolean('user_specific')->default(false); $t->timestamps();
        });
        Schema::create('ticket_promo_customers',function(Blueprint $t) {
            $t->foreignId('ticket_promo_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->primary(['ticket_promo_id','customer_id']);
        });
        Schema::table('ticket_orders',function(Blueprint $t) {
            $t->foreignId('ticket_promo_id')->nullable()->constrained();
            $t->json('promo_snapshot')->nullable(); $t->unsignedBigInteger('discount')->default(0);
            $t->timestamp('promo_applied_at')->nullable();
        });
        $id=DB::table('portal_menus')->insertGetId(['key'=>'ticketing.promos','label'=>'Promo Codes','route_name'=>'portal.ticketing.promos','parent_id'=>DB::table('portal_menus')->where('key','ticketing')->value('id'),'sort_order'=>8,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        foreach(DB::table('roles')->whereIn('name',['administrator','adm2','ticket_operator'])->pluck('id') as $role) DB::table('role_menu_permissions')->insert(['role_id'=>$role,'portal_menu_id'=>$id,'created_at'=>now(),'updated_at'=>now()]);
    }
    public function down(): void {
        $ids=DB::table('portal_menus')->where('key','ticketing.promos')->pluck('id');
        DB::table('role_menu_permissions')->whereIn('portal_menu_id',$ids)->delete();
        DB::table('portal_menus')->whereIn('id',$ids)->delete();
        Schema::table('ticket_orders',function(Blueprint $t) {
            if (DB::getDriverName()!=='sqlite') $t->dropForeign(['ticket_promo_id']);
            $t->dropColumn(['ticket_promo_id','promo_snapshot','discount','promo_applied_at']);
        });
        Schema::dropIfExists('ticket_promo_customers'); Schema::dropIfExists('ticket_promos');
    }
};
