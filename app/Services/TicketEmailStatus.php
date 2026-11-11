<?php
namespace App\Services;
use App\Models\{Customer,TicketEmailDelivery};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class TicketEmailStatus {
    public function start(Customer $customer,string $kind,?int $order=null,?string $recipient=null): TicketEmailDelivery {
        return TicketEmailDelivery::create(['customer_id'=>$customer->id,'ticket_order_id'=>$order,
            'recipient'=>strtolower($recipient ?? $customer->email),'kind'=>$kind,
            'message_id'=>Str::uuid().'@tickets.voiceofsoulchoir.id']);
    }
    public function fail(TicketEmailDelivery $delivery,string $detail): void {
        DB::transaction(function () use ($delivery,$detail) {
            $customer=Customer::lockForUpdate()->findOrFail($delivery->customer_id);
            $delivery=TicketEmailDelivery::lockForUpdate()->findOrFail($delivery->id);
            // Repeated inbox scans must not reopen an already handled failure.
            if ($delivery->failed_at) {
                if (!$delivery->resolved_at) $delivery->update(['error_detail'=>mb_substr($detail,0,16000)]);
                return;
            }
            $relevant=in_array(strtolower($delivery->recipient),array_map('strtolower',array_filter([$customer->email,$customer->pending_email])),true);
            $delivery->update(['error_detail'=>mb_substr($detail,0,16000),'failed_at'=>now(),'resolved_at'=>$relevant?null:now()]);
            if ($relevant) $customer->update(['email_issue_at'=>now()]);
        });
    }
    public function resolve(Customer $customer): void {
        TicketEmailDelivery::where('customer_id',$customer->id)->whereNotNull('failed_at')->whereNull('resolved_at')->update(['resolved_at'=>now()]);
        $customer->update(['email_issue_at'=>null]);
    }
    public function ingest(string $raw): int {
        if (strlen($raw)>2097152) throw new \InvalidArgumentException('Bounce exceeds 2 MB limit.');
        $parts=$this->parts($raw);
        $reports=[]; $ids=[];
        foreach ($parts as [$type,$body]) {
            if ($type==='message/delivery-status') $reports[]=$body;
            if (in_array($type,['message/rfc822','text/rfc822-headers'],true)) {
                if (preg_match('/^Message-ID:\s*<([^>]+)>/mi',$body,$m)) $ids[]=$m[1];
            }
        }
        if (!$reports || !$ids) return 0;
        $count=0;
        foreach ($reports as $report) {
            $report=preg_replace('/\r?\n[ \t]+/',' ',$report);
            foreach (preg_split('/\r?\n\r?\n/',$report) as $block) {
                if (!preg_match('/^Action:\s*(failed|delayed)\s*$/mi',$block) ||
                    !preg_match('/^Final-Recipient:\s*rfc822;\s*([^\s<>]+)\s*$/mi',$block,$recipient)) continue;
                $delivery=TicketEmailDelivery::whereIn('message_id',$ids)->where('recipient',strtolower($recipient[1]))->first();
                if (!$delivery) continue;
                $this->fail($delivery,trim($block));
                $count++;
            }
        }
        return $count;
    }
    // Decode MIME leaves, including DSNs attached as base64/quoted-printable.
    private function parts(string $raw,int $depth=0): array {
        if ($depth>12) return [];
        $split=preg_split('/\r?\n\r?\n/',$raw,2);
        if (count($split)!==2) return [];
        [$headers,$body]=$split;
        $headers=preg_replace('/\r?\n[ \t]+/',' ',$headers);
        preg_match('/^Content-Type:\s*([^;\s]+)/mi',$headers,$match);
        $type=strtolower($match[1]??'text/plain');
        if (preg_match('/^Content-Transfer-Encoding:\s*base64/mi',$headers)) $body=base64_decode(preg_replace('/\s+/','',$body),true) ?: '';
        elseif (preg_match('/^Content-Transfer-Encoding:\s*quoted-printable/mi',$headers)) $body=quoted_printable_decode($body);
        if (str_starts_with($type,'multipart/') && preg_match('/boundary\s*=\s*(?:"([^"]+)"|([^;\s]+))/i',$headers,$boundary)) {
            $result=[];
            $segments=preg_split('/^--'.preg_quote($boundary[1]?:$boundary[2],'/').'(?:--)?[ \t]*\r?$/m',$body);
            foreach (array_slice($segments,1,-1) as $part) $result=array_merge($result,$this->parts(ltrim($part,"\r\n"),$depth+1));
            return $result;
        }
        return [[$type,$body]];
    }
}
