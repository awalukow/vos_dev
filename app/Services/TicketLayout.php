<?php
namespace App\Services;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
class TicketLayout {
    public function parse(string $json): array {
        try { $layout=json_decode($json,true,512,JSON_THROW_ON_ERROR); }
        catch (\JsonException $e) { throw ValidationException::withMessages(['layout'=>'Upload or enter valid JSON.']); }
        $data=Validator::make(is_array($layout)?$layout:[],[
            'version'=>'required|integer|in:1','seats'=>'required|array|min:1|max:3000',
            'seats.*.label'=>'required|string|max:40|distinct','seats.*.class'=>'required|string|max:60',
            'seats.*.x'=>'required|numeric|min:0|max:100|multiple_of:0.25','seats.*.y'=>'required|integer|min:0|max:100',
            'dividers'=>'sometimes|array|max:20','dividers.*.label'=>'required|string|max:60',
            'dividers.*.y'=>'required|integer|min:0|max:100|distinct',
        ])->validate();
        $positions=[];
        foreach ($data['seats'] as $seat) {
            $key=$seat['x'].':'.$seat['y'];
            if (isset($positions[$key])) throw ValidationException::withMessages(['layout'=>'Two seats cannot occupy the same position.']);
            $positions[$key]=true;
        }
        foreach ($data['dividers']??[] as $divider) {
            foreach ($data['seats'] as $seat) if ($seat['y']===$divider['y']) throw ValidationException::withMessages(['layout'=>'Leave divider rows empty of seats.']);
        }
        $result=['version'=>1,'seats'=>array_map(fn($s)=>array_intersect_key($s,array_flip(['label','class','x','y'])),$data['seats'])];
        if (isset($data['dividers'])) $result['dividers']=array_map(fn($d)=>array_intersect_key($d,array_flip(['label','y'])),$data['dividers']);
        return $result;
    }
}
