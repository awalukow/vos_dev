<?php
namespace App\Http\Controllers;
use App\Models\Customer;
use App\Services\{TicketDelivery,TicketEmailStatus};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Hash};
use Illuminate\Validation\{Rule,ValidationException};
class TicketEmailController extends Controller {
    private function customer(): Customer {
        $customer=auth('customer')->user();
        abort_unless($customer && $customer->is_active,403);
        return $customer;
    }
    public function edit() { return view('tickets.email',['customer'=>$this->customer()]); }
    public function send(Request $request,TicketDelivery $delivery) {
        $customer=$this->customer();
        $request->merge(['email'=>strtolower(trim((string)$request->email))]);
        $data=$request->validate(['email'=>['required','email','max:190',Rule::unique('customers','email')->ignore($customer->id)]]);
        if ($data['email']===strtolower($customer->email)) throw ValidationException::withMessages(['email'=>'Please enter a new email address.']);
        $code=DB::transaction(function () use ($customer,$data) {
            $customer=Customer::lockForUpdate()->findOrFail($customer->id);
            if ($customer->pending_email_otp_sent_at && $customer->pending_email_otp_sent_at->gt(now()->subMinute())) throw ValidationException::withMessages(['email'=>'Please wait one minute before requesting another code.']);
            $code=(string)random_int(100000,999999);
            $customer->update(['pending_email'=>$data['email'],'pending_email_otp_hash'=>Hash::make($code),'pending_email_otp_expires_at'=>now()->addMinutes(10),'pending_email_otp_sent_at'=>now(),'pending_email_otp_attempts'=>0]);
            return $code;
        });
        $sent=$delivery->emailOtp($customer,$code,$data['email']);
        return back()->with($sent?'success':'error',$sent?'Enter the code sent to your new email address.':'Email delivery failed. Try another address or contact the administrator.');
    }
    public function confirm(Request $request,TicketEmailStatus $status) {
        $customer=$this->customer(); $request->validate(['otp'=>'required|digits:6']);
        $ok=DB::transaction(function () use ($customer,$request,$status) {
            $customer=Customer::lockForUpdate()->findOrFail($customer->id);
            if (!$customer->pending_email_otp_hash || !$customer->pending_email_otp_expires_at || $customer->pending_email_otp_expires_at->isPast() || $customer->pending_email_otp_attempts>=5) return false;
            $customer->increment('pending_email_otp_attempts');
            if (!Hash::check($request->otp,$customer->pending_email_otp_hash)) return false;
            if (Customer::where('email',$customer->pending_email)->where('id','!=',$customer->id)->exists()) throw ValidationException::withMessages(['email'=>'This email is already in use. Please choose another.']);
            $old=$customer->email;
            $customer->update(['email'=>$customer->pending_email,'email_verified_at'=>now(),'pending_email'=>null,'pending_email_otp_hash'=>null,'pending_email_otp_expires_at'=>null,'pending_email_otp_sent_at'=>null,'pending_email_otp_attempts'=>0,'otp_hash'=>null,'otp_expires_at'=>null,'otp_attempts'=>0]);
            $status->resolve($customer);
            TicketDelivery::audit('customer.email_changed',(string)$customer->id,null,['previous_email'=>$old,'email'=>$customer->email]);
            return true;
        });
        if (!$ok) return back()->withErrors(['otp'=>'The code is incorrect, expired, or has reached its attempt limit. Request a new code.']);
        return redirect()->route('tickets.orders')->with('success','Your new email has been verified and saved.');
    }
}
