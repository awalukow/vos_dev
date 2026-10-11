<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up(): void {
        Schema::create('ticket_fee_settings',function(Blueprint $t) {
            $t->id();
            $t->decimal('tax_percent',5,2)->default(0);
            $t->string('platform_fee_type')->default('fixed');
            $t->decimal('platform_fee_value',12,2)->default(0);
            $t->timestamps();
        });
        $legacy=DB::table('ticket_payment_methods')->where('type','midtrans')->first();
        DB::table('ticket_fee_settings')->insert(['id'=>1,'tax_percent'=>0,
            'platform_fee_type'=>$legacy->platform_fee_type??'fixed','platform_fee_value'=>$legacy->platform_fee_value??0,
            'created_at'=>now(),'updated_at'=>now()]);
        Schema::table('ticket_orders',fn(Blueprint $t)=>$t->json('fee_settings')->nullable());
        // Unsubmitted reservations adopt the shared platform fee; submitted payments
        // retain their original amounts and payment snapshots for reconciliation.
        $settings=['tax_percent'=>0,'platform_fee_type'=>$legacy->platform_fee_type??'fixed','platform_fee_value'=>$legacy->platform_fee_value??0];
        DB::table('ticket_orders')->where('status','awaiting_payment')->orderBy('id')->chunkById(200,function($orders) use($settings) {
            foreach ($orders as $order) {
                $subtotal=(int)$order->total;
                $value=(float)$settings['platform_fee_value'];
                $platform=$subtotal<=0 ? 0 : (int)round($settings['platform_fee_type']==='percent' ? $subtotal*$value/100 : $value);
                $snapshot=json_decode($order->payment_snapshot??'null',true)??[];
                $snapshot=array_merge($snapshot,['subtotal'=>$subtotal,'fees'=>['tax'=>0,'platform'=>$platform]]);
                DB::table('ticket_orders')->where('id',$order->id)->update(['fee_settings'=>json_encode($settings),'total'=>$subtotal+$platform,'payment_snapshot'=>json_encode($snapshot)]);
            }
        });
        DB::table('portal_menus')->where('key','ticketing.methods')->update(['label'=>'Payment Method & Fees','updated_at'=>now()]);
        foreach (DB::table('roles')->where('name','pengurus')->pluck('id') as $role) {
            foreach (DB::table('portal_menus')->whereIn('key',['ticketing','ticketing.methods'])->pluck('id') as $menu) {
                DB::table('role_menu_permissions')->insertOrIgnore(['role_id'=>$role,'portal_menu_id'=>$menu,'created_at'=>now(),'updated_at'=>now()]);
            }
        }
    }

    public function down(): void {
        DB::table('portal_menus')->where('key','ticketing.methods')->update(['label'=>'Payment Methods','updated_at'=>now()]);
        Schema::table('ticket_orders',fn(Blueprint $t)=>$t->dropColumn('fee_settings'));
        Schema::dropIfExists('ticket_fee_settings');
    }
};
