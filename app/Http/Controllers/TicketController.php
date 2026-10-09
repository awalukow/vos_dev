<?php
namespace App\Http\Controllers;
use App\Models\{PortalSinger,TicketEvent,TicketOrder,TicketOrderItem,TicketPaymentMethod};
use App\Services\{TicketBooking,TicketDelivery};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,Storage};

class TicketController extends Controller {
    public function index() {
        $events=TicketEvent::with('classes')->where('published',true)->where('starts_at','>',now())->orderBy('starts_at')->get();
        return view('tickets.events',compact('events'));
    }
    public function event(TicketEvent $event) {
        abort_unless($event->published && $event->starts_at->isFuture(),404);
        $event->load(['classes','seats.ticketClass','venue']);
        $reserved=$event->reservedItems()->get();
        return view('tickets.select',compact('event','reserved'));
    }
    public function reserve(Request $r,TicketEvent $event,TicketBooking $booking) {
        $data=$r->validate(['seats'=>'nullable|array|max:10','seats.*'=>'required|integer|distinct','quantities'=>'nullable|array|max:30','quantities.*'=>'required|integer|min:0|max:10']);
        $order=$booking->reserve(Auth::guard('customer')->user(),$event,$data);
        return redirect()->route('tickets.order',$order);
    }
    private function own(TicketOrder $order) { abort_unless($order->customer_id===Auth::guard('customer')->id(),403); }
    private function canVerify(TicketOrder $order): void {
        $staff=Auth::guard('portal')->user();
        $customer=Auth::guard('customer')->user();
        abort_unless(($customer && $customer->is_active && $customer->email_verified_at && $order->customer_id===$customer->id) || ($staff && $staff->is_active && !$staff->is_password_flushed && ($staff->isAdministrator() || $staff->isAdm2() || $staff->hasRole('ticket_operator'))),403);
    }
    public function promo(Request $r,TicketOrder $order,\App\Services\TicketPromotions $promos) {
        $this->own($order);
        $data=$r->validate(['promo_code'=>'nullable|string|max:64']);
        $promos->apply($order,$data['promo_code']??null);
        return back()->with('promo_success',$order->fresh()->ticket_promo_id ? __('Promo applied. Discount: Rp :amount',['amount'=>number_format($order->fresh()->discount,0,',','.')]) : __('Promo removed.'));
    }
    public function free(Request $r,TicketOrder $order,TicketBooking $booking,TicketDelivery $delivery) {
        $this->own($order);
        $data=$r->validate(['singer_id'=>'nullable|integer']);
        $booking->completeFree($order,isset($data['singer_id'])?(int)$data['singer_id']:null);
        if (!$delivery->tickets($order->fresh())) return back()->with('error','Your tickets are confirmed, but email delivery failed. Open My tickets to view them.');
        return back()->with('success','Your tickets are confirmed.');
    }
    public function orders() {
        $orders=Auth::guard('customer')->user()->orders()->with('event')->latest()->paginate(20);
        return view('tickets.orders',compact('orders'));
    }
    public function order(TicketOrder $order) {
        $this->own($order);
        $order->load(['items','event','method']);
        $methods=TicketPaymentMethod::where('active',true)->get();
        $singers=PortalSinger::where('active',true)->orderBy('name')->get();
        return view('tickets.order',compact('order','methods','singers'));
    }
    public function proof(Request $r,TicketOrder $order,TicketBooking $booking) {
        $this->own($order);
        $r->validate(['singer_id'=>['nullable','integer',\Illuminate\Validation\Rule::exists('portal_singers','id')->where('active',true)],'method'=>'required|exists:ticket_payment_methods,id','proof'=>'required|file|mimes:jpg,jpeg,png,pdf|max:5120']);
        $path=$r->file('proof')->store('ticket-proofs','local');
        try { $booking->submitProof($order,TicketPaymentMethod::findOrFail($r->method),$path,$r->filled('singer_id')?(int)$r->singer_id:null); }
        catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
        return back()->with('success','Payment proof received. Your tickets are reserved while our team reviews it.');
    }
    public function cancel(TicketOrder $order,TicketBooking $booking) {
        $this->own($order); $booking->cancel($order);
        return redirect()->route('tickets.orders')->with('success','Reservation cancelled and tickets released.');
    }
    public function qr(TicketOrderItem $item,TicketDelivery $delivery) {
        abort_unless($item->order,404);
        $this->own($item->order); abort_unless($item->order->status==='paid',403);
        return response($delivery->qr(route('tickets.validate',$item->token),$item->booking_label))->header('Content-Type','image/png')->header('Cache-Control','private, no-store');
    }
    public function bookingQr(TicketOrder $order,TicketDelivery $delivery) {
        $this->own($order); abort_unless($order->status==='paid',403);
        return response($delivery->qr(route('tickets.receipt',$order->reference),$order->booking_code))->header('Content-Type','image/png')->header('Cache-Control','private, no-store');
    }
    public function validateTicket(string $token) {
        $item=TicketOrderItem::where('token',$token)->whereHas('order')->with('order.event')->firstOrFail();
        $this->canVerify($item->order);
        return response()->view('tickets.validity',['order'=>$item->order,'item'=>$item])->header('Cache-Control','no-store')->header('X-Robots-Tag','noindex');
    }
    public function receipt(string $reference) {
        $order=TicketOrder::where('reference',$reference)->with('event','items')->firstOrFail();
        $this->canVerify($order);
        return response()->view('tickets.validity',['order'=>$order,'item'=>null])->header('Cache-Control','no-store')->header('X-Robots-Tag','noindex');
    }
}
