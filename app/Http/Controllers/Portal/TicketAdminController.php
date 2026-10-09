<?php
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Models\{Customer,TicketEvent,TicketVenue,TicketOrder,TicketOrderItem,TicketPaymentMethod};
use App\Services\{TicketBooking,TicketDelivery,TicketLayout};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,DB,Storage,Validator};
use Illuminate\Validation\ValidationException;

class TicketAdminController extends Controller {
    private function actor() { return Auth::guard('portal')->id(); }
    public function events() {
        $events=TicketEvent::with('classes')->orderByDesc('starts_at')->paginate(20);
        return view('portal.ticketing.events',compact('events'));
    }
    public function eventForm(?TicketEvent $event=null) {
        $event=$event ?? new TicketEvent(['seating_type'=>'free']);
        $event->load('classes');
        $venues=TicketVenue::orderBy('name')->get();
        return view('portal.ticketing.event-form',compact('event','venues'));
    }
    public function saveEvent(Request $r,?TicketEvent $event=null) {
        $data=$r->validate(['title'=>'required|string|max:190','description'=>'nullable|string|max:5000','starts_at'=>'required|date','location'=>'required|string|max:255','seating_type'=>'required|in:free,numbered','ticket_venue_id'=>'nullable|exists:ticket_venues,id','thumbnail'=>'nullable|image|mimes:jpeg,jpg,png,webp|max:4096','classes_json'=>'required|string|max:20000']);
        $event=$event ?? new TicketEvent;
        $uploaded=$r->hasFile('thumbnail')?$r->file('thumbnail')->store('ticket-media','local'):null;
        try {
            DB::transaction(function () use ($r,$data,$event,$uploaded) {
                $locked=$event->exists?TicketEvent::lockForUpdate()->findOrFail($event->id):$event;
                $hasOrders=$locked->exists && $locked->orders()->withoutGlobalScope('active')->exists();
                if ($r->boolean('published') && $data['seating_type']==='numbered' && empty($data['ticket_venue_id'])) throw ValidationException::withMessages(['ticket_venue_id'=>'This event needs an available venue before it can be published. Events with bookings and a removed venue must remain unpublished.']);
                if ($hasOrders && ($locked->seating_type!==$data['seating_type'] || (int)$locked->ticket_venue_id!==(int)($data['ticket_venue_id']??0))) throw ValidationException::withMessages(['seating_type'=>'Seating and venue are locked after the first booking. Create a new event for a different arrangement.']);
                $metadata=['title'=>$data['title'],'description'=>$data['description']??null,'starts_at'=>\Carbon\Carbon::parse($data['starts_at'],'Asia/Jakarta')->utc(),'location'=>$data['location'],'limited_seating'=>$r->boolean('limited_seating'),'published'=>$r->boolean('published')];
                if ($uploaded) $metadata['thumbnail']=$uploaded;
                if ($r->boolean('published') && !$uploaded && !$locked->thumbnail) throw ValidationException::withMessages(['thumbnail'=>'Add a thumbnail before publishing.']);
                if (!$hasOrders) {
                    try { $classes=json_decode($data['classes_json'],true,512,JSON_THROW_ON_ERROR); }
                    catch (\JsonException $e) { throw ValidationException::withMessages(['classes'=>'Ticket classes must be valid JSON.']); }
                    $classes=Validator::make(['classes'=>$classes],['classes'=>'required|array|min:1|max:30','classes.*.name'=>'required|string|max:60|distinct','classes.*.color'=>['required','regex:/^#[0-9a-fA-F]{6}$/'],'classes.*.price'=>'required|integer|min:1|max:100000000','classes.*.capacity'=>'required|integer|min:0|max:100000'])->validate()['classes'];
                    $metadata['seating_type']=$data['seating_type'];
                    $metadata['ticket_venue_id']=$data['seating_type']==='numbered'?($data['ticket_venue_id']??null):null;
                    $seats=[];
                    $metadata['layout_dividers']=[];
                    if ($metadata['seating_type']==='numbered') {
                        if (!$metadata['ticket_venue_id']) throw ValidationException::withMessages(['ticket_venue_id'=>'Choose a venue for numbered seating.']);
                        $venue=TicketVenue::lockForUpdate()->findOrFail($metadata['ticket_venue_id']);
                        $seats=$venue->layout['seats'];
                        $metadata['layout_dividers']=$venue->layout['dividers']??[];
                        foreach ($seats as $seat) if (!in_array($seat['class'],array_column($classes,'name'),true)) throw ValidationException::withMessages(['classes'=>'Create a ticket class named '.$seat['class'].' to match the venue layout.']);
                    }
                    $locked->fill($metadata)->save();
                    $locked->seats()->delete(); $locked->classes()->delete();
                    foreach ($classes as $class) {
                        $capacity=$metadata['seating_type']==='numbered'?count(array_filter($seats,fn($s)=>$s['class']===$class['name'])):$class['capacity'];
                        if ($capacity<1) throw ValidationException::withMessages(['classes'=>'Each class must have at least one ticket or assigned seat.']);
                        $created=$locked->classes()->create(['name'=>$class['name'],'color'=>$class['color'],'price'=>$class['price'],'capacity'=>$capacity]);
                        foreach ($seats as $seat) if ($seat['class']===$class['name']) $locked->seats()->create(['ticket_class_id'=>$created->id,'label'=>$seat['label'],'x'=>$seat['x'],'y'=>$seat['y']]);
                    }
                } else {
                    try { $classes=json_decode($data['classes_json'],true,512,JSON_THROW_ON_ERROR); }
                    catch (\JsonException $e) { throw ValidationException::withMessages(['classes'=>'Ticket classes must be valid JSON.']); }
                    $classes=Validator::make(['classes'=>$classes],['classes'=>'required|array|min:1|max:30','classes.*.id'=>'required|integer|distinct','classes.*.price'=>'required|integer|min:1|max:100000000'])->validate()['classes'];
                    $existing=$locked->classes()->get()->keyBy('id');
                    if (count($classes)!==$existing->count() || collect($classes)->contains(fn($class)=>!$existing->has($class['id']))) throw ValidationException::withMessages(['classes'=>'Ticket classes are locked after the first booking. Reload the event and edit its prices.']);
                    // Only current sale prices change; order items and totals retain their snapshots.
                    foreach ($classes as $class) $existing->get($class['id'])->update(['price'=>$class['price']]);
                    $locked->fill($metadata)->save();
                }
                TicketDelivery::audit('event.saved',(string)$locked->id,$this->actor());
            },3);
        } catch (\Throwable $e) { if ($uploaded) Storage::disk('local')->delete($uploaded); throw $e; }
        return redirect()->route('portal.ticketing.events')->with('success','Event saved.');
    }
    public function removeEvent(TicketEvent $event) {
        DB::transaction(function () use ($event) {
            $locked=TicketEvent::lockForUpdate()->findOrFail($event->id);
            $locked->update(['RowStatus'=>-1]);
            TicketDelivery::audit('event.removed',(string)$locked->id,$this->actor());
        },3);
        return redirect()->route('portal.ticketing.events')->with('success','Event removed. Existing bookings and history preserved.');
    }
    public function removeOrder(TicketOrder $order,TicketBooking $booking) {
        $booking->remove($order,$this->actor());
        return redirect()->route('portal.ticketing.orders')->with('success','Booking removed and tickets released. History preserved.');
    }
    public function venues() {
        $venues=TicketVenue::orderBy('name')->get();
        return view('portal.ticketing.venues',compact('venues'));
    }
    public function venueForm(?TicketVenue $venue=null) {
        $venue=$venue ?? new TicketVenue(['layout'=>['version'=>1,'seats'=>[]]]);
        return view('portal.ticketing.venue-form',compact('venue'));
    }
    public function saveVenue(Request $r,TicketLayout $parser,?TicketVenue $venue=null) {
        $data=$r->validate(['name'=>'required|string|max:190','address'=>'nullable|string|max:255','layout'=>'nullable|string|max:1000000','layout_file'=>'nullable|file|max:1024']);
        $layout=$parser->parse($r->hasFile('layout_file')?file_get_contents($r->file('layout_file')->getRealPath()):($data['layout']??''));
        $venue=$venue ?? new TicketVenue;
        $venue->fill(['name'=>$data['name'],'address'=>$data['address']??null,'layout'=>$layout])->save();
        TicketDelivery::audit('venue.saved',(string)$venue->id,$this->actor());
        return redirect()->route('portal.ticketing.venues')->with('success','Venue saved. Existing event maps remain unchanged until edited; booked events retain their original map.');
    }
    public function exportVenue(TicketVenue $venue) {
        return response(json_encode($venue->layout,JSON_PRETTY_PRINT))->header('Content-Type','application/json')->header('Content-Disposition','attachment; filename="venue-'.$venue->id.'.json"');
    }
    public function removeVenue(TicketVenue $venue) {
        $count=DB::transaction(function () use ($venue) {
            // Match event editing/booking lock order; preserve event seat and order snapshots.
            $events=TicketEvent::withoutGlobalScope('active')->where('ticket_venue_id',$venue->id)->orderBy('id')->lockForUpdate()->get();
            $locked=TicketVenue::lockForUpdate()->findOrFail($venue->id);
            foreach ($events as $event) $event->update(['published'=>false,'ticket_venue_id'=>null]);
            $locked->delete();
            TicketDelivery::audit('venue.removed',(string)$venue->id,$this->actor(),['disabled_event_ids'=>$events->modelKeys()]);
            return $events->count();
        },3);
        return redirect()->route('portal.ticketing.venues')->with('success',"Venue removed. {$count} linked event(s) unpublished; existing bookings preserved.");
    }
    public function orders(Request $r) {
        $data=$r->validate(['q'=>'nullable|string|max:190','status'=>'nullable|in:awaiting_payment,payment_review,midtrans_pending,paid,rejected,cancelled,expired']);
        $orders=app(\App\Services\TicketReports::class)->orders($data)->with(['customer','event'])->latest()->paginate(20)->withQueryString();
        return view('portal.ticketing.orders',compact('orders'));
    }
    public function order(TicketOrder $order) {
        $order->load(['customer','event','items']);
        return response()->view('portal.ticketing.order',compact('order'))->header('Cache-Control','private, no-store');
    }
    public function bookingQr(TicketOrder $order,TicketDelivery $delivery) {
        abort_unless($order->status==='paid',403);
        return response($delivery->qr(route('tickets.receipt',$order->reference),$order->booking_code))->header('Content-Type','image/png')->header('Cache-Control','private, no-store');
    }
    public function ticketQr(TicketOrder $order,TicketOrderItem $item,TicketDelivery $delivery) {
        abort_unless($order->status==='paid' && (int)$item->ticket_order_id===(int)$order->id,403);
        return response($delivery->qr(route('tickets.validate',$item->token),$item->booking_label))->header('Content-Type','image/png')->header('Cache-Control','private, no-store');
    }
    public function payments() {
        $orders=TicketOrder::with(['customer','event','items'])->where('status','payment_review')->oldest('proof_uploaded_at')->paginate(20);
        $recent=TicketOrder::with(['customer','event'])->whereIn('status',['paid','rejected'])->latest('reviewed_at')->take(20)->get();
        return view('portal.ticketing.payments',compact('orders','recent'));
    }
    public function proof(TicketOrder $order) {
        abort_unless($order->proof_path && Storage::disk('local')->exists($order->proof_path),404);
        return response()->file(Storage::disk('local')->path($order->proof_path),['Cache-Control'=>'private, no-store','X-Content-Type-Options'=>'nosniff']);
    }
    public function review(Request $r,TicketOrder $order,TicketBooking $booking,TicketDelivery $delivery) {
        $r->validate(['decision'=>'required|in:approve,reject','note'=>'nullable|required_if:decision,reject|string|max:1000']);
        $approved=$r->decision==='approve';
        $booking->review($order,$this->actor(),$approved,$r->note);
        if ($approved && !$delivery->tickets($order)) return back()->with('error','Payment approved and tickets issued, but email failed. Use Resend tickets. The customer can also open My tickets.');
        return back()->with('success',$approved?'Payment approved. QR tickets emailed.':'Payment rejected and inventory released.');
    }
    public function resend(TicketOrder $order,TicketDelivery $delivery) {
        abort_unless($order->status==='paid',422);
        $sent=$delivery->tickets($order);
        TicketDelivery::audit('tickets.resent',$order->reference,$this->actor(),['sent'=>$sent]);
        return back()->with($sent?'success':'error',$sent?'Tickets emailed.':'Email failed. Check mail configuration.');
    }
    public function customers(Request $r) {
        $customers=Customer::when($r->q,fn($q)=>$q->where(function ($q) use ($r) { $q->where('email','like','%'.$r->q.'%')->orWhere('name','like','%'.$r->q.'%'); }))->latest()->paginate(20)->withQueryString();
        return view('portal.ticketing.customers',compact('customers'));
    }
    public function customer(Request $r,Customer $customer) {
        $data=$r->validate(['name'=>'required|string|max:120','phone'=>['required','string','max:30','regex:/^(?=(?:\D*\d){7,15}\D*$)[+0-9 ()-]+$/'],'dob'=>'required|date_format:d/m/Y|before:today']);
        $data=\App\Services\TicketCustomerInput::normalize($data);
        $customer->update($data+['is_active'=>$r->boolean('is_active')]);
        TicketDelivery::audit('customer.updated',(string)$customer->id,$this->actor());
        return back()->with('success','Customer updated.');
    }
    public function otp(Customer $customer,TicketDelivery $delivery) {
        abort_unless($customer->is_active,422);
        $code=$delivery->issueOtp($customer,true,$this->actor());
        return back()->with('manual_otp',$code)->with('otp_customer',$customer->email)->with('success','Manual code generated. It expires in 10 minutes and replaces the previous code.');
    }
    public function methods() {
        $admin=auth('portal')->user()->isAdministrator() || auth('portal')->user()->isAdm2();
        $methods=TicketPaymentMethod::when(!$admin,fn($q)=>$q->where('type','midtrans'))->get();
        return view('portal.ticketing.methods',compact('methods'));
    }
    public function testMidtrans(TicketPaymentMethod $method, \App\Services\MidtransPayments $payments) {
        abort_unless(auth('portal')->user()->isAdministrator() || auth('portal')->user()->isAdm2(),403);
        abort_unless($method->type==='midtrans',404);
        return redirect()->route('portal.ticketing.methods')->with('midtrans_connection',$payments->testConnection($method));
    }
    public function method(Request $r,TicketPaymentMethod $method) {
        $admin=auth('portal')->user()->isAdministrator() || auth('portal')->user()->isAdm2();
        if ($method->type==='midtrans') {
            abort_if(!$admin && $r->hasAny(['environment','merchant_id','server_key','client_key','name','instructions']),403);
            $rules=['active'=>'nullable|boolean'];
            foreach (['processing','platform'] as $fee) {
                $rules[$fee.'_fee_type']='required|in:fixed,percent';
                $rules[$fee.'_fee_value']='required|numeric|min:0|max:'.($r->input($fee.'_fee_type')==='percent'?'100':'10000000');
            }
            if ($admin) $rules+=['environment'=>'required|in:sandbox,production','merchant_id'=>'nullable|string|max:100','server_key'=>'nullable|string|max:255','client_key'=>'nullable|string|max:255'];
            $data=$r->validate($rules);
            if ($admin && $data['environment']!==$method->environment && empty($data['server_key'])) throw ValidationException::withMessages(['server_key'=>'Enter the matching server key when changing environments.']);
            foreach (['server_key','client_key'] as $key) if (empty($data[$key])) unset($data[$key]);
            $method->fill($data);
            if ($r->boolean('active') && (!$method->server_key || !$method->merchant_id)) throw ValidationException::withMessages(['active'=>'An admin must configure the merchant ID and server key before enabling Midtrans.']);
            $method->active=$r->boolean('active');
            $method->save();
            TicketDelivery::audit('payment_method.updated',(string)$method->id,$this->actor());
            return back()->with('success','Midtrans settings updated.');
        }
        abort_unless($admin,403);
        $data=$r->validate(['name'=>'required|string|max:100','instructions'=>'required|string|max:3000','qr_image'=>'nullable|image|mimes:png,jpg,jpeg|max:4096']);
        unset($data['qr_image']);
        if ($r->hasFile('qr_image')) $data['qr_image']=$r->file('qr_image')->store('ticket-media','local');
        if ($method->type==='qris' && $r->boolean('active') && !($data['qr_image']??$method->qr_image)) throw ValidationException::withMessages(['qr_image'=>'Upload your merchant QRIS code before enabling.']);
        $method->update($data+['active'=>$r->boolean('active')]);
        TicketDelivery::audit('payment_method.updated',(string)$method->id,$this->actor());
        return back()->with('success','Payment method updated.');
    }
}
