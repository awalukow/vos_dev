<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        $parent=DB::table('portal_menus')->where('key','ticketing')->value('id');
        $id=DB::table('portal_menus')->insertGetId(['key'=>'ticketing.orders','label'=>'Order List','route_name'=>'portal.ticketing.orders','parent_id'=>$parent,'sort_order'=>6,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        foreach(DB::table('roles')->whereIn('name',['administrator','adm2','ticket_operator'])->pluck('id') as $role) {
            DB::table('role_menu_permissions')->insert(['role_id'=>$role,'portal_menu_id'=>$id,'created_at'=>now(),'updated_at'=>now()]);
        }
    }
    public function down(): void {
        $id=DB::table('portal_menus')->where('key','ticketing.orders')->value('id');
        DB::table('role_menu_permissions')->where('portal_menu_id',$id)->delete();
        DB::table('portal_menus')->where('id',$id)->delete();
    }
};
