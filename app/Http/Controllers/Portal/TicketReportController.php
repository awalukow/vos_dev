<?php
namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\{TicketReports,TicketWorkbook};
use Illuminate\Http\Request;

class TicketReportController extends Controller {
    private function data(Request $request,TicketReports $reports): array {
        $data=$request->validate(['event'=>'nullable|integer|min:1']);
        return $reports->dashboard(isset($data['event'])?(int)$data['event']:null);
    }
    public function dashboard(Request $request,TicketReports $reports) {
        return response()->view('portal.ticketing.dashboard',$this->data($request,$reports))->header('Cache-Control','private, no-store');
    }
    public function exportDashboard(Request $request,TicketReports $reports,TicketWorkbook $workbook) {
        $data=$this->data($request,$reports); $t=$data['totals'];
        $summary=[
            ['Laporan','Kinerja penjualan tiket Voice of Soul'],
            ['Konser',$data['selected']?->title??'Semua konser aktif (tidak dihapus)'],
            ['Dibuat (WIB)',$data['generatedAt']->format('d/m/Y H:i:s')],
            ['Cakupan','Seluruh masa penjualan; pesanan dan konser yang dihapus dikecualikan.'],
            ['Lunas (kursi)',$t['paid']],['Belum lunas (kursi)',$t['unpaid']],['Sisa kursi',$t['remaining']],['Kapasitas',$t['capacity']],
            ['Nominal terjual (IDR)',$t['revenue']],['Biaya pembayaran diterima (IDR)',$t['fees']],['Total pembayaran lunas (IDR)',$t['receipts']],
            ['Diskon pesanan lunas (IDR)',$t['discount']],['Nilai tiket belum lunas (IDR)',$t['unpaid_value']],
            ['Pesanan lunas',$t['paid_orders']],['Menunggu verifikasi pembayaran',$t['review']],
            ['Tingkat penjualan',$t['capacity']?round($t['paid']/$t['capacity']*100,1).'%':'0%'],
            ['Definisi lunas','Jumlah tiket pada pesanan paid, termasuk tiket gratis.'],
            ['Definisi belum lunas','Reservasi belum kedaluwarsa, payment_review, dan midtrans_pending.'],
            ['Definisi nominal terjual','Total pembayaran pesanan lunas dikurangi biaya pemrosesan dan platform; setelah diskon.'],
            ['Rekonsiliasi','Nominal terjual + biaya pembayaran = total pembayaran lunas.'],
            ['Peringkat referral','Lima referral dengan kursi lunas terbanyak; jika sama, berdasarkan nominal terjual lalu kode. Tanpa referral dikecualikan.'],
        ];
        $rows=array_map(fn($r)=>[$r['event']->title,$r['event']->starts_at->timezone('Asia/Jakarta')->format('d/m/Y H:i'),$r['event']->location,$r['capacity'],$r['paid'],$r['unpaid'],$r['remaining'],$r['revenue'],$r['fees'],$r['receipts'],$r['discount'],$r['unpaid_value'],$r['paid_orders'],$r['review']],$data['rows']);
        $referrals=array_map(fn($r)=>[$r['name'],$r['code'],$r['seats'],$r['orders'],$r['revenue']],$data['referrals']);
        $statuses=[]; foreach($data['statuses'] as $status=>$count) $statuses[]=[$status,$count];
        return $workbook->download('laporan-kinerja-'.now()->format('Ymd-His').'.xlsx',[
            ['name'=>'Ringkasan laporan','headers'=>['Keterangan','Hasil'],'rows'=>$summary,'widths'=>[42,110]],
            ['name'=>'Kinerja konser','headers'=>['Konser','Tanggal (WIB)','Lokasi','Kapasitas','Lunas','Belum lunas','Sisa kursi','Nominal terjual','Biaya pembayaran','Total pembayaran lunas','Diskon','Nilai belum lunas','Pesanan lunas','Menunggu verifikasi'],'rows'=>$rows,'money'=>[7,8,9,10,11]],
            ['name'=>'Top 5 referral','headers'=>['Nama','Kode referral','Kursi lunas','Pesanan lunas','Nominal terjual'],'rows'=>$referrals,'money'=>[4]],
            ['name'=>'Status pesanan','headers'=>['Status','Jumlah pesanan'],'rows'=>$statuses],
        ]);
    }
    public function exportOrders(Request $request,TicketReports $reports,TicketWorkbook $workbook) {
        $filters=$request->validate(['q'=>'nullable|string|max:190','status'=>'nullable|in:awaiting_payment,payment_review,midtrans_pending,paid,rejected,cancelled,expired']);
        $query=$reports->orders($filters,true)->with(['customer','event','method','items'=>fn($q)=>$q->withoutGlobalScope('active')]);
        $date=fn($value)=>$value?->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i:s')??'';
        $orders=(function() use($query,$date) {
            foreach((clone $query)->lazyById(500) as $o) {
                $fees=TicketReports::fees($o);
                yield [$o->booking_code,$o->reference,$date($o->created_at),$o->event?->title,$date($o->event?->starts_at),$o->event?->location,$o->customer?->name,$o->customer?->email,$o->customer?->phone,TicketReports::status($o),$o->RowStatus,$o->event?->RowStatus,$o->items->count(),(int)$o->items->sum('price'),(int)$o->discount,$o->total-$fees,(int)($o->payment_snapshot['fees']['processing']??0),(int)($o->payment_snapshot['fees']['platform']??0),$o->total,$o->payment_snapshot['name']??$o->method?->name,$o->promo_snapshot['code']??'',$o->singer_name,$o->referral_code,$date($o->expires_at),$date($o->proof_uploaded_at),$date($o->reviewed_at),$o->review_note,$date($o->tickets_emailed_at)];
            }
        })();
        $tickets=(function() use($query) {
            foreach((clone $query)->lazyById(500) as $o) foreach($o->items as $item) yield [$o->booking_code,$o->reference,$item->id,$item->class_name,$item->seat_label??'Bebas',(int)$item->price,$item->RowStatus];
        })();
        return $workbook->download('transaksi-tiket-'.now()->format('Ymd-His').'.xlsx',[
            ['name'=>'Panduan','headers'=>['Keterangan','Isi'],'widths'=>[36,110],'rows'=>[
                ['Laporan','Seluruh transaksi tiket, termasuk pesanan dan tiket yang dihapus.'],['Dibuat (WIB)',now()->timezone('Asia/Jakarta')->format('d/m/Y H:i:s')],['Pencarian',$filters['q']??'Semua'],['Status',$filters['status']??'Semua'],
                ['RowStatus','0 = aktif; -1 = dihapus. Penghapusan bukan pengembalian dana.'],['Nilai tiket','Nilai setelah diskon, sebelum biaya. Nilai pada transaksi belum lunas bukan penerimaan.'],['Rincian tiket','Harga tiket sebelum diskon; diskon dan biaya dibukukan sekali per pesanan.'],['Keamanan','Kode QR, kredensial pembayaran, dan tautan bukti pembayaran tidak disertakan.'],
            ]],
            ['name'=>'Transaksi','headers'=>['Kode booking','Referensi','Dibuat (WIB)','Konser','Tanggal konser (WIB)','Lokasi','Pelanggan','Email','Telepon','Status','RowStatus pesanan','RowStatus konser','Jumlah tiket','Harga awal','Diskon','Nilai tiket','Biaya pemrosesan','Biaya platform','Total tagihan','Metode pembayaran','Kode promo','Nama referral','Kode referral','Kedaluwarsa (WIB)','Bukti diunggah (WIB)','Ditinjau (WIB)','Catatan peninjauan','Email tiket (WIB)'],'rows'=>$orders,'money'=>[13,14,15,16,17,18]],
            ['name'=>'Rincian tiket','headers'=>['Kode booking','Referensi','ID tiket','Kelas','Kursi','Harga awal','RowStatus tiket'],'rows'=>$tickets,'money'=>[5]],
        ]);
    }
}
