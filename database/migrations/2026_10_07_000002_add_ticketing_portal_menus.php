<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        $parent = DB::table('portal_menus')->insertGetId([
            'key'=>'ticketing', 'label'=>'Ticketing', 'icon'=>'calendar-days',
            'route_name'=>null, 'parent_id'=>null, 'sort_order'=>6,
            'is_active'=>true, 'created_at'=>$now, 'updated_at'=>$now,
        ]);
        $children = [
            'events'=>'Events',
            'venues'=>'Venue Designer',
            'customers'=>'Customers & OTP',
            'methods'=>'Payment Methods',
            'payments'=>'Payment Approvals',
        ];
        $menuIds = ['ticketing'=>$parent];
        foreach ($children as $key=>$label) {
            $menuIds[$key] = DB::table('portal_menus')->insertGetId([
                'key'=>'ticketing.'.$key, 'label'=>$label,
                'route_name'=>'portal.ticketing.'.$key, 'parent_id'=>$parent,
                'sort_order'=>count($menuIds), 'is_active'=>true,
                'created_at'=>$now, 'updated_at'=>$now,
            ]);
        }
        foreach (DB::table('roles')->whereIn('name',['administrator','adm2','ticket_operator'])->get() as $role) {
            $grants = $role->name==='ticket_operator'
                ? [$parent,$menuIds['payments']]
                : array_values($menuIds);
            foreach ($grants as $menuId) {
                DB::table('role_menu_permissions')->insert([
                    'role_id'=>$role->id,'portal_menu_id'=>$menuId,
                    'created_at'=>$now,'updated_at'=>$now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $keys = ['ticketing','ticketing.events','ticketing.venues','ticketing.customers','ticketing.methods','ticketing.payments'];
        $ids = DB::table('portal_menus')->whereIn('key',$keys)->pluck('id');
        DB::table('role_menu_permissions')->whereIn('portal_menu_id',$ids)->delete();
        DB::table('portal_menus')->whereIn('key',array_slice($keys,1))->delete();
        DB::table('portal_menus')->where('key','ticketing')->delete();
    }
};