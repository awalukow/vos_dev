<?php
namespace App\Services;
use App\Models\{Customer,TicketOrder};
use Illuminate\Support\Facades\{DB,Hash,Mail};
use Illuminate\Validation\ValidationException;

class TicketDelivery {
    public static function audit(string $action,string $subject,?int $actor=null,array $details=[]): void {
        DB::table('ticket_audit_logs')->insert(['actor_id'=>$actor,'action'=>$action,'subject'=>$subject,'details'=>json_encode($details),'created_at'=>now(),'updated_at'=>now()]);
    }
    public function issueOtp(Customer $customer, bool $manual=false, ?int $actor=null): string {
        return DB::transaction(function () use ($customer,$manual,$actor) {
            $customer = Customer::lockForUpdate()->findOrFail($customer->id);
            if ($customer->email_verified_at) throw ValidationException::withMessages(['email'=>'This email is already verified.']);
            if (!$manual && $customer->otp_sent_at && $customer->otp_sent_at->gt(now()->subMinute())) throw ValidationException::withMessages(['otp'=>'Please wait one minute before requesting another code.']);
            $code=(string)random_int(100000,999999);
            $customer->update(['otp_hash'=>Hash::make($code),'otp_expires_at'=>now()->addMinutes(10),'otp_attempts'=>0,'otp_sent_at'=>now()]);
            self::audit($manual?'otp.manual_generated':'otp.sent',(string)$customer->id,$actor);
            return $code;
        });
    }
    public function emailOtp(Customer $customer,string $code): bool {
        try {
            Mail::raw("Hello {$customer->name},\n\nYour VOS Tickets verification code is {$code}.\nIt expires in 10 minutes. Never share this code except on the VOS verification page.",fn($m)=>$m->to($customer->email)->subject('Verify your VOS Tickets email'));
            return true;
        } catch (\Throwable $e) {
            report($e); return false;
        }
    }
    public function verifyOtp(Customer $customer,string $code): bool {
        return DB::transaction(function () use ($customer,$code) {
            $customer=Customer::lockForUpdate()->findOrFail($customer->id);
            if (!$customer->is_active || !$customer->otp_hash || !$customer->otp_expires_at || $customer->otp_expires_at->isPast() || $customer->otp_attempts>=5) return false;
            $customer->increment('otp_attempts');
            if (!Hash::check($code,$customer->otp_hash)) return false;
            $customer->update(['email_verified_at'=>now(),'otp_hash'=>null,'otp_expires_at'=>null,'otp_attempts'=>0]);
            return true;
        });
    }
    public function qr(string $url, ?string $label=null): string {
        require_once base_path('vendor/tecnickcom/tcpdf/tcpdf_barcodes_2d.php');
        $png=(new \TCPDF2DBarcode($url,'QRCODE,M'))->getBarcodePngData(6,6,[26,23,45]);
        if ($label===null) return $png;
        $qr=imagecreatefromstring($png);
        $padding=24; $heading=36;
        $width=max(imagesx($qr)+2*$padding,strlen($label)*imagefontwidth(5)+2*$padding);
        $canvas=imagecreatetruecolor($width,imagesy($qr)+2*$padding+$heading);
        imagefill($canvas,0,0,imagecolorallocate($canvas,255,255,255));
        imagestring($canvas,5,(int)(($width-strlen($label)*imagefontwidth(5))/2),$padding,$label,imagecolorallocate($canvas,26,23,45));
        imagecopy($canvas,$qr,(int)(($width-imagesx($qr))/2),$padding+$heading,0,0,imagesx($qr),imagesy($qr));
        ob_start(); imagepng($canvas); $result=ob_get_clean();
        imagedestroy($qr); imagedestroy($canvas);
        return $result;
    }
    public function tickets(TicketOrder $order): bool {
        $order->refresh()->load(['items','event','customer']);
        if ($order->RowStatus !== 0 || $order->status !== 'paid') return false;
        try {
            Mail::send('tickets.emails.confirmed',['order'=>$order],function ($m) use ($order) {
                $m->to($order->customer->email)->subject('Your tickets · '.$order->event->title);
                $m->attachData($this->qr(route('tickets.receipt',$order->reference),$order->booking_code),'booking-qr.png',['mime'=>'image/png']);
                foreach ($order->items as $index=>$item) {
                    $m->attachData($this->qr(route('tickets.validate',$item->token),$item->booking_label),'ticket-'.($index+1).'.png',['mime'=>'image/png']);
                }
            });
            $order->update(['tickets_emailed_at'=>now()]);
            return true;
        } catch (\Throwable $e) { report($e); return false; }
    }
}
