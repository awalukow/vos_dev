<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void {
        Schema::table('ticket_events',function(Blueprint $t) {
            $t->json('memory_photos')->nullable();
            $t->string('memory_video',11)->nullable();
        });
        $parent=DB::table('portal_menus')->where('key','ticketing')->value('id');
        $id=DB::table('portal_menus')->insertGetId(['key'=>'ticketing.dashboard','label'=>'Dashboard','route_name'=>'portal.ticketing.dashboard','parent_id'=>$parent,'sort_order'=>0,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        foreach(DB::table('roles')->whereIn('name',['administrator','adm2','ticket_operator'])->pluck('id') as $role) {
            DB::table('role_menu_permissions')->insert(['role_id'=>$role,'portal_menu_id'=>$id,'created_at'=>now(),'updated_at'=>now()]);
        }
    }
    public function down(): void {
        $id=DB::table('portal_menus')->where('key','ticketing.dashboard')->value('id');
        DB::table('role_menu_permissions')->where('portal_menu_id',$id)->delete();
        DB::table('portal_menus')->where('id',$id)->delete();
        Schema::table('ticket_events',fn(Blueprint $t)=>$t->dropColumn(['memory_photos','memory_video']));
    }
};
