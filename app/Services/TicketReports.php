<?php
namespace App\Services;

use App\Models\{TicketEvent,TicketOrder};

class TicketReports {
    public function orders(array $filters=[],bool $includeRemoved=false) {
        $query=TicketOrder::query();
        if ($includeRemoved) $query->withoutGlobalScope('active');
        return $query->when($filters['q']??null,fn($q,$term)=>$q->where(function($q) use($term) {
            $q->where('reference','like','%'.$term.'%')->orWhere('booking_code','like','%'.$term.'%')
                ->orWhere('referral_code','like','%'.$term.'%')->orWhere('singer_name','like','%'.$term.'%')
                ->orWhereHas('customer',fn($q)=>$q->where('name','like','%'.$term.'%')->orWhere('email','like','%'.$term.'%'));
        }))->when($filters['status']??null,function($q,$status) {
            if ($status==='expired') $q->where(fn($q)=>$q->where('status','expired')->orWhere(fn($q)=>$q->where('status','awaiting_payment')->where('expires_at','<=',now())));
            else { $q->where('status',$status); if ($status==='awaiting_payment') $q->where('expires_at','>',now()); }
        });
    }
    public static function status(TicketOrder $order): string {
        return $order->status==='awaiting_payment' && $order->expires_at?->lte(now())?'expired':$order->status;
    }
    public static function fees(TicketOrder $order): int {
        return (int)($order->payment_snapshot['fees']['processing']??0)+(int)($order->payment_snapshot['fees']['platform']??0);
    }
    public function dashboard(?int $eventId=null): array {
        $events=TicketEvent::with('classes')->orderByDesc('starts_at')->get();
        $selected=$eventId ? $events->firstWhere('id',$eventId) : null;
        if ($eventId) abort_unless($selected,404);
        $scope=$selected ? $events->where('id',$selected->id) : $events;
        $orders=TicketOrder::whereIn('ticket_event_id',$scope->modelKeys())->withCount('items')->get();
        $totals=['capacity'=>0,'paid'=>0,'unpaid'=>0,'remaining'=>0,'revenue'=>0,'fees'=>0,'receipts'=>0,'discount'=>0,'paid_orders'=>0,'review'=>0,'unpaid_value'=>0];
        $rows=[]; $referrals=[]; $statuses=[];
        foreach ($scope as $event) {
            $row=['event'=>$event,'capacity'=>(int)$event->classes->sum('capacity'),'paid'=>0,'unpaid'=>0,'remaining'=>0,'revenue'=>0,'fees'=>0,'receipts'=>0,'discount'=>0,'paid_orders'=>0,'review'=>0,'unpaid_value'=>0];
            foreach ($orders->where('ticket_event_id',$event->id) as $order) {
                $status=self::status($order);
                $statuses[$status]=($statuses[$status]??0)+1;
                if ($status==='paid') {
                    $fees=self::fees($order); $revenue=$order->total-$fees;
                    $row['paid']+=$order->items_count; $row['paid_orders']++;
                    $row['revenue']+=$revenue; $row['fees']+=$fees; $row['receipts']+=$order->total; $row['discount']+=$order->discount;
                    if ($order->referral_code) {
                        $key=$order->referral_code;
                        if (!isset($referrals[$key])) $referrals[$key]=['name'=>$order->singer_name,'code'=>$key,'seats'=>0,'orders'=>0,'revenue'=>0];
                        $referrals[$key]['seats']+=$order->items_count; $referrals[$key]['orders']++; $referrals[$key]['revenue']+=$revenue;
                    }
                } elseif (in_array($status,['awaiting_payment','payment_review','midtrans_pending'],true)) {
                    $row['unpaid']+=$order->items_count; $row['unpaid_value']+=$order->total-self::fees($order);
                    if ($status==='payment_review') $row['review']++;
                }
            }
            $row['remaining']=max(0,$row['capacity']-$row['paid']-$row['unpaid']);
            foreach ($totals as $key=>$value) $totals[$key]+=$row[$key];
            $rows[]=$row;
        }
        $referrals=array_values($referrals);
        usort($referrals,fn($a,$b)=>($b['seats']<=>$a['seats']) ?: (($b['revenue']<=>$a['revenue']) ?: strcmp($a['code'],$b['code'])));
        return compact('events','selected','totals','rows','statuses')+['referrals'=>array_slice($referrals,0,5),'generatedAt'=>now()->timezone('Asia/Jakarta')];
    }
}
