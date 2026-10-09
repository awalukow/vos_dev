<?php
namespace App\Console\Commands;
use App\Models\TicketOrder;
use App\Services\MidtransPayments;
use Illuminate\Console\Command;

class SyncMidtrans extends Command {
    protected $signature='tickets:sync-midtrans';
    protected $description='Reconcile pending Midtrans payments using the provider status API';
    public function handle(MidtransPayments $payments): int {
        $failed=false;
        TicketOrder::where('status','midtrans_pending')->chunkById(100,function($orders) use($payments,&$failed) {
            foreach ($orders as $order) {
                try {
                    $payments->sync($order);
                    if ($order->fresh()->status==='midtrans_pending' && $order->expires_at?->isPast()) $this->warn('Payment still unresolved; inventory retained: '.$order->reference);
                } catch (\Throwable $e) {
                    $failed=true;
                    $this->error('Unable to reconcile '.$order->reference.'. Check Midtrans connectivity and credentials.');
                }
            }
        });
        return $failed?1:0;
    }
}
