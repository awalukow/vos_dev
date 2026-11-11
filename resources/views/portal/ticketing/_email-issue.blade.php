@if($customer->email_issue_at)
<div class="notice error" role="alert">
@if(auth('portal')->user()->isAdministrator() || auth('portal')->user()->isAdm2())
<strong>Email Issue · {{ $customer->email }}</strong>
<p>Email delivery has failed. Check the error below; a sender/server block also requires the email provider's help.</p>
@else
Email Issue, ask user to confirm a new email and notify administrator
@endif
</div>
@endif
@if(auth('portal')->user()->isAdministrator() || auth('portal')->user()->isAdm2())
@foreach($customer->emailFailures as $failure)
<details><summary>Email error · {{ $failure->recipient }} · {{ $failure->failed_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB · {{ $failure->resolved_at?'Resolved':'Unresolved' }}</summary>
@if($failure->ticket_order_id)<a href="{{ route('portal.ticketing.orders.show',$failure->ticket_order_id) }}">Related order #{{ $failure->ticket_order_id }}</a>@endif
<pre style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $failure->error_detail }}</pre></details>
@endforeach
@endif
