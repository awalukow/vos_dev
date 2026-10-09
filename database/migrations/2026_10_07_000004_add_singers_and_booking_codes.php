<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void {
        Schema::create('portal_singers', function (Blueprint $t) {
            $t->id(); $t->string('name',120); $t->string('referral_code',30)->unique();
            $t->boolean('active')->default(true); $t->timestamps();
        });
        Schema::table('ticket_orders', function (Blueprint $t) {
            $t->string('booking_code',5)->nullable()->unique();
            $t->foreignId('portal_singer_id')->nullable()->constrained('portal_singers');
            $t->string('referral_code',30)->nullable(); $t->string('singer_name',120)->nullable();
        });
        // Keep historical references and QR URLs intact when upgrading existing orders.
        DB::table('ticket_orders')->whereNull('booking_code')->orderBy('id')->chunkById(200, function ($orders) {
            foreach ($orders as $order) {
                do {
                    $code='';
                    for ($i=0;$i<5;$i++) $code.='ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'[random_int(0,35)];
                } while (DB::table('ticket_orders')->where('booking_code',$code)->exists());
                DB::table('ticket_orders')->where('id',$order->id)->update(['booking_code'=>$code]);
            }
        });
        $parent=DB::table('portal_menus')->where('key','user_mgmt')->value('id');
        $id=DB::table('portal_menus')->insertGetId(['key'=>'user_mgmt.singers','label'=>'Singer List','route_name'=>'portal.singers.index','parent_id'=>$parent,'sort_order'=>2,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        foreach (DB::table('roles')->whereIn('name',['administrator','adm2'])->pluck('id') as $role) {
            DB::table('role_menu_permissions')->insert(['role_id'=>$role,'portal_menu_id'=>$id,'created_at'=>now(),'updated_at'=>now()]);
        }
    }
    public function down(): void {
        $id=DB::table('portal_menus')->where('key','user_mgmt.singers')->value('id');
        DB::table('role_menu_permissions')->where('portal_menu_id',$id)->delete();
        DB::table('portal_menus')->where('id',$id)->delete();
        Schema::table('ticket_orders', function (Blueprint $t) {
            $t->dropForeign(['portal_singer_id']);
            $t->dropUnique(['booking_code']);
            $t->dropColumn(['booking_code','portal_singer_id','referral_code','singer_name']);
        });
        Schema::dropIfExists('portal_singers');
    }
};
