<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSignature extends Model
{
    protected $table = 'document_signatures';

    protected $fillable = [
        'document_id', 'signer_id', 'assigned_by', 'sort_order',
        'status', 'signed_at',
        'qr_page', 'qr_position_x', 'qr_position_y', 'qr_size',
        'signed_file_path', 'rejection_reason',
    ];

    protected $casts = [
        'signed_at'     => 'datetime',
        'qr_position_x' => 'float',
        'qr_position_y' => 'float',
        'qr_size'       => 'float',
    ];

    public function document()
    {
        return $this->belongsTo(PortalDocument::class, 'document_id');
    }

    public function signer()
    {
        return $this->belongsTo(PortalUser::class, 'signer_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(PortalUser::class, 'assigned_by');
    }
}
