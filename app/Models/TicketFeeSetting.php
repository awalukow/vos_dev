<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class TicketFeeSetting extends Model {
    protected $guarded = ['id'];

    public static function current(): self { return static::findOrFail(1); }

    public function snapshot(): array {
        return $this->only(['tax_percent','platform_fee_type','platform_fee_value']);
    }

    public static function calculate(array $settings, int $subtotal): array {
        if ($subtotal<=0) return ['tax'=>0,'platform'=>0];
        $value=(float)($settings['platform_fee_value']??0);
        return [
            'tax'=>(int)round($subtotal*(float)($settings['tax_percent']??0)/100),
            'platform'=>(int)round(($settings['platform_fee_type']??'fixed')==='percent' ? $subtotal*$value/100 : $value),
        ];
    }
}
