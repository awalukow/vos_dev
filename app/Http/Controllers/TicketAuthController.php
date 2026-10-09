<?php
namespace App\Http\Controllers;
use App\Models\Customer;
use App\Services\TicketDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,Hash};
use Illuminate\Validation\Rules\Password;

class TicketAuthController extends Controller {
    public function loginForm() { return view('tickets.auth',['mode'=>'login']); }
    public function registerForm() { return view('tickets.auth',['mode'=>'register']); }
    public function register(Request $r,TicketDelivery $delivery) {
        $r->merge(['email'=>strtolower(trim((string)$r->email))]);
        $data=$r->validate(['name'=>'required|string|max:120','dob'=>'required|date_format:d/m/Y|before:today','phone'=>['required','string','max:30','regex:/^(?=(?:\D*\d){7,15}\D*$)[+0-9 ()-]+$/'],'email'=>'required|email|max:190|unique:customers,email','password'=>['required','confirmed',Password::min(10)]]);
        $data=\App\Services\TicketCustomerInput::normalize($data);
        $data['password']=Hash::make($data['password']);
        $customer=Customer::create($data);
        Auth::guard('customer')->login($customer);
        $r->session()->regenerate();
        $code=$delivery->issueOtp($customer);
        $sent=$delivery->emailOtp($customer,$code);
        return redirect()->route('tickets.verify')->with($sent?'success':'error',$sent?'We emailed your verification code.':'Your account was created, but email delivery failed. Use resend or contact the organizer.');
    }
    public function login(Request $r) {
        $data=$r->validate(['email'=>'required|email','password'=>'required|string']);
        $data['email']=strtolower(trim($data['email']));
        if (!Auth::guard('customer')->attempt($data+['is_active'=>true],$r->boolean('remember'))) return back()->withErrors(['email'=>'The email or password is incorrect, or the account is inactive.'])->withInput($r->only('email'));
        $r->session()->regenerate();
        if (!Auth::guard('customer')->user()->email_verified_at) return redirect()->route('tickets.verify');
        $r->session()->forget('url.intended');
        return redirect()->route('tickets.events');
    }
    public function verifyForm() {
        if (!Auth::guard('customer')->check()) return redirect()->route('tickets.login');
        if (Auth::guard('customer')->user()->email_verified_at) return redirect()->route('tickets.events');
        return view('tickets.auth',['mode'=>'verify']);
    }
    public function verify(Request $r,TicketDelivery $delivery) {
        $r->validate(['otp'=>'required|digits:6']);
        $customer=Auth::guard('customer')->user(); abort_unless($customer,403);
        if (!$delivery->verifyOtp($customer,$r->otp)) return back()->withErrors(['otp'=>'The code is incorrect, expired, or has reached its attempt limit. Request a new code.']);
        $r->session()->forget('url.intended');
        return redirect()->route('tickets.events')->with('success','Email verified. Find your next concert.');
    }
    public function resend(TicketDelivery $delivery) {
        $customer=Auth::guard('customer')->user(); abort_unless($customer && $customer->is_active,403);
        $code=$delivery->issueOtp($customer);
        $sent=$delivery->emailOtp($customer,$code);
        return back()->with($sent?'success':'error',$sent?'A new code has been sent.':'Email delivery failed. Please contact the organizer.');
    }
    public function logout(Request $r) {
        Auth::guard('customer')->logout();
        $r->session()->regenerate();
        $r->session()->regenerateToken();
        return redirect()->route('tickets.events');
    }
}
