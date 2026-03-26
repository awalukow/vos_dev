<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadAccessRequest extends Model
{
    protected $table = 'upload_access_requests';

    protected $fillable = [
        'portal_user_id',
        'message',
        'status',
        'reviewed_by',
        'reviewer_note',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(PortalUser::class, 'portal_user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(PortalUser::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
