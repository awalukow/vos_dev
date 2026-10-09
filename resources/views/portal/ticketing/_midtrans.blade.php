<form class="panel" method="post" action="{{ route('portal.ticketing.methods.update',$method) }}">@csrf
<span class="badge">Midtrans · {{ $method->active?'Active':'Deactivated' }}</span>
<h2>QRIS (Automated Check)</h2><p class="muted">Customers pay on Midtrans and receive tickets after verified payment confirmation.</p>
@if(auth('portal')->user()->isAdministrator() || auth('portal')->user()->isAdm2())
<label for="midtrans-environment">Environment</label><select id="midtrans-environment" name="environment">@foreach(['sandbox'=>'Sandbox (testing)','production'=>'Production'] as $value=>$label)<option value="{{ $value }}" @selected(old('environment',$method->environment)===$value)>{{ $label }}</option>@endforeach</select>
<label for="merchant-id">Merchant ID</label><input id="merchant-id" name="merchant_id" value="{{ old('merchant_id',$method->merchant_id) }}" maxlength="100">
@foreach(['server_key'=>'Server key','client_key'=>'Client key'] as $key=>$label)
<label for="midtrans-{{ $key }}">{{ $label }} · {{ $method->$key?'Configured':'Not configured' }}</label><input type="password" id="midtrans-{{ $key }}" name="{{ $key }}" autocomplete="new-password" maxlength="255" placeholder="Leave blank to keep the saved key">
@endforeach
<p class="tiny muted">The hosted checkout uses the server key. The client key is stored for account configuration. When switching environments, enter the matching keys.</p>
<button type="submit" class="btn btn-secondary" form="midtrans-connection-test">Test connection</button>
<p class="tiny muted">Tests the saved environment and server key, even while Midtrans is deactivated. Save changes first. This does not test QRIS activation or webhook delivery.</p>
@if(session('midtrans_connection'))
<div class="notice {{ session('midtrans_connection.ok')?'success':'error' }}" role="status">{{ session('midtrans_connection.message') }}</div>
@endif
<p class="tiny">Set this Payment Notification URL in the Midtrans dashboard:<br><code style="overflow-wrap:anywhere">{{ route('tickets.midtrans.notification') }}</code></p>
@else
<p class="tiny muted">Account configuration and API keys are managed by an administrator.</p>
@endif
@foreach(['processing'=>'Processing Fee','platform'=>'Platform Fee'] as $fee=>$label)
@php($explanation=$fee==='processing'?'The fee added for processing the payment.':'The added fee for the platform.')
<label for="{{ $fee }}-fee-value">{{ $label }} <span tabindex="0" role="img" aria-label="{{ $explanation }}" title="{{ $explanation }}">ⓘ</span></label>
<select name="{{ $fee }}_fee_type" aria-label="{{ $label }} type"><option value="fixed" @selected(old($fee.'_fee_type',$method->{$fee.'_fee_type'})==='fixed')>Fixed amount (Rp)</option><option value="percent" @selected(old($fee.'_fee_type',$method->{$fee.'_fee_type'})==='percent')>Percentage (%)</option></select>
<input type="number" id="{{ $fee }}-fee-value" name="{{ $fee }}_fee_value" min="0" step="0.01" value="{{ old($fee.'_fee_value',$method->{$fee.'_fee_value'}) }}" required>
@endforeach
<p class="tiny muted">Set a fee to 0 to disable it. Each percentage applies separately to the ticket total after discounts. Fees are rounded to whole rupiah.</p>
<label class="check"><input type="checkbox" name="active" value="1" @checked(old('active',$method->active))> Activate Midtrans</label>
<button class="btn btn-primary" style="margin-top:20px">Save Midtrans settings</button>
</form>
@if(auth('portal')->user()->isAdministrator() || auth('portal')->user()->isAdm2())
<form id="midtrans-connection-test" method="post" action="{{ route('portal.ticketing.methods.test-midtrans',$method) }}" hidden>@csrf</form>
@endif
