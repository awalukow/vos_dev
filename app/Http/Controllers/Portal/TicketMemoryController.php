<?php
namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\TicketEvent;
use App\Services\TicketDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Storage};
use Illuminate\Validation\ValidationException;

class TicketMemoryController extends Controller {
    public function edit(TicketEvent $event) {
        abort_unless($event->starts_at->lte(now()),404);
        return view('portal.ticketing.memories',compact('event'));
    }
    public function save(Request $request,TicketEvent $event) {
        abort_unless($event->starts_at->lte(now()),404);
        $data=$request->validate(['video'=>'nullable|url|max:500','photos'=>'nullable|array|max:30','photos.*'=>'required|image|mimes:jpeg,jpg,png,webp|max:4096','remove'=>'nullable|array','remove.*'=>'integer|min:0']);
        $video=null;
        if (!empty($data['video'])) {
            $url=parse_url($data['video']); $host=strtolower($url['host']??'');
            $path=$url['path']??''; parse_str($url['query']??'',$query);
            if ($host==='youtu.be') $video=trim($path,'/');
            elseif (in_array($host,['youtube.com','www.youtube.com','m.youtube.com'],true)) {
                if ($path==='/watch') $video=$query['v']??null;
                elseif (preg_match('~^/(?:embed|shorts|live)/([a-zA-Z0-9_-]{11})/?$~',$path,$matches)) $video=$matches[1];
            }
            if (!is_string($video) || !preg_match('/^[a-zA-Z0-9_-]{11}$/D',$video)) throw ValidationException::withMessages(['video'=>'Enter a valid YouTube video link.']);
        }
        $uploaded=[]; $removed=[];
        try {
            foreach ($request->file('photos',[]) as $photo) $uploaded[]=$photo->store('ticket-memories','local');
            DB::transaction(function() use ($event,$data,$uploaded,$video,&$removed) {
                $locked=TicketEvent::lockForUpdate()->findOrFail($event->id);
                abort_unless($locked->starts_at->lte(now()),422);
                $photos=[];
                foreach ($locked->memory_photos??[] as $index=>$path) {
                    if (in_array($index,$data['remove']??[])) $removed[]=$path;
                    else $photos[]=$path;
                }
                $photos=array_merge($photos,$uploaded);
                if (count($photos)>30) throw ValidationException::withMessages(['photos'=>'Keep at most 30 photos per concert.']);
                $locked->update(['memory_photos'=>$photos,'memory_video'=>$video]);
                TicketDelivery::audit('event.memories_saved',(string)$event->id,auth('portal')->id());
            });
        } catch (\Throwable $e) { Storage::disk('local')->delete($uploaded); throw $e; }
        Storage::disk('local')->delete($removed);
        return back()->with('success','Concert memories saved.');
    }
}
