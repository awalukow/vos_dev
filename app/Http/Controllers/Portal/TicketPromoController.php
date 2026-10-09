<?php
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Models\{Customer,TicketPromo};
use App\Services\TicketDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule,ValidationException};

class TicketPromoController extends Controller {
    public function index(Request $r) {
        $editing=$r->filled('edit') ? TicketPromo::with('customers')->findOrFail($r->integer('edit')) : new TicketPromo;
        return view('portal.ticketing.promos',['promos'=>TicketPromo::latest()->paginate(20),'editing'=>$editing]);
    }
    public function save(Request $r,?TicketPromo $promo=null) {
        $r->merge(['code'=>strtoupper(trim((string)$r->input('code')))]);
        $data=$r->validate([
            'code'=>['required','string','max:64','regex:/^[A-Z0-9_-]+$/',Rule::unique('ticket_promos')->ignore($promo?->id)],
            'type'=>'required|in:percent,fixed,bogo,bundle,free_ticket',
            'value'=>'nullable|integer|min:1|max:1000000000',
            'buy_quantity'=>'nullable|integer|min:1|max:9','free_quantity'=>'nullable|integer|min:1|max:9',
            'expires_at'=>'nullable|date','daily_limit'=>'nullable|integer|min:1|max:1000000',
            'active'=>'nullable|boolean','single_use'=>'nullable|boolean','user_specific'=>'nullable|boolean',
            'customer_emails'=>'nullable|string|max:100000',
        ]);
        if (in_array($data['type'],['percent','fixed']) && empty($data['value'])) throw ValidationException::withMessages(['value'=>'Enter a discount value.']);
        if ($data['type']==='percent' && $data['value']>100) throw ValidationException::withMessages(['value'=>'Percentage must be between 1 and 100.']);
        if ($data['type']==='bundle' && (empty($data['buy_quantity']) || empty($data['free_quantity']) || $data['buy_quantity']+$data['free_quantity']>10)) throw ValidationException::withMessages(['buy_quantity'=>'Paid plus free ticket quantities must be between 2 and 10.']);
        $emails=collect(preg_split('/[\s,;]+/',strtolower(trim($data['customer_emails']??'')),-1,PREG_SPLIT_NO_EMPTY))->unique();
        $customers=Customer::whereIn(DB::raw('LOWER(email)'),$emails)->pluck('id');
        if ($r->boolean('user_specific') && ($emails->isEmpty() || $customers->count()!==$emails->count())) throw ValidationException::withMessages(['customer_emails'=>'Enter registered customer emails. One or more emails could not be found.']);
        unset($data['customer_emails']);
        foreach(['active','single_use','user_specific'] as $field) $data[$field]=$r->boolean($field);
        foreach(['value'=>0,'buy_quantity'=>1,'free_quantity'=>1] as $field=>$default) $data[$field]=$data[$field]??$default;
        $data['expires_at']=empty($data['expires_at'])?null:\Carbon\Carbon::parse($data['expires_at'],'Asia/Jakarta')->timezone(config('app.timezone'));
        DB::transaction(function() use($promo,$data,$customers) {
            $record=$promo ? TicketPromo::lockForUpdate()->findOrFail($promo->id) : new TicketPromo;
            $record->fill($data)->save();
            $record->customers()->sync($data['user_specific']?$customers->all():[]);
            TicketDelivery::audit('promo.saved',(string)$record->id,auth('portal')->id(),['code'=>$record->code]);
        },3);
        return redirect()->route('portal.ticketing.promos')->with('success','Promo saved.');
    }
}
