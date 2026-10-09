<?php
namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalSinger;
use App\Services\TicketDelivery;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SingerController extends Controller {
    public function index() {
        $singers=PortalSinger::orderBy('name')->paginate(30);
        return view('portal.singers.index',compact('singers'));
    }
    public function save(Request $request, ?PortalSinger $singer=null) {
        $singer=$singer ?? new PortalSinger;
        $request->merge(['referral_code'=>strtoupper(trim((string)$request->referral_code))]);
        $data=$request->validate([
            'name'=>'required|string|max:120',
            'referral_code'=>['required','string','max:30','regex:/^[A-Z0-9_-]+$/',Rule::unique('portal_singers')->ignore($singer->id)],
        ]);
        $singer->fill($data+['active'=>$request->boolean('active')])->save();
        TicketDelivery::audit('singer.saved',(string)$singer->id,auth('portal')->id());
        return redirect()->route('portal.singers.index')->with('success','Singer saved.');
    }
}
