<?php
namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasRowStatus {
    public static function bootHasRowStatus(): void {
        static::addGlobalScope('active', function (Builder $query) {
            $query->where($query->getModel()->qualifyColumn('RowStatus'), 0);
        });
    }

    public function initializeHasRowStatus(): void {
        $this->casts['RowStatus'] = 'integer';
        $this->attributes['RowStatus'] = 0;
    }
}
