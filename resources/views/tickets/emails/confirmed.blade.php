<!doctype html><html lang="id"><body style="font-family:Arial,sans-serif;background:#f7f5fa;color:#251e36;padding:25px">
<div style="max-width:600px;margin:auto;background:white;padding:30px;border-radius:16px">
<img src="{{ $message->embed(public_path('assets/images/vos-logo.jpg')) }}" width="110" alt="Voice of Soul Choir" style="display:block;height:auto;margin-bottom:24px"><h1>Tiket Anda sudah siap!</h1>
<p>Halo {{ $order->customer->name }}, pembayaran Anda telah dikonfirmasi. Terima kasih telah menjadi bagian dari konser kami.</p><h2>{{ $order->event->title }}</h2>
<p>{{ $order->event->starts_at->copy()->locale('id')->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB<br>{{ $order->event->location }}</p>
<p>Kode pemesanan: <strong>{{ $order->booking_code }}</strong><br>Referral: {{ $order->singer_name ?? 'Tanpa referral penyanyi' }} @if($order->referral_code)({{ $order->referral_code }})@endif<br>Referensi pemesanan: {{ $order->reference }}<br>Total pembayaran: Rp {{ number_format($order->total,0,',','.') }}</p>
@foreach(['processing'=>'Biaya pemrosesan','platform'=>'Biaya platform'] as $fee=>$label)
@if(($order->payment_snapshot['fees'][$fee]??0)>0)<p>{{ $label }}: Rp {{ number_format($order->payment_snapshot['fees'][$fee],0,',','.') }}</p>@endif
@endforeach
<p><a href="{{ route('tickets.order',$order) }}">Lihat pemesanan dan tiket Anda</a></p>
@if($order->ticket_promo_id)<p>Kode promo: <strong>{{ $order->promo_snapshot['code']??'' }}</strong><br>Diskon: Rp {{ number_format($order->discount,0,',','.') }}@if($order->promo_snapshot['free_tickets']??0)<br>Tiket gratis: {{ $order->promo_snapshot['free_tickets'] }}@endif</p>@endif
<h3>QR pemesanan · {{ $order->booking_code }}</h3><img width="150" style="height:auto" alt="QR pemesanan" src="{{ $message->embedData(app(\App\Services\TicketDelivery::class)->qr(route('tickets.receipt',$order->reference),$order->booking_code), 'booking-inline.png', 'image/png') }}">
@foreach($order->items as $item)
<hr><h3>Tiket {{ $item->booking_label }} · {{ $item->class_name }}</h3><p>{{ $item->seat_label?'Kursi '.$item->seat_label:'Tempat duduk bebas' }} · Berlaku untuk satu orang</p>
<img width="150" style="height:auto" alt="QR tiket per orang" src="{{ $message->embedData(app(\App\Services\TicketDelivery::class)->qr(route('tickets.validate',$item->token),$item->booking_label), 'ticket-inline-'.$item->id.'.png', 'image/png') }}">
@endforeach
<p>Kode QR juga dilampirkan sebagai berkas PNG. Jaga kerahasiaannya dan siapkan untuk ditunjukkan saat tiba di lokasi konser.</p><p>Sampai jumpa di konser,<br>Voice of Soul Choir</p></div></body></html>
