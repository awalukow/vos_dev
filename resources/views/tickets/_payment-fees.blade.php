@foreach(['tax'=>'Tax','platform'=>'Platform Fee','processing'=>'Processing Fee'] as $fee=>$label)
@if(($fees[$fee]??0)>0)
@php($explanation=$fee==='tax'?'Tax applied to the ticket total after discounts.':($fee==='processing'?'The fee added for processing the payment.':'The added fee for the platform.'))
<div class="row tiny"><span>{{ __($label) }} <span class="fee-info" tabindex="0" role="img" aria-label="{{ __($explanation) }}">ⓘ<span role="tooltip">{{ __($explanation) }}</span></span></span><strong>Rp {{ number_format($fees[$fee],0,',','.') }}</strong></div>
@endif
@endforeach
